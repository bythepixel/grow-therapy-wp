<?php
namespace Grow\BricksTags\Tags;

use Grow\BricksTags\Rendering\Finalizer;
use Grow\BricksTags\Support\Context;
use Grow\BricksTags\Support\Parser;

if (!defined('ABSPATH')) { exit; }

final class GtFieldTag {
  /**
   * {gt_field:meta_key[|source:post|term|user][|id:<ID>][|raw][|noescape][|DEFAULT][|filters]}
   */
  public static function render(string $argStr, $post, string $context) {
    $parts = explode('|', (string)$argStr);
    $meta_key = trim(array_shift($parts));
    if ($meta_key === '') return '';

    $opts   = Parser::parseOptionTokens($parts); // flags, kv, filters, fallback
    $source = isset($opts['kv']['source']) ? strtolower($opts['kv']['source']) : 'post';
    $id     = isset($opts['kv']['id']) ? (int)$opts['kv']['id'] : 0;

    $value = '';

    if ($source === 'post') {
      $post_id = $id ?: ( $post instanceof \WP_Post ? $post->ID : get_the_ID() );
      if ($post_id) $value = get_post_meta($post_id, $meta_key, true);
    } elseif ($source === 'term') {
      $term_id = $id ?: Context::inferTermId();
      if ($term_id) $value = get_term_meta($term_id, $meta_key, true);
    } elseif ($source === 'user') {
      $user_id = $id ?: Context::inferUserId();
      if ($user_id) $value = get_user_meta($user_id, $meta_key, true);
    }

    // Pre-resolve any {gt_ctx:...} INSIDE the fetched string BEFORE other tags
    if (is_string($value) && $value !== '') {
      $value = Parser::resolveGtCtxWithinString($value, $context);
    }

    // Then let Bricks render remaining tags in LOOP ITEM context (only if braces remain)
    if (is_string($value) && $value !== '' && strpos($value, '{') !== false && function_exists('bricks_render_dynamic_data')) {
      $loop_post_id = ($source === 'post')
        ? ($id ?: ( $post instanceof \WP_Post ? $post->ID : get_the_ID() ))
        : ( $post instanceof \WP_Post ? $post->ID : get_the_ID() );
      $value = bricks_render_dynamic_data($value, $loop_post_id, $context);
    }

    // Fallback if empty
    if (Finalizer::isEffectivelyEmpty($value) && $opts['fallback'] !== null) {
      $value = $opts['fallback'];
    }

    // Apply formatters & finalize
    $value = Finalizer::applyFiltersChain($value, $opts['filters']);
    return Finalizer::finalizeOutput($value, $opts['flags'], $context);
  }
}
