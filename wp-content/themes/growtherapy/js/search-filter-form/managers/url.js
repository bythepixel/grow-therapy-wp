import { CONFIG } from '../core/config.js';

export class URLManager {
  constructor(callbacks) {
    this.callbacks = callbacks;
    
    this.specialtyPatterns = [
      /specialty\[(\d+)\]/g,
      /specialty=([^&]+)/g,
      /Specialty=([^&]+)/g,
      /SPECIALTY=([^&]+)/g
    ];
  }

  populateFromUrlParams() {
    const urlParams = new URLSearchParams(window.location.search);
    
    if (!this.hasRelevantParams(urlParams)) return;
    
    const populationPromises = [];
    
    for (const [param, dropdownType] of CONFIG.PARAM_MAPPINGS.URL_TO_DROPDOWN) {
      const value = urlParams.get(param);
      if (value) {
        populationPromises.push(
          this.populateDropdownFromParam(dropdownType, value)
        );
      }
    }
    
    const specialties = this.parseSpecialtyParams(urlParams);
    if (specialties.length > 0) {
      populationPromises.push(
        this.populateDropdownFromParam('needs', specialties)
      );
    }
    
    if (populationPromises.length > 0) {
      Promise.allSettled(populationPromises)
        .then(this.handlePopulationResults.bind(this))
        .catch(error => console.warn('URL population error:', error));
    }
  }

  hasRelevantParams(urlParams) {
    return CONFIG.CONSTANTS.URL_PARAMS.some(param => urlParams.has(param)) ||
           this.hasSpecialtyParams(urlParams);
  }

  hasSpecialtyParams(urlParams) {
    return urlParams.has('specialty') || 
           urlParams.has('Specialty') || 
           urlParams.has('SPECIALTY') ||
           Array.from(urlParams.keys()).some(key => 
             key.startsWith('specialty[') || 
             key.toLowerCase().includes('specialty') || 
             key.toLowerCase().includes('need')
           );
  }

  parseSpecialtyParams(urlParams) {
    let specialties = urlParams.getAll('specialty');
    if (specialties.length > 0) return specialties;
    
    const specialtyValues = new Set();
    
    for (const [key, value] of urlParams.entries()) {
      if (this.isSpecialtyKey(key)) {
        specialtyValues.add(decodeURIComponent(value));
      }
    }
    
    if (specialtyValues.size === 0) {
      const searchString = window.location.search;
      for (const pattern of this.specialtyPatterns) {
        const matches = searchString.matchAll(pattern);
        for (const match of matches) {
          specialtyValues.add(decodeURIComponent(match[1]));
        }
      }
    }
    
    return Array.from(specialtyValues);
  }

  isSpecialtyKey(key) {
    return key.startsWith('specialty[') || 
           key.endsWith(']') ||
           key.toLowerCase().includes('specialty') || 
           key.toLowerCase().includes('need');
  }

  populateDropdownFromParam(dropdownType, values) {
    const dropdowns = document.querySelectorAll(CONFIG.ELEMENTS.dropdown);
    
    if (dropdowns.length === 0) return 0;
    
    const expectedApiKey = CONFIG.PARAM_MAPPINGS.DROPDOWN_TO_API.get(dropdownType);
    if (!expectedApiKey) return 0;
    
    const valueSet = new Set(Array.isArray(values) ? values : [values]);
    const updates = [];
    let populatedCount = 0;
    
    for (const dropdown of dropdowns) {
      const modal = dropdown.querySelector(CONFIG.ELEMENTS.dropdownModal);
      if (!modal) continue;
      
      const apiKey = this.callbacks.getDropdownApiKey(dropdown);
      if (apiKey !== expectedApiKey) continue;
      
      const checkboxes = dropdown.querySelectorAll('input[type="checkbox"]');
      for (const checkbox of checkboxes) {
        if (valueSet.has(checkbox.value)) {
          updates.push(() => this.processCheckboxUpdate(checkbox));
          populatedCount++;
        }
      }
    }
    
    if (updates.length > 0) {
      requestAnimationFrame(() => {
        updates.forEach(update => update());
      });
    }
    
    return populatedCount;
  }

  processCheckboxUpdate(checkbox) {
    checkbox.checked = true;
    
    // Only add visual styling for single-select checkboxes
    if (checkbox.matches(CONFIG.ELEMENTS.checkboxSingleSelect)) {
      const option = checkbox.closest(CONFIG.ELEMENTS.option);
      if (option) {
        option.classList.add(CONFIG.CSS_CLASSES.optionSelected);
      }
    }
    
    this.callbacks.updateDropdownLabel(checkbox);
    checkbox.dispatchEvent(new Event('change', { bubbles: true }));
  }

  handlePopulationResults(results) {
    const successCount = results.filter(result => result.status === 'fulfilled').length;
    if (successCount > 0) {
      console.log(`SearchFilterForm: Successfully populated ${successCount} dropdowns from URL parameters`);
    }
  }
}
