<?php
namespace Grow\BricksTags\Rendering;

if (!defined('ABSPATH')) { exit; }

final class Finalizer {

  /** final output shaping (image context arrays, sanitize unless raw/noescape, implode arrays) */
  public static function finalizeOutput($value, array $flags, string $context) {
    // Image context wants array of IDs
    if ($context === 'image' && is_scalar($value) && preg_match('/^\d+$/', (string)$value)) {
      return [ (int)$value ];
    }

    // Sanitize unless raw/noescape
    if (!in_array('raw', $flags, true) && !in_array('noescape', $flags, true)) {
      if (is_array($value)) {
        $value = array_map(static function ($v) {
          return is_string($v) ? wp_kses_post($v) : $v;
        }, $value);
      } elseif (is_string($value)) {
        $value = wp_kses_post($value);
      }
    }

    // Readable string if still array
    if (is_array($value)) {
      $value = implode(', ', array_map('strval', $value));
    }
    return $value;
  }

  /** empty-ness check for fallbacks */
  public static function isEffectivelyEmpty($v): bool {
    if (is_array($v)) return count($v) === 0;
    if ($v === null) return true;
    if (is_string($v)) return trim($v) === '';
    return false;
  }

  /** value transformation filters (trim/lower/upper/join/number/date/etc.) */
  public static function applyFiltersChain($value, array $filters) {
    foreach ($filters as $f) {
      [$name, $arg] = $f + [null, null];
      switch ($name) {
        case 'trim':
          if (is_string($value)) $value = trim($value);
          break;
        case 'lower':
          if (is_string($value)) $value = function_exists('mb_strtolower') ? mb_strtolower($value) : strtolower($value);
          break;
        case 'upper':
          if (is_string($value)) $value = function_exists('mb_strtoupper') ? mb_strtoupper($value) : strtoupper($value);
          break;
        case 'title':
          if (is_string($value)) {
            $value = function_exists('mb_convert_case') ? mb_convert_case($value, MB_CASE_TITLE, 'UTF-8') : ucwords(strtolower($value));
          }
          break;
        case 'ucfirst':
          if (is_string($value) && $value !== '') {
            $first = function_exists('mb_substr') ? mb_substr($value, 0, 1) : substr($value, 0, 1);
            $rest  = function_exists('mb_substr') ? mb_substr($value, 1)   : substr($value, 1);
            $value = (function_exists('mb_strtoupper') ? mb_strtoupper($first) : strtoupper($first)) . $rest;
          }
          break;
        case 'strip_tags':
          if (is_string($value)) $value = strip_tags($value);
          break;
        case 'nl2br':
          if (is_string($value)) $value = nl2br($value);
          break;
        case 'escape_attr':
          if (is_string($value)) $value = esc_attr($value);
          break;
        case 'json':
          $value = wp_json_encode($value);
          break;
        case 'length':
          if (is_array($value)) $value = count($value);
          else $value = is_string($value) ? (function_exists('mb_strlen') ? mb_strlen($value) : strlen($value)) : 0;
          break;
        case 'unique':
          if (is_array($value)) $value = array_values(array_unique($value));
          break;
        case 'join':
          if (is_array($value)) {
            $sep = isset($arg) ? (string)$arg : ', ';
            $value = implode($sep, array_map('strval', $value));
          }
          break;
        case 'number':
          $dec = is_numeric($arg) ? (int)$arg : 0;
          if (is_numeric($value)) $value = number_format((float)$value, $dec, '.', ',');
          break;
        case 'date':
          $fmt = ($arg !== null && $arg !== '') ? (string)$arg : 'Y-m-d';
          $ts  = null;
          if (is_numeric($value)) {
            $ts = (int)$value;
          } elseif (is_string($value) && $value !== '') {
            $try = strtotime($value);
            if ($try !== false) $ts = $try;
          }
          if ($ts !== null) $value = date_i18n($fmt, $ts);
          break;
      }
    }
    return $value;
  }
}
