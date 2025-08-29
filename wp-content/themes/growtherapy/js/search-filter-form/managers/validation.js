'use strict';

import { CONFIG } from '../config.js';

/**
 * Form Validation System
 * Handles form field validation, error states, and validation caching
 */
export class ValidationManager {
  #stateCache = new Map();
  #cachedRequiredDropdowns = null;
  #cachedErrorDropdowns = null;
  
  STATES = {
    VALID: 'valid',
    ERROR: 'error'
  };
  
  /**
   * Get cached required dropdowns or query DOM if not cached
   * @returns {NodeList} - Required dropdown elements
   */
  #getRequiredDropdowns() {
    if (!this.#cachedRequiredDropdowns) {
      this.#cachedRequiredDropdowns = document.querySelectorAll('[data-required="true"]');
    }
    return this.#cachedRequiredDropdowns;
  }
  
  /**
   * Get cached error dropdowns or query DOM if not cached
   * @returns {NodeList} - Error dropdown elements
   */
  #getErrorDropdowns() {
    if (!this.#cachedErrorDropdowns) {
      this.#cachedErrorDropdowns = document.querySelectorAll(CONFIG.CSS_CLASSES.dropdownError);
    }
    return this.#cachedErrorDropdowns;
  }
  
  /**
   * Clear DOM caches when validation state changes
   */
  #clearDOMCache() {
    this.#cachedRequiredDropdowns = null;
    this.#cachedErrorDropdowns = null;
  }
  
  validateRequiredFields() {
    const requiredDropdowns = this.#getRequiredDropdowns();
    
    if (requiredDropdowns.length === 0) {
      return true;
    }
    
    let isValid = true;
    
    requiredDropdowns.forEach(dropdown => {
      const dropdownWrapper = dropdown.closest(CONFIG.ELEMENTS.dropdown);
      if (!dropdownWrapper) return;
      
      const checkboxes = dropdownWrapper.querySelectorAll('input[type="checkbox"]:checked');
      const hasSelection = checkboxes.length > 0;
      const currentState = hasSelection ? this.STATES.VALID : this.STATES.ERROR;
      const cachedState = this.#stateCache.get(dropdownWrapper);
      
      // Only update state if it has changed
      if (cachedState !== currentState) {
        if (!hasSelection) {
          const message = dropdown.dataset.validationMessage ?? 'This field is required';
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
    // Early return if state hasn't changed
    const cachedState = this.#stateCache.get(dropdown);
    if (cachedState === state) {
      return;
    }
    
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
    
    this.#stateCache.set(dropdown, state);
    this.#clearDOMCache(); // Clear cache when state changes
  }
  
  clearFieldError(dropdown) {
    this.setFieldState(dropdown, this.STATES.VALID);
  }
  
  clearAllErrors() {
    const errorDropdowns = this.#getErrorDropdowns();
    
    // Early return if no errors to clear
    if (errorDropdowns.length === 0) {
      this.#stateCache.clear();
      this.#clearDOMCache();
      return;
    }
    
    errorDropdowns.forEach(dropdown => {
      this.clearFieldError(dropdown);
    });
    
    this.#stateCache.clear();
    this.#clearDOMCache();
  }
  
  clearFieldCache(dropdown) {
    this.#stateCache.delete(dropdown);
  }
  
  getValidationStats() {
    const requiredDropdowns = this.#getRequiredDropdowns();
    
    // Early return if no required fields
    if (requiredDropdowns.length === 0) {
      return {
        total: 0,
        valid: 0,
        errors: 0,
        isValid: true
      };
    }
    
    let validCount = 0;
    let errorCount = 0;
    
    requiredDropdowns.forEach(dropdown => {
      const dropdownWrapper = dropdown.closest(CONFIG.ELEMENTS.dropdown);
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
    this.#stateCache.clear();
    this.#clearDOMCache();
  }
  
  validateField(dropdown) {
    // Early return if dropdown is not valid
    if (!dropdown) {
      return false;
    }
    
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
}
