'use strict';

/**
 * Configuration file for Search Filter Form
 * Centralizes all constants, element selectors, and CSS classes
 */
export const CONFIG = {
  CONSTANTS: {
    URL_PARAMS: ['state', 'insurance', 'specialty', 'typeOfCare'],
    RETRY_DELAY: 100,
    MODAL_PREFIX: 'modal-'
  },

  // Element selectors for DOM queries
  ELEMENTS: {
    checkboxSingleSelect: '[data-search-filter-form-single-select]',
    deselectButton: '[data-search-filter-form-deselect-button]',
    doneButton: '[data-search-filter-form-done-button]',
    dropdown: '[data-search-filter-form-dropdown]',
    dropdownModal: '[data-search-filter-form-dropdown-modal]',
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
  },

  // Parameter mappings for URL operations and API integration
  PARAM_MAPPINGS: {
    // URL parameter to dropdown type mapping
    URL_TO_DROPDOWN: new Map([
      ['state', 'location'],
      ['insurance', 'insurance'],
      ['typeOfCare', 'type_of_care']
    ]),
    
    // Dropdown type to API key mapping
    DROPDOWN_TO_API: new Map([
      ['location', 'states'],
      ['insurance', 'payors'],
      ['needs', 'specialties'],
      ['type_of_care', 'type-of-care']
    ]),
    
    // API key to URL parameter mapping
    API_TO_URL: new Map([
      ['states', 'state'],
      ['payors', 'insurance'],
      ['specialties', 'specialty'],
      ['type-of-care', 'typeOfCare']
    ])
  }
};
