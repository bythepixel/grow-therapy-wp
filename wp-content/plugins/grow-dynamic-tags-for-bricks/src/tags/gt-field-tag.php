<?php

declare(strict_types=1);

namespace Grow\BricksTags\Tags;

use Grow\BricksTags\Rendering\Finalizer;
use Grow\BricksTags\Support\Context;
use Grow\BricksTags\Support\Parser;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * GT Field Tag Handler
 * 
 * Fetches meta data from posts, terms, or users with support for nested
 * dynamic tags and various formatting options.
 * 
 * Usage: {gt_field:payor_name|source:post|id:123|title|Default Value}
 * 
 * Perfect for extracting specific data that can then be used in FAQ content
 * or other dynamic elements.
 */
final class GtFieldTag
{
    /**
     * Render the field tag
     * 
     * @param string $argStr The tag arguments
     * @param mixed $post The current post object
     * @param string $context The rendering context
     * @return string The resolved meta value
     */
    public static function render(string $argStr, $post, string $context): string
    {
        $parts = explode('|', $argStr);
        $metaKey = trim(array_shift($parts));
        
        if ($metaKey === '') {
            return '';
        }

        // Parse options (flags, key-value pairs, filters, fallback)
        $opts = Parser::parseOptionTokens($parts);
        $source = isset($opts['kv']['source']) ? strtolower($opts['kv']['source']) : 'post';
        $id = isset($opts['kv']['id']) ? (int) $opts['kv']['id'] : 0;

        // Fetch the meta value based on source type
        $value = self::fetchMetaValue($source, $metaKey, $id, $post);

        // Pre-resolve any {gt_ctx:...} tags within the fetched value
        if (is_string($value) && $value !== '') {
            $value = Parser::resolveGtCtxWithinString($value, $context);
        }

        // Let Bricks render any remaining dynamic tags
        if (is_string($value) && $value !== '' && strpos($value, '{') !== false && function_exists('bricks_render_dynamic_data')) {
            $loopPostId = self::getLoopPostId($source, $id, $post);
            $value = bricks_render_dynamic_data($value, $loopPostId, $context);
        }

        // Apply fallback if empty
        if (Finalizer::isEffectivelyEmpty($value) && $opts['fallback'] !== null) {
            $value = $opts['fallback'];
        }

        // Apply formatters and finalize
        $value = Finalizer::applyFiltersChain($value, $opts['filters']);
        return Finalizer::finalizeOutput($value, $opts['flags'], $context);
    }

    /**
     * Fetch meta value based on source type
     */
    private static function fetchMetaValue(string $source, string $metaKey, int $id, $post): string
    {
        switch ($source) {
            case 'post':
                $postId = $id ?: ($post instanceof \WP_Post ? $post->ID : get_the_ID());
                return $postId ? (string) get_post_meta($postId, $metaKey, true) : '';
                
            case 'term':
                $termId = $id ?: Context::inferTermId();
                return $termId ? (string) get_term_meta($termId, $metaKey, true) : '';
                
            case 'user':
                $userId = $id ?: Context::inferUserId();
                return $userId ? (string) get_user_meta($userId, $metaKey, true) : '';
                
            default:
                return '';
        }
    }

    /**
     * Get the appropriate post ID for loop context
     */
    private static function getLoopPostId(string $source, int $id, $post): int
    {
        if ($source === 'post') {
            return $id ?: ($post instanceof \WP_Post ? $post->ID : get_the_ID());
        }
        
        return $post instanceof \WP_Post ? $post->ID : get_the_ID();
    }
}
