<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Filter API Configuration
 */
define('FILTER_API_ENDPOINT', 'https://growtherapy.com/api/filters');
define('FILTER_API_CACHE_DURATION', 300); // 5 minutes, set to 0 for no caching

/**
 * Generic API fetch function
 */
function fetch_api_data($endpoint, $cache_key) {
    // Check cache if duration > 0
    if (FILTER_API_CACHE_DURATION > 0) {
        $cached_data = get_transient($cache_key);
        if ($cached_data !== false) {
            return $cached_data;
        }
    }

    $response = wp_remote_get(FILTER_API_ENDPOINT . '/' . $endpoint);

    if (is_wp_error($response)) {
        error_log("Failed to fetch {$endpoint}: " . $response->get_error_message());
        return null;
    }

    $status_code = wp_remote_retrieve_response_code($response);
    if ($status_code !== 200) {
        error_log("Failed to fetch {$endpoint}: Status code {$status_code}");
        return null;
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("Failed to fetch {$endpoint}: Invalid JSON response");
        return null;
    }

    // Cache the data if duration > 0
    if (FILTER_API_CACHE_DURATION > 0) {
        set_transient($cache_key, $data, FILTER_API_CACHE_DURATION);
    }

    return $data;
}

/**
 * Fetch states and payors data
 */
function fetch_states_payors() {
    return fetch_api_data('states-payors', 'growtherapy_states_payors');
}

/**
 * Fetch specialties data
 */
function fetch_specialties() {
    return fetch_api_data('specialties', 'growtherapy_specialties');
}

/**
 * Load filter API data once and store in global variables
 */
add_action('init', function() {
    global $growtherapy_states_payors, $growtherapy_specialties;
    $growtherapy_states_payors = fetch_states_payors();
    $growtherapy_specialties = fetch_specialties();
}, 5);

/**
 * Helper functions for filter API data
 */
if (!function_exists('get_filter_api_states')) {
    function get_filter_api_states() {
        global $growtherapy_states_payors;
        return $growtherapy_states_payors['states'] ?? [];
    }
}

if (!function_exists('get_filter_api_states_by_payor')) {
    function get_filter_api_states_by_payor() {
        global $growtherapy_states_payors;
        return $growtherapy_states_payors['statesByPayor'] ?? [];
    }
}

if (!function_exists('get_filter_api_payors')) {
    function get_filter_api_payors() {
        global $growtherapy_states_payors;
        return $growtherapy_states_payors['payors'] ?? [];
    }
}

if (!function_exists('get_filter_api_payors_by_state')) {
    function get_filter_api_payors_by_state() {
        global $growtherapy_states_payors;
        return $growtherapy_states_payors['payorsByState'] ?? [];
    }
}

if (!function_exists('get_filter_api_specialties')) {
    function get_filter_api_specialties() {
        global $growtherapy_specialties;
        return $growtherapy_specialties['specialties'] ?? [];
    }
}
