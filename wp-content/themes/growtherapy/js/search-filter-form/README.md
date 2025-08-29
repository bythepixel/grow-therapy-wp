# Search Filter Form System

A modular search filter form system built with vanilla JavaScript ES6 modules. Designed for multi-form synchronization with advanced filtering, validation, and URL state management.

## Architecture Overview

The system follows a **Manager Pattern** architecture where each manager handles a specific concern:

```
SearchFilterForm (Main Orchestrator)
├── EventsManager (Event handling & routing)
├── FormSyncManager (Multi-form synchronization)
├── ModalManager (Modal state & behavior)
├── SearchManager (Search functionality)
├── URLManager (URL parameter handling)
└── ValidationManager (Form validation)
```

## Core Components

### SearchFilterForm (Main Class)
- **Purpose**: Central orchestrator that coordinates all managers
- **Responsibilities**: 
  - Manager initialization and coordination
  - Cross-filtering logic
  - Form submission handling
  - Single-select checkbox behavior

### EventsManager
- **Purpose**: Handles all event binding, delegation, and routing
- **Features**:
  - Single event delegation for performance
  - Centralized event configuration
  - Automatic cleanup and memory management
  - Passive event handling where appropriate

### FormSyncManager
- **Purpose**: Manages multi-form synchronization and state consistency
- **Features**:
  - Checkbox synchronization across all forms
  - Dropdown label updates
  - Cross-filtering coordination
  - Validation state synchronization

### ModalManager
- **Purpose**: Handles modal opening, closing, and state management
- **Features**:
  - Modal state tracking
  - Accessibility attributes management
  - Search manager coordination
  - Cleanup event emission

### SearchManager
- **Purpose**: Handles search functionality within dropdowns
- **Features**:
  - Debounced search input
  - Cross-filtering integration
  - Search data caching
  - Option visibility management

### URLManager
- **Purpose**: Handles URL parameter population and multi-form updates
- **Features**:
  - Centralized parameter configuration
  - Multi-form population
  - Cross-filtering triggers
  - Performance-optimized DOM queries

### ValidationManager
- **Purpose**: Manages form validation and error states
- **Features**:
  - Required field validation
  - Error state synchronization across forms
  - Accessibility integration
  - Validation message management

## Configuration

### CONFIG Object
Centralized configuration for all selectors and CSS classes:

```javascript
export const CONFIG = {
  ELEMENTS: {
    checkboxSingleSelect: '[data-search-filter-form-single-select]',
    deselectButton: '[data-search-filter-form-deselect-button]',
    // ... other selectors
  },
  CSS_CLASSES: {
    dropdownError: 'search-filter-form-dropdown--error',
    modalActive: 'search-filter-form-modal--active',
    // ... other classes
  }
};
```

## Usage

### Basic Initialization
```javascript
import { SearchFilterForm } from './core/index.js';

// Automatically initializes on DOM ready
new SearchFilterForm();
```

### HTML Structure Requirements
```html
<div data-search-filter-form-dropdown>
  <button data-search-filter-form-modal-trigger>
    <span data-search-filter-form-label>Select option</span>
  </button>
  
  <div data-search-filter-form-dropdown-modal aria-hidden="true">
    <input data-search-filter-form-search-input type="text" placeholder="Search...">
    
    <label data-search-filter-form-option class="search-filter-form-modal__option">
      <input type="checkbox" name="states-options" value="alabama" data-search-data='{"label":"Alabama"}'>
      Alabama
    </label>
    
    <button data-search-filter-form-done-button>Done</button>
  </div>
</div>
```

## Features

### Multi-Form Synchronization
- All forms automatically stay in sync
- Checkbox selections propagate across forms
- Validation states synchronized
- Dropdown labels updated consistently

### Advanced Filtering
- Cross-field filtering based on data attributes
- Search within dropdowns
- Dynamic option availability
- URL state persistence

### Accessibility
- ARIA attributes management
- Keyboard navigation support
- Screen reader compatibility
- Focus management

### Performance Optimizations
- Event delegation for click handling
- Debounced search input
- Targeted DOM queries
- RequestAnimationFrame for updates

## Manager Dependencies

```
EventsManager ← SearchFilterForm
FormSyncManager ← SearchFilterForm, ValidationManager
ModalManager ← SearchFilterForm, SearchManager
SearchManager ← CONFIG
URLManager ← FormSyncManager, SearchFilterForm
ValidationManager ← CONFIG
```

## Event Flow

1. **Initialization**: URL population → Event binding → Ready state
2. **User Interaction**: Event delegation → Manager routing → State updates
3. **Form Sync**: Checkbox changes → Multi-form updates → Cross-filtering
4. **Validation**: Field changes → Validation checks → Error state sync
5. **Submission**: Form submit → Validation → URL building → Navigation

## URL Parameter Support

The system automatically handles these URL parameters:
- `state` → `states-options`
- `insurance` → `payors-options`
- `typeOfCare` → `type-of-care-options`
- `specialty[]` → `specialties-options`

## Browser Support

- ES6 Modules
- Modern DOM APIs
- CSS Custom Properties
- WeakMap and Set support

## File Structure

```
search-filter-form/
├── core/
│   ├── config.js (Configuration constants)
│   ├── search-filter-form.js (Main orchestrator)
│   └── index.js (Core exports)
├── managers/
│   ├── events.js (Event management)
│   ├── form-sync.js (Form synchronization)
│   ├── modal.js (Modal management)
│   ├── search.js (Search functionality)
│   ├── url.js (URL handling)
│   ├── validation.js (Form validation)
│   └── index.js (Manager exports)
├── utils/
│   ├── utils.js (Utility functions)
│   └── index.js (Utility exports)
├── index.js (Main entry point)
└── README.md (This file)
```

## Development Guidelines

### Adding New Managers
1. Create manager class with focused responsibility
2. Export from managers/index.js
3. Initialize in SearchFilterForm constructor
4. Add to appropriate event handlers

### Extending Configuration
1. Add new selectors to CONFIG.ELEMENTS
2. Add new CSS classes to CONFIG.CSS_CLASSES
3. Update relevant managers to use new config
