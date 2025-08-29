import { CONFIG } from '../core/config.js';

const domCache = new Map();

export const dom = {
  // Find modal by input name with caching
  findModalByInputName: (inputName) => {
    if (!inputName) return null;
    
    const cacheKey = `modal:${inputName}`;
    if (domCache.has(cacheKey)) {
      const cached = domCache.get(cacheKey);
      if (document.contains(cached)) {
        return cached;
      }
      domCache.delete(cacheKey);
    }
    
    const inputs = document.querySelectorAll(`input[name="${inputName}"]`);
    if (inputs.length === 0) return null;
    
    const firstInput = inputs[0];
    const modal = firstInput.closest('[data-search-filter-form-dropdown-modal]');
    
    if (modal) {
      domCache.set(cacheKey, modal);
    }
    
    return modal;
  },
  
  // Find option element containing checkbox
  findOption: (checkbox) => {
    if (!checkbox || !checkbox.closest) return null;
    return checkbox.closest(CONFIG.ELEMENTS.option);
  },
  
  // Find all checkboxes within modal
  findCheckboxes: (modal) => {
    if (!modal || !modal.querySelectorAll) return [];
    return modal.querySelectorAll(`${CONFIG.ELEMENTS.option} input[type="checkbox"]`);
  },
  
  // Find dropdown containing modal
  findDropdown: (modal) => {
    if (!modal || !modal.closest) return null;
    return modal.closest(CONFIG.ELEMENTS.dropdown);
  },
  
  // Clear DOM cache
  clearCache: () => {
    domCache.clear();
  },
  
  // Get cache stats for debugging
  getCacheStats: () => {
    return {
      size: domCache.size,
      keys: Array.from(domCache.keys())
    };
  }
};
