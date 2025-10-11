<?php

namespace Art\FakeContent\Utils;

use Automattic\WooCommerce\Enums\ProductType;
use WC_Product;

final class WoocommerceUtils {

	/**
	 * Возвращает массив разрешенных для генерации taxonomies
	 *
	 * @return array
	 */
	public static function get_allowed_taxonomies(): array {

		return [ 'product_cat', 'product_brand', 'product_tag' ];
	}


	/**
	 * @param  \WC_Product $product
	 *
	 * @return void
	 */
	public static function set_random_stock( WC_Product $product ): void {

		$product->set_stock_quantity( RandomUtils::get_random_int( 2, 20, 10 ) );
		$product->set_manage_stock( RandomUtils::get_random_bool() );
		$product->set_stock_status( RandomUtils::get_random_item( array_keys( wc_get_product_stock_status_options() ) ) );
	}


	/**
	 * @param  \WC_Product $product
	 *
	 * @return void
	 */
	public static function set_random_price( WC_Product $product ): void {

		$product->set_regular_price( RandomUtils::get_random_int() );

		if ( $product->get_regular_price() && $product->get_regular_price() > 0 ) {
			$should_apply_discount = wp_rand( 1, 100 ) <= 80;

			if ( $should_apply_discount ) {
				$discount_price = round( $product->get_regular_price() * 0.8 );
				$product->set_sale_price( $discount_price );
			}
		}
	}


	public static function set_random_images( WC_Product $product ): void {

		$image_ids = QueryUtils::get_uploaded_images();

		if ( empty( $image_ids ) ) {
			return;
		}

		$product->set_image_id( RandomUtils::get_random_item( $image_ids ) );

		if ( $product->is_type( ProductType::VARIATION ) ) {
			return;
		}

		$gallery = array_diff( $image_ids, [ $product->get_image_id() ] );

		$product->set_gallery_image_ids( RandomUtils::get_random_array( $gallery, 0 ) );
	}


	public static function set_random_upsells( WC_Product $product, $limit = 5 ): void {

		$args = [
			'limit'   => $limit,
			'status'  => 'publish',
			'return'  => 'ids',
			'orderby' => 'rand',
			'exclude' => [ $product->get_id() ],
		];

		$upsell_ids = wc_get_products( $args );

		if ( ! empty( $upsell_ids ) ) {
			$product->set_upsell_ids( $upsell_ids );
			$product->save();
		}
	}


	public static function set_random_taxonomy( WC_Product $product, string $taxonomy, $limit = null ): void {

		$terms = QueryUtils::get_terms( $taxonomy );

		if ( ! empty( $terms ) ) {
			$terms = array_map( 'intval', $terms );

			wp_set_object_terms( $product->get_id(), RandomUtils::get_random_array( $terms, 1, $limit ), $taxonomy );
		}
	}


	/**
	 * Генерирует короткий уникальный SKU без привязки к названию.
	 *
	 * @param  string $prefix  (Опционально) Префикс: 'prod', 'var', 'book' и т.д.
	 * @param  int    $max_len Макс. длина итогового SKU (до добавления суффикса уникальности). По умолчанию 16.
	 *
	 * @return string Уникальный SKU.
	 */
	public static function generate_sku( string $prefix = 'prod', int $max_len = 16 ): string {

		$base = $prefix;
		if ( strlen( $base ) < $max_len - 4 ) {
			$suffix = substr( wp_hash( uniqid() ), - 4 );
			$base   .= '-' . $suffix;
		}

		if ( strlen( $base ) > $max_len ) {
			$base = substr( $base, 0, $max_len );
			$base = rtrim( $base, '-' );
		}

		return wc_product_generate_unique_sku( 0, $base );
	}
}
