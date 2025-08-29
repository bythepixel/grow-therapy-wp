'use strict';

import { CONFIG } from '../core/config.js';

/**
 * Form Validation System
 * Handles form field validation, error states, and validation caching
 */
export class ValidationManager {
  constructor(forms) {
    this.forms = forms;
  }
  
  STATES = {
    VALID: 'valid',
    ERROR: 'error'
  }
  
  validateRequiredFields(formContext = null) {
    const context = formContext || document;
    const requiredDropdowns = context.querySelectorAll('[data-required="true"]');
    
    if (requiredDropdowns.length === 0) {
      return true;
    }
    
    let isValid = true;
    
    requiredDropdowns.forEach(dropdown => {
      const dropdownWrapper = dropdown.closest(CONFIG.ELEMENTS.dropdown);
      if (!dropdownWrapper) return;
      
      const checkboxes = dropdownWrapper.querySelectorAll('input[type="checkbox"]:checked');
      const hasSelection = checkboxes.length > 0;
      
      if (!hasSelection) {
        const message = dropdown.dataset.validationMessage ?? 'This field is required';
        this.setFieldState(dropdownWrapper, this.STATES.ERROR, message);
        isValid = false;
      } else {
        this.setFieldState(dropdownWrapper, this.STATES.VALID);
      }
    });
    
    return isValid;
  }
  
  setFieldState(dropdown, state, message = '') {
    const errorMsg = dropdown.querySelector(CONFIG.ELEMENTS.validationMessage);
    const button = dropdown.querySelector(CONFIG.ELEMENTS.modalTrigger);
    
    if (state === this.STATES.ERROR) {
      dropdown.classList.add(CONFIG.CSS_CLASSES.dropdownError);
      
      if (errorMsg) {
        errorMsg.textContent = message;
        errorMsg.style.display = 'block';
      }
      
      button?.setAttribute('aria-invalid', 'true');
    } else {
      dropdown.classList.remove(CONFIG.CSS_CLASSES.dropdownError);
      
      if (errorMsg) {
        errorMsg.style.display = 'none';
        errorMsg.textContent = '';
      }
      
      button?.removeAttribute('aria-invalid');
    }
    
    // Sync validation state to all other forms
    this.syncValidationToAllForms(dropdown, state, message);
  }
  
  clearFieldError(dropdown) {
    this.setFieldState(dropdown, this.STATES.VALID);
  }
  
  clearAllErrors(formContext = null) {
    const context = formContext || document;
    const errorDropdowns = context.querySelectorAll(CONFIG.CSS_CLASSES.dropdownError);
    
    if (errorDropdowns.length === 0) return;
    
    errorDropdowns.forEach(dropdown => {
      this.clearFieldError(dropdown);
    });
  }
  
  
  
  validateField(dropdown) {
    if (!dropdown) return false;
    
    const checkboxes = dropdown.querySelectorAll('input[type="checkbox"]:checked');
    const hasSelection = checkboxes.length > 0;
    
    if (!hasSelection) {
      const button = dropdown.querySelector('[data-required="true"]');
      const message = button?.dataset.validationMessage ?? 'This field is required';
      this.setFieldState(dropdown, this.STATES.ERROR, message);
      return false;
    } else {
      this.setFieldState(dropdown, this.STATES.VALID);
      return true;
    }
  }

  syncValidationToAllForms(sourceDropdown, state, message) {
    if (!this.forms || this.forms.length === 0) return;
    
    const fieldName = this.getFieldIdentifier(sourceDropdown);
    if (!fieldName) return;
    
    this.forms.forEach(form => {
      if (form === sourceDropdown.closest('form')) return;
      
      const targetDropdown = this.findDropdownInForm(form, fieldName);
      if (targetDropdown) {
        this.setFieldStateInForm(targetDropdown, state, message);
      }
    });
  }

  getFieldIdentifier(dropdown) {
    const nameInput = dropdown.querySelector('input[type="checkbox"]');
    if (nameInput && nameInput.name) {
      return nameInput.name;
    }
    
    return dropdown.dataset.fieldName || dropdown.dataset.name || null;
  }

  findDropdownInForm(form, fieldName) {
    const checkboxes = form.querySelectorAll(`input[name="${fieldName}"]`);
    if (checkboxes.length === 0) return null;
    
    return checkboxes[0].closest(CONFIG.ELEMENTS.dropdown);
  }

  setFieldStateInForm(dropdown, state, message = '') {
    const errorMsg = dropdown.querySelector(CONFIG.ELEMENTS.validationMessage);
    const button = dropdown.querySelector(CONFIG.ELEMENTS.modalTrigger);
    
    if (state === this.STATES.ERROR) {
      dropdown.classList.add(CONFIG.CSS_CLASSES.dropdownError);
      
      if (errorMsg) {
        errorMsg.textContent = message;
        errorMsg.style.display = 'block';
      }
      
      button?.setAttribute('aria-invalid', 'true');
    } else {
      dropdown.classList.remove(CONFIG.CSS_CLASSES.dropdownError);
      
      if (errorMsg) {
        errorMsg.style.display = 'none';
        errorMsg.textContent = '';
      }
      
      button?.removeAttribute('aria-invalid');
    }
  }
}
