import { CONFIG } from '../core/config.js';

export class URLManager {
  constructor(callbacks) {
    this.callbacks = callbacks;
    
    this.parameterConfig = {
      state: {
        paramName: 'state',
        dropdownType: 'states-options',
        singleSelect: true
      },
      insurance: {
        paramName: 'insurance', 
        dropdownType: 'payors-options',
        singleSelect: true
      },
      typeOfCare: {
        paramName: 'typeOfCare',
        dropdownType: 'type-of-care-options',
        singleSelect: true
      },
      specialty: {
        paramName: 'specialty',
        dropdownType: 'specialties-options',
        singleSelect: false,
        patterns: [
          /specialty\[(\d+)\]/g,
          /specialty=([^&]+)/g,
          /Specialty=([^&]+)/g,
          /SPECIALTY=([^&]+)/g
        ]
      }
    };
  }

  slugify(text) {
    if (!text) return '';
    return text
      .toLowerCase()
      .trim()
      .replace(/[^\w\s-]/g, '') // Remove special chars except word chars, spaces, hyphens
      .replace(/\s+/g, '-')     // Replace spaces with hyphens
      .replace(/-+/g, '-')      // Replace multiple hyphens with single
      .replace(/^-+|-+$/g, ''); // Remove leading/trailing hyphens
  }

  populateFromUrlParams() {
    const urlParams = new URLSearchParams(window.location.search);
    
    if (!this.hasRelevantParams(urlParams)) {
      return;
    }
    
    const populationResults = [];
    
    Object.entries(this.parameterConfig).forEach(([key, config]) => {
      if (config.singleSelect) {
        const value = urlParams.get(config.paramName);
        if (value) {
          const result = this.populateDropdownFromParam(config.dropdownType, [value]);
          if (result > 0) {
            populationResults.push({ type: key, count: result });
          }
        }
      } else {
        // Handle specialty parameters (multi-select)
        const values = this.parseSpecialtyParams(urlParams, config);
        if (values.length > 0) {
          const result = this.populateDropdownFromParam(config.dropdownType, values);
          if (result > 0) {
            populationResults.push({ type: key, count: result });
          }
        }
      }
    });
    
    if (populationResults.length > 0) {
      this.triggerMultiFormSync(populationResults);
    }
  }

  hasRelevantParams(urlParams) {
    return Object.values(this.parameterConfig).some(config => {
      if (config.singleSelect) {
        return urlParams.has(config.paramName);
      } else {
        return this.hasSpecialtyParams(urlParams, config);
      }
    });
  }

  hasSpecialtyParams(urlParams, config) {
    return urlParams.has(config.paramName) || 
           urlParams.has(config.paramName.charAt(0).toUpperCase() + config.paramName.slice(1)) ||
           urlParams.has(config.paramName.toUpperCase()) ||
           Array.from(urlParams.keys()).some(key => 
             key.startsWith(config.paramName + '[') || 
             key.toLowerCase().includes(config.paramName) || 
             key.toLowerCase().includes('need')
           );
  }

  parseSpecialtyParams(urlParams, config) {
    let values = urlParams.getAll(config.paramName);
    if (values.length > 0) return values;
    
    const valueSet = new Set();
    
    for (const [key, value] of urlParams.entries()) {
      if (this.isSpecialtyKey(key, config)) {
        valueSet.add(decodeURIComponent(value));
      }
    }
    
    if (valueSet.size === 0) {
      const searchString = window.location.search;
      for (const pattern of config.patterns) {
        const matches = searchString.matchAll(pattern);
        for (const match of matches) {
          valueSet.add(decodeURIComponent(match[1]));
        }
      }
    }
    
    return Array.from(valueSet);
  }

  isSpecialtyKey(key, config) {
    return key.startsWith(config.paramName + '[') || 
           key.endsWith(']') ||
           key.toLowerCase().includes(config.paramName) || 
           key.toLowerCase().includes('need');
  }

  populateDropdownFromParam(dropdownType, values) {
    const checkboxes = document.querySelectorAll(`input[name="${dropdownType}"]`);
    
    if (checkboxes.length === 0) return 0;
    
    const valueSet = new Set(Array.isArray(values) ? values : [values]);
    const updates = [];
    let populatedCount = 0;
    
    for (const checkbox of checkboxes) {
      if (valueSet.has(checkbox.value)) {
        updates.push(() => this.processCheckboxUpdate(checkbox));
        populatedCount++;
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
    
    if (checkbox.matches(CONFIG.ELEMENTS.checkboxSingleSelect)) {
      const option = checkbox.closest(CONFIG.ELEMENTS.option);
      if (option) {
        option.classList.add(CONFIG.CSS_CLASSES.optionSelected);
      }
    }
    
    this.callbacks.updateDropdownLabel(checkbox);
    checkbox.dispatchEvent(new Event('change', { bubbles: true }));
  }

  triggerMultiFormSync(populationResults) {
    if (this.callbacks.triggerCrossFiltering) {
      this.callbacks.triggerCrossFiltering();
    }
  }

  /**
   * Builds URLSearchParams from form checkbox selections.
   */
  buildSearchParams(form) {
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
    
    for (const [name, values] of Object.entries(selections)) {
      if (name === 'specialties-options') {
        values.forEach((value, index) => {
          searchParams.append(`specialty[${index}]`, value);
        });
      } else if (name === 'states-options') {
        searchParams.append('state', values[0]);
      } else if (name === 'payors-options') {
        searchParams.append('insurance', values[0]);
      } else if (name === 'type-of-care-options') {
        searchParams.append('typeOfCare', values[0]);
      }
    }

    searchParams.append('setting', 'Virtual');
    
    return searchParams;
  }

  /**
   * Constructs the final search URL from URLSearchParams.
   */
  buildSearchUrl(searchParams) {
    const stateValue = searchParams.get('state');
    const stateSlug = stateValue ? this.slugify(stateValue) : '';
    
    const baseUrl = `https://growtherapy.com/find/${stateSlug}`;
    
    return baseUrl + '?' + searchParams.toString();
  }
}
