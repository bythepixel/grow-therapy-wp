'use strict';

/**
 * Configuration file for Search Filter Form
 * Centralizes all constants, element selectors, and CSS classes
 */
export const CONFIG = {
  // Element selectors for DOM queries
  ELEMENTS: {
    checkboxSingleSelect: '[data-search-filter-form-single-select]',
    deselectButton: '[data-search-filter-form-deselect-button]',
    doneButton: '[data-search-filter-form-done-button]',
    dropdown: '[data-search-filter-form-dropdown]',
    dropdownModal: '[data-search-filter-form-dropdown-modal]',
    form: '[data-search-filter-form]',
    label: '[data-search-filter-form-label]',
    modalTrigger: '[data-search-filter-form-modal-trigger]',
    option: '[data-search-filter-form-option]',
    searchInput: '[data-search-filter-form-search-input]',
    validationMessage: '[data-search-filter-form-validation-message]'
  },

  // CSS classes for styling and state management
  CSS_CLASSES: {
    dropdownError: 'search-filter-form-dropdown--error',
    modalActive: 'search-filter-form-modal--active',
    optionHidden: 'search-filter-form-modal__option--hidden',
    optionSelected: 'search-filter-form-modal__option--selected'
  }
};
