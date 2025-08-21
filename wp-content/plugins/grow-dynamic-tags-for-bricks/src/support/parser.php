<?php
namespace Grow\BricksTags\Support;

use Grow\BricksTags\Rendering\Finalizer;

if (!defined('ABSPATH')) { exit; }

final class Parser {

  /** Replace all {gt_ctx:...} inside a string with resolved values (host page scope), applying filters/flags/fallbacks. */
  public static function resolveGtCtxWithinString(string $s, string $context): string {
    if (strpos($s, '{gt_ctx:') === false) return $s;

    $out = '';
    $i = 0; $len = strlen($s);
    // Hard cap to avoid pathological scans
    if ($len > 100000) { return $s; }

    while ($i < $len) {
      $start = strpos($s, '{gt_ctx:', $i);
      if ($start === false) { $out .= substr($s, $i); break; }

      $out .= substr($s, $i, $start - $i);
      $cursor = $start + strlen('{gt_ctx:');

      // inner part
      $inner = '';
      if ($cursor < $len && $s[$cursor] === '{') {
        $endInner = self::findMatchingBrace($s, $cursor);
        if ($endInner === -1) { // malformed, copy literally
          $out .= substr($s, $start, 1);
          $i = $start + 1;
          continue;
        }
        $inner = substr($s, $cursor, $endInner - $cursor + 1);
        $cursor = $endInner + 1;
      } else {
        $segEnd = $cursor;
        while ($segEnd < $len && $s[$segEnd] !== '|' && $s[$segEnd] !== '}') $segEnd++;
        $inner = substr($s, $cursor, $segEnd - $cursor);
        $cursor = $segEnd;
      }

      // options until closing '}'
      $optStart = $cursor;
      while ($cursor < $len && $s[$cursor] !== '}') $cursor++;
      if ($cursor >= $len) { $out .= substr($s, $start); $i = $len; break; }

      $optionsStr = substr($s, $optStart, $cursor - $optStart); // may start with '|'
      $parts = [];
      if ($optionsStr !== '') {
        $optionsStr = ltrim($optionsStr, '|');
        if ($optionsStr !== '') $parts = explode('|', $optionsStr);
      }
      $opts = self::parseOptionTokens($parts);

      // Resolve this one {gt_ctx:...}
      $host_id = Context::resolveHostPageId(get_post());
      $resolved = $inner;
      if (function_exists('bricks_render_dynamic_data')) {
        $resolved = bricks_render_dynamic_data($inner, $host_id, $context);
      }
      if (Finalizer::isEffectivelyEmpty($resolved) && $opts['fallback'] !== null) {
        $resolved = $opts['fallback'];
      }
      $resolved = Finalizer::applyFiltersChain($resolved, $opts['filters']);
      $resolved = Finalizer::finalizeOutput($resolved, $opts['flags'] ?? [], $context);

      $out .= $resolved;
      $i = $cursor + 1; // past '}'
    }
    return $out;
  }

  /** Parse a full {gt_ctx:...} tag's arg string into [inner, options] */
  public static function parseGtCtxTag(string $argStr): array {
    $argStr = (string)$argStr;
    $inner = ''; $rest = '';

    if ($argStr !== '' && $argStr[0] === '{') {
      $pos = self::findMatchingBrace($argStr, 0);
      if ($pos !== -1) { $inner = substr($argStr, 0, $pos + 1); $rest = substr($argStr, $pos + 1); }
    } else {
      $cut = strpos($argStr, '|');
      if ($cut === false) { $inner = $argStr; $rest = ''; }
      else { $inner = substr($argStr, 0, $cut); $rest = substr($argStr, $cut); }
    }

    $parts = [];
    if ($rest !== '') {
      $rest = ltrim($rest, '|');
      if ($rest !== '') $parts = explode('|', $rest);
    }
    $opts = self::parseOptionTokens($parts);
    return [$inner, $opts];
  }

  /** Token parser for flags/kv/filters/fallback */
  public static function parseOptionTokens(array $tokens): array {
    $flags = []; $kv = []; $filters = []; $fallback = null;

    $known = ['trim','lower','upper','title','ucfirst','strip_tags','nl2br','escape_attr','json','length','unique','join','number','date'];
    foreach ($tokens as $tok) {
      $tok = trim($tok); if ($tok === '') continue;

      if (strpos($tok, ':') !== false || strpos($tok, '=') !== false) {
        $sep = (strpos($tok, ':') !== false) ? ':' : '=';
        [$k, $v] = array_map('trim', explode($sep, $tok, 2));
        if ($k === 'default') { $fallback = $v; continue; }
        if ($k === 'id')      { $kv['id'] = $v; continue; }
        if ($k === 'source')  { $kv['source'] = $v; continue; }
        if (in_array($k, $known, true)) { $filters[] = [$k, $v]; continue; }
        $kv[$k] = $v; continue;
      }

      if (in_array($tok, $known, true)) { $filters[] = [$tok, null]; continue; }
      if (in_array($tok, ['raw','noescape'], true)) { $flags[] = $tok; continue; }

      if ($fallback === null) { $fallback = $tok; continue; }
    }

    return [
      'flags'    => $flags,
      'kv'       => $kv,
      'filters'  => $filters,
      'fallback' => $fallback,
    ];
  }

  /** Find matching '}' for a '{' starting at $start (single-nesting aware) */
  public static function findMatchingBrace(string $s, int $start): int {
    if (!isset($s[$start]) || $s[$start] !== '{') return -1;
    $depth = 0; $len = strlen($s);
    for ($i = $start; $i < $len; $i++) {
      if ($s[$i] === '{') { $depth++; }
      elseif ($s[$i] === '}') {
        $depth--;
        if ($depth === 0) return $i;
      }
    }
    return -1;
  }
}
