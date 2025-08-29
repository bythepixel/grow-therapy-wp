'use strict';

import { CONFIG } from '../core/config.js';

/**
 * Generic Events Manager
 * Handles event binding, delegation, routing, and cleanup for the search filter form system
 */
export class EventsManager {
  constructor(handlers, options = {}) {
    this.handlers = handlers;
    this.options = {
      passive: true,
      capture: false,
      ...options
    };
    
    // Track bound event listeners for cleanup
    this.boundListeners = new Map();
    
    this.eventConfig = {
      click: { handler: this.handleGlobalClick.bind(this), options: { passive: false, capture: false } },
      change: { handler: this.handleCheckboxChange.bind(this), options: { passive: true, capture: false } },
      submit: { handler: this.handleFormSubmit.bind(this), options: { passive: false, capture: false } },
      input: { handler: this.handleInput.bind(this), options: { passive: true, capture: false } },
      modalCleanup: { handler: this.handleModalCleanup.bind(this), options: { passive: true, capture: false } }
    };
    
    this.handleGlobalClick = this.handleGlobalClick.bind(this);
    this.handleCheckboxChange = this.handleCheckboxChange.bind(this);
    this.handleFormSubmit = this.handleFormSubmit.bind(this);
    this.handleInput = this.handleInput.bind(this);
    this.handleModalCleanup = this.handleModalCleanup.bind(this);
  }

  bind() {
    Object.entries(this.eventConfig).forEach(([eventType, config]) => {
      document.addEventListener(eventType, config.handler, config.options);
      this.boundListeners.set(eventType, config.handler);
    });
  }

  unbind() {
    this.boundListeners.forEach((listener, event) => {
      document.removeEventListener(event, listener);
    });
    this.boundListeners.clear();
  }

  handleGlobalClick(e) {
    const target = e.target;
    
    const clickTargets = [
      { selector: CONFIG.ELEMENTS.modalTrigger, handler: 'modalTrigger' },
      { selector: CONFIG.ELEMENTS.doneButton, handler: 'doneButton' },
      { selector: CONFIG.ELEMENTS.deselectButton, handler: 'deselectButton' }
    ];
    
    for (const { selector, handler } of clickTargets) {
      const element = target.closest(selector);
      if (element) {
        e.preventDefault();
        e.stopPropagation();
        this.handlers[handler]?.(e);
        return;
      }
    }
    
    this.handlers.clickOutside?.(e);
  }

  handleCheckboxChange(e) {
    this.handlers.checkboxChange?.(e);
  }

  handleFormSubmit(e) {
    this.handlers.formSubmit?.(e);
  }

  handleInput(e) {
    this.handlers.input?.(e);
  }

  handleModalCleanup(e) {
    this.handlers.modalCleanup?.(e);
  }
}
