<?php
namespace Grow\BricksTags\Support;

if (!defined('ABSPATH')) { exit; }

final class Context {
  private static ?int $hostIdCache = null;

  /** resolve Page 2 (host page / queried object) with caching */
  public static function resolveHostPageId($post): int {
    if (self::$hostIdCache !== null) return self::$hostIdCache;

    $host_id = get_queried_object_id();
    if (!$host_id) {
      $global_post = get_post();
      if ($global_post instanceof \WP_Post) $host_id = $global_post->ID;
    }
    if (!$host_id && $post instanceof \WP_Post) $host_id = $post->ID;
    if (!$host_id) $host_id = get_the_ID();

    self::$hostIdCache = (int)$host_id;
    return self::$hostIdCache;
  }

  public static function inferTermId(): int {
    $qo = get_queried_object();
    if ($qo && isset($qo->term_id)) return (int)$qo->term_id;
    return 0;
  }

  public static function inferUserId(): int {
    $u = wp_get_current_user();
    return ($u && $u->exists()) ? (int)$u->ID : 0;
  }
}
