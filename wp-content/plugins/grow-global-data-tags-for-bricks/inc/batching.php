<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Iterate over post IDs in batches (pages) to reduce memory usage.
 * Returns a flat array of IDs; internally pages through a WP_Query.
 */
if ( ! function_exists( 'grow_data_iter_post_ids' ) ) {
	function grow_data_iter_post_ids( $args ) {
		$ids  = [];
		$page = 1;
		$per  = apply_filters( 'grow_data_query_batch_size', GROW_DATA_BATCH_SIZE );
		if ( ! is_int( $per ) || $per < 1 ) { $per = 100; }

		do {
			$paged_args = $args;
			$paged_args['posts_per_page'] = $per;
			$paged_args['paged']          = $page;
			$paged_args['fields']         = 'ids';
			$paged_args['no_found_rows']  = true;

			$q = new WP_Query( $paged_args );
			if ( ! $q->have_posts() ) { break; }

			$batch = $q->posts; // IDs (fields => ids)
			$ids   = array_merge( $ids, $batch );

			grow_data_log( sprintf( 'Fetched batch %d: %d ids', $page, count( $batch ) ) );

			$page++;
			wp_reset_postdata();
		} while ( true );

		return $ids;
	}
}
