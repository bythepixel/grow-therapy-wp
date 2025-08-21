<?php
namespace Grow\BricksTags;

use Grow\BricksTags\Rendering\ContentRenderer;
use Grow\BricksTags\Tags\GtCtxTag;
use Grow\BricksTags\Tags\GtFieldTag;

if (!defined('ABSPATH')) { exit; }

final class Plugin {
  // Public tag slugs
  public const TAG_FIELD = 'gt_field';
  public const TAG_CTX   = 'gt_ctx';

  // Recursion guard (global)
  private static int $depth = 0;
  private const MAX_DEPTH = 8;

  // Optional diagnostics
  private const DEBUG = false;

  public static function init(): void {
    // 1) Register tags for Bricks UI/parser (register both bare + brace for compatibility)
    add_filter('bricks/dynamic_tags_list', [self::class, 'registerTags']);

    // 2) Tag-level resolver (Bricks calls this for each discovered tag)
    add_filter('bricks/dynamic_data/render_tag', [self::class, 'renderTag'], 20, 3);

    // 3) Early pre-pass — resolve {gt_ctx:...} BEFORE other tags
    add_filter('bricks/dynamic_data/render_content', [ContentRenderer::class, 'backendPre'], 1, 3);
    add_filter('bricks/frontend/render_data',       [ContentRenderer::class, 'frontendPre'], 1, 3);   // <— was 2

    // 4) Fallback content replacer — guarantee our tags render anywhere
    add_filter('bricks/dynamic_data/render_content', [ContentRenderer::class, 'backendFallback'], 20, 3);
    add_filter('bricks/frontend/render_data',       [ContentRenderer::class, 'frontendFallback'], 20, 3); // <— was 2
  }

  /** Show tags in the Bricks picker */
  public static function registerTags(array $tags): array {
    // Dual registration (brace + bare)
    $tags[] = ['name' => self::TAG_FIELD,              'label' => 'GT • Meta (parse inner tags)', 'group' => 'Grow'];
    $tags[] = ['name' => '{' . self::TAG_FIELD . '}',  'label' => 'GT • Meta (parse inner tags)', 'group' => 'Grow'];
    $tags[] = ['name' => self::TAG_CTX,                'label' => 'GT • Host Page Context',        'group' => 'Grow'];
    $tags[] = ['name' => '{' . self::TAG_CTX . '}',    'label' => 'GT • Host Page Context',        'group' => 'Grow'];
    return $tags;
  }

  /** Dispatch Bricks dynamic tag → our implementation */
  public static function renderTag($tag, $post, string $context = 'text') {
    if (!is_string($tag)) return $tag;
    if (!self::enter()) return $tag;
    try {
      $clean = trim($tag, '{}');

      if (strpos($clean, self::TAG_FIELD . ':') === 0) {
        return GtFieldTag::render(substr($clean, strlen(self::TAG_FIELD . ':')), $post, $context);
      }
      if (strpos($clean, self::TAG_CTX . ':') === 0) {
        return GtCtxTag::render(substr($clean, strlen(self::TAG_CTX . ':')), $post, $context);
      }
      return $tag;
    } finally {
      self::leave();
    }
  }

  /** Recursion guard enter */
  public static function enter(): bool {
    if (self::$depth >= self::MAX_DEPTH) {
      if (self::DEBUG) error_log('[GT Tags] depth cap reached; aborting render step');
      return false;
    }
    self::$depth++;
    return true;
  }

  /** Recursion guard leave */
  public static function leave(): void {
    if (self::$depth > 0) self::$depth--;
  }
}
