<?php
/**
 * This file handles custom URL rewriting for landing pages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

define('GROW_THERAPY_LANDING_POST_TYPE', 'landing-page');
define('GROW_THERAPY_LANDING_TAXONOMY', 'landing-page-type');

/**
 * Add rewrite rules for ACF landing pages
 * 
 * Business requirement: Client prefers to use taxonomies for managing hierarchical urls
 * to enable URLs like /start/find-therapist-nb/
 */
function acf_landing_pages_rewrite_rules() {
    $terms = get_transient('landing_page_terms');
    
    if (false === $terms) {
        $terms = get_terms(array(
            'taxonomy' => GROW_THERAPY_LANDING_TAXONOMY,
            'hide_empty' => false,
        ));
        
        set_transient('landing_page_terms', $terms, HOUR_IN_SECONDS);
    }

    if (!is_wp_error($terms) && !empty($terms)) {
        foreach ($terms as $term) {
            add_rewrite_rule(
                '^' . $term->slug . '/([^/]+)/?$',
                'index.php?landing_page_slug=$matches[1]&landing_page_type_slug=' . $term->slug,
                'top'
            );
        }
    }
}
add_action('init', 'acf_landing_pages_rewrite_rules', 20);

function acf_landing_pages_query_vars($vars) {
    return array_merge($vars, ['landing_page_slug', 'landing_page_type_slug']);
}
add_filter('query_vars', 'acf_landing_pages_query_vars');

/**
 * Handle template redirect for CPT landing pages
 * 
 * Routes landing page requests to the appropriate template
 * and validates that the page belongs to the specified type
 */
function acf_landing_pages_template_redirect() {
    global $wp_query;
    
    $page_slug = get_query_var('landing_page_slug');
    $type_slug = get_query_var('landing_page_type_slug');
    
    if (!$page_slug || !$type_slug) {
        return;
    }
    
    $posts = get_posts(array(
        'name' => $page_slug,
        'post_type' => GROW_THERAPY_LANDING_POST_TYPE,
        'post_status' => 'publish',
        'numberposts' => 1,
        'tax_query' => array(
            array(
                'taxonomy' => GROW_THERAPY_LANDING_TAXONOMY,
                'field' => 'slug',
                'terms' => $type_slug,
            ),
        ),
        'no_found_rows' => true,
        'update_post_term_cache' => false,
        'update_post_meta_cache' => false,
    ));
    
    if (!empty($posts)) {
        $post = $posts[0];
        
        global $wp_query, $post;
        $wp_query = new WP_Query(array(
            'p' => $post->ID,
            'post_type' => GROW_THERAPY_LANDING_POST_TYPE
        ));
        
        $wp_query->is_single = true;
        $wp_query->is_singular = true;
        $wp_query->is_404 = false;
        
        include(get_query_template('single'));
        exit;
    } else {
        grow_therapy_log_rewrite_error("Landing page not found: {$page_slug} for type: {$type_slug}", [
            'page_slug' => $page_slug,
            'type_slug' => $type_slug
        ]);
        
        global $wp_query;
        $wp_query->set_404();
        status_header(404);
        get_template_part(404);
        exit;
    }
}
add_action('template_redirect', 'acf_landing_pages_template_redirect', 1);

function acf_landing_pages_permalink($post_link, $post) {
    if ($post->post_type !== GROW_THERAPY_LANDING_POST_TYPE) {
        return $post_link;
    }
    
    static $term_cache = [];
    $post_id = $post->ID;
    
    if (!isset($term_cache[$post_id])) {
        $terms = wp_get_post_terms($post_id, GROW_THERAPY_LANDING_TAXONOMY, ['fields' => 'slugs']);
        $term_cache[$post_id] = !empty($terms) && !is_wp_error($terms) ? $terms[0] : '';
    }
    
    $term_slug = $term_cache[$post_id];
    
    if ($term_slug) {
        return home_url('/' . $term_slug . '/' . $post->post_name . '/');
    }
    
    return $post_link;
}
add_filter('post_type_link', 'acf_landing_pages_permalink', 10, 2);

/**
 * Flush rewrite rules when taxonomy terms change
 * 
 * Automatically clears rewrite cache when landing page types
 * are created, edited, or deleted
 */
function acf_landing_pages_flush_rules($term_id, $tt_id, $taxonomy) {
    if ($taxonomy === GROW_THERAPY_LANDING_TAXONOMY) {
        grow_therapy_cleanup_rewrite_transients(['landing_page_terms'], $taxonomy);
    }
}
add_action('created_term', 'acf_landing_pages_flush_rules', 10, 3);
add_action('edited_term', 'acf_landing_pages_flush_rules', 10, 3);
add_action('delete_term', 'acf_landing_pages_flush_rules', 10, 3);

/**
 * Manual flush function for initial setup
 * 
 * Only runs once after adding this code to ensure
 * rewrite rules are properly registered
 */
function acf_landing_pages_manual_flush() {
    if (!get_option('acf_landing_pages_flushed')) {
        flush_rewrite_rules();
        update_option('acf_landing_pages_flushed', true);
    }
}
add_action('init', 'acf_landing_pages_manual_flush', 99);

function acf_landing_pages_admin_notice() {
    if (isset($_GET['acf_landing_activated']) && current_user_can('manage_options')) {
        echo '<div class="notice notice-success is-dismissible">';
        echo '<p>ACF Landing Pages rewrite rules activated! Please go to <a href="' . admin_url('options-permalink.php') . '">Settings > Permalinks</a> and click "Save Changes" to flush the rewrite rules.</p>';
        echo '</div>';
    }
}
add_action('admin_notices', 'acf_landing_pages_admin_notice');
