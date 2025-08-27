<?php
/**
 * This file handles custom URL rewriting for events
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

define('GROW_THERAPY_EVENT_BASE', 'events');
define('GROW_THERAPY_EVENT_POST_TYPE', 'event');
define('GROW_THERAPY_EVENT_TAXONOMY', 'event-category');

/**
 * Register custom rewrite rules for the events system
 * 
 * Business Requirement: Client prefers taxonomy-based navigation over WordPress page hierarchy
 * for better UI organization of events.
 */
function custom_event_rewrites() {
    $rewrite_rules = [
        // 1. Event CPT archive: domain.com/events/
        '^' . GROW_THERAPY_EVENT_BASE . '/?$' => 'index.php?post_type=' . GROW_THERAPY_EVENT_POST_TYPE,
        
        // 2. Event category taxonomy archive: domain.com/events/{event-category-slug}/
        '^' . GROW_THERAPY_EVENT_BASE . '/([^/]+)/?$' => 'index.php?' . GROW_THERAPY_EVENT_TAXONOMY . '=$matches[1]',
        
        // 3. Single event with category: domain.com/events/{event-category-slug}/{event-post-slug}
        '^' . GROW_THERAPY_EVENT_BASE . '/([^/]+)/([^/]+)/?$' => 'index.php?' . GROW_THERAPY_EVENT_TAXONOMY . '=$matches[1]&' . GROW_THERAPY_EVENT_POST_TYPE . '=$matches[2]',
        
        // 4. Pagination for event category archives
        '^' . GROW_THERAPY_EVENT_BASE . '/([^/]+)/page/([0-9]{1,})/?$' => 'index.php?' . GROW_THERAPY_EVENT_TAXONOMY . '=$matches[1]&paged=$matches[2]',
        
        // 5. Pagination for main event archive
        '^' . GROW_THERAPY_EVENT_BASE . '/page/([0-9]{1,})/?$' => 'index.php?post_type=' . GROW_THERAPY_EVENT_POST_TYPE . '&paged=$matches[1]',
    ];
    
    foreach ($rewrite_rules as $pattern => $replacement) {
        add_rewrite_rule($pattern, $replacement, 'top');
    }
}
add_action('init', 'custom_event_rewrites');

/**
 * Generate custom permalinks for event posts
 * 
 * Creates URLs like /events/workshops/breathing-techniques-workshop/
 * instead of default WordPress structure.
 */
function custom_event_permalink($permalink, $post) {
    if ($post->post_type !== GROW_THERAPY_EVENT_POST_TYPE) {
        return $permalink;
    }
    
    static $term_cache = [];
    $post_id = $post->ID;
    
    if (!isset($term_cache[$post_id])) {
        $terms = wp_get_post_terms($post_id, GROW_THERAPY_EVENT_TAXONOMY, ['fields' => 'slugs']);
        $term_cache[$post_id] = !empty($terms) && !is_wp_error($terms) ? $terms[0] : 'uncategorized';
    }
    
    $category_slug = $term_cache[$post_id];
    
    return home_url('/' . GROW_THERAPY_EVENT_BASE . "/{$category_slug}/{$post->post_name}/");
}
add_filter('post_type_link', 'custom_event_permalink', 10, 2);

/**
 * Generate custom taxonomy term links for event categories
 * 
 * Ensures category archive URLs follow our custom structure instead of default WordPress 
 * taxonomy URLs.
 */
function custom_event_category_link($link, $term, $taxonomy) {
    return $taxonomy === GROW_THERAPY_EVENT_TAXONOMY 
        ? home_url('/' . GROW_THERAPY_EVENT_BASE . "/{$term->slug}/")
        : $link;
}
add_filter('term_link', 'custom_event_category_link', 10, 3);

/**
 * Register custom query variables for our rewrite system
 * 
 * These variables allow WordPress to understand our custom URL structure
 * and route requests to the appropriate content.
 */
function custom_event_query_vars($vars) {
    return array_merge($vars, [GROW_THERAPY_EVENT_POST_TYPE, GROW_THERAPY_EVENT_TAXONOMY]);
}
add_filter('query_vars', 'custom_event_query_vars');

/**
 * Parse incoming requests and route them to appropriate content
 * 
 * This handles the custom URL structure where events are nested under categories
 * rather than using standard WordPress post routing.
 */
function custom_event_parse_request($wp) {
    // Early return for non-event requests
    if (!isset($wp->query_vars['post_type']) && 
        !isset($wp->query_vars[GROW_THERAPY_EVENT_TAXONOMY]) && 
        !isset($wp->query_vars[GROW_THERAPY_EVENT_POST_TYPE])) {
        return;
    }

    $query_vars = $wp->query_vars;

    if (!isset($query_vars[GROW_THERAPY_EVENT_TAXONOMY]) && !isset($query_vars[GROW_THERAPY_EVENT_POST_TYPE])) {
        return;
    }
    
    if (isset($query_vars[GROW_THERAPY_EVENT_TAXONOMY]) && isset($query_vars[GROW_THERAPY_EVENT_POST_TYPE])) {
        custom_event_handle_single_event_query($wp, $query_vars);
    } elseif (isset($query_vars[GROW_THERAPY_EVENT_TAXONOMY])) {
        custom_event_handle_category_archive_query($wp, $query_vars);
    }
}

/**
 * Handle single event post queries
 * 
 * Validates that the event actually belongs to the specified category
 * to prevent URL manipulation and ensure proper content routing.
 */
function custom_event_handle_single_event_query($wp, $query_vars) {
    $event_slug = $query_vars[GROW_THERAPY_EVENT_POST_TYPE];
    $category_slug = $query_vars[GROW_THERAPY_EVENT_TAXONOMY];
    
    $event_query = new WP_Query([
        'post_type' => GROW_THERAPY_EVENT_POST_TYPE,
        'name' => $event_slug,
        'posts_per_page' => 1,
        'fields' => 'ids',
        'no_found_rows' => true,
        'update_post_term_cache' => false,
        'update_post_meta_cache' => false,
    ]);
    
    if ($event_query->have_posts()) {
        $post_id = $event_query->posts[0];
        
        $post_terms = wp_get_object_terms($post_id, GROW_THERAPY_EVENT_TAXONOMY, ['fields' => 'slugs']);
        
        if (!is_wp_error($post_terms) && in_array($category_slug, $post_terms, true)) {
            $wp->query_vars['post_type'] = GROW_THERAPY_EVENT_POST_TYPE;
            $wp->query_vars['name'] = $event_slug;
            unset($wp->query_vars[GROW_THERAPY_EVENT_POST_TYPE], $wp->query_vars[GROW_THERAPY_EVENT_TAXONOMY]);
        } else {
            $wp->query_vars['error'] = '404';
        }
    } else {
        grow_therapy_log_rewrite_error("Event not found: {$event_slug} for category: {$category_slug}", [
            'event_slug' => $event_slug,
            'category_slug' => $category_slug
        ]);
        $wp->query_vars['error'] = '404';
    }
}

/**
 * Handle category archive queries
 * 
 * Routes requests for category archives to the appropriate taxonomy template
 * while maintaining our custom URL structure.
 */
function custom_event_handle_category_archive_query($wp, $query_vars) {
    $category_slug = $query_vars[GROW_THERAPY_EVENT_TAXONOMY];
    
    $term = get_term_by('slug', $category_slug, GROW_THERAPY_EVENT_TAXONOMY, OBJECT, 'raw');
    
    if ($term && !is_wp_error($term)) {
        $wp->query_vars['taxonomy'] = GROW_THERAPY_EVENT_TAXONOMY;
        $wp->query_vars['term'] = $category_slug;
        unset($wp->query_vars[GROW_THERAPY_EVENT_TAXONOMY]);
    } else {
        grow_therapy_log_rewrite_error("Category not found: {$category_slug}", [
            'category_slug' => $category_slug,
            'taxonomy' => GROW_THERAPY_EVENT_TAXONOMY
        ]);
        $wp->query_vars['error'] = '404';
    }
}
add_action('parse_request', 'custom_event_parse_request');

/**
 * Flush rewrite rules when taxonomy terms change
 * 
 * Automatically clears rewrite cache when event categories
 * are created, edited, or deleted
 */
function custom_event_flush_rules($term_id, $tt_id, $taxonomy) {
    if ($taxonomy === GROW_THERAPY_EVENT_TAXONOMY) {
        grow_therapy_cleanup_rewrite_transients([], $taxonomy);
    }
}
add_action('created_term', 'custom_event_flush_rules', 10, 3);
add_action('edited_term', 'custom_event_flush_rules', 10, 3);
add_action('delete_term', 'custom_event_flush_rules', 10, 3);
