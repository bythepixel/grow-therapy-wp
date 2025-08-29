/**
 * Utility functions for Search Filter Form
 */
export const utils = {
  /**
   * Generate a unique ID for form instances
   * Uses crypto.randomUUID() when available, falls back to Math.random()
   * @returns {string} - Unique identifier with prefix
   */
  generateId: () => {
    const prefix = 'search-filter-form-';
    if (typeof crypto !== 'undefined' && crypto.randomUUID) {
      return prefix + crypto.randomUUID();
    }
    return prefix + Math.random().toString(36).substring(2, 11);
  },
  
  /**
   * Convert text to URL-friendly slug format
   * Optimized regex pattern for better performance
   * @param {string} text - Text to convert
   * @returns {string} - URL-friendly slug
   */
  slugify: (text) => {
    if (!text) return '';
    return text
      .toLowerCase()
      .trim()
      .replace(/[^\w\s-]/g, '') // Remove special chars except word chars, spaces, hyphens
      .replace(/\s+/g, '-')     // Replace spaces with hyphens
      .replace(/-+/g, '-')      // Replace multiple hyphens with single
      .replace(/^-+|-+$/g, ''); // Remove leading/trailing hyphens
  }
};
