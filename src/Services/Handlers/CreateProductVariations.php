<?php

namespace Art\FakeContent\Services\Handlers;

use Art\FakeContent\Utils\RandomUtils;
use Art\FakeContent\Utils\WoocommerceUtils;
use WC_Product;
use WC_Product_Variation;

class CreateProductVariations {

	const MAX_VARIATIONS = 100;


	protected WC_Product $parent_product;


	protected int $parent_id;


	public function __construct( $parent_product, $parent_id ) {

		$this->parent_product = $parent_product;
		$this->parent_id      = $parent_id;
	}


	/**
	 * @throws \WC_Data_Exception  выбрасываем исключение при ошибке.
	 */
	public function run(): void {

		$variation_attributes = $this->prepare_variation_attributes();

		if ( empty( $variation_attributes ) ) {
			return;
		}

		$combinations = wc_array_cartesian( $variation_attributes );

		if ( count( $combinations ) > self::MAX_VARIATIONS ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( "Skipping variations for product $this->parent_id: too many combinations (" . count( $combinations ) . ')' );

			return;
		}

		$default_variation_data = $this->create_variations_from_combinations( $combinations );

		if ( null !== $default_variation_data ) {
			$this->set_as_default_variation( $default_variation_data['attributes'] );
		}
	}


	private function prepare_variation_attributes(): array {

		$variation_attributes = [];

		foreach ( $this->parent_product->get_attributes() as $attribute ) {
			if ( ! $attribute->get_variation() ) {
				continue;
			}

			$terms = $attribute->get_options();
			if ( empty( $terms ) ) {
				continue;
			}

			$term_names = [];
			foreach ( $terms as $term_id ) {
				$term = get_term( $term_id );
				if ( $term && ! is_wp_error( $term ) ) {
					$term_names[] = $term->name;
				}
			}

			if ( ! empty( $term_names ) ) {
				$variation_attributes[ $attribute->get_name() ] = RandomUtils::get_random_items( 5, $term_names );
			}
		}

		return $variation_attributes;
	}


	/**
	 * @throws \WC_Data_Exception выбрасываем исключение при ошибке.
	 */
	private function create_variations_from_combinations( array $combinations ): ?array {

		$default_variation_data = null;

		foreach ( $combinations as $combination ) {
			$variation   = $this->create_single_variation( $combination );
			$variation_id = $variation->save();

			if ( null === $default_variation_data ) {
				$default_variation_data = [
					'variation_id' => $variation_id,
					'attributes'   => $this->sanitize_combination_attributes( $combination ),
				];
			}
		}

		return $default_variation_data;
	}


	/**
	 * @throws \WC_Data_Exception выбрасываем исключение при ошибке.
	 */
	private function create_single_variation( array $combination ): WC_Product_Variation {

		$variation = new WC_Product_Variation();

		$variation->set_parent_id( $this->parent_id );

		WoocommerceUtils::set_random_price( $variation );
		WoocommerceUtils::set_random_stock( $variation );
		WoocommerceUtils::set_random_images( $variation );

		$attributes = $this->sanitize_combination_attributes( $combination );

		$variation->set_attributes( $attributes );
		$variation->set_status( 'publish' );
		$variation->set_sku( WoocommerceUtils::generate_sku( $variation->get_type() ) );

		return $variation;
	}


	private function sanitize_combination_attributes( array $combination ): array {

		$sanitized = [];
		foreach ( $combination as $attribute_name => $value ) {
			$sanitized[ $attribute_name ] = sanitize_title( $value );
		}

		return $sanitized;
	}


	private function set_as_default_variation( array $attributes ): void {

		$this->parent_product->set_default_attributes( $attributes );
		$this->parent_product->save();
	}
}
