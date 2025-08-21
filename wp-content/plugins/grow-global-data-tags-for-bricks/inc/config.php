<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Config & feature flags (guarded so site owners can override in wp-config.php)
 */
if ( ! defined( 'GROW_DATA_TAG_PREFIX' ) )         define( 'GROW_DATA_TAG_PREFIX', 'grow_data_' );
if ( ! defined( 'GROW_DATA_TOKEN_DELIM' ) )        define( 'GROW_DATA_TOKEN_DELIM', '_grow_data_' );
if ( ! defined( 'GROW_DATA_TAG_GROUP_DEFAULT' ) )  define( 'GROW_DATA_TAG_GROUP_DEFAULT', 'Global Data' );
if ( ! defined( 'GROW_DATA_TRANSIENT' ) )          define( 'GROW_DATA_TRANSIENT',  'grow_global_data_registry_v1' );
if ( ! defined( 'GROW_DATA_CACHE_TTL' ) )          define( 'GROW_DATA_CACHE_TTL',  5 * MINUTE_IN_SECONDS );
if ( ! defined( 'GROW_DATA_DEBUG' ) )              define( 'GROW_DATA_DEBUG',      defined('WP_DEBUG') && WP_DEBUG );
if ( ! defined( 'GROW_DATA_INCLUDE_EMPTY' ) )      define( 'GROW_DATA_INCLUDE_EMPTY', false );
if ( ! defined( 'GROW_DATA_BATCH_SIZE' ) )         define( 'GROW_DATA_BATCH_SIZE', 100 ); // default query page size
