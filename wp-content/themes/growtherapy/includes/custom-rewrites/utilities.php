<?php
/**
 * Shared utilities for custom rewrite systems
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Standard error logging for rewrite systems
 * 
 * Provides consistent error logging across all rewrite functions
 */
function grow_therapy_log_rewrite_error($message, $context = []) {
    $log_entry = 'Rewrite Error: ' . $message;
    
    if (!empty($context)) {
        $log_entry .= ' | Context: ' . json_encode($context);
    }
    
    error_log($log_entry);
}

/**
 * Standard transient cleanup for rewrite systems
 * 
 * Cleans up transients when taxonomy terms change
 */
function grow_therapy_cleanup_rewrite_transients($transient_names, $taxonomy) {
    if (is_array($transient_names)) {
        foreach ($transient_names as $transient_name) {
            delete_transient($transient_name);
        }
    }
    delete_option('rewrite_rules');
}
