/**
 * Modal Management System
 * Handles modal opening, closing, state management, and cleanup
 */
import { CONFIG } from '../core/config.js';

export class ModalManager {
  constructor(activeModals, searchManager) {
    this.activeModals = activeModals;
    this.searchManager = searchManager;
  }

  getOpenModal(dropdown) {
    const modal = dropdown.querySelector(CONFIG.ELEMENTS.dropdownModal);
    return modal && this.activeModals.has(modal) ? modal : null;
  }
  
  toggle(modal, dropdown) {
    if (this.activeModals.has(modal)) {
      this.close(modal);
    } else {
      this.closeAll();
      this.open(modal, dropdown);
    }
  }
  
  open(modal, dropdown) {
    const button = dropdown.querySelector(CONFIG.ELEMENTS.modalTrigger);
    
    modal.classList.remove('aria-hidden');
    modal.classList.add(CONFIG.CSS_CLASSES.modalActive);
    modal.setAttribute('aria-hidden', 'false');
    
    if (button) {
      button.setAttribute('aria-expanded', 'true');
    }
    
    this.activeModals.add(modal);
    this.searchManager.clear(modal);
  }

  close(modal) {
    const dropdown = modal.closest(CONFIG.ELEMENTS.dropdown);
    if (!dropdown) return;
    
    const button = dropdown.querySelector(CONFIG.ELEMENTS.modalTrigger);
    
    modal.classList.remove(CONFIG.CSS_CLASSES.modalActive);
    modal.classList.add('aria-hidden');
    modal.setAttribute('aria-hidden', 'true');
    
    if (button) {
      button.setAttribute('aria-expanded', 'false');
      button.focus();
    }
    
    this.activeModals.delete(modal);
    this.notifyCleanup();
  }

  closeAll() {
    if (this.activeModals.size === 0) return;
    
    const modalsToClose = Array.from(this.activeModals);
    this.activeModals.clear();
    
    modalsToClose.forEach(modal => {
      const dropdown = modal.closest(CONFIG.ELEMENTS.dropdown);
      if (!dropdown) return;
      
      const button = dropdown.querySelector(CONFIG.ELEMENTS.modalTrigger);
      
      modal.classList.remove(CONFIG.CSS_CLASSES.modalActive);
      modal.classList.add('aria-hidden');
      modal.setAttribute('aria-hidden', 'true');
      
      if (button) {
        button.setAttribute('aria-expanded', 'false');
      }
    });
    
    this.notifyCleanup();
  }
  
  notifyCleanup() {
    // Emit cleanup event for main class coordination
    const event = new CustomEvent('modalCleanup', {
      detail: { activeModals: this.activeModals }
    });
    document.dispatchEvent(event);
  }
  
  handleModalOpen(e) {
    const button = e.target.closest(CONFIG.ELEMENTS.modalTrigger);
    if (!button) return;
    
    const dropdown = button.closest(CONFIG.ELEMENTS.dropdown);
    if (!dropdown) return;
    
    const modal = dropdown.querySelector(CONFIG.ELEMENTS.dropdownModal);
    if (!modal) return;
    
    this.toggle(modal, dropdown);
  }
}
