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
    // Modal trigger clicks
    $(document).on('click', '.search-filter-select-trigger', this.handleModalOpen.bind(this));
    
    // Modal close events
    $(document).on('click', '.done-button', this.handleDoneClick.bind(this));
    $(document).on('click', '.close-modal', this.handleCloseModal.bind(this));
    $(document).on('click', '.modal-backdrop', this.handleBackdropClick.bind(this));
    
    // Search input filtering
    $(document).on('input', '.search-input', this.handleSearchInput.bind(this));
    
    // Clickable option selection (single-select)
    $(document).on('click', '.search-filter-option', this.handleOptionClick.bind(this));
    
    // Checkbox selection (multi-select)
    $(document).on('change', 'input[type="checkbox"]', this.handleCheckboxChange.bind(this));
    
    // Form submission
    $(document).on('submit', '.search-filter-form', this.handleFormSubmit.bind(this));
    
    // Close modal on escape key
    $(document).on('keydown', this.handleKeydown.bind(this));
  }

    loadApiData() {
      // API data is already loaded via wp_localize_script
      if (typeof searchFilterFormData !== 'undefined') {
        this.apiData = searchFilterFormData.apiData;
      }
    }

    handleModalOpen(e) {
      e.preventDefault();
      e.stopPropagation();
      
      const $trigger = $(e.currentTarget);
      const $dropdown = $trigger.closest('.search-filter-dropdown-wrapper');
      const $modal = $dropdown.find('.search-filter-modal');
      
      // Close any other open modals
      this.closeAllModals();
      
      // Open current modal
      this.openModal($modal, $dropdown);
    }

    openModal($modal, $dropdown) {
      // Get dropdown configuration
      const isRequired = $dropdown.hasClass('search-filter-dropdown-wrapper--required');
      const isSingleSelect = $dropdown.hasClass('search-filter-dropdown-wrapper--single-select');
      
      // Populate options based on dropdown type
      this.populateOptions($modal, $dropdown);
      
      // Show modal
      $modal.removeClass('aria-hidden').addClass('active');
      $modal.attr('aria-hidden', 'false');
      
      // Update trigger appearance
      $dropdown.find('.search-filter-select-trigger').attr('aria-expanded', 'true');
      
      // Focus management
      $modal.find('.search-input').focus();
      
      // Trap focus in modal
      this.trapFocus($modal);
    }

    closeModal($modal) {
      const $dropdown = $modal.closest('.search-filter-dropdown-wrapper');
      
      $modal.removeClass('active').addClass('aria-hidden');
      $modal.attr('aria-hidden', 'true');
      
      // Update trigger appearance
      $dropdown.find('.search-filter-select-trigger').attr('aria-expanded', 'false');
      
      // Return focus to trigger
      $dropdown.find('.search-filter-select-trigger').focus();
    }

    closeAllModals() {
      $('.search-filter-modal').each((index, modal) => {
        this.closeModal($(modal));
      });
    }

    populateOptions($modal, $dropdown) {
      const $optionsContainer = $modal.find('.search-filter-options-modal__options-container');
      const apiKey = $optionsContainer.data('api-key');
      const isSingleSelect = $dropdown.hasClass('search-filter-dropdown-wrapper--single-select');
      
      if (!this.apiData || !this.apiData[apiKey]) {
        $optionsContainer.html('<div class="no-options">No options available</div>');
        return;
      }

      const options = this.apiData[apiKey];
      
      if (isSingleSelect) {
        // Clickable options for single select
        const $optionsList = $modal.find('.search-filter-options-modal__options-list');
        let optionsHtml = '';
        
        options.forEach((option, index) => {
          const optionId = option.id || option.value || option.name;
          const optionText = option.name || option.label || option.value;
          
          optionsHtml += `
            <div class="search-filter-option" data-value="${optionId}" data-text="${optionText}">
              <span class="search-filter-option__text">${optionText}</span>
              <button type="button" class="search-filter-option__remove" aria-label="Remove ${optionText}">×</button>
            </div>
          `;
        });
        
        $optionsList.html(optionsHtml);
      } else {
        // Checkboxes for multi select
        const $checkboxOptions = $modal.find('.search-filter-options-modal__checkbox-options');
        let optionsHtml = '';
        
        options.forEach((option, index) => {
          const optionId = option.id || option.value || option.name;
          const optionText = option.name || option.label || option.value;
          const inputId = `checkbox-${apiKey}-${optionId}`;
          
          optionsHtml += `
            <div class="search-filter-option">
              <input type="checkbox" id="${inputId}" name="${apiKey}[]" value="${optionId}" class="checkbox-input">
              <label for="${inputId}" class="checkbox-label">${optionText}</label>
            </div>
          `;
        });
        
        $checkboxOptions.html(optionsHtml);
      }
    }

    handleSearchInput(e) {
      const $input = $(e.currentTarget);
      const searchTerm = $input.val().toLowerCase();
      const $modal = $input.closest('.search-filter-options-modal');
      const isSingleSelect = $modal.closest('.search-filter-dropdown-wrapper').hasClass('search-filter-dropdown-wrapper--single-select');
      
      if (isSingleSelect) {
        const $options = $modal.find('.search-filter-option');
        $options.each(function() {
          const $option = $(this);
          const optionText = $option.find('.search-filter-option__text').text().toLowerCase();
          
          if (optionText.includes(searchTerm)) {
            $option.show();
          } else {
            $option.hide();
          }
        });
      } else {
        const $options = $modal.find('.search-filter-option');
        $options.each(function() {
          const $option = $(this);
          const optionText = $option.find('.checkbox-label').text().toLowerCase();
          
          if (optionText.includes(searchTerm)) {
            $option.show();
          } else {
            $option.hide();
          }
        });
      }
    }

    handleOptionClick(e) {
      const $option = $(e.currentTarget);
      const $modal = $option.closest('.search-filter-options-modal');
      const $dropdown = $modal.closest('.search-filter-dropdown-wrapper');
      const isSingleSelect = $dropdown.hasClass('search-filter-dropdown-wrapper--single-select');
      
      if (isSingleSelect) {
        // Single select - handle clickable option
        const value = $option.data('value');
        const text = $option.data('text');
        
        // Update trigger text
        $dropdown.find('.search-filter-select-trigger__label').text(text);
        
        // Close modal automatically for single select
        this.closeModal($modal);
      }
      // Multi-select is handled by checkbox change events
    }

    handleCheckboxChange(e) {
      const $checkbox = $(e.currentTarget);
      const $modal = $checkbox.closest('.search-filter-modal');
      const $dropdown = $modal.closest('.search-filter-dropdown-wrapper');
      const value = $checkbox.val();
      const text = $checkbox.siblings('.checkbox-label').text();
      const isChecked = $checkbox.is(':checked');
      
      // Count selected checkboxes
      const $selectedCheckboxes = $dropdown.find('input[type="checkbox"]:checked');
      const selectedCount = $selectedCheckboxes.length;
      
      // Update trigger text
      if (selectedCount === 0) {
        const placeholder = $dropdown.find('.search-filter-select-trigger__label').text().replace(/\s*\d+\s*selected/, '');
        $dropdown.find('.search-filter-select-trigger__label').text(placeholder);
      } else {
        const baseText = $dropdown.find('.search-filter-select-trigger__label').text().replace(/\s*\d+\s*selected/, '');
        $dropdown.find('.search-filter-select-trigger__label').text(`${baseText} ${selectedCount} selected`);
      }
    }



    handleDoneClick(e) {
      e.preventDefault();
      const $modal = $(e.currentTarget).closest('.search-filter-options-modal');
      this.closeModal($modal);
    }

    handleCloseModal(e) {
      e.preventDefault();
      const $modal = $(e.currentTarget).closest('.search-filter-options-modal');
      this.closeModal($modal);
    }

    handleBackdropClick(e) {
      const $modal = $(e.currentTarget).siblings('.search-filter-modal');
      this.closeModal($modal);
    }

    handleKeydown(e) {
      if (e.key === 'Escape') {
        this.closeAllModals();
      }
    }

    trapFocus($modal) {
      const focusableElements = $modal.find('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
      const firstElement = focusableElements.first();
      const lastElement = focusableElements.last();
      
      // Handle tab key navigation
      $modal.on('keydown', (e) => {
        if (e.key === 'Tab') {
          if (e.shiftKey) {
            if (document.activeElement === firstElement[0]) {
              e.preventDefault();
              lastElement.focus();
            }
          } else {
            if (document.activeElement === lastElement[0]) {
              e.preventDefault();
              firstElement.focus();
            }
          }
        }
      });
    }

    handleFormSubmit(e) {
      e.preventDefault();
      
      const $form = $(e.currentTarget);
      const $dropdowns = $form.find('.search-filter-dropdown-wrapper');
      const searchParams = new URLSearchParams();
      let isValid = true;
      
      // Collect values from each dropdown
      $dropdowns.each((index, dropdown) => {
        const $dropdown = $(dropdown);
        const type = this.getDropdownType($dropdown);
        const isRequired = $dropdown.hasClass('search-filter-dropdown-wrapper--required');
        const isSingleSelect = $dropdown.hasClass('search-filter-dropdown-wrapper--single-select');
        
        let value = null;
        
        if (isSingleSelect) {
          // Get selected option value from trigger text
          const triggerText = $dropdown.find('.search-filter-select-trigger__label').text();
          const placeholder = $dropdown.find('.search-filter-select-trigger').data('placeholder') || $dropdown.find('.search-filter-select-trigger__label').text().replace(/\s*\*$/, '');
          
          // If trigger text is different from placeholder, it means something is selected
          if (triggerText !== placeholder) {
            // Find the option that matches the trigger text
            const $options = $dropdown.find('.search-filter-option');
            $options.each(function() {
              const $option = $(this);
              if ($option.data('text') === triggerText) {
                value = $option.data('value');
                return false; // break the loop
              }
            });
          }
        } else {
          // Get selected checkbox values
          const $selectedCheckboxes = $dropdown.find('input[type="checkbox"]:checked');
          value = $selectedCheckboxes.map(function() {
            return $(this).val();
          }).get();
        }
        
        // Validate required fields
        if (isRequired && (!value || (Array.isArray(value) && value.length === 0))) {
          isValid = false;
          $dropdown.addClass('error');
        } else {
          $dropdown.removeClass('error');
          
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
        this.showValidationError($form);
        return;
      }
      
      // Redirect to search results page with params
      const baseUrl = window.location.origin + '/find/therapists'; // You can make this configurable
      const searchUrl = baseUrl + '?' + searchParams.toString();
      window.location.href = searchUrl;
    }

    getDropdownType($dropdown) {
      // Extract type from dropdown classes or data attributes
      const classes = $dropdown.attr('class');
      if (classes.includes('location')) return 'location';
      if (classes.includes('insurance')) return 'insurance';
      if (classes.includes('needs')) return 'needs';
      return 'unknown';
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
