(function($) {
  'use strict';

  class SearchFilterForm {
    constructor() {
      this.init();
    }

    init() {
      this.bindEvents();
      this.loadApiData();
    }

    bindEvents() {
      // Dropdown trigger clicks
      $(document).on('click', '.dropdown-trigger', this.handleDropdownClick.bind(this));
      
      // Modal close events
      $(document).on('click', '.done-button', this.handleDoneClick.bind(this));
      $(document).on('click', '.close-modal', this.handleCloseModal.bind(this));
      
      // Search input filtering
      $(document).on('input', '.search-input', this.handleSearchInput.bind(this));
      
      // Option selection
      $(document).on('click', '.option-item', this.handleOptionClick.bind(this));
      
      // Option removal (X button)
      $(document).on('click', '.remove-option', this.handleRemoveOption.bind(this));
      
      // Form submission
      $(document).on('submit', '.search-filter-form', this.handleFormSubmit.bind(this));
      
      // Close modal when clicking outside
      $(document).on('click', this.handleOutsideClick.bind(this));
      
      // Close modal on escape key
      $(document).on('keydown', this.handleKeydown.bind(this));
    }

    loadApiData() {
      // API data is already loaded via wp_localize_script
      if (typeof searchFilterFormData !== 'undefined') {
        this.apiData = searchFilterFormData.apiData;
      }
    }

    handleDropdownClick(e) {
      e.preventDefault();
      e.stopPropagation();
      
      const $trigger = $(e.currentTarget);
      const dropdownType = $trigger.data('dropdown');
      const $modal = $(`#modal-${dropdownType}`);
      
      // Close any other open modals
      this.closeAllModals();
      
      // Toggle current modal
      if ($modal.hasClass('active')) {
        this.closeModal($modal);
      } else {
        this.openModal($modal, dropdownType);
      }
    }

    openModal($modal, dropdownType) {
      // Get dropdown configuration
      const $dropdown = $modal.closest('.dropdown-wrapper');
      const isRequired = $dropdown.hasClass('required');
      const isSingleSelect = $dropdown.hasClass('single-select');
      
      // Populate options based on dropdown type
      this.populateOptions($modal, dropdownType);
      
      // Show modal
      $modal.addClass('active');
      $modal.find('.search-input').focus();
      
      // Update trigger appearance
      $modal.closest('.dropdown-wrapper').find('.dropdown-arrow').addClass('active');
    }

    closeModal($modal) {
      $modal.removeClass('active');
      $modal.closest('.dropdown-wrapper').find('.dropdown-arrow').removeClass('active');
    }

    closeAllModals() {
      $('.dropdown-modal').removeClass('active');
      $('.dropdown-arrow').removeClass('active');
    }

    populateOptions($modal, dropdownType) {
      const $optionsList = $modal.find('.options-list');
      const apiKey = $modal.find('.options-list').data('api-key');
      
      if (!this.apiData || !this.apiData[apiKey]) {
        $optionsList.html('<div class="no-options">No options available</div>');
        return;
      }

      const options = this.apiData[apiKey];
      let optionsHtml = '';
      
      options.forEach(option => {
        const optionId = option.id || option.value || option.name;
        const optionText = option.name || option.label || option.value;
        
        optionsHtml += `
          <div class="option-item" data-value="${optionId}" data-text="${optionText}">
            <span class="option-text">${optionText}</span>
            <span class="option-checkbox"></span>
          </div>
        `;
      });
      
      $optionsList.html(optionsHtml);
    }

    handleSearchInput(e) {
      const $input = $(e.currentTarget);
      const searchTerm = $input.val().toLowerCase();
      const $modal = $input.closest('.dropdown-modal');
      const $options = $modal.find('.option-item');
      
      $options.each(function() {
        const $option = $(this);
        const optionText = $option.data('text').toLowerCase();
        
        if (optionText.includes(searchTerm)) {
          $option.show();
        } else {
          $option.hide();
        }
      });
    }

    handleOptionClick(e) {
      const $option = $(e.currentTarget);
      const $modal = $option.closest('.dropdown-modal');
      const $dropdown = $modal.closest('.dropdown-wrapper');
      const isSingleSelect = $dropdown.hasClass('single-select');
      
      const value = $option.data('value');
      const text = $option.data('text');
      
      if (isSingleSelect) {
        // Single select - replace current selection
        this.selectSingleOption($dropdown, value, text);
        this.closeModal($modal);
      } else {
        // Multi select - toggle selection
        this.toggleMultiOption($dropdown, value, text);
      }
    }

    selectSingleOption($dropdown, value, text) {
      // Clear previous selection
      $dropdown.find('.selected-options').remove();
      
      // Add new selection
      const $selectedOptions = $(`
        <div class="selected-options">
          <span class="selected-option">
            <span class="option-text">${text}</span>
            <span class="remove-option" data-value="${value}">×</span>
          </span>
        </div>
      `);
      
      $dropdown.append($selectedOptions);
      
      // Update hidden input
      $dropdown.find('input[type="hidden"]').val(value);
      
      // Update trigger text
      $dropdown.find('.dropdown-text').text(text);
    }

    toggleMultiOption($dropdown, value, text) {
      let $selectedOptions = $dropdown.find('.selected-options');
      
      if ($selectedOptions.length === 0) {
        $selectedOptions = $('<div class="selected-options"></div>');
        $dropdown.append($selectedOptions);
      }
      
      // Check if option is already selected
      const $existingOption = $selectedOptions.find(`[data-value="${value}"]`);
      
      if ($existingOption.length > 0) {
        // Remove option
        $existingOption.remove();
      } else {
        // Add option
        const $newOption = $(`
          <span class="selected-option">
            <span class="option-text">${text}</span>
            <span class="remove-option" data-value="${value}">×</span>
          </span>
        `);
        
        $selectedOptions.append($newOption);
      }
      
      // Update hidden input with array of values
      const selectedValues = [];
      $selectedOptions.find('.selected-option').each(function() {
        selectedValues.push($(this).find('.remove-option').data('value'));
      });
      
      $dropdown.find('input[type="hidden"]').val(selectedValues);
      
      // Update trigger text
      if (selectedValues.length === 0) {
        $dropdown.find('.dropdown-text').text($dropdown.find('.dropdown-trigger').data('placeholder') || 'Select options');
      } else {
        $dropdown.find('.dropdown-text').text(`${selectedValues.length} selected`);
      }
    }

    handleRemoveOption(e) {
      e.preventDefault();
      e.stopPropagation();
      
      const $removeBtn = $(e.currentTarget);
      const value = $removeBtn.data('value');
      const $dropdown = $removeBtn.closest('.dropdown-wrapper');
      const isSingleSelect = $dropdown.hasClass('single-select');
      
      if (isSingleSelect) {
        // Reset single select dropdown
        this.resetDropdown($dropdown);
      } else {
        // Remove specific option from multi select
        $removeBtn.closest('.selected-option').remove();
        this.updateMultiSelectInput($dropdown);
      }
    }

    resetDropdown($dropdown) {
      $dropdown.find('.selected-options').remove();
      $dropdown.find('input[type="hidden"]').val('');
      
      const placeholder = $dropdown.find('.dropdown-trigger').data('placeholder') || 'Select option';
      $dropdown.find('.dropdown-text').text(placeholder);
    }

    updateMultiSelectInput($dropdown) {
      const $selectedOptions = $dropdown.find('.selected-options');
      const selectedValues = [];
      
      $selectedOptions.find('.selected-option').each(function() {
        selectedValues.push($(this).find('.remove-option').data('value'));
      });
      
      $dropdown.find('input[type="hidden"]').val(selectedValues);
      
      if (selectedValues.length === 0) {
        const placeholder = $dropdown.find('.dropdown-trigger').data('placeholder') || 'Select options';
        $dropdown.find('.dropdown-text').text(placeholder);
      } else {
        $dropdown.find('.dropdown-text').text(`${selectedValues.length} selected`);
      }
    }

    handleDoneClick(e) {
      e.preventDefault();
      const $modal = $(e.currentTarget).closest('.dropdown-modal');
      this.closeModal($modal);
    }

    handleCloseModal(e) {
      e.preventDefault();
      const $modal = $(e.currentTarget).closest('.dropdown-modal');
      this.closeModal($modal);
    }

    handleOutsideClick(e) {
      if (!$(e.target).closest('.dropdown-wrapper').length) {
        this.closeAllModals();
      }
    }

    handleKeydown(e) {
      if (e.key === 'Escape') {
        this.closeAllModals();
      }
    }

    handleFormSubmit(e) {
      const $form = $(e.currentTarget);
      const $requiredFields = $form.find('input[required]');
      let isValid = true;
      
      // Validate required fields
      $requiredFields.each(function() {
        const $field = $(this);
        const value = $field.val();
        
        if (!$field.val() || (Array.isArray(value) && value.length === 0)) {
          isValid = false;
          $field.addClass('error');
        } else {
          $field.removeClass('error');
        }
      });
      
      if (!isValid) {
        e.preventDefault();
        this.showValidationError($form);
      }
    }

    showValidationError($form) {
      // Remove existing error messages
      $form.find('.validation-error').remove();
      
      // Add error message
      const errorMessage = $form.data('error-message') || 'Please fill in all required fields.';
      const $errorDiv = $(`<div class="validation-error">${errorMessage}</div>`);
      
      $form.prepend($errorDiv);
      
      // Scroll to error
      $('html, body').animate({
        scrollTop: $errorDiv.offset().top - 100
      }, 500);
    }
  }

  // Initialize when DOM is ready
  $(document).ready(function() {
    new SearchFilterForm();
  });

})(jQuery);
