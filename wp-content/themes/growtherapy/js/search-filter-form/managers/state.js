/**
 * State Management System
 * Handles form state, selections, synchronization, and external state changes
 */
import { CONFIG } from '../core/config.js';

export class StateManager {
  constructor(instanceId, callbacks) {
    this.instanceId = instanceId;
    this.callbacks = callbacks;
  }

  getShared() {
    if (!SearchFilterForm.sharedState) {
      SearchFilterForm.sharedState = {
        selections: new Map(),
        instances: new Set()
      };
    }
    return SearchFilterForm.sharedState;
  }

  update(name, value, checked) {
    const sharedState = SearchFilterForm.sharedState;
    if (!sharedState) return;
    
    if (!sharedState.selections.has(name)) {
      sharedState.selections.set(name, new Set());
    }
    
    const selectionSet = sharedState.selections.get(name);
    
    if (checked) {
      selectionSet.add(value);
    } else {
      selectionSet.delete(value);
    }
    
    this.notify(name, value, checked);
  }

  notify(name, value, checked) {
    const event = new CustomEvent('searchFilterFormStateChange', {
      detail: {
        name,
        value,
        checked,
        sourceInstanceId: this.instanceId
      }
    });
    
    document.dispatchEvent(event);
  }

  handleExternal(event) {
    const { name, value, checked, sourceInstanceId } = event.detail;
    
    if (sourceInstanceId === this.instanceId) return;
    
    const checkbox = this.findCheckbox(name, value);
    if (checkbox) {
      checkbox.checked = checked;
      
      const option = checkbox.closest(CONFIG.ELEMENTS.option);
      if (option) {
        option.classList.toggle(CONFIG.CSS_CLASSES.optionSelected, checked);
      }
      
      this.callbacks.updateDropdownLabel(checkbox);
      
      if (checked) {
        this.callbacks.applyCrossFiltering(checkbox);
      } else {
        this.callbacks.resetCrossFiltering(checkbox);
      }
    }
  }

  findCheckbox(name, value) {
    const dropdowns = CONFIG.ELEMENTS.dropdown ? 
      document.querySelectorAll(CONFIG.ELEMENTS.dropdown) : 
      document.querySelectorAll('[data-search-filter-form-dropdown]');
    
    for (const dropdown of dropdowns) {
      const checkbox = dropdown.querySelector(`input[name="${name}"][value="${value}"]`);
      if (checkbox) return checkbox;
    }
    
    return null;
  }

  sync() {
    const sharedState = SearchFilterForm.sharedState;
    if (!sharedState || !sharedState.selections) return;
    
    for (const [name, values] of sharedState.selections) {
      for (const value of values) {
        const checkbox = this.findCheckbox(name, value);
        if (checkbox && !checkbox.checked) {
          checkbox.checked = true;
          
          const option = checkbox.closest(CONFIG.ELEMENTS.option);
          if (option) {
            option.classList.add(CONFIG.CSS_CLASSES.optionSelected);
          }
          
          this.callbacks.updateDropdownLabel(checkbox);
          this.callbacks.applyCrossFiltering(checkbox);
        }
      }
    }
    
    sharedState.instances.add(this.instanceId);
  }
}
