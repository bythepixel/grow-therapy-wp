<?php
/**
 * Load all custom rewrite systems
 * 
 * This file ensures proper loading order:
 * 1. Utilities first (shared functions)
 * 2. Individual rewrite systems
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Load shared utilities first
require_once __DIR__ . '/utilities.php';

// Load individual rewrite systems
require_once __DIR__ . '/events.php';
require_once __DIR__ . '/guides.php';
require_once __DIR__ . '/landing-pages.php';
