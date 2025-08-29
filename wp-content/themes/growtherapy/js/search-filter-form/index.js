'use strict';

import { CONFIG } from './config.js';
import { ValidationManager } from './managers/index.js';

class SearchFilterForm {
  // Static constants for better performance and maintainability
  static CONSTANTS = CONFIG.CONSTANTS;

  constructor() {
    this.activeModals = new Set();
    this.searchDebounceTimers = new Map();
    this.instanceId = this.utils.generateId();
    
    this.validation = new ValidationManager();
    
    this.init();
  }
  
  utils = {
    generateId: () => 'search-filter-form-' + Math.random().toString(36).substr(2, 9),
    
    slugify: (text) => text
      .toLowerCase()
      .replace(/[^a-z0-9\s-]/g, '')
      .replace(/\s+/g, '-')
      .replace(/-+/g, '-')
      .trim('-'),
    
    /**
     * Generate URL parameters from current form state
     * @returns {string} - URL query string with current selections
     */
    generateUrlParams: () => {
      const params = new URLSearchParams();
      const dropdowns = document.querySelectorAll(CONFIG.ELEMENTS.dropdown);
      
      for (const dropdown of dropdowns) {
        const modal = dropdown.querySelector(CONFIG.ELEMENTS.dropdownModal);
        if (!modal?.id) continue;
        
        const apiKey = modal.id.slice(CONFIG.CONSTANTS.MODAL_PREFIX.length); // Remove 'modal-' prefix
        const paramName = CONFIG.PARAM_MAPPINGS.API_TO_URL.get(apiKey);
        if (!paramName) continue;
        
        const checkboxes = dropdown.querySelectorAll('input[type="checkbox"]:checked');
        if (checkboxes.length === 0) continue;
        
        // Add values to params
        for (const checkbox of checkboxes) {
          if (paramName === 'specialty') {
            params.append(paramName, checkbox.value);
          } else {
            params.set(paramName, checkbox.value);
          }
        }
      }
      
      return params.toString();
    }
  };
  
  stateManager = {
    getShared: () => {
      if (!SearchFilterForm.sharedState) {
        SearchFilterForm.sharedState = {
          selections: new Map(),
          instances: new Set()
        };
      }
      return SearchFilterForm.sharedState;
    },

    update: (name, value, checked) => {
      const sharedState = SearchFilterForm.sharedState;
      if (!sharedState) return;
      
      if (!sharedState.selections.has(name)) {
        sharedState.selections.set(name, new Set());
      }
      
      const selectionSet = sharedState.selections.get(name);
      
      if (checked) {
        selectionSet.add(value);
      } else {
        selectionSet.delete(value);
      }
      
      this.stateManager.notify(name, value, checked);
    },

    notify: (name, value, checked) => {
      const event = new CustomEvent('searchFilterFormStateChange', {
        detail: {
          name,
          value,
          checked,
          sourceInstanceId: this.instanceId
        }
      });
      
      document.dispatchEvent(event);
    },

    handleExternal: (event) => {
      const { name, value, checked, sourceInstanceId } = event.detail;
      
      if (sourceInstanceId === this.instanceId) return;
      
      const checkbox = this.stateManager.findCheckbox(name, value);
      if (checkbox) {
        checkbox.checked = checked;
        
        const option = checkbox.closest(CONFIG.ELEMENTS.option);
        if (option) {
          option.classList.toggle(CONFIG.CSS_CLASSES.optionSelected, checked);
        }
        
        this.updateDropdownLabel(checkbox);
        
        if (checked) {
          this.applyCrossFiltering(checkbox);
        } else {
          this.resetCrossFiltering(checkbox);
        }
      }
    },

    findCheckbox: (name, value) => {
      const dropdowns = CONFIG.ELEMENTS.dropdown ? 
        document.querySelectorAll(CONFIG.ELEMENTS.dropdown) : 
        document.querySelectorAll('[data-search-filter-form-dropdown]');
      
      for (const dropdown of dropdowns) {
        const checkbox = dropdown.querySelector(`input[name="${name}"][value="${value}"]`);
        if (checkbox) return checkbox;
      }
      
      return null;
    },

    sync: () => {
      const sharedState = SearchFilterForm.sharedState;
      if (!sharedState || !sharedState.selections) return;
      
      for (const [name, values] of sharedState.selections) {
        for (const value of values) {
          const checkbox = this.stateManager.findCheckbox(name, value);
          if (checkbox && !checkbox.checked) {
            checkbox.checked = true;
            
            const option = checkbox.closest(CONFIG.ELEMENTS.option);
            if (option) {
              option.classList.add(CONFIG.CSS_CLASSES.optionSelected);
            }
            
            this.updateDropdownLabel(checkbox);
            this.applyCrossFiltering(checkbox);
          }
        }
      }
      
      sharedState.instances.add(this.instanceId);
    }
  };

  init() {
    this.bindEvents();
    this.stateManager.sync();
    
    // Try to populate from URL params immediately
    this.populateFromUrlParams();
    
    // If no dropdowns found, retry after a short delay (in case they're still loading)
    if (document.querySelectorAll(CONFIG.ELEMENTS.dropdown).length === 0) {
      setTimeout(() => {
        this.populateFromUrlParams();
      }, SearchFilterForm.CONSTANTS.RETRY_DELAY);
    }
  }

  /**
   * Parse URL parameters and populate dropdowns accordingly
   * Supports: state, insurance, specialty[], typeOfCare
   */
  populateFromUrlParams() {
    const urlParams = new URLSearchParams(window.location.search);
    const hasRelevantParams = SearchFilterForm.CONSTANTS.URL_PARAMS.some(param => urlParams.has(param));
    if (!hasRelevantParams) return;
    
    const populationPromises = [];
    for (const [param, dropdownType] of CONFIG.PARAM_MAPPINGS.URL_TO_DROPDOWN) {
      const value = urlParams.get(param);
      if (value) {
        populationPromises.push(this.populateDropdownFromParam(dropdownType, value));
      }
    }
    
    // Handle specialty array parameter (needs dropdown) - try multiple formats
    let specialties = [];
    
    // Try standard specialty parameter
    specialties = urlParams.getAll('specialty');
    
    // If empty, try specialty[] format (array notation)
    if (specialties.length === 0) {
      const specialtyArrayParams = [];
      for (const [key, value] of urlParams.entries()) {
        if (key.startsWith('specialty[') && key.endsWith(']')) {
          specialtyArrayParams.push(value);
        }
      }
      if (specialtyArrayParams.length > 0) {
        specialties = specialtyArrayParams;
      }
    }
    
    // If still empty, try specialty with different casing
    if (specialties.length === 0) {
      specialties = urlParams.getAll('Specialty') || urlParams.getAll('SPECIALTY');
    }
    
    // If still empty, try to find any parameter that might be specialty-related
    if (specialties.length === 0) {
      for (const [key, value] of urlParams.entries()) {
        if (key.toLowerCase().includes('specialty') || key.toLowerCase().includes('need')) {
          specialties = [value];
          break;
        }
      }
    }
    
    // If still empty, try to decode the URL and look for specialty-related content
    if (specialties.length === 0) {
      const decodedUrl = decodeURIComponent(window.location.search);
      
      // Look for specialty patterns in the decoded URL
      const specialtyMatches = decodedUrl.match(/specialty[^=&]*=([^&]+)/gi);
      if (specialtyMatches) {
        specialties = specialtyMatches.map(match => {
          const value = match.split('=')[1];
          return decodeURIComponent(value);
        });
      }
    }
    
    if (specialties.length > 0) {
      populationPromises.push(this.populateDropdownFromParam('needs', specialties));
    }
    
    // Process all populations concurrently
    Promise.allSettled(populationPromises).then(results => {
      const successCount = results.filter(result => result.status === 'fulfilled').length;
      if (successCount > 0) {
        console.log(`SearchFilterForm: Successfully populated ${successCount} dropdowns from URL parameters`);
      }
    });
  }

  /**
   * Populate a specific dropdown based on parameter value
   * @param {string} dropdownType - The type of dropdown to populate
   * @param {string|string[]} values - Single value or array of values
   */
  populateDropdownFromParam(dropdownType, values) {
    // Cache DOM queries for better performance
    const dropdowns = document.querySelectorAll(CONFIG.ELEMENTS.dropdown);
    if (dropdowns.length === 0) return 0;
    
    const expectedApiKey = CONFIG.PARAM_MAPPINGS.DROPDOWN_TO_API.get(dropdownType) || null;
    if (!expectedApiKey) return 0;
    
    // Convert single value to array for consistent processing
    const valueArray = Array.isArray(values) ? values : [values];
    
    // Use Set for O(1) value lookups
    const valueSet = new Set(valueArray);
    let populatedCount = 0;
    
    // Batch DOM updates for better performance
    const updates = [];
    
    for (const dropdown of dropdowns) {
      const modal = dropdown.querySelector(CONFIG.ELEMENTS.dropdownModal);
      if (!modal) continue;
      
      const apiKey = this.getDropdownApiKey(dropdown);
      
      if (apiKey !== expectedApiKey) continue;
      
      // Find all checkboxes at once to avoid multiple DOM queries
      const checkboxes = dropdown.querySelectorAll('input[type="checkbox"]');
      
      for (const checkbox of checkboxes) {
        if (valueSet.has(checkbox.value)) {
          updates.push(() => this.processCheckboxUpdate(checkbox));
          populatedCount++;
        }
      }
    }
    
    // Batch process all updates for better performance
    if (updates.length > 0) {
      // Use requestAnimationFrame for smooth UI updates
      requestAnimationFrame(() => {
        updates.forEach(update => update());
      });
    }
    
    return populatedCount;
  }

  /**
   * Process a single checkbox update (extracted for better performance)
   * @param {HTMLInputElement} checkbox - The checkbox to update
   */
  processCheckboxUpdate(checkbox) {
    checkbox.checked = true;
    
    // Update UI state
    const option = checkbox.closest(CONFIG.ELEMENTS.option);
    if (option) {
      option.classList.add(CONFIG.CSS_CLASSES.optionSelected);
    }
    
    // Update dropdown label
    this.updateDropdownLabel(checkbox);
    
    // Apply cross-filtering if needed
    this.applyCrossFiltering(checkbox);
  }

  /**
   * Get the API key for a specific dropdown
   * @param {HTMLElement} dropdown - The dropdown element
   * @returns {string} - The API key (states, payors, specialties, type-of-care)
   */
  getDropdownApiKey(dropdown) {
    const modal = dropdown.querySelector(CONFIG.ELEMENTS.dropdownModal);
    if (!modal?.id) {
      return null;
    }
    
    // Use startsWith for better performance than regex
    const apiKey = modal.id.startsWith(SearchFilterForm.CONSTANTS.MODAL_PREFIX) ? 
                   modal.id.slice(SearchFilterForm.CONSTANTS.MODAL_PREFIX.length) : null;
    
    return apiKey;
  }

  bindEvents() {
    document.addEventListener('click', this.eventManager.handleGlobalClick.bind(this), { 
      passive: false,
      capture: false 
    });
    
    document.addEventListener('change', this.handleCheckboxChange.bind(this), { 
      passive: true,
      capture: false 
    });
    
    document.addEventListener('submit', this.handleFormSubmit.bind(this), { 
      passive: false,
      capture: false 
    });
    
    document.addEventListener('input', this.searchManager.handleInput.bind(this), { 
      passive: true,
      capture: false 
    });
    
    document.addEventListener('searchFilterFormStateChange', this.stateManager.handleExternal.bind(this), { 
      passive: true,
      capture: false 
    });
  }

  eventManager = {
    handleGlobalClick: (e) => {
      const modalTrigger = e.target.closest(CONFIG.ELEMENTS.modalTrigger);
      const doneButton = e.target.closest(CONFIG.ELEMENTS.doneButton);
      const deselectButton = e.target.closest(CONFIG.ELEMENTS.deselectButton);
      
      if (modalTrigger) {
        e.preventDefault();
        e.stopPropagation();
        this.handleModalOpen(modalTrigger);
        return;
      }
      
      if (doneButton) {
        e.preventDefault();
        e.stopPropagation();
        this.handleDoneClick(doneButton);
        return;
      }
      
      if (deselectButton) {
        e.preventDefault();
        e.stopPropagation();
        this.handleDeselectClick(e);
        return;
      }
      
      this.handleClickOutside(e);
    }
  };

  modalManager = {
    isOpen: (modal) => this.activeModals.has(modal),
    
    getOpenModal: (dropdown) => {
      const modal = dropdown.querySelector(CONFIG.ELEMENTS.dropdownModal);
      return modal && this.activeModals.has(modal) ? modal : null;
    },
    
    toggle: (modal, dropdown) => {
      if (this.activeModals.has(modal)) {
        this.modalManager.close(modal);
      } else {
        this.modalManager.closeAll();
        this.modalManager.open(modal, dropdown);
      }
    },
    
    hasOpenModal: () => this.activeModals.size > 0,
    
    getOpenModals: () => Array.from(this.activeModals),
    
    open: (modal, dropdown) => {
      const button = dropdown.querySelector(CONFIG.ELEMENTS.modalTrigger);
      
      modal.classList.remove('aria-hidden');
      modal.classList.add(CONFIG.CSS_CLASSES.modalActive);
      modal.setAttribute('aria-hidden', 'false');
      button?.setAttribute('aria-expanded', 'true');
      
      this.activeModals.add(modal);
      this.searchManager.clear(modal);
    },

    close: (modal) => {
      const dropdown = modal.closest(CONFIG.ELEMENTS.dropdown);
      if (!dropdown) return;
      
      const button = dropdown.querySelector(CONFIG.ELEMENTS.modalTrigger);
      
      modal.classList.remove(CONFIG.CSS_CLASSES.modalActive);
      modal.classList.add('aria-hidden');
      modal.setAttribute('aria-hidden', 'true');
      button?.setAttribute('aria-expanded', 'false');
      button?.focus();
      
      this.activeModals.delete(modal);
      this.cleanup();
    },

    closeAll: () => {
      for (const modal of this.activeModals) {
        this.modalManager.close(modal);
      }
    },
    
    closeByDropdown: (dropdown) => {
      const modal = this.modalManager.getOpenModal(dropdown);
      if (modal) {
        this.modalManager.close(modal);
      }
    }
  };

  handleModalOpen(button) {
    const dropdown = button.closest(CONFIG.ELEMENTS.dropdown);
    if (!dropdown) return;
    
    const modal = dropdown.querySelector(CONFIG.ELEMENTS.dropdownModal);
    if (!modal) return;
    
    this.modalManager.toggle(modal, dropdown);
  }

  /**
   * Clean up resources when closing modals
   */
  cleanup() {
    this.searchDebounceTimers.forEach(timer => clearTimeout(timer));
    this.searchDebounceTimers.clear();
  }
  
  /**
   * Remove this instance from shared state (called when instance is destroyed)
   */
  destroy() {
    const sharedState = SearchFilterForm.sharedState;
    if (sharedState && sharedState.instances) {
      sharedState.instances.delete(this.instanceId);
    }
  }

  handleCheckboxChange(e) {
    const { target: checkbox } = e;
    
    this.stateManager.update(checkbox.name, checkbox.value, checkbox.checked);
    
    if (!checkbox.matches(CONFIG.ELEMENTS.checkboxSingleSelect)) {
      this.updateDropdownLabel(checkbox);
      return;
    }
    
    const { name } = checkbox;
    const modal = checkbox.closest(CONFIG.ELEMENTS.dropdownModal);
    
    if (!modal || !name) {
      console.warn('Required elements not found for single-select behavior', { modal, name });
      return;
    }
    
    if (checkbox.checked) {
      const selector = `input[name="${name}"]:not(#${checkbox.id})`;
      const otherCheckboxes = modal.querySelectorAll(selector);
      
      otherCheckboxes.forEach(otherCheckbox => {
        this.deselectSingleSelectOption(otherCheckbox);
      });
      
      const currentOption = this.dom.findOption(checkbox);
      if (currentOption) {
        currentOption.classList.add(CONFIG.CSS_CLASSES.optionSelected);
      }
      
      this.modalManager.close(modal);
      this.applyCrossFiltering(checkbox);
    } else {
      const label = checkbox.closest('label');
      if (label && !e.target.closest(CONFIG.ELEMENTS.deselectButton)) {
        checkbox.checked = true;
        return;
      }
      
      this.deselectSingleSelectOption(checkbox);
    }
    
    this.updateDropdownLabel(checkbox);
  }

  deselectSingleSelectOption(checkbox) {
    checkbox.checked = false;
    
    this.stateManager.update(checkbox.name, checkbox.value, false);
    
    const option = this.dom.findOption(checkbox);
    if (option) {
      option.classList.remove(CONFIG.CSS_CLASSES.optionSelected);
    }
    
    this.resetCrossFiltering(checkbox);
    this.updateDropdownLabel(checkbox);
  }

  handleDeselectClick(e) {
    const deselectButton = e.target.closest(CONFIG.ELEMENTS.deselectButton);

    if (!deselectButton) return;
    
    e.preventDefault();
    e.stopPropagation();
    
    const option = deselectButton.closest(CONFIG.ELEMENTS.option);
    const checkbox = option?.querySelector('input[type="checkbox"]');

    if (checkbox) {
      this.deselectSingleSelectOption(checkbox);
    }
  }

  handleDoneClick(doneButton) {
    const modal = doneButton.closest(CONFIG.ELEMENTS.dropdownModal);
    if (modal) {
      this.modalManager.close(modal);
    }
  }

  handleClickOutside(e) {
    for (const modal of this.activeModals) {
      if (!modal.contains(e.target)) {
        this.modalManager.close(modal);
      }
    }
  }

  searchManager = {
    handleInput: (e) => {
      const searchInput = e.target;
      if (!searchInput.matches(CONFIG.ELEMENTS.searchInput)) return;

      if (this.searchDebounceTimers.has(searchInput)) {
        clearTimeout(this.searchDebounceTimers.get(searchInput));
      }

      const timer = setTimeout(() => {
        this.searchManager.perform(searchInput);
        this.searchDebounceTimers.delete(searchInput);
      }, 300);

      this.searchDebounceTimers.set(searchInput, timer);
    },

    perform: (searchInput) => {
      const modal = searchInput.closest(CONFIG.ELEMENTS.dropdownModal);
      if (!modal) return;

      const searchTerm = searchInput.value.toLowerCase().trim();
      
      if (!searchTerm) {
        this.searchManager.applyCrossFiltering(modal);
        return;
      }

      const options = modal.querySelectorAll(CONFIG.ELEMENTS.option);
      
      options.forEach(option => {
        const checkbox = option.querySelector('input[type="checkbox"]');
        if (!checkbox) return;

        const searchData = checkbox.dataset.searchData;
        if (!searchData) return;

        try {
          const data = JSON.parse(searchData);
          const isSearchMatch = data.searchText.includes(searchTerm);
          const isCrossFiltered = !option.classList.contains(CONFIG.CSS_CLASSES.optionHidden);
          
          option.style.display = (isSearchMatch && isCrossFiltered) ? '' : 'none';
        } catch (error) {
          console.warn('Invalid search data for option:', checkbox);
          option.style.display = '';
        }
      });
    },

    applyCrossFiltering: (modal) => {
      const options = modal.querySelectorAll(CONFIG.ELEMENTS.option);
      options.forEach(option => {
        const isCrossFiltered = !option.classList.contains(CONFIG.CSS_CLASSES.optionHidden);
        option.style.display = isCrossFiltered ? '' : 'none';
      });
    },

    clear: (modal) => {
      const searchInput = modal.querySelector(CONFIG.ELEMENTS.searchInput);
      if (searchInput) {
        searchInput.value = '';
      }
      
      this.searchManager.applyCrossFiltering(modal);
    }
  };

  applyCrossFiltering(checkbox) {
    const form = checkbox.closest('form');
    if (!form) return;

    const selectedState = form.querySelector('input[name="states-options"]:checked');
    const selectedInsurance = form.querySelector('input[name="payors-options"]:checked');

    if (selectedState) {
      this.filterOptionsByRelatedData(selectedState, 'relatedInsurance', 'payors-options');
    }

    if (selectedInsurance) {    
      this.filterOptionsByRelatedData(selectedInsurance, 'relatedStates', 'states-options');
    }
  }

  filterOptionsByRelatedData(selectedItem, dataAttribute, targetModalInputName) {
    const relatedData = selectedItem.dataset[dataAttribute];
    if (!relatedData) return;

    try {
      const relatedItems = JSON.parse(relatedData);
      const itemValues = new Set(relatedItems.map(item => item.value || item.id));

      const targetModal = this.dom.findModalByInputName(targetModalInputName);
      if (!targetModal) return;

      const options = targetModal.querySelectorAll(CONFIG.ELEMENTS.option);
      
      options.forEach(option => {
        const checkbox = option.querySelector('input[type="checkbox"]');
        if (!checkbox) return;

        const isAvailable = itemValues.has(checkbox.value);
        option.classList.toggle(CONFIG.CSS_CLASSES.optionHidden, !isAvailable);
      });
    } catch (error) {
      console.warn(`Error parsing ${dataAttribute} data:`, error);
    }
  }

  dom = {
    findModalByInputName: (inputName) => {
      const inputs = document.querySelectorAll(`input[name="${inputName}"]`);
      if (inputs.length === 0) return null;
      
      const firstInput = inputs[0];
      return firstInput.closest('[data-search-filter-form-dropdown-modal]');
    },
    
    findOption: (checkbox) => checkbox.closest(CONFIG.ELEMENTS.option),
    
    findCheckboxes: (modal) => modal.querySelectorAll(CONFIG.ELEMENTS.option + ' input[type="checkbox"]'),
    
    findDropdown: (modal) => modal.closest(CONFIG.ELEMENTS.dropdown)
  };

  resetCrossFiltering(checkbox) {
    const { name } = checkbox;
    
    const crossFilterMap = {
      'states-options': 'payors-options',
      'payors-options': 'states-options'
    };
    
    const targetModalName = crossFilterMap[name];
    if (!targetModalName) return;
    
    const targetModal = this.dom.findModalByInputName(targetModalName);
    if (targetModal) {
      const options = targetModal.querySelectorAll(CONFIG.ELEMENTS.option);
      options.forEach(option => {
        option.classList.remove(CONFIG.CSS_CLASSES.optionHidden);
      });
    }
  }

  handleFormSubmit(e) {
    e.preventDefault();
    
    this.validation.clearAllErrors();
    
    if (!this.validation.validateRequiredFields()) {
      return false;
    }
    
    const form = e.target;
    const searchParams = new URLSearchParams();
    
    const checkboxes = form.querySelectorAll('input[type="checkbox"]:checked');
    
    const selections = {};
    for (const checkbox of checkboxes) {
      const name = checkbox.name;
      if (!selections[name]) {
        selections[name] = [];
      }
      selections[name].push(checkbox.value);
    }
    
    for (const [name, values] of Object.entries(selections)) {
      if (name === 'specialties-options') {
        values.forEach((value, index) => {
          searchParams.append(`specialty[${index}]`, value);
        });
      } else if (name === 'states-options') {
        searchParams.append('state', values[0]);
      } else if (name === 'payors-options') {
        searchParams.append('insurance', values[0]);
      } else if (name === 'type-of-care-options') {
        searchParams.append('typeOfCare', values[0]);
      }
    }
    
    const stateValue = selections['states-options']?.[0];
    const stateSlug = stateValue ? this.utils.slugify(stateValue) : '';
    
    const baseUrl = window.location.origin + '/find/therapists';
    const urlPath = stateSlug ? `/${stateSlug}` : '';
    const searchUrl = baseUrl + urlPath + '?' + searchParams.toString();
    
    console.log(searchUrl);
    
    // window.location.href = searchUrl;
  }

  updateDropdownLabel(checkbox) {
    const modal = checkbox.closest(CONFIG.ELEMENTS.dropdownModal);
    const dropdown = modal?.closest(CONFIG.ELEMENTS.dropdown);
    if (!dropdown) return;
    
    const button = dropdown.querySelector(CONFIG.ELEMENTS.modalTrigger);
    const label = button?.querySelector(CONFIG.ELEMENTS.label);
    if (!label) return;
    
    const name = checkbox.name;
    const checkedOptions = dropdown.querySelectorAll(`input[name="${name}"]:checked`);
    
    if (checkedOptions.length === 0) {
      label.textContent = button.dataset.placeholder || 'Select option';
    } else if (checkedOptions.length === 1) {
      const option = checkedOptions[0].closest(CONFIG.ELEMENTS.option);
      if (option) {
        const textContent = Array.from(option.childNodes)
          .filter(node => node.nodeType === Node.TEXT_NODE)
          .map(node => node.textContent.trim())
          .join(' ');
        label.textContent = textContent || 'Option selected';
      }
    } else {
      const placeholder = button.dataset.placeholder || 'Options';
      label.textContent = `${placeholder} (${checkedOptions.length})`;
    }

    if (checkedOptions.length > 0) {
      this.validation.validateField(dropdown);
    }
  }
}

document.addEventListener('DOMContentLoaded', () => {
  new SearchFilterForm();
});
