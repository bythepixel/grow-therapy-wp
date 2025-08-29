'use strict';

/**
 * Form Validation System
 * Handles form field validation, error states, and validation caching
 */
export class ValidationManager {
  constructor(elements, cssClasses) {
    this.elements = elements;
    this.cssClasses = cssClasses;
    this.stateCache = new Map();
    this.STATES = {
      VALID: 'valid',
      ERROR: 'error'
    };
  }
  
  validateRequiredFields() {
    const requiredDropdowns = document.querySelectorAll('[data-required="true"]');
    let isValid = true;
    
    requiredDropdowns.forEach(dropdown => {
      const dropdownWrapper = dropdown.closest(this.elements.dropdown);
      if (!dropdownWrapper) return;
      
      const checkboxes = dropdownWrapper.querySelectorAll('input[type="checkbox"]:checked');
      const hasSelection = checkboxes.length > 0;
      const currentState = hasSelection ? this.STATES.VALID : this.STATES.ERROR;
      const cachedState = this.stateCache.get(dropdownWrapper);
      
      if (cachedState !== currentState) {
        if (!hasSelection) {
          const message = dropdown.dataset.validationMessage || 'This field is required';
          this.setFieldState(dropdownWrapper, this.STATES.ERROR, message);
          isValid = false;
        } else {
          this.setFieldState(dropdownWrapper, this.STATES.VALID);
        }
      } else if (!hasSelection) {
        isValid = false;
      }
    });
    
    return isValid;
  }
  
  setFieldState(dropdown, state, message = '') {
    const errorMsg = dropdown.querySelector(this.elements.validationMessage);
    const button = dropdown.querySelector(this.elements.modalTrigger);
    
    if (state === this.STATES.ERROR) {
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
    
    this.stateCache.set(dropdown, state);
  }
  
  clearFieldError(dropdown) {
    this.setFieldState(dropdown, 'valid');
  }
  
  clearAllErrors() {
    const errorDropdowns = document.querySelectorAll(this.cssClasses.dropdownError);
    errorDropdowns.forEach(dropdown => {
      this.clearFieldError(dropdown);
    });
    this.stateCache.clear();
  }
  
  clearFieldCache(dropdown) {
    this.stateCache.delete(dropdown);
  }
  
  getValidationStats() {
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
  }
  
  reset() {
    this.clearAllErrors();
    this.stateCache.clear();
  }
  
  validateField(dropdown) {
    const checkboxes = dropdown.querySelectorAll('input[type="checkbox"]:checked');
    const hasSelection = checkboxes.length > 0;
    
    if (!hasSelection) {
      const button = dropdown.querySelector('[data-required="true"]');
      const message = button?.dataset.validationMessage || 'This field is required';
      this.setFieldState(dropdown, this.STATES.ERROR, message);
      return false;
    } else {
      this.setFieldState(dropdown, this.STATES.VALID);
      return true;
    }
  }
}
