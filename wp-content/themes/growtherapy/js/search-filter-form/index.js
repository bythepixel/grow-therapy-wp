'use strict';

/**
 * Search Filter Form
 * Handles dropdown modals, search filtering, and form submission
 * 
 * SHARED STATE FEATURE:
 * Multiple instances of this form on the same page will automatically
 * share their state. When a user selects an option in one instance,
 * all other instances will be updated to reflect the same selection.
 */
class SearchFilterForm {
  // Static constants for better performance and maintainability
  static CONSTANTS = {
    URL_PARAMS: ['state', 'insurance', 'specialty', 'typeOfCare'],
    RETRY_DELAY: 100,
    MODAL_PREFIX: 'modal-'
  };

  constructor() {
    this.elements = {
      checkboxSingleSelect: '[data-search-filter-form-single-select]',
      deselectButton: '[data-search-filter-form-deselect-button]',
      doneButton: '[data-search-filter-form-done-button]',
      dropdown: '[data-search-filter-form-dropdown]',
      dropdownModal: '[data-search-filter-form-dropdown-modal]',
      label: '[data-search-filter-form-label]',
      modalTrigger: '[data-search-filter-form-modal-trigger]',
      option: '[data-search-filter-form-option]',
      searchInput: '[data-search-filter-form-search-input]',
      validationMessage: '[data-search-filter-form-validation-message]',
    };

    this.cssClasses = {
      dropdownError: 'search-filter-form-dropdown--error',
      modalActive: 'search-filter-form-modal--active',
      optionHidden: 'search-filter-form-modal__option--hidden',
      optionSelected: 'search-filter-form-modal__option--selected',
    };

    this.activeModals = new Set();
    this.searchDebounceTimers = new Map();
    this.instanceId = this.utils.generateId();
    
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
      
      // Use Map for O(1) lookups
      const paramMap = new Map([
        ['states', 'state'],
        ['payors', 'insurance'],
        ['specialties', 'specialty'],
        ['type-of-care', 'typeOfCare']
      ]);
      
      // Cache selector for better performance
      const dropdowns = document.querySelectorAll('[data-search-filter-form-dropdown]');
      
      for (const dropdown of dropdowns) {
        const modal = dropdown.querySelector('[data-search-filter-form-dropdown-modal]');
        if (!modal?.id) continue;
        
        const apiKey = modal.id.slice(SearchFilterForm.CONSTANTS.MODAL_PREFIX.length); // Remove 'modal-' prefix
        const paramName = paramMap.get(apiKey);
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
        
        const option = checkbox.closest(this.elements.option);
        if (option) {
          option.classList.toggle(this.cssClasses.optionSelected, checked);
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
      const dropdowns = this.elements.dropdown ? 
        document.querySelectorAll(this.elements.dropdown) : 
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
            
            const option = checkbox.closest(this.elements.option);
            if (option) {
              option.classList.add(this.cssClasses.optionSelected);
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
    if (document.querySelectorAll(this.elements.dropdown).length === 0) {
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
    
    // Cache relevant parameter checks for better performance
    const hasRelevantParams = SearchFilterForm.CONSTANTS.URL_PARAMS.some(param => urlParams.has(param));
    
    if (!hasRelevantParams) return;
    
    // Use Map for O(1) lookups instead of Object.entries iteration
    const paramMappings = new Map([
      ['state', 'location'],
      ['insurance', 'insurance'],
      ['typeOfCare', 'type_of_care']
    ]);
    
    // Batch process parameters for better performance
    const populationPromises = [];
    
    for (const [param, dropdownType] of paramMappings) {
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
    const dropdowns = document.querySelectorAll(this.elements.dropdown);
    if (dropdowns.length === 0) return 0;
    
    const expectedApiKey = this.getExpectedApiKey(dropdownType);
    if (!expectedApiKey) return 0;
    
    // Convert single value to array for consistent processing
    const valueArray = Array.isArray(values) ? values : [values];
    
    // Use Set for O(1) value lookups
    const valueSet = new Set(valueArray);
    let populatedCount = 0;
    
    // Batch DOM updates for better performance
    const updates = [];
    
    for (const dropdown of dropdowns) {
      const modal = dropdown.querySelector(this.elements.dropdownModal);
      if (!modal) continue;
      
      // Determine which dropdown this is based on API key
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
    const option = checkbox.closest(this.elements.option);
    if (option) {
      option.classList.add(this.cssClasses.optionSelected);
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
    const modal = dropdown.querySelector(this.elements.dropdownModal);
    if (!modal?.id) {
      return null;
    }
    
    // Use startsWith for better performance than regex
    const apiKey = modal.id.startsWith(SearchFilterForm.CONSTANTS.MODAL_PREFIX) ? 
                   modal.id.slice(SearchFilterForm.CONSTANTS.MODAL_PREFIX.length) : null;
    
    return apiKey;
  }

  /**
   * Get the expected API key for a dropdown type
   * @param {string} dropdownType - The dropdown type (location, insurance, needs, type_of_care)
   * @returns {string} - The expected API key
   */
  getExpectedApiKey(dropdownType) {
    // Use Map for O(1) lookups instead of object property access
    const apiKeyMap = new Map([
      ['location', 'states'],
      ['insurance', 'payors'],
      ['needs', 'specialties'],
      ['type_of_care', 'type-of-care']
    ]);
    
    return apiKeyMap.get(dropdownType) || null;
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
      const modalTrigger = e.target.closest(this.elements.modalTrigger);
      const doneButton = e.target.closest(this.elements.doneButton);
      const deselectButton = e.target.closest(this.elements.deselectButton);
      
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
      const modal = dropdown.querySelector(this.elements.dropdownModal);
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
      const button = dropdown.querySelector(this.elements.modalTrigger);
      
      modal.classList.remove('aria-hidden');
      modal.classList.add(this.cssClasses.modalActive);
      modal.setAttribute('aria-hidden', 'false');
      button?.setAttribute('aria-expanded', 'true');
      
      this.activeModals.add(modal);
      this.searchManager.clear(modal);
    },

    close: (modal) => {
      const dropdown = modal.closest(this.elements.dropdown);
      if (!dropdown) return;
      
      const button = dropdown.querySelector(this.elements.modalTrigger);
      
      modal.classList.remove(this.cssClasses.modalActive);
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
    const dropdown = button.closest(this.elements.dropdown);
    if (!dropdown) return;
    
    const modal = dropdown.querySelector(this.elements.dropdownModal);
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
    
    if (!checkbox.matches(this.elements.checkboxSingleSelect)) {
      this.updateDropdownLabel(checkbox);
      return;
    }
    
    const { name } = checkbox;
    const modal = checkbox.closest(this.elements.dropdownModal);
    
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
        currentOption.classList.add(this.cssClasses.optionSelected);
      }
      
      this.modalManager.close(modal);
      this.applyCrossFiltering(checkbox);
    } else {
      const label = checkbox.closest('label');
      if (label && !e.target.closest(this.elements.deselectButton)) {
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
      option.classList.remove(this.cssClasses.optionSelected);
    }
    
    this.resetCrossFiltering(checkbox);
    this.updateDropdownLabel(checkbox);
  }

  handleDeselectClick(e) {
    const deselectButton = e.target.closest(this.elements.deselectButton);

    if (!deselectButton) return;
    
    e.preventDefault();
    e.stopPropagation();
    
    const option = deselectButton.closest(this.elements.option);
    const checkbox = option?.querySelector('input[type="checkbox"]');

    if (checkbox) {
      this.deselectSingleSelectOption(checkbox);
    }
  }

  handleDoneClick(doneButton) {
    const modal = doneButton.closest(this.elements.dropdownModal);
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
      if (!searchInput.matches(this.elements.searchInput)) return;

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
      const modal = searchInput.closest(this.elements.dropdownModal);
      if (!modal) return;

      const searchTerm = searchInput.value.toLowerCase().trim();
      
      if (!searchTerm) {
        this.searchManager.applyCrossFiltering(modal);
        return;
      }

      const options = modal.querySelectorAll(this.elements.option);
      
      options.forEach(option => {
        const checkbox = option.querySelector('input[type="checkbox"]');
        if (!checkbox) return;

        const searchData = checkbox.dataset.searchData;
        if (!searchData) return;

        try {
          const data = JSON.parse(searchData);
          const isSearchMatch = data.searchText.includes(searchTerm);
          const isCrossFiltered = !option.classList.contains(this.cssClasses.optionHidden);
          
          option.style.display = (isSearchMatch && isCrossFiltered) ? '' : 'none';
        } catch (error) {
          console.warn('Invalid search data for option:', checkbox);
          option.style.display = '';
        }
      });
    },

    applyCrossFiltering: (modal) => {
      const options = modal.querySelectorAll(this.elements.option);
      options.forEach(option => {
        const isCrossFiltered = !option.classList.contains(this.cssClasses.optionHidden);
        option.style.display = isCrossFiltered ? '' : 'none';
      });
    },

    clear: (modal) => {
      const searchInput = modal.querySelector(this.elements.searchInput);
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

      const options = targetModal.querySelectorAll(this.elements.option);
      
      options.forEach(option => {
        const checkbox = option.querySelector('input[type="checkbox"]');
        if (!checkbox) return;

        const isAvailable = itemValues.has(checkbox.value);
        option.classList.toggle(this.cssClasses.optionHidden, !isAvailable);
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
    
    findOption: (checkbox) => checkbox.closest(this.elements.option),
    
    findCheckboxes: (modal) => modal.querySelectorAll(this.elements.option + ' input[type="checkbox"]'),
    
    findDropdown: (modal) => modal.closest(this.elements.dropdown)
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
      const options = targetModal.querySelectorAll(this.elements.option);
      options.forEach(option => {
        option.classList.remove(this.cssClasses.optionHidden);
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

  /**
   * Validation system for form fields
   */
  validation = {
    // Cache for validation states to avoid unnecessary DOM queries
    stateCache: new Map(),
    
    // Constants for validation states
    STATES: {
      VALID: 'valid',
      ERROR: 'error'
    },
    
    /**
     * Validate all required fields
     * @returns {boolean} - True if all required fields are valid
     */
    validateRequiredFields: () => {
      const requiredDropdowns = document.querySelectorAll('[data-required="true"]');
      let isValid = true;
      
      requiredDropdowns.forEach(dropdown => {
        const dropdownWrapper = dropdown.closest(this.elements.dropdown);
        if (!dropdownWrapper) return;
        
        const checkboxes = dropdownWrapper.querySelectorAll('input[type="checkbox"]:checked');
        const hasSelection = checkboxes.length > 0;
        const currentState = hasSelection ? this.validation.STATES.VALID : this.validation.STATES.ERROR;
        const cachedState = this.validation.stateCache.get(dropdownWrapper);
        
        if (cachedState !== currentState) {
          if (!hasSelection) {
            const message = dropdown.dataset.validationMessage || 'This field is required';
            this.validation.setFieldState(dropdownWrapper, this.validation.STATES.ERROR, message);
            isValid = false;
          } else {
            this.validation.setFieldState(dropdownWrapper, this.validation.STATES.VALID);
          }
        } else if (!hasSelection) {
          isValid = false;
        }
      });
      
      return isValid;
    },

    /**
     * Set field validation state
     * @param {HTMLElement} dropdown - The dropdown wrapper element
     * @param {string} state - 'valid' or 'error'
     * @param {string} [message] - Error message (required for error state)
     */
    setFieldState: (dropdown, state, message = '') => {
      const errorMsg = dropdown.querySelector(this.elements.validationMessage);
      const button = dropdown.querySelector(this.elements.modalTrigger);
      
      if (state === this.validation.STATES.ERROR) {
        dropdown.classList.add(this.cssClasses.dropdownError);
        
        if (errorMsg) {
          errorMsg.textContent = message;
          errorMsg.style.display = 'block';
        }
        
        if (button) {
          button.setAttribute('aria-invalid', 'true');
        }
      } else {
        dropdown.classList.remove(this.cssClasses.dropdownError);
        
        if (errorMsg) {
          errorMsg.style.display = 'none';
          errorMsg.textContent = '';
        }
        
        if (button) {
          button.removeAttribute('aria-invalid');
        }
      }
      
      this.validation.stateCache.set(dropdown, state);
    },

    /**
     * Clear validation error for a specific dropdown
     * @param {HTMLElement} dropdown - The dropdown wrapper element
     */
    clearFieldError: (dropdown) => {
      this.validation.setFieldState(dropdown, 'valid');
    },

    /**
     * Clear all validation errors
     */
    clearAllErrors: () => {
      const errorDropdowns = document.querySelectorAll(this.cssClasses.dropdownError);
      errorDropdowns.forEach(dropdown => {
        this.validation.clearFieldError(dropdown);
      });
      this.validation.stateCache.clear();
    },

    /**
     * Clear validation cache for a specific field
     * @param {HTMLElement} dropdown - The dropdown wrapper element
     */
    clearFieldCache: (dropdown) => {
      this.validation.stateCache.delete(dropdown);
    },

    /**
     * Get validation statistics
     * @returns {Object} - Object with validation counts and status
     */
    getValidationStats: () => {
      const requiredDropdowns = document.querySelectorAll('[data-required="true"]');
      let validCount = 0;
      let errorCount = 0;
      
      requiredDropdowns.forEach(dropdown => {
        const dropdownWrapper = dropdown.closest(this.elements.dropdown);
        if (!dropdownWrapper) return;
        
        const checkboxes = dropdownWrapper.querySelectorAll('input[type="checkbox"]:checked');
        if (checkboxes.length > 0) {
          validCount++;
        } else {
          errorCount++;
        }
      });
      
      return {
        total: requiredDropdowns.length,
        valid: validCount,
        errors: errorCount,
        isValid: errorCount === 0
      };
    },

    /**
     * Reset entire validation system
     */
    reset: () => {
      this.validation.clearAllErrors();
      this.validation.stateCache.clear();
    },

    /**
     * Validate a single field
     * @param {HTMLElement} dropdown - The dropdown wrapper element
     * @returns {boolean} - True if field is valid
     */
    validateField: (dropdown) => {
      const checkboxes = dropdown.querySelectorAll('input[type="checkbox"]:checked');
      const hasSelection = checkboxes.length > 0;
      
      if (!hasSelection) {
        const button = dropdown.querySelector('[data-required="true"]');
        const message = button?.dataset.validationMessage || 'This field is required';
        this.validation.setFieldState(dropdown, this.validation.STATES.ERROR, message);
        return false;
      } else {
        this.validation.setFieldState(dropdown, this.validation.STATES.VALID);
        return true;
      }
    }
  };

  updateDropdownLabel(checkbox) {
    const modal = checkbox.closest(this.elements.dropdownModal);
    const dropdown = modal?.closest(this.elements.dropdown);
    if (!dropdown) return;
    
    const button = dropdown.querySelector(this.elements.modalTrigger);
    const label = button?.querySelector(this.elements.label);
    if (!label) return;
    
    const name = checkbox.name;
    const checkedOptions = dropdown.querySelectorAll(`input[name="${name}"]:checked`);
    
    if (checkedOptions.length === 0) {
      label.textContent = button.dataset.placeholder || 'Select option';
    } else if (checkedOptions.length === 1) {
      const option = checkedOptions[0].closest(this.elements.option);
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
