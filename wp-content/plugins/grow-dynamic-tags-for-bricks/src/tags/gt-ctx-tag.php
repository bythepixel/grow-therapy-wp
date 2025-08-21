<?php
namespace Grow\BricksTags\Tags;

use Grow\BricksTags\Rendering\Finalizer;
use Grow\BricksTags\Support\Context;
use Grow\BricksTags\Support\Parser;

if (!defined('ABSPATH')) { exit; }

final class GtCtxTag {
  /**
   * {gt_ctx:{...}[|DEFAULT][|filters]}
   * Resolve the inner string in host-page (queried object) context.
   */
  public static function render(string $argStr, $post, string $context) {
    // Parse inner + options (supports inner literal or inner {...})
    [$inner, $opts] = Parser::parseGtCtxTag($argStr);

    $host_id = Context::resolveHostPageId($post);
    $value   = $inner;

    if (function_exists('bricks_render_dynamic_data')) {
      $value = bricks_render_dynamic_data($inner, $host_id, $context);
    }

    if (Finalizer::isEffectivelyEmpty($value) && $opts['fallback'] !== null) {
      $value = $opts['fallback'];
    }

    $value = Finalizer::applyFiltersChain($value, $opts['filters']);
    return Finalizer::finalizeOutput($value, $opts['flags'] ?? [], $context);
  }
}
