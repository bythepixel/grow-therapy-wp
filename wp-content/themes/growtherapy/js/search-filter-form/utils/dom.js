import { CONFIG } from '../core/config.js';

const domCache = new Map();

export const dom = {
  // Find option element containing checkbox
  findOption: (checkbox) => {
    if (!checkbox || !checkbox.closest) return null;
    return checkbox.closest(CONFIG.ELEMENTS.option);
  },
  
  // Clear DOM cache
  clearCache: () => {
    domCache.clear();
  },
};
