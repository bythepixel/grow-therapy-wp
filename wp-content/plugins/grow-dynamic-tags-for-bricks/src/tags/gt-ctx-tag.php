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
 * GT Context Tag Handler
 * 
 * Resolves content in host page context, perfect for FAQs that need to reference
 * the current page's data (e.g., payor name, location, etc.)
 * 
 * Usage: {gt_ctx:{payor_name}|Default Payor|title}
 * 
 * This allows you to have one FAQ template that dynamically populates
 * context-specific information from the page where it's displayed.
 */
final class GtCtxTag
{
    /**
     * Render the context tag
     * 
     * @param string $argStr The tag arguments (e.g., "{payor_name}|Default|title")
     * @param mixed $post The current post object
     * @param string $context The rendering context (text, image, etc.)
     * @return string The resolved content
     */
    public static function render(string $argStr, $post, string $context): string
    {
        // Parse inner content and options
        [$inner, $opts] = Parser::parseGtCtxTag($argStr);

        // Get the host page ID (the page where this FAQ is being displayed)
        $hostId = Context::resolveHostPageId($post);
        
        // Start with the inner content
        $value = $inner;

        // Resolve any dynamic data in the inner content using Bricks
        if (function_exists('bricks_render_dynamic_data')) {
            $value = bricks_render_dynamic_data($inner, $hostId, $context);
        }

        // Apply fallback if the resolved value is empty
        if (Finalizer::isEffectivelyEmpty($value) && $opts['fallback'] !== null) {
            $value = $opts['fallback'];
        }

        // Apply any filters and finalize the output
        $value = Finalizer::applyFiltersChain($value, $opts['filters']);
        return Finalizer::finalizeOutput($value, $opts['flags'] ?? [], $context);
    }
}
