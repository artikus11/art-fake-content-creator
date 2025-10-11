<?php

namespace Art\FakeContent\Services\Handlers;

use Art\FakeContent\Utils\RandomUtils;
use WC_Product_Attribute;

class AttachmentAttributes {

	protected array $config;


	private array $term_cache = [];


	public function __construct( $config ) {

		$this->config = $config;
	}


	public function generate_attributes(): array {

		$this->clear_attribute_cache();

		$product_attributes = [];
		$attributes_to_use  = RandomUtils::get_random_array( $this->config, 0, 5 );

		if ( $attributes_to_use > 0 ) {
			foreach ( $attributes_to_use as $attribute_slug => $attribute_data ) {
				$attribute = $this->create_product_attribute( $attribute_slug, $attribute_data );
				if ( $attribute ) {
					$product_attributes[] = $attribute;
				}
			}
		}

		return $product_attributes;
	}


	private function create_product_attribute( string $slug, array $data ): ?WC_Product_Attribute {

		if ( ! taxonomy_exists( $slug ) ) {
			return null;
		}

		$terms_to_use = RandomUtils::get_random_array( $data['terms'] );
		$term_ids     = $this->get_attribute_terms( $slug, $terms_to_use );

		if ( empty( $term_ids ) ) {
			return null;
		}

		$sorted_term_ids = $this->sort_term_ids_by_original_order( $term_ids, $slug, $data['terms'] );

		$attribute = new WC_Product_Attribute();
		$attribute->set_id( wc_attribute_taxonomy_id_by_name( $slug ) );
		$attribute->set_name( $slug );
		$attribute->set_options( $sorted_term_ids );
		$attribute->set_position( 0 );
		$attribute->set_visible( true );

		if ( ! empty( $data['variation'] ) ) {
			$attribute->set_variation( $data['variation'] );
		}

		return $attribute;
	}


	private function get_attribute_terms( string $taxonomy, array $terms ): array {

		$cache_key = $taxonomy . '|' . md5( maybe_serialize( $terms ) );

		if ( isset( $this->term_cache[ $cache_key ] ) ) {
			return $this->term_cache[ $cache_key ];
		}

		$term_ids           = [];
		$processed_terms    = [];
		$term_order_mapping = [];

		foreach ( $terms as $order => $term_name ) {
			$term_name = trim( $term_name );
			$term_slug = sanitize_title( $term_name );

			if ( isset( $processed_terms[ $term_slug ] ) ) {
				continue;
			}

			$processed_terms[ $term_slug ] = true;

			$existing_term = get_term_by( 'slug', $term_slug, $taxonomy ) ? :
				get_term_by( 'name', $term_name, $taxonomy );

			if ( $existing_term && ! is_wp_error( $existing_term ) ) {
				$term_ids[]                                    = $existing_term->term_id;
				$term_order_mapping[ $existing_term->term_id ] = $order;
				continue;
			}

			$new_term = wp_insert_term( $term_name, $taxonomy, [
				'slug'        => $term_slug,
				'description' => '',
			] );

			if ( is_wp_error( $new_term ) ) {
				if ( $new_term->get_error_code() === 'term_exists' ) {
					$term_id                        = $new_term->get_error_data();
					$term_ids[]                    = $term_id;
					$term_order_mapping[ $term_id ] = $order;
				}
				continue;
			}

			$term_ids[] = $new_term['term_id'];

			$term_order_mapping[ $new_term['term_id'] ] = $order;
		}

		$this->set_terms_order( $taxonomy, $term_order_mapping );
		$this->term_cache[ $cache_key ] = $term_ids;

		return $term_ids;
	}


	private function sort_term_ids_by_original_order( array $term_ids, string $taxonomy, array $original_terms ): array {

		$term_map = [];
		foreach ( $term_ids as $term_id ) {
			$term = get_term( $term_id, $taxonomy );
			if ( $term && ! is_wp_error( $term ) ) {
				$term_map[ $term->slug ] = $term_id;
			}
		}

		$sorted_ids = [];
		foreach ( $original_terms as $term_name ) {
			$slug = sanitize_title( trim( $term_name ) );
			if ( isset( $term_map[ $slug ] ) ) {
				$sorted_ids[] = $term_map[ $slug ];
			}
		}

		return $sorted_ids;
	}


	private function set_terms_order( string $taxonomy, array $term_order_mapping ): void {

		if ( ! function_exists( 'wc_set_term_order' ) ) {
			return;
		}

		foreach ( $term_order_mapping as $term_id => $order ) {
			wc_set_term_order( $term_id, $order, $taxonomy );
		}
	}


	private function clear_attribute_cache(): void {

		delete_transient( 'wc_attribute_taxonomies' );
		wp_cache_flush();
	}
}
