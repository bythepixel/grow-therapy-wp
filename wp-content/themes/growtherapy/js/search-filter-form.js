'use strict';

/**
 * Search Filter Form - Vanilla JavaScript implementation
 * Handles dropdown modals, search filtering, and form submission
 */
class SearchFilterForm {
  constructor() {
    this.elements = {
      deselectButton: '[data-search-filter-form-deselect-button]',
      checkboxSingleSelect: '[data-search-filter-form-single-select]',
      dropdown: '[data-search-filter-form-dropdown]',
      dropdownButton: '[data-search-filter-form-dropdown-button]',
      dropdownModal: '[data-search-filter-form-dropdown-modal]',
      searchInput: '[data-search-filter-form-search-input]',
    };

    this.cssClasses = {
      modalActive: 'search-filter-form__dropdown-modal--active',
      optionHidden: 'search-filter-form__dropdown-modal-option--hidden',
      optionSelected: 'search-filter-form__dropdown-modal-option--selected',
    };

    this.activeModals = new Set();
    this.searchDebounceTimers = new Map();
    
    this.init();
  }

  init() {
    this.bindEvents();
  }

  bindEvents() {
    document.addEventListener('click', this.handleGlobalClick.bind(this), { 
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
    
    // Handle search input filtering
    document.addEventListener('input', this.handleSearchInput.bind(this), { 
      passive: true,
      capture: false 
    });
  }

  handleGlobalClick(e) {
    const dropdownButton = e.target.closest(this.elements.dropdownButton);
    const doneButton = e.target.closest('.search-filter-form__dropdown-modal-done-button');
    const deselectButton = e.target.closest(this.elements.deselectButton);
    
    if (dropdownButton) {
      e.preventDefault();
      e.stopPropagation();
      this.handleModalOpen(dropdownButton);
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
    
    // Close modal when clicking outside
    this.handleClickOutside(e);
  }

  handleModalOpen(button) {
    const dropdown = button.closest(this.elements.dropdown);
    const modal = dropdown?.querySelector(this.elements.dropdownModal);
    
    if (!dropdown || !modal) return;
    
    this.closeAllModals();
    this.openModal(modal, dropdown);
  }

  openModal(modal, dropdown) {
    const updates = [
      () => modal.classList.remove('aria-hidden'),
      () => modal.classList.add(this.cssClasses.modalActive),
      () => modal.setAttribute('aria-hidden', 'false'),
      () => dropdown.querySelector(this.elements.dropdownButton)?.setAttribute('aria-expanded', 'true')
    ];
    
    updates.forEach(update => update());
    this.activeModals.add(modal);
    
    // Clear search input and show all options when modal opens
    this.clearSearchInput(modal);
  }

  closeModal(modal) {
    const dropdown = modal.closest(this.elements.dropdown);
    if (!dropdown) return;
    
    const updates = [
      () => modal.classList.remove(this.cssClasses.modalActive),
      () => modal.classList.add('aria-hidden'),
      () => modal.setAttribute('aria-hidden', 'true'),
      () => dropdown.querySelector(this.elements.dropdownButton)?.setAttribute('aria-expanded', 'false'),
      () => dropdown.querySelector(this.elements.dropdownButton)?.focus()
    ];
    
    updates.forEach(update => update());
    this.activeModals.delete(modal);
    
    this.cleanup();
  }

  closeAllModals() {
    for (const modal of this.activeModals) {
      this.closeModal(modal);
    }
  }

  /**
   * Clean up resources when closing modals
   */
  cleanup() {
    this.searchDebounceTimers.forEach(timer => clearTimeout(timer));
    this.searchDebounceTimers.clear();
  }

  /**
   * Handle checkbox changes for single-select behavior
   * @param {Event} e - Change event
   */
  handleCheckboxChange(e) {
    const { target: checkbox } = e;
    
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
      
      // Add selected class to current option
      const currentOption = checkbox.closest('.search-filter-form__dropdown-modal-option');
      if (currentOption) {
        currentOption.classList.add(this.cssClasses.optionSelected);
      }
      
      // Close modal for single-select after selection
      this.closeModal(modal);
      
      // Apply cross-filtering to other dropdowns
      this.applyCrossFiltering(checkbox);
    } else {
      const label = checkbox.closest('label');
      if (label && !e.target.closest('.search-filter-form__dropdown-modal-option-deselect')) {
        checkbox.checked = true;
        return;
      }
      
      this.deselectSingleSelectOption(checkbox);
    }
    
    this.updateDropdownLabel(checkbox);
  }

  /**
   * Deselect a single-select option (used by both X button and new selection)
   * @param {HTMLInputElement} checkbox - The checkbox to deselect
   */
  deselectSingleSelectOption(checkbox) {
    checkbox.checked = false;
    
    const option = checkbox.closest('.search-filter-form__dropdown-modal-option');
    if (option) {
      option.classList.remove(this.cssClasses.optionSelected);
    }
    
    this.resetCrossFiltering(checkbox);
    this.updateDropdownLabel(checkbox);
  }

  /**
   * Handle X icon clicks for deselection
   * @param {Event} e - Click event
   */
  handleDeselectClick(e) {
    const deselectButton = e.target.closest(this.elements.deselectButton);

    if (!deselectButton) return;
    
    e.preventDefault();
    e.stopPropagation();
    
    const option = deselectButton.closest('.search-filter-form__dropdown-modal-option');
    const checkbox = option?.querySelector('input[type="checkbox"]');

    if (checkbox) {
      this.deselectSingleSelectOption(checkbox);
    }
  }

  /**
   * Handle Done button clicks to close modals
   * @param {HTMLElement} doneButton - The Done button element
   */
  handleDoneClick(doneButton) {
    const modal = doneButton.closest(this.elements.dropdownModal);
    if (modal) {
      this.closeModal(modal);
    }
  }

  /**
   * Handle clicks outside of modals to close them
   * @param {Event} e - Click event
   */
  handleClickOutside(e) {
    // Check if click is outside any active modal
    for (const modal of this.activeModals) {
      if (!modal.contains(e.target)) {
        this.closeModal(modal);
      }
    }
  }

  /**
   * Handle search input filtering
   * @param {Event} e - Input event
   */
  handleSearchInput(e) {
    const searchInput = e.target;
    if (!searchInput.matches(this.elements.searchInput)) return;

    if (this.searchDebounceTimers.has(searchInput)) {
      clearTimeout(this.searchDebounceTimers.get(searchInput));
    }

    const timer = setTimeout(() => {
      this.performSearch(searchInput);
      this.searchDebounceTimers.delete(searchInput);
    }, 300);

    this.searchDebounceTimers.set(searchInput, timer);
  }

  /**
   * Perform the actual search filtering
   * @param {HTMLInputElement} searchInput - The search input element
   */
  performSearch(searchInput) {
    const modal = searchInput.closest(this.elements.dropdownModal);
    if (!modal) return;

    const searchTerm = searchInput.value.toLowerCase().trim();
    
    if (!searchTerm) {
      this.applyCrossFilteringToModal(modal);
      return;
    }

    const options = modal.querySelectorAll('.search-filter-form__dropdown-modal-option');
    
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
  }

  /**
   * Apply cross-filtering to a specific modal
   * @param {HTMLElement} modal - The modal element
   */
  applyCrossFilteringToModal(modal) {
    const options = modal.querySelectorAll('.search-filter-form__dropdown-modal-option');
    options.forEach(option => {
      const isCrossFiltered = !option.classList.contains(this.cssClasses.optionHidden);
      option.style.display = isCrossFiltered ? '' : 'none';
    });
  }

  /**
   * Clear search input and show all options (respecting cross-filtering)
   * @param {HTMLElement} modal - The modal element
   */
  clearSearchInput(modal) {
    const searchInput = modal.querySelector('.search-filter-form-input');
    if (searchInput) {
      searchInput.value = '';
    }
    
    // Apply cross-filtering to show appropriate options
    this.applyCrossFilteringToModal(modal);
  }

  /**
   * Apply cross-filtering to other dropdowns based on current selection
   * @param {HTMLInputElement} checkbox - The checked checkbox
   */
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

  /**
   * Filter options in a modal based on related data from another selection
   * @param {HTMLInputElement} selectedItem - The selected checkbox (state or insurance)
   * @param {string} dataAttribute - The data attribute to read (relatedInsurance or relatedStates)
   * @param {string} targetModalInputName - The input name to find the target modal
   */
  filterOptionsByRelatedData(selectedItem, dataAttribute, targetModalInputName) {
    const relatedData = selectedItem.dataset[dataAttribute];
    if (!relatedData) return;

    try {
      const relatedItems = JSON.parse(relatedData);
      const itemValues = new Set(relatedItems.map(item => item.value || item.id));

      const targetModal = this.findModalByInputName(targetModalInputName);
      if (!targetModal) return;

      const options = targetModal.querySelectorAll('.search-filter-form__dropdown-modal-option');
      
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

  /**
   * Find a modal by the name of its input options
   * @param {string} inputName - The name attribute of the input options
   * @return {HTMLElement|null} - The modal element or null if not found
   */
  findModalByInputName(inputName) {
    const inputs = document.querySelectorAll(`input[name="${inputName}"]`);
    if (inputs.length === 0) return null;
    
    const firstInput = inputs[0];
    return firstInput.closest('[data-search-filter-form-dropdown-modal]');
  }

  /**
   * Reset cross-filtering when a selection is cleared
   * @param {HTMLInputElement} checkbox - The unchecked checkbox
   */
  resetCrossFiltering(checkbox) {
    const { name } = checkbox;
    
    // Map input names to their corresponding target modals for cross-filtering reset
    const crossFilterMap = {
      'states-options': 'payors-options',
      'payors-options': 'states-options'
    };
    
    const targetModalName = crossFilterMap[name];
    if (!targetModalName) return;
    
    const targetModal = this.findModalByInputName(targetModalName);
    if (targetModal) {
      const options = targetModal.querySelectorAll('.search-filter-form__dropdown-modal-option');
      options.forEach(option => {
        option.classList.remove(this.cssClasses.optionHidden);
      });
    }
  }

  /**
   * Handle form submission and collect selected values
   * @param {Event} e - Submit event
   */
  handleFormSubmit(e) {
    e.preventDefault();
    
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
    
    // Build query parameters
    for (const [name, values] of Object.entries(selections)) {
      if (name === 'specialties-options') {
        // Handle specialties as array parameters
        values.forEach((value, index) => {
          searchParams.append(`specialty[${index}]`, value);
        });
      } else if (name === 'states-options') {
        // Handle states as single parameter
        searchParams.append('state', values[0]);
      } else if (name === 'payors-options') {
        // Handle insurance as single parameter
        searchParams.append('insurance', values[0]);
      }
    }
    
    // Get state slug for URL path
    const stateValue = selections['states-options']?.[0];
    const stateSlug = stateValue ? this.convertToSlug(stateValue) : '';
    
    // Build URL with path parameter and query string
    const baseUrl = window.location.origin + '/find/therapists';
    const urlPath = stateSlug ? `/${stateSlug}` : '';
    const searchUrl = baseUrl + urlPath + '?' + searchParams.toString();
    
    console.log(searchUrl);
    
    // window.location.href = searchUrl;
  }

  /**
   * Convert text to URL-friendly slug
   * @param {string} text - Text to convert
   * @return {string} - URL-friendly slug
   */
  convertToSlug(text) {
    return text
      .toLowerCase()
      .replace(/[^a-z0-9\s-]/g, '')
      .replace(/\s+/g, '-')
      .replace(/-+/g, '-')
      .trim('-');
  }

  /**
   * Update dropdown button label to show selected values
   * @param {HTMLInputElement} checkbox - The changed checkbox
   */
  updateDropdownLabel(checkbox) {
    const modal = checkbox.closest(this.elements.dropdownModal);
    const dropdown = modal?.closest(this.elements.dropdown);
    if (!dropdown) return;
    
    const button = dropdown.querySelector(this.elements.dropdownButton);
    const label = button?.querySelector('.search-filter-form__dropdown-button-label');
    if (!label) return;
    
    const name = checkbox.name;
    const checkedOptions = dropdown.querySelectorAll(`input[name="${name}"]:checked`);
    
    if (checkedOptions.length === 0) {
      label.textContent = button.dataset.placeholder || 'Select option';
    } else if (checkedOptions.length === 1) {
      const option = checkedOptions[0].closest('.search-filter-form__dropdown-modal-option');
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
  }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
  new SearchFilterForm();
});
