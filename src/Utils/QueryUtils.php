<?php

namespace Art\FakeContent\Utils;

use WP_Query;

final class QueryUtils {

	public static function get_uploaded_images( $meta_key = StringUtils::META_KEY, $meta_value = '1' ): array {

		return get_posts( [
			'post_type'      => 'attachment',
			'post_mime_type' => 'image',
			'posts_per_page' => - 1,
			'post_status'    => 'inherit',
			'fields'         => 'ids',
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'meta_query'     => [
				[
					'key'   => $meta_key,
					'value' => $meta_value,
				],
			],
		] );
	}


	public static function get_terms( string $taxonomy, bool $hide_empty = false ): array {

		return get_terms( [
			'taxonomy'   => $taxonomy,
			'hide_empty' => $hide_empty,
			'fields'     => 'ids',
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'meta_query' => [
				[
					'key'     => StringUtils::META_KEY,
					'compare' => 'EXISTS',
				],
			],
		] );
	}


	public static function get_posts( $post_type ): array {

		$query = new WP_Query(
			[
				'post_type'      => $post_type,
				'post_status'    => 'any',
				'posts_per_page' => - 1,
				'fields'         => 'ids',
				'nopaging'       => true,
				'no_found_rows'  => true,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'meta_query'     => [
					[
						'key'   => StringUtils::META_KEY,
						'value' => '1',
					],
				],
			]
		);

		return $query->posts;
	}
}
