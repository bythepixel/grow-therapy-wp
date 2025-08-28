<?php

declare(strict_types=1);

namespace Grow\BricksTags\Rendering;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Final output processing and formatting utilities
 * 
 * Handles output sanitization, formatting filters, and context-specific
 * output shaping for dynamic tag content.
 */
final class Finalizer
{
    /**
     * Finalize output with proper sanitization and formatting
     * 
     * @param mixed $value The value to process
     * @param array $flags Processing flags (raw, noescape)
     * @param string $context The rendering context
     * @return mixed The processed value
     */
    public static function finalizeOutput($value, array $flags, string $context)
    {
        // Handle image context arrays
        if ($context === 'image' && is_scalar($value) && preg_match('/^\d+$/', (string) $value)) {
            return [(int) $value];
        }

        // Sanitize unless raw/noescape flags are set
        if (!in_array('raw', $flags, true) && !in_array('noescape', $flags, true)) {
            $value = self::sanitizeValue($value);
        }

        // Convert arrays to readable strings
        if (is_array($value)) {
            $value = implode(', ', array_map('strval', $value));
        }

        return $value;
    }

    /**
     * Check if a value is effectively empty
     */
    public static function isEffectivelyEmpty($value): bool
    {
        if (is_array($value)) {
            return count($value) === 0;
        }
        if ($value === null) {
            return true;
        }
        if (is_string($value)) {
            return trim($value) === '';
        }
        return false;
    }

    /**
     * Apply a chain of formatting filters to a value
     */
    public static function applyFiltersChain($value, array $filters)
    {
        foreach ($filters as $filter) {
            [$name, $arg] = $filter + array_pad($filter, 2, null);
            $value = self::applyFilter($value, $name, $arg);
        }
        return $value;
    }

    /**
     * Apply a single filter to a value
     */
    private static function applyFilter($value, string $name, $arg)
    {
        switch ($name) {
            case 'trim':
                return is_string($value) ? trim($value) : $value;
                
            case 'lower':
                return is_string($value) ? self::mbStrToLower($value) : $value;
                
            case 'upper':
                return is_string($value) ? self::mbStrToUpper($value) : $value;
                
            case 'title':
                return is_string($value) ? self::mbConvertCase($value, MB_CASE_TITLE) : $value;
                
            case 'ucfirst':
                return is_string($value) ? self::mbUcFirst($value) : $value;
                
            case 'strip_tags':
                return is_string($value) ? strip_tags($value) : $value;
                
            case 'nl2br':
                return is_string($value) ? nl2br($value) : $value;
                
            case 'escape_attr':
                return is_string($value) ? esc_attr($value) : $value;
                
            case 'json':
                return wp_json_encode($value);
                
            case 'length':
                return self::getLength($value);
                
            case 'unique':
                return is_array($value) ? array_values(array_unique($value)) : $value;
                
            case 'join':
                return self::joinArray($value, $arg);
                
            case 'number':
                return self::formatNumber($value, $arg);
                
            case 'date':
                return self::formatDate($value, $arg);
                
            default:
                return $value;
        }
    }

    /**
     * Sanitize a value using WordPress functions
     */
    private static function sanitizeValue($value)
    {
        if (is_array($value)) {
            return array_map(static function ($v) {
                return is_string($v) ? wp_kses_post($v) : $v;
            }, $value);
        }
        
        if (is_string($value)) {
            return wp_kses_post($value);
        }
        
        return $value;
    }

    /**
     * Multi-byte string to lowercase with fallback
     */
    private static function mbStrToLower(string $value): string
    {
        return function_exists('mb_strtolower') ? mb_strtolower($value) : strtolower($value);
    }

    /**
     * Multi-byte string to uppercase with fallback
     */
    private static function mbStrToUpper(string $value): string
    {
        return function_exists('mb_strtoupper') ? mb_strtoupper($value) : strtoupper($value);
    }

    /**
     * Multi-byte case conversion with fallback
     */
    private static function mbConvertCase(string $value, int $mode): string
    {
        return function_exists('mb_convert_case') 
            ? mb_convert_case($value, $mode, 'UTF-8') 
            : ucwords(strtolower($value));
    }

    /**
     * Multi-byte uppercase first with fallback
     */
    private static function mbUcFirst(string $value): string
    {
        if ($value === '') {
            return $value;
        }
        
        $first = function_exists('mb_substr') ? mb_substr($value, 0, 1) : substr($value, 0, 1);
        $rest = function_exists('mb_substr') ? mb_substr($value, 1) : substr($value, 1);
        
        $firstUpper = function_exists('mb_strtoupper') ? mb_strtoupper($first) : strtoupper($first);
        return $firstUpper . $rest;
    }

    /**
     * Get length of value
     */
    private static function getLength($value): int
    {
        if (is_array($value)) {
            return count($value);
        }
        
        if (is_string($value)) {
            return function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
        }
        
        return 0;
    }

    /**
     * Join array with separator
     */
    private static function joinArray($value, $arg): string
    {
        if (!is_array($value)) {
            return (string) $value;
        }
        
        $separator = $arg !== null ? (string) $arg : ', ';
        return implode($separator, array_map('strval', $value));
    }

    /**
     * Format number with decimal places
     */
    private static function formatNumber($value, $arg): string
    {
        if (!is_numeric($value)) {
            return (string) $value;
        }
        
        $decimals = is_numeric($arg) ? (int) $arg : 0;
        return number_format((float) $value, $decimals, '.', ',');
    }

    /**
     * Format date with specified format
     */
    private static function formatDate($value, $arg): string
    {
        $format = ($arg !== null && $arg !== '') ? (string) $arg : 'Y-m-d';
        $timestamp = self::getTimestamp($value);
        
        if ($timestamp === null) {
            return (string) $value;
        }
        
        return date_i18n($format, $timestamp);
    }

    /**
     * Get timestamp from various input types
     */
    private static function getTimestamp($value): ?int
    {
        if (is_numeric($value)) {
            return (int) $value;
        }
        
        if (is_string($value) && $value !== '') {
            $timestamp = strtotime($value);
            return $timestamp !== false ? $timestamp : null;
        }
        
        return null;
    }
}
