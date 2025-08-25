'use strict';

/**
 * Search Filter Form - Vanilla JavaScript implementation
 * Handles dropdown modals, search filtering, and form submission
 */
class SearchFilterForm {
  constructor() {
    this.selectors = {
      dropdownButton: '[data-search-filter-form-dropdown-button]',
      dropdownModal: '[data-search-filter-form-dropdown-modal]',
      searchInput: '.search-input',
      searchFilterOption: '.search-filter-option',
      checkboxInput: 'input[type="checkbox"]',
      searchFilterForm: '.search-filter-form',
      doneButton: '.done-button',
      closeModal: '.close-modal',
      modalBackdrop: '.modal-backdrop',
      searchFilterModal: '.search-filter-modal',
      dropdownButtonLabel: '.search-filter-form__dropdown-button__label',
      optionsContainer: '.search-filter-form__dropdown-modal__options-container',
      optionsList: '.search-filter-form__dropdown-modal__options-list',
      checkboxOptions: '.search-filter-form__dropdown-modal__checkbox-options',
      validationError: '.validation-error'
    };

    this.apiData = null;
    this.activeModals = new Set();
    this.focusTrapHandlers = new Map();
    
    this.init();
  }

  init() {
    this.loadApiData();
    this.bindEvents();
  }

  loadApiData() {
    if (typeof searchFilterFormData !== 'undefined') {
      this.apiData = searchFilterFormData.apiData;
    }
  }

  bindEvents() {
    // Use event delegation for better performance
    document.addEventListener('click', this.handleGlobalClick.bind(this));
    document.addEventListener('input', this.handleGlobalInput.bind(this));
    document.addEventListener('change', this.handleGlobalChange.bind(this));
    document.addEventListener('submit', this.handleGlobalSubmit.bind(this));
    document.addEventListener('keydown', this.handleGlobalKeydown.bind(this));
  }

  handleGlobalClick(e) {
    const target = e.target;
    
    // Modal trigger clicks
    if (target.matches(this.selectors.dropdownButton)) {
      this.handleModalOpen(e);
      return;
    }
    
    // Modal close events
    if (target.matches(this.selectors.doneButton)) {
      this.handleDoneClick(e);
      return;
    }
    
    if (target.matches(this.selectors.closeModal)) {
      this.handleCloseModal(e);
      return;
    }
    
    if (target.matches(this.selectors.modalBackdrop)) {
      this.handleBackdropClick(e);
      return;
    }
    
    // Clickable option selection (single-select)
    if (target.matches(this.selectors.searchFilterOption)) {
      this.handleOptionClick(e);
      return;
    }
  }

  handleGlobalInput(e) {
    if (e.target.matches(this.selectors.searchInput)) {
      this.handleSearchInput(e);
    }
  }

  handleGlobalChange(e) {
    if (e.target.matches(this.selectors.checkboxInput)) {
      this.handleCheckboxChange(e);
    }
  }

  handleGlobalSubmit(e) {
    if (e.target.matches(this.selectors.searchFilterForm)) {
      this.handleFormSubmit(e);
    }
  }

  handleGlobalKeydown(e) {
    if (e.key === 'Escape') {
      this.closeAllModals();
    }
  }

  handleModalOpen(e) {
    e.preventDefault();
    e.stopPropagation();
    
    const trigger = e.currentTarget;
    const dropdown = trigger.closest('.search-filter-form__dropdown');
    const modal = dropdown.querySelector('.search-filter-modal');
    
    if (!dropdown || !modal) return;
    
    // Close any other open modals
    this.closeAllModals();
    
    // Open current modal
    this.openModal(modal, dropdown);
  }

  openModal(modal, dropdown) {
    // Get dropdown configuration
    const isRequired = dropdown.classList.contains('search-filter-form__dropdown--required');
    const isSingleSelect = dropdown.classList.contains('search-filter-form__dropdown--single-select');
    
    // Populate options based on dropdown type
    this.populateOptions(modal, dropdown);
    
    // Show modal
    modal.classList.remove('aria-hidden');
    modal.classList.add('active');
    modal.setAttribute('aria-hidden', 'false');
    
    // Update trigger appearance
    const button = dropdown.querySelector(this.selectors.dropdownButton);
    if (button) button.setAttribute('aria-expanded', 'true');
    
    // Focus management
    const searchInput = modal.querySelector(this.selectors.searchInput);
    if (searchInput) searchInput.focus();
    
    // Trap focus in modal
    this.trapFocus(modal);
    
    // Track active modal
    this.activeModals.add(modal);
  }

  closeModal(modal) {
    const dropdown = modal.closest('.search-filter-form__dropdown');
    if (!dropdown) return;
    
    modal.classList.remove('active');
    modal.classList.add('aria-hidden');
    modal.setAttribute('aria-hidden', 'true');
    
    // Update trigger appearance
    const button = dropdown.querySelector(this.selectors.dropdownButton);
    if (button) button.setAttribute('aria-expanded', 'false');
    
    // Return focus to trigger
    if (button) button.focus();
    
    // Remove focus trap
    this.removeFocusTrap(modal);
    
    // Remove from active modals
    this.activeModals.delete(modal);
  }

  closeAllModals() {
    this.activeModals.forEach(modal => {
      this.closeModal(modal);
    });
  }

  populateOptions(modal, dropdown) {
    const optionsContainer = modal.querySelector(this.selectors.optionsContainer);
    if (!optionsContainer) return;
    
    const apiKey = optionsContainer.dataset.apiKey;
    const isSingleSelect = dropdown.classList.contains('search-filter-form__dropdown--single-select');
    
    if (!this.apiData || !this.apiData[apiKey]) {
      optionsContainer.innerHTML = '<div class="no-options">No options available</div>';
      return;
    }

    const options = this.apiData[apiKey];
    
    if (isSingleSelect) {
      // Clickable options for single select
      const optionsList = modal.querySelector(this.selectors.optionsList);
      if (!optionsList) return;
      
      const optionsHtml = options.map(option => {
        const optionId = option.id || option.value || option.name;
        const optionText = option.name || option.label || option.value;
        
        return `
          <div class="search-filter-option" data-value="${this.escapeHtml(optionId)}" data-text="${this.escapeHtml(optionText)}">
            <span class="search-filter-option__text">${this.escapeHtml(optionText)}</span>
            <button type="button" class="search-filter-option__remove" aria-label="Remove ${this.escapeHtml(optionText)}">×</button>
          </div>
        `;
      }).join('');
      
      optionsList.innerHTML = optionsHtml;
    } else {
      // Checkboxes for multi select
      const checkboxOptions = modal.querySelector(this.selectors.checkboxOptions);
      if (!checkboxOptions) return;
      
      const optionsHtml = options.map(option => {
        const optionId = option.id || option.value || option.name;
        const optionText = option.name || option.label || option.value;
        const inputId = `checkbox-${apiKey}-${optionId}`;
        
        return `
          <div class="search-filter-option">
            <input type="checkbox" id="${this.escapeHtml(inputId)}" name="${this.escapeHtml(apiKey)}[]" value="${this.escapeHtml(optionId)}" class="checkbox-input">
            <label for="${this.escapeHtml(inputId)}" class="checkbox-label">${this.escapeHtml(optionText)}</label>
          </div>
        `;
      }).join('');
      
      checkboxOptions.innerHTML = optionsHtml;
    }
  }

  handleSearchInput(e) {
    const input = e.currentTarget;
    const searchTerm = input.value.toLowerCase();
    const modal = input.closest(this.selectors.dropdownModal);
    if (!modal) return;
    
    const dropdown = modal.closest('.search-filter-form__dropdown');
    const isSingleSelect = dropdown.classList.contains('search-filter-form__dropdown--single-select');
    
    const options = modal.querySelectorAll(this.selectors.searchFilterOption);
    
    options.forEach(option => {
      let optionText = '';
      
      if (isSingleSelect) {
        const textElement = option.querySelector('.search-filter-option__text');
        optionText = textElement ? textElement.textContent.toLowerCase() : '';
      } else {
        const labelElement = option.querySelector('.checkbox-label');
        optionText = labelElement ? labelElement.textContent.toLowerCase() : '';
      }
      
      if (optionText.includes(searchTerm)) {
        option.style.display = '';
      } else {
        option.style.display = 'none';
      }
    });
  }

  handleOptionClick(e) {
    const option = e.currentTarget;
    const modal = option.closest(this.selectors.dropdownModal);
    if (!modal) return;
    
    const dropdown = modal.closest('.search-filter-form__dropdown');
    const isSingleSelect = dropdown.classList.contains('search-filter-form__dropdown--single-select');
    
    if (isSingleSelect) {
      // Single select - handle clickable option
      const value = option.dataset.value;
      const text = option.dataset.text;
      
      // Update trigger text
      const label = dropdown.querySelector(this.selectors.dropdownButtonLabel);
      if (label) label.textContent = text;
      
      // Close modal automatically for single select
      this.closeModal(modal);
    }
    // Multi-select is handled by checkbox change events
  }

  handleCheckboxChange(e) {
    const checkbox = e.currentTarget;
    const modal = checkbox.closest('.search-filter-modal');
    if (!modal) return;
    
    const dropdown = modal.closest('.search-filter-form__dropdown');
    const label = checkbox.nextElementSibling;
    if (!label || !label.matches('.checkbox-label')) return;
    
    // Count selected checkboxes
    const selectedCheckboxes = dropdown.querySelectorAll('input[type="checkbox"]:checked');
    const selectedCount = selectedCheckboxes.length;
    
    // Update trigger text
    const buttonLabel = dropdown.querySelector(this.selectors.dropdownButtonLabel);
    if (buttonLabel) {
      const baseText = buttonLabel.textContent.replace(/\s*\d+\s*selected/, '').trim();
      if (selectedCount === 0) {
        buttonLabel.textContent = baseText;
      } else {
        buttonLabel.textContent = `${baseText} ${selectedCount} selected`;
      }
    }
  }

  handleDoneClick(e) {
    e.preventDefault();
    const modal = e.target.closest(this.selectors.dropdownModal);
    if (modal) this.closeModal(modal);
  }

  handleCloseModal(e) {
    e.preventDefault();
    const modal = e.target.closest(this.selectors.dropdownModal);
    if (modal) this.closeModal(modal);
  }

  handleBackdropClick(e) {
    const modal = e.target.nextElementSibling;
    if (modal && modal.matches('.search-filter-modal')) {
      this.closeModal(modal);
    }
  }

  trapFocus(modal) {
    const focusableElements = modal.querySelectorAll(
      'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
    );
    
    if (focusableElements.length === 0) return;
    
    const firstElement = focusableElements[0];
    const lastElement = focusableElements[focusableElements.length - 1];
    
    const handleTabKey = (e) => {
      if (e.key === 'Tab') {
        if (e.shiftKey) {
          if (document.activeElement === firstElement) {
            e.preventDefault();
            lastElement.focus();
          }
        } else {
          if (document.activeElement === lastElement) {
            e.preventDefault();
            firstElement.focus();
          }
        }
      }
    };
    
    modal.addEventListener('keydown', handleTabKey);
    
    // Store handler for cleanup
    this.focusTrapHandlers.set(modal, handleTabKey);
  }

  removeFocusTrap(modal) {
    const handler = this.focusTrapHandlers.get(modal);
    if (handler) {
      modal.removeEventListener('keydown', handler);
      this.focusTrapHandlers.delete(modal);
    }
  }

  handleFormSubmit(e) {
    e.preventDefault();
    
    const form = e.currentTarget;
    const dropdowns = form.querySelectorAll('.search-filter-form__dropdown');
    const searchParams = new URLSearchParams();
    let isValid = true;
    
    // Collect values from each dropdown
    dropdowns.forEach(dropdown => {
      const type = this.getDropdownType(dropdown);
      const isRequired = dropdown.classList.contains('search-filter-form__dropdown--required');
      const isSingleSelect = dropdown.classList.contains('search-filter-form__dropdown--single-select');
      
      let value = null;
      
      if (isSingleSelect) {
        // Get selected option value from trigger text
        const buttonLabel = dropdown.querySelector(this.selectors.dropdownButtonLabel);
        const triggerText = buttonLabel ? buttonLabel.textContent : '';
        const button = dropdown.querySelector(this.selectors.dropdownButton);
        const placeholder = button ? (button.dataset.placeholder || buttonLabel.textContent.replace(/\s*\*$/, '')) : '';
        
        // If trigger text is different from placeholder, it means something is selected
        if (triggerText !== placeholder) {
          // Find the option that matches the trigger text
          const options = dropdown.querySelectorAll('.search-filter-option');
          for (const option of options) {
            if (option.dataset.text === triggerText) {
              value = option.dataset.value;
              break;
            }
          }
        }
      } else {
        // Get selected checkbox values
        const selectedCheckboxes = dropdown.querySelectorAll('input[type="checkbox"]:checked');
        value = Array.from(selectedCheckboxes).map(checkbox => checkbox.value);
      }
      
      // Validate required fields
      if (isRequired && (!value || (Array.isArray(value) && value.length === 0))) {
        isValid = false;
        dropdown.classList.add('error');
      } else {
        dropdown.classList.remove('error');
        
        // Add to search params
        if (value) {
          if (Array.isArray(value)) {
            value.forEach(v => searchParams.append(type, v));
          } else {
            searchParams.append(type, value);
          }
        }
      }
    });
    
    if (!isValid) {
      this.showValidationError(form);
      return;
    }
    
    // Redirect to search results page with params
    const baseUrl = window.location.origin + '/find/therapists';
    const searchUrl = baseUrl + '?' + searchParams.toString();
    window.location.href = searchUrl;
  }

  getDropdownType(dropdown) {
    // Extract type from dropdown classes or data attributes
    const classes = dropdown.className;
    if (classes.includes('location')) return 'location';
    if (classes.includes('insurance')) return 'insurance';
    if (classes.includes('needs')) return 'needs';
    return 'unknown';
  }

  showValidationError(form) {
    // Remove existing error messages
    const existingError = form.querySelector(this.selectors.validationError);
    if (existingError) existingError.remove();
    
    // Add error message
    const errorMessage = form.dataset.errorMessage || 'Please fill in all required fields.';
    const errorDiv = document.createElement('div');
    errorDiv.className = 'validation-error';
    errorDiv.textContent = errorMessage;
    
    form.insertBefore(errorDiv, form.firstChild);
    
    // Scroll to error with smooth behavior
    errorDiv.scrollIntoView({ 
      behavior: 'smooth', 
      block: 'center' 
    });
  }

  escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
  new SearchFilterForm();
});
