<?php
/**
 * This file handles all custom URL rewriting
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

define('GROW_THERAPY_GUIDE_BASE', 'therapy-basics');
define('GROW_THERAPY_POST_TYPE', 'guide');
define('GROW_THERAPY_TAXONOMY', 'guide-topic');

/**
 * Register custom rewrite rules for the therapy guides system
 * 
 * Business Requirement: Client prefers taxonomy-based navigation over WordPress page hierarchy
 * for better UI organization of therapy guides.
 */
function custom_guide_rewrites() {
    $rewrite_rules = [
        // 1. Guide CPT archive: domain.com/therapy-basics/
        '^' . GROW_THERAPY_GUIDE_BASE . '/?$' => 'index.php?post_type=' . GROW_THERAPY_POST_TYPE,
        
        // 2. Guide topic taxonomy archive: domain.com/therapy-basics/{guide-topic-slug}/
        '^' . GROW_THERAPY_GUIDE_BASE . '/([^/]+)/?$' => 'index.php?' . GROW_THERAPY_TAXONOMY . '=$matches[1]',
        
        // 3. Single guide with topic: domain.com/therapy-basics/{guide-topic-slug}/{guide-post-slug}
        '^' . GROW_THERAPY_GUIDE_BASE . '/([^/]+)/([^/]+)/?$' => 'index.php?' . GROW_THERAPY_TAXONOMY . '=$matches[1]&' . GROW_THERAPY_POST_TYPE . '=$matches[2]',
        
        // 4. Pagination for guide topic archives
        '^' . GROW_THERAPY_GUIDE_BASE . '/([^/]+)/page/([0-9]{1,})/?$' => 'index.php?' . GROW_THERAPY_TAXONOMY . '=$matches[1]&paged=$matches[2]',
        
        // 5. Pagination for main guide archive
        '^' . GROW_THERAPY_GUIDE_BASE . '/page/([0-9]{1,})/?$' => 'index.php?post_type=' . GROW_THERAPY_POST_TYPE . '&paged=$matches[1]',
    ];
    
    foreach ($rewrite_rules as $pattern => $replacement) {
        add_rewrite_rule($pattern, $replacement, 'top');
    }
}
add_action('init', 'custom_guide_rewrites');

/**
 * Generate custom permalinks for guide posts
 * 
 * Creates URLs like /therapy-basics/getting-therapy/breathing-techniques/
 * instead of default WordPress structure.
 */
function custom_guide_permalink($permalink, $post) {
    if ($post->post_type !== GROW_THERAPY_POST_TYPE) {
        return $permalink;
    }
    
    static $term_cache = [];
    $post_id = $post->ID;
    
    if (!isset($term_cache[$post_id])) {
        $terms = wp_get_post_terms($post_id, GROW_THERAPY_TAXONOMY, ['fields' => 'slugs']);
        $term_cache[$post_id] = !empty($terms) && !is_wp_error($terms) ? $terms[0] : 'uncategorized';
    }
    
    $topic_slug = $term_cache[$post_id];
    
    return home_url('/' . GROW_THERAPY_GUIDE_BASE . "/{$topic_slug}/{$post->post_name}/");
}
add_filter('post_type_link', 'custom_guide_permalink', 10, 2);

/**
 * Generate custom taxonomy term links for guide topics
 * 
 * Ensures topic archive URLs follow our custom structure instead of default WordPress 
 * taxonomy URLs.
 */
function custom_guide_topic_link($link, $term, $taxonomy) {
    return $taxonomy === GROW_THERAPY_TAXONOMY 
        ? home_url('/' . GROW_THERAPY_GUIDE_BASE . "/{$term->slug}/")
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
    return array_merge($vars, [GROW_THERAPY_POST_TYPE, GROW_THERAPY_TAXONOMY]);
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
    // Early return for non-guide requests
    if (!isset($wp->query_vars['post_type']) && 
        !isset($wp->query_vars[GROW_THERAPY_TAXONOMY]) && 
        !isset($wp->query_vars[GROW_THERAPY_POST_TYPE])) {
        return;
    }

    $query_vars = $wp->query_vars;

    if (!isset($query_vars[GROW_THERAPY_TAXONOMY]) && !isset($query_vars[GROW_THERAPY_POST_TYPE])) {
        return;
    }
    
    if (isset($query_vars[GROW_THERAPY_TAXONOMY]) && isset($query_vars[GROW_THERAPY_POST_TYPE])) {
        custom_guide_handle_single_guide_query($wp, $query_vars);
    } elseif (isset($query_vars[GROW_THERAPY_TAXONOMY])) {
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
    $guide_slug = $query_vars[GROW_THERAPY_POST_TYPE];
    $topic_slug = $query_vars[GROW_THERAPY_TAXONOMY];
    
    $guide_query = new WP_Query([
        'post_type' => GROW_THERAPY_POST_TYPE,
        'name' => $guide_slug,
        'posts_per_page' => 1,
        'fields' => 'ids',
        'no_found_rows' => true,
        'update_post_term_cache' => false,
        'update_post_meta_cache' => false,
    ]);
    
    if ($guide_query->have_posts()) {
        $post_id = $guide_query->posts[0];
        
        $post_terms = wp_get_object_terms($post_id, GROW_THERAPY_TAXONOMY, ['fields' => 'slugs']);
        
        if (!is_wp_error($post_terms) && in_array($topic_slug, $post_terms, true)) {
            $wp->query_vars['post_type'] = GROW_THERAPY_POST_TYPE;
            $wp->query_vars['name'] = $guide_slug;
            unset($wp->query_vars[GROW_THERAPY_POST_TYPE], $wp->query_vars[GROW_THERAPY_TAXONOMY]);
        } else {
            $wp->query_vars['error'] = '404';
        }
    } else {
        error_log("Guide not found: {$guide_slug} for topic: {$topic_slug}");
        $wp->query_vars['error'] = '404';
    }
}

/**
 * Handle topic archive queries
 * 
 * Routes requests for topic archives to the appropriate taxonomy template
 * while maintaining our custom URL structure. This ensures that when someone
 * visits /therapy-basics/getting-therapy/, they see all guides related to getting therapy.
 */
function custom_guide_handle_topic_archive_query($wp, $query_vars) {
    $topic_slug = $query_vars[GROW_THERAPY_TAXONOMY];
    
    $term = get_term_by('slug', $topic_slug, GROW_THERAPY_TAXONOMY, OBJECT, 'raw');
    
    if ($term && !is_wp_error($term)) {
        $wp->query_vars['taxonomy'] = GROW_THERAPY_TAXONOMY;
        $wp->query_vars['term'] = $topic_slug;
        unset($wp->query_vars[GROW_THERAPY_TAXONOMY]);
    } else {
        error_log("Topic not found: {$topic_slug}");
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

/**
 * Custom Rewrite Rules for Landing Pages
 */

// Add rewrite rules for ACF landing pages
function acf_landing_pages_rewrite_rules() {
    // Get all terms for the landing-page-type taxonomy
    $terms = get_terms(array(
        'taxonomy' => 'landing-page-type',
        'hide_empty' => false,
    ));

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

// Add custom query vars
function acf_landing_pages_query_vars($vars) {
    $vars[] = 'landing_page_slug';
    $vars[] = 'landing_page_type_slug';
    return $vars;
}
add_filter('query_vars', 'acf_landing_pages_query_vars');

// Handle the template redirect for ACF landing pages
function acf_landing_pages_template_redirect() {
    global $wp_query;
    
    $page_slug = get_query_var('landing_page_slug');
    $type_slug = get_query_var('landing_page_type_slug');
    
    if ($page_slug && $type_slug) {
        // Find the post using ACF post type and taxonomy
        $posts = get_posts(array(
            'name' => $page_slug,
            'post_type' => 'landing-page', // ACF post type key
            'post_status' => 'publish',
            'numberposts' => 1,
            'tax_query' => array(
                array(
                    'taxonomy' => 'landing-page-type', // ACF taxonomy key
                    'field' => 'slug',
                    'terms' => $type_slug,
                ),
            ),
        ));
        
        if (!empty($posts)) {
            $post = $posts[0];
            
            // Set up the global post data
            global $wp_query, $post;
            $wp_query = new WP_Query(array(
                'p' => $post->ID,
                'post_type' => 'landing-page'
            ));
            
            // Set up post data
            $wp_query->is_single = true;
            $wp_query->is_singular = true;
            $wp_query->is_404 = false;
            
            // Load the template
            include(get_query_template('single'));
            exit;
        } else {
            // Post not found, show 404
            global $wp_query;
            $wp_query->set_404();
            status_header(404);
            get_template_part(404);
            exit;
        }
    }
}
add_action('template_redirect', 'acf_landing_pages_template_redirect', 1);

// Modify the permalink structure for ACF landing pages
function acf_landing_pages_permalink($post_link, $post) {
    if ($post->post_type === 'landing-page') { // ACF post type key
        $terms = wp_get_object_terms($post->ID, 'landing-page-type'); // ACF taxonomy key
        
        if (!is_wp_error($terms) && !empty($terms)) {
            $term_slug = $terms[0]->slug;
            $post_link = home_url('/' . $term_slug . '/' . $post->post_name . '/');
        }
    }
    
    return $post_link;
}
add_filter('post_type_link', 'acf_landing_pages_permalink', 10, 2);

// Flush rewrite rules when taxonomy terms change
function acf_landing_pages_flush_rules($term_id, $tt_id, $taxonomy) {
    if ($taxonomy === 'landing-page-type') { // ACF taxonomy key
        delete_option('rewrite_rules');
    }
}
add_action('created_term', 'acf_landing_pages_flush_rules', 10, 3);
add_action('edited_term', 'acf_landing_pages_flush_rules', 10, 3);
add_action('delete_term', 'acf_landing_pages_flush_rules', 10, 3);

// Manual flush function (call once after adding this code)
function acf_landing_pages_manual_flush() {
    if (!get_option('acf_landing_pages_flushed')) {
        flush_rewrite_rules();
        update_option('acf_landing_pages_flushed', true);
    }
}
add_action('init', 'acf_landing_pages_manual_flush', 99);

// Optional: Add admin notice for flushing permalinks
function acf_landing_pages_admin_notice() {
    if (isset($_GET['acf_landing_activated']) && current_user_can('manage_options')) {
        echo '<div class="notice notice-success is-dismissible">';
        echo '<p>ACF Landing Pages rewrite rules activated! Please go to <a href="' . admin_url('options-permalink.php') . '">Settings > Permalinks</a> and click "Save Changes" to flush the rewrite rules.</p>';
        echo '</div>';
    }
}
add_action('admin_notices', 'acf_landing_pages_admin_notice');
