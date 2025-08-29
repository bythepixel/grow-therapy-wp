'use strict';

import { CONFIG } from '../core/config.js';

/**
 * Form Synchronization Manager
 * Handles multi-form synchronization, dropdown label updates, and checkbox state management
 */
export class FormSyncManager {
  constructor(forms, validationManager, callbacks = {}) {
    this.forms = forms;
    this.validationManager = validationManager;
    this.callbacks = {
      applyCrossFiltering: callbacks.applyCrossFiltering || (() => {}),
      resetCrossFiltering: callbacks.resetCrossFiltering || (() => {})
    };
  }

  syncCheckboxToAllForms(sourceCheckbox) {
    if (!this.forms || this.forms.length === 0) return;
    
    const { name, value } = sourceCheckbox;
    this.callbacks.applyCrossFiltering();
    
    this.forms.forEach(form => {
      const targetCheckbox = form.querySelector(`input[name="${name}"][value="${value}"]`);
      if (targetCheckbox && !targetCheckbox.checked) {
        targetCheckbox.checked = true;
        
        const option = targetCheckbox.closest(CONFIG.ELEMENTS.option);
        if (option && targetCheckbox.matches(CONFIG.ELEMENTS.checkboxSingleSelect)) {
          option.classList.add(CONFIG.CSS_CLASSES.optionSelected);
        }
        
        this.updateDropdownLabel(targetCheckbox);
        
        const dropdown = targetCheckbox.closest(CONFIG.ELEMENTS.dropdown);
        if (dropdown && dropdown.querySelector('[data-required="true"]')) {
          this.validationManager.validateField(dropdown);
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
        if (option && targetCheckbox.matches(CONFIG.ELEMENTS.checkboxSingleSelect)) {
          option.classList.remove(CONFIG.CSS_CLASSES.optionSelected);
        }
        
        this.updateDropdownLabel(targetCheckbox);
        this.callbacks.resetCrossFiltering(targetCheckbox);
      }
    });
  }

  updateDropdownLabel(checkbox) {
    try {
      const dropdown = checkbox.closest(CONFIG.ELEMENTS.dropdown);
      if (!dropdown) return;

      const searchData = JSON.parse(checkbox.dataset.searchData);
      const checkboxLabel = searchData.label;
      
      const button = dropdown.querySelector(CONFIG.ELEMENTS.modalTrigger);
      if (!button) return;
      
      const label = button.querySelector(CONFIG.ELEMENTS.label);
      if (!label) return;
      
      const checkboxName = checkbox.name;
      const checkedOptions = dropdown.querySelectorAll(`input[name="${checkboxName}"]:checked`);
      const checkedCount = checkedOptions.length;
      
      if (checkedCount === 0) {
        label.textContent = button.dataset.placeholder || 'Select option';
      } else if (checkedCount === 1) {
        label.textContent = checkboxLabel;
      } else {
        // Multi-select
        const placeholder = button.dataset.placeholder || 'Options';
        label.textContent = `${placeholder} (${checkedCount})`;
      }
      
      if (checkedCount > 0) {
        this.validationManager.validateField(dropdown);
      }
    } catch (error) {
      console.warn('Failed to parse searchData:', error);
    }
  }
}
