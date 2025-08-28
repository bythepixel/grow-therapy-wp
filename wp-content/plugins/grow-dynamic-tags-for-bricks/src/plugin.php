<?php

declare(strict_types=1);

namespace Grow\BricksTags;

use Grow\BricksTags\Rendering\ContentRenderer;
use Grow\BricksTags\Tags\GtCtxTag;
use Grow\BricksTags\Tags\GtFieldTag;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main plugin class for Grow Bricks Dynamic Tags
 * 
 * Provides two dynamic tags:
 * - {gt_ctx:...} for host page context resolution
 * - {gt_field:...} for meta data fetching with nested tag support
 */
final class Plugin
{
    // Public tag slugs
    public const TAG_FIELD = 'gt_field';
    public const TAG_FIELD_PREFIX = 'gt_field:';
    public const TAG_CTX = 'gt_ctx';
    public const TAG_CTX_PREFIX = 'gt_ctx:';

    // Recursion protection
    private static int $depth = 0;
    private const MAX_DEPTH = 8;

    // Performance optimization: cache compiled regex patterns
    private static ?string $tagPattern = null;

    /**
     * Initialize the plugin
     */
    public static function init(): void
    {
        // Register tags for Bricks UI
        add_filter('bricks/dynamic_tags_list', [self::class, 'registerTags']);

        // Tag-level resolver
        add_filter('bricks/dynamic_data/render_tag', [self::class, 'renderTag'], 20, 3);

        // Early pre-pass for {gt_ctx:...} tags
        add_filter('bricks/dynamic_data/render_content', [ContentRenderer::class, 'backendPre'], 1, 3);
        add_filter('bricks/frontend/render_data', [ContentRenderer::class, 'frontendPre'], 1, 3);

        // Fallback content replacer
        add_filter('bricks/dynamic_data/render_content', [ContentRenderer::class, 'backendFallback'], 20, 3);
        add_filter('bricks/frontend/render_data', [ContentRenderer::class, 'frontendFallback'], 20, 3);
    }

    /**
     * Register tags in the Bricks picker
     */
    public static function registerTags(array $tags): array
    {
        $tags[] = [
            'name' => self::TAG_FIELD,
            'label' => 'GT • Meta (parse inner tags)',
            'group' => 'Grow'
        ];
        $tags[] = [
            'name' => '{' . self::TAG_FIELD . '}',
            'label' => 'GT • Meta (parse inner tags)',
            'group' => 'Grow'
        ];
        $tags[] = [
            'name' => self::TAG_CTX,
            'label' => 'GT • Host Page Context',
            'group' => 'Grow'
        ];
        $tags[] = [
            'name' => '{' . self::TAG_CTX . '}',
            'label' => 'GT • Host Page Context',
            'group' => 'Grow'
        ];

        return $tags;
    }

    /**
     * Dispatch Bricks dynamic tag to our implementation
     */
    public static function renderTag($tag, $post, string $context = 'text')
    {
        if (!is_string($tag)) {
            return $tag;
        }

        if (!self::enter()) {
            return $tag;
        }

        try {
            $clean = trim($tag, '{}');

            if (str_starts_with($clean, self::TAG_FIELD_PREFIX)) {
                return GtFieldTag::render(
                    substr($clean, strlen(self::TAG_FIELD_PREFIX)),
                    $post,
                    $context
                );
            }

            if (str_starts_with($clean, self::TAG_CTX_PREFIX)) {
                return GtCtxTag::render(
                    substr($clean, strlen(self::TAG_CTX_PREFIX)),
                    $post,
                    $context
                );
            }

            return $tag;
        } finally {
            self::leave();
        }
    }

    /**
     * Get compiled regex pattern for tag matching
     */
    public static function getTagPattern(): string
    {
        if (self::$tagPattern === null) {
            self::$tagPattern = '/{(gt_(?:field|ctx):[^}]+)}/';
        }

        return self::$tagPattern;
    }

    /**
     * Recursion guard enter
     */
    public static function enter(): bool
    {
        if (self::$depth >= self::MAX_DEPTH) {
            return false;
        }

        self::$depth++;
        return true;
    }

    /**
     * Recursion guard leave
     */
    public static function leave(): void
    {
        if (self::$depth > 0) {
            self::$depth--;
        }
    }
}
