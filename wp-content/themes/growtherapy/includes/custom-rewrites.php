<?php
/**
 * This file handles all custom URL rewriting
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register custom rewrite rules for the therapy guides system
 * 
 * Business Requirement: Client prefers taxonomy-based navigation over WordPress page hierarchy
 * for better UI organization of therapy guides.
 */
function custom_guide_rewrites() {
    $guide_base = 'therapy-basics';
    
    $rewrite_rules = [
        // 1. Guide CPT archive: domain.com/therapy-basics/
        '^' . $guide_base . '/?$' => 'index.php?post_type=guide',
        
        // 2. Guide topic taxonomy archive: domain.com/therapy-basics/{guide-topic-slug}/
        '^' . $guide_base . '/([^/]+)/?$' => 'index.php?guide-topic=$matches[1]',
        
        // 3. Single guide with topic: domain.com/therapy-basics/{guide-topic-slug}/{guide-post-slug}
        '^' . $guide_base . '/([^/]+)/([^/]+)/?$' => 'index.php?guide-topic=$matches[1]&guide=$matches[2]',
        
        // 4. Pagination for guide topic archives
        '^' . $guide_base . '/([^/]+)/page/([0-9]{1,})/?$' => 'index.php?guide-topic=$matches[1]&paged=$matches[2]',
        
        // 5. Pagination for main guide archive
        '^' . $guide_base . '/page/([0-9]{1,})/?$' => 'index.php?post_type=guide&paged=$matches[1]',
    ];
    
    foreach ($rewrite_rules as $pattern => $replacement) {
        add_rewrite_rule($pattern, $replacement, 'top');
    }
}
add_action('init', 'custom_guide_rewrites');

/**
 * Generate custom permalinks for guide posts
 * 
 * Creates URLs like /therapy-basics/anxiety/breathing-techniques/
 * instead of default WordPress structure.
 */
function custom_guide_permalink($permalink, $post) {
    if ($post->post_type !== 'guide') {
        return $permalink;
    }
    
    static $term_cache = [];
    $post_id = $post->ID;
    
    if (!isset($term_cache[$post_id])) {
        $terms = wp_get_post_terms($post_id, 'guide-topic', ['fields' => 'slugs']);
        $term_cache[$post_id] = !empty($terms) && !is_wp_error($terms) ? $terms[0] : 'uncategorized';
    }
    
    $topic_slug = $term_cache[$post_id];
    
    return home_url("/therapy-basics/{$topic_slug}/{$post->post_name}/");
}
add_filter('post_type_link', 'custom_guide_permalink', 10, 2);

/**
 * Generate custom taxonomy term links for guide topics
 * 
 * Ensures topic archive URLs follow our custom structure instead of default WordPress 
 * taxonomy URLs.
 */
function custom_guide_topic_link($link, $term, $taxonomy) {
    return $taxonomy === 'guide-topic' 
        ? home_url("/therapy-basics/{$term->slug}/")
        : $link;
}
add_filter('term_link', 'custom_guide_topic_link', 10, 3);

/**
 * Register custom query variables for our rewrite system
 * 
 * These variables allow WordPress to understand our custom URL structure
 * and route requests to the appropriate content. Without these, WordPress
 * wouldn't know how to handle our custom URL parameters.
 */
function custom_guide_query_vars($vars) {
    return array_merge($vars, ['guide', 'guide-topic']);
}
add_filter('query_vars', 'custom_guide_query_vars');

/**
 * Parse incoming requests and route them to appropriate content
 * 
 * This handles the custom URL structure where guides are nested under topics
 * rather than using standard WordPress post routing. It's essentially a custom
 * router that intercepts requests and maps them to the right content.
 */
function custom_guide_parse_request($wp) {
    $query_vars = $wp->query_vars;

    if (!isset($query_vars['guide-topic']) && !isset($query_vars['guide'])) {
        return;
    }
    
    if (isset($query_vars['guide-topic']) && isset($query_vars['guide'])) {
        custom_guide_handle_single_guide_query($wp, $query_vars);
    } elseif (isset($query_vars['guide-topic'])) {
        custom_guide_handle_topic_archive_query($wp, $query_vars);
    }
}

/**
 * Handle single guide post queries
 * 
 * Validates that the guide actually belongs to the specified topic
 * to prevent URL manipulation and ensure proper content routing.
 */
function custom_guide_handle_single_guide_query($wp, $query_vars) {
    $guide_slug = $query_vars['guide'];
    $topic_slug = $query_vars['guide-topic'];
    
    $guide_query = new WP_Query([
        'post_type' => 'guide',
        'name' => $guide_slug,
        'posts_per_page' => 1,
        'fields' => 'ids',
        'no_found_rows' => true,
        'update_post_term_cache' => false,
        'update_post_meta_cache' => false,
    ]);
    
    if ($guide_query->have_posts()) {
        $post_id = $guide_query->posts[0];
        
        $post_terms = wp_get_object_terms($post_id, 'guide-topic', ['fields' => 'slugs']);
        
        if (!is_wp_error($post_terms) && in_array($topic_slug, $post_terms, true)) {
            $wp->query_vars['post_type'] = 'guide';
            $wp->query_vars['name'] = $guide_slug;
            unset($wp->query_vars['guide'], $wp->query_vars['guide-topic']);
        } else {
            $wp->query_vars['error'] = '404';
        }
    } else {
        $wp->query_vars['error'] = '404';
    }
}

/**
 * Handle topic archive queries
 * 
 * Routes requests for topic archives to the appropriate taxonomy template
 * while maintaining our custom URL structure. This ensures that when someone
 * visits /therapy-basics/anxiety/, they see all guides related to anxiety.
 */
function custom_guide_handle_topic_archive_query($wp, $query_vars) {
    $topic_slug = $query_vars['guide-topic'];
    
    $term = get_term_by('slug', $topic_slug, 'guide-topic', OBJECT, 'raw');
    
    if ($term && !is_wp_error($term)) {
        $wp->query_vars['taxonomy'] = 'guide-topic';
        $wp->query_vars['term'] = $topic_slug;
        unset($wp->query_vars['guide-topic']);
    } else {
        $wp->query_vars['error'] = '404';
    }
}
add_action('parse_request', 'custom_guide_parse_request');

/**
 * Flush rewrite rules on theme activation
 * 
 * Only flushes when necessary to avoid performance impact. WordPress
 * rewrite rules are expensive to regenerate, so we only do it when
 * the theme is first activated or when explicitly needed.
 */
function flush_guide_rewrites() {
    if (get_option('grow_therapy_rewrites_flushed') !== 'done') {
        custom_guide_rewrites();
        flush_rewrite_rules();
        update_option('grow_therapy_rewrites_flushed', 'done');
    }
}
add_action('after_switch_theme', 'flush_guide_rewrites');
