import { CONFIG } from '../core/config.js';

export class SearchManager {
  constructor() {
    this.searchDebounceTimers = new Map();
    this.parsedDataCache = new WeakMap();
    this.debounceDelay = 300;
  }

  handleInput(e) {
    const searchInput = e.target;
    if (!searchInput.matches(CONFIG.ELEMENTS.searchInput)) return;

    const existingTimer = this.searchDebounceTimers.get(searchInput);
    if (existingTimer) {
      clearTimeout(existingTimer);
    }

    const timer = setTimeout(() => {
      this.perform(searchInput);
      this.searchDebounceTimers.delete(searchInput);
    }, this.debounceDelay);

    this.searchDebounceTimers.set(searchInput, timer);
  }

  perform(searchInput) {
    const modal = searchInput.closest(CONFIG.ELEMENTS.dropdownModal);
    if (!modal) return;

    const searchTerm = searchInput.value.toLowerCase().trim();
    
    if (!searchTerm) {
      this.applyCrossFiltering(modal);
      return;
    }

    this.filterOptionsBySearchTerm(modal, searchTerm);
  }

  filterOptionsBySearchTerm(modal, searchTerm) {
    const options = modal.querySelectorAll(CONFIG.ELEMENTS.option);
    if (options.length === 0) return;

    for (const option of options) {
      const checkbox = option.querySelector('input[type="checkbox"]');
      if (!checkbox) continue;

      const searchData = this.getParsedSearchData(checkbox);
      if (!searchData) continue;

      const isSearchMatch = searchData.searchText.includes(searchTerm);
      const isCrossFiltered = !option.classList.contains(CONFIG.CSS_CLASSES.optionHidden);
      
      const shouldShow = isSearchMatch && isCrossFiltered;
      const currentDisplay = option.style.display;
      const newDisplay = shouldShow ? '' : 'none';
      
      if (currentDisplay !== newDisplay) {
        option.style.display = newDisplay;
      }
    }
  }

  getParsedSearchData(checkbox) {
    if (this.parsedDataCache.has(checkbox)) {
      return this.parsedDataCache.get(checkbox);
    }

    const searchData = checkbox.dataset.searchData;
    if (!searchData) return null;

    try {
      const parsed = JSON.parse(searchData);
      this.parsedDataCache.set(checkbox, parsed);
      return parsed;
    } catch (error) {
      console.warn('Invalid search data for option:', checkbox);
      return null;
    }
  }

  applyCrossFiltering(modal) {
    const options = modal.querySelectorAll(CONFIG.ELEMENTS.option);
    if (options.length === 0) return;

    for (const option of options) {
      const isCrossFiltered = !option.classList.contains(CONFIG.CSS_CLASSES.optionHidden);
      const shouldShow = isCrossFiltered;
      const currentDisplay = option.style.display;
      const newDisplay = shouldShow ? '' : 'none';
      
      if (currentDisplay !== newDisplay) {
        option.style.display = newDisplay;
      }
    }
  }

  clear(modal) {
    const searchInput = modal.querySelector(CONFIG.ELEMENTS.searchInput);
    if (searchInput) {
      searchInput.value = '';
    }
    
    this.applyCrossFiltering(modal);
  }

  cleanup() {
    this.searchDebounceTimers.forEach(timer => clearTimeout(timer));
    this.searchDebounceTimers.clear();
    this.parsedDataCache = new WeakMap();
  }
}
