<?php

declare(strict_types=1);

namespace Grow\BricksTags\Support;

use Grow\BricksTags\Rendering\Finalizer;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Parser utilities for dynamic tag processing
 * 
 * Handles parsing of tag arguments, options, and nested tag resolution
 * with optimized performance for large content blocks.
 */
final class Parser
{
    // Performance optimization: cache known filter names
    private const KNOWN_FILTERS = [
        'trim', 'lower', 'upper', 'title', 'ucfirst', 'strip_tags', 'nl2br',
        'escape_attr', 'json', 'length', 'unique', 'join', 'number', 'date'
    ];

    private const KNOWN_FLAGS = ['raw', 'noescape'];

    /**
     * Replace all {gt_ctx:...} tags inside a string with resolved values
     * 
     * @param string $content The content to process
     * @param string $context The rendering context
     * @return string The processed content
     */
    public static function resolveGtCtxWithinString(string $content, string $context): string
    {
        if (strpos($content, '{gt_ctx:') === false) {
            return $content;
        }

        // Hard cap to avoid pathological scans
        if (strlen($content) > 100000) {
            return $content;
        }

        $output = '';
        $position = 0;
        $length = strlen($content);

        while ($position < $length) {
            $start = strpos($content, '{gt_ctx:', $position);
            if ($start === false) {
                $output .= substr($content, $position);
                break;
            }

            // Add content before the tag
            $output .= substr($content, $position, $start - $position);
            $cursor = $start + strlen('{gt_ctx:');

            // Parse inner content
            $inner = self::parseInnerContent($content, $cursor, $length);
            $cursor += strlen($inner);

            // Parse options
            $options = self::parseOptions($content, $cursor, $length);
            $cursor = $options['cursor'];

            // Resolve the tag
            $resolved = self::resolveGtCtxTag($inner, $context, $options['parsed']);

            $output .= $resolved;
            $position = $cursor + 1; // past '}'
        }

        return $output;
    }

    /**
     * Parse a full {gt_ctx:...} tag's argument string
     */
    public static function parseGtCtxTag(string $argStr): array
    {
        $inner = '';
        $rest = '';

        if ($argStr !== '' && $argStr[0] === '{') {
            $pos = self::findMatchingBrace($argStr, 0);
            if ($pos !== -1) {
                $inner = substr($argStr, 0, $pos + 1);
                $rest = substr($argStr, $pos + 1);
            }
        } else {
            $cut = strpos($argStr, '|');
            if ($cut === false) {
                $inner = $argStr;
                $rest = '';
            } else {
                $inner = substr($argStr, 0, $cut);
                $rest = substr($argStr, $cut);
            }
        }

        $parts = [];
        if ($rest !== '') {
            $rest = ltrim($rest, '|');
            if ($rest !== '') {
                $parts = explode('|', $rest);
            }
        }

        $opts = self::parseOptionTokens($parts);
        return [$inner, $opts];
    }

    /**
     * Parse option tokens into structured data
     */
    public static function parseOptionTokens(array $tokens): array
    {
        $flags = [];
        $kv = [];
        $filters = [];
        $fallback = null;

        foreach ($tokens as $token) {
            $token = trim($token);
            if ($token === '') {
                continue;
            }

            if (strpos($token, ':') !== false || strpos($token, '=') !== false) {
                $sep = (strpos($token, ':') !== false) ? ':' : '=';
                [$key, $value] = array_map('trim', explode($sep, $token, 2));
                
                if ($key === 'default') {
                    $fallback = $value;
                    continue;
                }
                if ($key === 'id') {
                    $kv['id'] = $value;
                    continue;
                }
                if ($key === 'source') {
                    $kv['source'] = $value;
                    continue;
                }
                if (in_array($key, self::KNOWN_FILTERS, true)) {
                    $filters[] = [$key, $value];
                    continue;
                }
                $kv[$key] = $value;
                continue;
            }

            if (in_array($token, self::KNOWN_FILTERS, true)) {
                $filters[] = [$token, null];
                continue;
            }
            if (in_array($token, self::KNOWN_FLAGS, true)) {
                $flags[] = $token;
                continue;
            }

            if ($fallback === null) {
                $fallback = $token;
                continue;
            }
        }

        return [
            'flags' => $flags,
            'kv' => $kv,
            'filters' => $filters,
            'fallback' => $fallback,
        ];
    }

    /**
     * Find matching closing brace for an opening brace
     */
    public static function findMatchingBrace(string $content, int $start): int
    {
        if (!isset($content[$start]) || $content[$start] !== '{') {
            return -1;
        }

        $depth = 0;
        $length = strlen($content);

        for ($i = $start; $i < $length; $i++) {
            if ($content[$i] === '{') {
                $depth++;
            } elseif ($content[$i] === '}') {
                $depth--;
                if ($depth === 0) {
                    return $i;
                }
            }
        }

        return -1;
    }

    /**
     * Parse inner content of a gt_ctx tag
     */
    private static function parseInnerContent(string $content, int $cursor, int $length): string
    {
        if ($cursor >= $length) {
            return '';
        }

        if ($content[$cursor] === '{') {
            $endInner = self::findMatchingBrace($content, $cursor);
            if ($endInner === -1) {
                return '';
            }
            return substr($content, $cursor, $endInner - $cursor + 1);
        }

        $segEnd = $cursor;
        while ($segEnd < $length && $content[$segEnd] !== '|' && $content[$segEnd] !== '}') {
            $segEnd++;
        }

        return substr($content, $cursor, $segEnd - $cursor);
    }

    /**
     * Parse options section of a gt_ctx tag
     */
    private static function parseOptions(string $content, int $cursor, int $length): array
    {
        $optStart = $cursor;
        while ($cursor < $length && $content[$cursor] !== '}') {
            $cursor++;
        }

        $optionsStr = substr($content, $optStart, $cursor - $optStart);
        $parts = [];

        if ($optionsStr !== '') {
            $optionsStr = ltrim($optionsStr, '|');
            if ($optionsStr !== '') {
                $parts = explode('|', $optionsStr);
            }
        }

        return [
            'cursor' => $cursor,
            'parsed' => self::parseOptionTokens($parts)
        ];
    }

    /**
     * Resolve a single gt_ctx tag
     */
    private static function resolveGtCtxTag(string $inner, string $context, array $opts): string
    {
        $hostId = Context::resolveHostPageId(get_post());
        $resolved = $inner;

        if (function_exists('bricks_render_dynamic_data')) {
            $resolved = bricks_render_dynamic_data($inner, $hostId, $context);
        }

        if (Finalizer::isEffectivelyEmpty($resolved) && $opts['fallback'] !== null) {
            $resolved = $opts['fallback'];
        }

        $resolved = Finalizer::applyFiltersChain($resolved, $opts['filters']);
        return Finalizer::finalizeOutput($resolved, $opts['flags'] ?? [], $context);
    }
}
