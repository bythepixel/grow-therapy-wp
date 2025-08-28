<?php

declare(strict_types=1);

namespace Grow\BricksTags\Support;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Context resolution utilities for determining host page and object IDs
 */
final class Context
{
    private static ?int $hostIdCache = null;
    private static ?int $termIdCache = null;
    private static ?int $userIdCache = null;

    /**
     * Resolve the host page ID (queried object) with caching
     * This is the page where the FAQ is being displayed
     */
    public static function resolveHostPageId($post): int
    {
        if (self::$hostIdCache !== null) {
            return self::$hostIdCache;
        }

        // Try to get the queried object ID first
        $hostId = get_queried_object_id();
        
        if (!$hostId) {
            $globalPost = get_post();
            if ($globalPost instanceof \WP_Post) {
                $hostId = $globalPost->ID;
            }
        }
        
        if (!$hostId && $post instanceof \WP_Post) {
            $hostId = $post->ID;
        }
        
        if (!$hostId) {
            $hostId = get_the_ID();
        }

        self::$hostIdCache = (int) $hostId;
        return self::$hostIdCache;
    }

    /**
     * Get the current term ID with caching
     */
    public static function inferTermId(): int
    {
        if (self::$termIdCache !== null) {
            return self::$termIdCache;
        }

        $queriedObject = get_queried_object();
        if ($queriedObject && isset($queriedObject->term_id)) {
            self::$termIdCache = (int) $queriedObject->term_id;
            return self::$termIdCache;
        }

        self::$termIdCache = 0;
        return 0;
    }

    /**
     * Get the current user ID with caching
     */
    public static function inferUserId(): int
    {
        if (self::$userIdCache !== null) {
            return self::$userIdCache;
        }

        $user = wp_get_current_user();
        if ($user && $user->exists()) {
            self::$userIdCache = (int) $user->ID;
            return self::$userIdCache;
        }

        self::$userIdCache = 0;
        return 0;
    }

    /**
     * Clear all caches (useful for testing or when context changes)
     */
    public static function clearCache(): void
    {
        self::$hostIdCache = null;
        self::$termIdCache = null;
        self::$userIdCache = null;
    }
}
