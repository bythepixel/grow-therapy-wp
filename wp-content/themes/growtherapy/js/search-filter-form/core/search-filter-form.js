'use strict';

import { CONFIG } from './config.js';
import {
  dom,
  utils,
  ModalManager,
  SearchManager,
  URLManager,
  ValidationManager,
} from '../managers/index.js';

export default class SearchFilterForm {
  constructor() {
    this.activeModals = new Set();

    this.forms = document.querySelectorAll(CONFIG.ELEMENTS.form);
    
    this.searchManager = new SearchManager();
    this.modalManager = new ModalManager(this.activeModals, this.searchManager);
    this.urlManager = new URLManager({
      getDropdownApiKey: this.getDropdownApiKey.bind(this),
      updateDropdownLabel: this.updateDropdownLabel.bind(this)
    });
    this.validation = new ValidationManager(this.forms);
    
    this.init();
  }
  
  init() {
    this.bindEvents();
    this.urlManager.populateFromUrlParams();
  }

  /**
   * Get the API key for a specific dropdown (states, payors, specialties, type-of-care)
   */
  getDropdownApiKey(dropdown) {
    const modal = dropdown.querySelector(CONFIG.ELEMENTS.dropdownModal);
    if (!modal?.id) {
      return null;
    }
    
    // Use startsWith for better performance than regex
    const apiKey = modal.id.startsWith(CONFIG.CONSTANTS.MODAL_PREFIX) ? 
                   modal.id.slice(CONFIG.CONSTANTS.MODAL_PREFIX.length) : null;
    
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
    
    document.addEventListener('input', (e) => this.searchManager.handleInput(e), { 
      passive: true,
      capture: false 
    });
    
    document.addEventListener('modalCleanup', this.cleanup.bind(this), { 
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
        this.modalManager.handleModalOpen(modalTrigger);
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

  cleanup() {
    this.searchManager.cleanup();
  }

  handleCheckboxChange(e) {
    const { target: checkbox } = e;
    
    if (!checkbox.matches(CONFIG.ELEMENTS.checkboxSingleSelect)) {
      if (checkbox.checked) {
        this.syncCheckboxToAllForms(checkbox);
      } else {
        this.syncCheckboxDeselectionToAllForms(checkbox);
      }
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
        this.syncCheckboxDeselectionToAllForms(otherCheckbox);
      });
      
      const currentOption = dom.findOption(checkbox);
      if (currentOption) {
        currentOption.classList.add(CONFIG.CSS_CLASSES.optionSelected);
      }
      
      this.modalManager.close(modal);
      this.applyCrossFiltering(checkbox);
      this.syncCheckboxToAllForms(checkbox);
    } else {
      const label = checkbox.closest('label');
      if (label && !e.target.closest(CONFIG.ELEMENTS.deselectButton)) {
        checkbox.checked = true;
        this.syncCheckboxToAllForms(checkbox);
        return;
      }
      
      this.syncCheckboxDeselectionToAllForms(checkbox);
    }
  }

  handleDeselectClick(e) {
    const deselectButton = e.target.closest(CONFIG.ELEMENTS.deselectButton);

    if (!deselectButton) return;
    
    e.preventDefault();
    e.stopPropagation();
    
    const option = deselectButton.closest(CONFIG.ELEMENTS.option);
    const checkbox = option?.querySelector('input[type="checkbox"]');

    if (checkbox) {
      this.syncCheckboxDeselectionToAllForms(checkbox);
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

      const targetModal = dom.findModalByInputName(targetModalInputName);
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

  resetCrossFiltering(checkbox) {
    const { name } = checkbox;
    
    const crossFilterMap = {
      'states-options': 'payors-options',
      'payors-options': 'states-options'
    };
    
    const targetModalName = crossFilterMap[name];
    if (!targetModalName) return;
    
    const targetModal = dom.findModalByInputName(targetModalName);
    if (targetModal) {
      const options = targetModal.querySelectorAll(CONFIG.ELEMENTS.option);
      options.forEach(option => {
        option.classList.remove(CONFIG.CSS_CLASSES.optionHidden);
      });
    }
  }

  handleFormSubmit(e) {
    e.preventDefault();
    
    const form = e.target;
    
    this.validation.clearAllErrors(form);
    
    if (!this.validation.validateRequiredFields(form)) {
      return false;
    }
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
    const stateSlug = stateValue ? utils.slugify(stateValue) : '';
    
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

  syncCheckboxToAllForms(sourceCheckbox) {
    if (!this.forms || this.forms.length === 0) return;
    
    const { name, value } = sourceCheckbox;
    
    this.forms.forEach(form => {
      const targetCheckbox = form.querySelector(`input[name="${name}"][value="${value}"]`);
      if (targetCheckbox && !targetCheckbox.checked) {
        targetCheckbox.checked = true;
        
        const option = targetCheckbox.closest(CONFIG.ELEMENTS.option);
        if (option) {
          option.classList.add(CONFIG.CSS_CLASSES.optionSelected);
        }
        
        this.updateDropdownLabel(targetCheckbox);
        this.applyCrossFiltering(targetCheckbox);
        
        const dropdown = targetCheckbox.closest(CONFIG.ELEMENTS.dropdown);
        if (dropdown && dropdown.querySelector('[data-required="true"]')) {
          this.validation.validateField(dropdown);
        }
      }
    });
  }

  syncCheckboxDeselectionToAllForms(sourceCheckbox) {
    if (!this.forms || this.forms.length === 0) return;
    
    const { name, value } = sourceCheckbox;
    
    this.forms.forEach(form => {
      const targetCheckbox = form.querySelector(`input[name="${name}"][value="${value}"]`);
      if (targetCheckbox && targetCheckbox.checked) {
        targetCheckbox.checked = false;
        
        const option = targetCheckbox.closest(CONFIG.ELEMENTS.option);
        if (option) {
          option.classList.remove(CONFIG.CSS_CLASSES.optionSelected);
        }
        
        this.updateDropdownLabel(targetCheckbox);
        this.resetCrossFiltering(targetCheckbox);
      }
    });
  }
}
