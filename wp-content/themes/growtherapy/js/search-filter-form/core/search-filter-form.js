'use strict';

import { CONFIG } from './config.js';
import {
  utils,
  EventsManager,
  FormSyncManager,
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
    this.validation = new ValidationManager(this.forms);
    
    this.formSyncManager = new FormSyncManager(this.forms, this.validation, {
      applyCrossFiltering: this.applyCrossFiltering.bind(this),
      resetCrossFiltering: this.resetCrossFiltering.bind(this)
    });
    
    this.urlManager = new URLManager({
      updateDropdownLabel: this.formSyncManager.updateDropdownLabel.bind(this.formSyncManager),
      triggerCrossFiltering: this.applyCrossFiltering.bind(this)
    });
    
    this.eventsManager = new EventsManager({
      checkboxChange: this.handleCheckboxChange.bind(this),
      clickOutside: this.handleClickOutside.bind(this),
      deselectButton: this.handleDeselectClick.bind(this),
      doneButton: this.handleDoneClick.bind(this),
      formSubmit: this.handleFormSubmit.bind(this),
      input: (e) => this.searchManager.handleInput(e),
      modalCleanup: this.cleanup.bind(this),
      modalTrigger: this.modalManager.handleModalOpen.bind(this.modalManager),
    });
    
    this.init();
  }
  
  init() {
    this.urlManager.populateFromUrlParams();
    this.eventsManager.bind();
  }

  cleanup() {
    this.searchManager.cleanup();
  }

  destroy() {
    this.eventsManager.unbind();
    this.searchManager.cleanup();
  }

  handleCheckboxChange(e) {
    const { target: checkbox } = e;
    
    if (!checkbox.matches(CONFIG.ELEMENTS.checkboxSingleSelect)) {
      this.formSyncManager.updateDropdownLabel(checkbox);
      if (checkbox.checked) {
        this.formSyncManager.syncCheckboxToAllForms(checkbox);
      } else {
        this.formSyncManager.syncCheckboxDeselectionToAllForms(checkbox);
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
      this.handleSingleSelectCheck(checkbox, modal);
    } else {
      this.handleSingleSelectUncheck(checkbox, e);
    }
  }

  handleSingleSelectCheck(checkbox, modal) {
    const { name } = checkbox;
    const selector = `input[name="${name}"]:not(#${checkbox.id})`;
    const otherCheckboxes = modal.querySelectorAll(selector);
    
    otherCheckboxes.forEach(otherCheckbox => {
      this.formSyncManager.syncCheckboxDeselectionToAllForms(otherCheckbox);
    });
    
    const currentOption = checkbox.closest(CONFIG.ELEMENTS.option);
    if (currentOption) {
      currentOption.classList.add(CONFIG.CSS_CLASSES.optionSelected);
    }
    
    this.modalManager.close(modal);
    this.formSyncManager.updateDropdownLabel(checkbox);
    this.formSyncManager.syncCheckboxToAllForms(checkbox);
  }

  handleSingleSelectUncheck(checkbox, e) {
    const label = checkbox.closest('label');
    if (label && !e.target.closest(CONFIG.ELEMENTS.deselectButton)) {
      checkbox.checked = true;
      this.formSyncManager.updateDropdownLabel(checkbox);
      this.formSyncManager.syncCheckboxToAllForms(checkbox);
      return;
    }
    
    this.formSyncManager.updateDropdownLabel(checkbox);
    this.formSyncManager.syncCheckboxDeselectionToAllForms(checkbox);
  }

  handleDeselectClick(e) {
    const deselectButton = e.target.closest(CONFIG.ELEMENTS.deselectButton);
    if (!deselectButton) return;
    
    e.preventDefault();
    e.stopPropagation();
    
    const option = deselectButton.closest(CONFIG.ELEMENTS.option);
    const checkbox = option?.querySelector('input[type="checkbox"]');

    if (checkbox) {
      this.formSyncManager.updateDropdownLabel(checkbox);
      this.formSyncManager.syncCheckboxDeselectionToAllForms(checkbox);
    }
  }

  handleDoneClick(e) {
    const doneButton = e.target.closest(CONFIG.ELEMENTS.doneButton);
    if (!doneButton) return;
    
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

  applyCrossFiltering() {
    let globalSelectedState = null;
    let globalSelectedInsurance = null;

    for (const form of this.forms) {
      if (!globalSelectedState) {
        globalSelectedState = form.querySelector('input[name="states-options"]:checked');
      }
      if (!globalSelectedInsurance) {
        globalSelectedInsurance = form.querySelector('input[name="payors-options"]:checked');
      }
      if (globalSelectedState && globalSelectedInsurance) break;
    }

    if (globalSelectedState) {
      this.filterOptionsByRelatedData(globalSelectedState, 'relatedInsurance', 'payors-options');
    }

    if (globalSelectedInsurance) {    
      this.filterOptionsByRelatedData(globalSelectedInsurance, 'relatedStates', 'states-options');
    }
  }

  filterOptionsByRelatedData(selectedItem, dataAttribute, targetModalInputName) {
    const relatedData = selectedItem.dataset[dataAttribute];
    if (!relatedData) return;

    try {
      const relatedItems = JSON.parse(relatedData);
      const itemValues = new Set(relatedItems.map(item => item.value || item.id));

      const targetInputs = document.querySelectorAll(`input[name="${targetModalInputName}"]`);
      
      targetInputs.forEach(input => {
        const modal = input.closest(CONFIG.ELEMENTS.dropdownModal);
        if (!modal) return;

        const options = modal.querySelectorAll(CONFIG.ELEMENTS.option);
        
        options.forEach(option => {
          const checkbox = option.querySelector('input[type="checkbox"]');
          if (!checkbox) return;

          const isAvailable = itemValues.has(checkbox.value);
          option.classList.toggle(CONFIG.CSS_CLASSES.optionHidden, !isAvailable);
        });
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
    
    const targetInputs = document.querySelectorAll(`input[name="${targetModalName}"]`);
    
    targetInputs.forEach(input => {
      const modal = input.closest(CONFIG.ELEMENTS.dropdownModal);
      if (modal) {
        const options = modal.querySelectorAll(CONFIG.ELEMENTS.option);
        options.forEach(option => {
          option.classList.remove(CONFIG.CSS_CLASSES.optionHidden);
        });
      }
    });
  }

  handleFormSubmit(e) {
    e.preventDefault();
    
    const form = e.target;
    this.validation.clearAllErrors(form);
    
    if (!this.validation.validateRequiredFields(form)) {
      return false;
    }

    const searchParams = this.buildSearchParams(form);
    const searchUrl = this.buildSearchUrl(searchParams);
    
    window.location.href = searchUrl;
  }

  buildSearchParams(form) {
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

    searchParams.append('setting', 'Virtual');
    
    return searchParams;
  }

  buildSearchUrl(searchParams) {
    const stateValue = searchParams.get('state');
    const stateSlug = stateValue ? utils.slugify(stateValue) : '';
    
    const baseUrl = `https://growtherapy.com/find/${stateSlug}`;
    
    return baseUrl + '?' + searchParams.toString();
  }
}
