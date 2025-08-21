<?php
namespace Grow\BricksTags\Rendering;

use Grow\BricksTags\Plugin;
use Grow\BricksTags\Tags\GtCtxTag;
use Grow\BricksTags\Tags\GtFieldTag;
use Grow\BricksTags\Support\Parser;

if (!defined('ABSPATH')) { exit; }

final class ContentRenderer {
  // Prevent repeated pre-pass on the same render pipeline
  private static bool $ctxPassActive = false;

  /** Builder: early pre-pass — resolve {gt_ctx:...} first */
  public static function backendPre($content, $post, string $context = 'text') {
    if (!is_string($content) || strpos($content, '{gt_ctx:') === false) return $content;
    if (self::$ctxPassActive) return $content;
    if (!Plugin::enter()) return $content;
    self::$ctxPassActive = true;
    try {
      return Parser::resolveGtCtxWithinString($content, $context);
    } finally {
      self::$ctxPassActive = false;
      Plugin::leave();
    }
  }

  /** Frontend: early pre-pass — resolve {gt_ctx:...} first (signature = ($content, $post, $context)) */
  public static function frontendPre($content, $post, string $context = 'text') {
    if (!is_string($content) || strpos($content, '{gt_ctx:') === false) return $content;
    if (self::$ctxPassActive) return $content;
    if (!Plugin::enter()) return $content;
    self::$ctxPassActive = true;
    try {
      return Parser::resolveGtCtxWithinString($content, $context);
    } finally {
      self::$ctxPassActive = false;
      Plugin::leave();
    }
  }

  /** Builder: fallback replacer — render any remaining {gt_field:...}/{gt_ctx:...} inline */
  public static function backendFallback($content, $post, string $context = 'text') {
    return self::replaceAllOurTags($content, $post, $context);
  }

  /** Frontend: fallback replacer (signature = ($content, $post, $context)) */
  public static function frontendFallback($content, $post, string $context = 'text') {
    return self::replaceAllOurTags($content, $post, $context);
  }

  private static function replaceAllOurTags($content, $post, string $context) {
    if (!is_string($content) || strpos($content, '{gt_') === false) return $content;
    if (!Plugin::enter()) return $content;
    try {
      if (!preg_match_all('/{(gt_(?:field|ctx):[^}]+)}/', $content, $matches, PREG_SET_ORDER)) {
        return $content;
      }
      foreach ($matches as $m) {
        $whole = $m[0];   // with braces
        $inner = $m[1];   // without braces

        if (strpos($inner, Plugin::TAG_FIELD . ':') === 0) {
          $replacement = GtFieldTag::render(substr($inner, strlen(Plugin::TAG_FIELD . ':')), $post, $context);
        } elseif (strpos($inner, Plugin::TAG_CTX . ':') === 0) {
          $replacement = GtCtxTag::render(substr($inner, strlen(Plugin::TAG_CTX . ':')), $post, $context);
        } else {
          $replacement = $whole;
        }

        // SAFE literal single replacement: avoid preg replacement so $ and \ aren't treated as backrefs/escapes
        $pos = strpos($content, $whole);
        if ($pos !== false) {
          $content = substr_replace($content, (string)$replacement, $pos, strlen($whole));
        }
      }
      return $content;
    } finally {
      Plugin::leave();
    }
  }
}
