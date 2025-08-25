<?php

declare(strict_types=1);

namespace Grow\BricksTags\Rendering;

use Grow\BricksTags\Plugin;
use Grow\BricksTags\Tags\GtCtxTag;
use Grow\BricksTags\Tags\GtFieldTag;
use Grow\BricksTags\Support\Parser;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Content renderer for processing dynamic tags in Bricks content
 * 
 * Handles both backend (builder) and frontend rendering with
 * optimized performance and proper recursion protection.
 */
final class ContentRenderer
{
    // Prevent repeated pre-pass on the same render pipeline
    private static bool $ctxPassActive = false;

    /**
     * Backend pre-pass: resolve {gt_ctx:...} tags first
     */
    public static function backendPre($content, $post, string $context = 'text')
    {
        if (!is_string($content) || strpos($content, '{gt_ctx:') === false) {
            return $content;
        }

        if (self::$ctxPassActive) {
            return $content;
        }

        if (!Plugin::enter()) {
            return $content;
        }

        self::$ctxPassActive = true;
        try {
            return Parser::resolveGtCtxWithinString($content, $context);
        } finally {
            self::$ctxPassActive = false;
            Plugin::leave();
        }
    }

    /**
     * Frontend pre-pass: resolve {gt_ctx:...} tags first
     */
    public static function frontendPre($content, $post, string $context = 'text')
    {
        if (!is_string($content) || strpos($content, '{gt_ctx:') === false) {
            return $content;
        }

        if (self::$ctxPassActive) {
            return $content;
        }

        if (!Plugin::enter()) {
            return $content;
        }

        self::$ctxPassActive = true;
        try {
            return Parser::resolveGtCtxWithinString($content, $context);
        } finally {
            self::$ctxPassActive = false;
            Plugin::leave();
        }
    }

    /**
     * Backend fallback: render any remaining tags inline
     */
    public static function backendFallback($content, $post, string $context = 'text')
    {
        return self::replaceAllOurTags($content, $post, $context);
    }

    /**
     * Frontend fallback: render any remaining tags inline
     */
    public static function frontendFallback($content, $post, string $context = 'text')
    {
        return self::replaceAllOurTags($content, $post, $context);
    }

    /**
     * Replace all our dynamic tags in the content
     */
    private static function replaceAllOurTags($content, $post, string $context)
    {
        if (!is_string($content) || strpos($content, '{gt_') === false) {
            return $content;
        }

        if (!Plugin::enter()) {
            return $content;
        }

        try {
            // Use the cached regex pattern from Plugin class
            $pattern = Plugin::getTagPattern();
            
            if (!preg_match_all($pattern, $content, $matches, PREG_SET_ORDER)) {
                return $content;
            }

            foreach ($matches as $match) {
                $whole = $match[0];   // with braces
                $inner = $match[1];   // without braces

                $replacement = self::renderTag($inner, $post, $context);
                
                // Safe literal replacement to avoid regex backreference issues
                $pos = strpos($content, $whole);
                if ($pos !== false) {
                    $content = substr_replace($content, (string) $replacement, $pos, strlen($whole));
                }
            }

            return $content;
        } finally {
            Plugin::leave();
        }
    }

    /**
     * Render a single tag based on its type
     */
    private static function renderTag(string $inner, $post, string $context): string
    {
        if (str_starts_with($inner, Plugin::TAG_FIELD . ':')) {
            return GtFieldTag::render(
                substr($inner, strlen(Plugin::TAG_FIELD . ':')),
                $post,
                $context
            );
        }

        if (str_starts_with($inner, Plugin::TAG_CTX . ':')) {
            return GtCtxTag::render(
                substr($inner, strlen(Plugin::TAG_CTX . ':')),
                $post,
                $context
            );
        }

        return $inner;
    }
}
