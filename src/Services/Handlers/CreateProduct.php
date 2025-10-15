<?php

namespace Art\FakeContent\Services\Handlers;

use Art\FakeContent\Utils\RandomUtils;
use Art\FakeContent\Utils\StringUtils;
use Art\FakeContent\Utils\WoocommerceUtils;
use Automattic\WooCommerce\Enums\ProductType;
use WC_Product;

class CreateProduct {

	protected array $config;


	protected string $type;


	public WC_Product $product;


	public int $product_id;


	protected ?string $attr_to_use;


	public function __construct( $config, $type ) {

		$this->config      = $config;
		$this->type        = $type;
		$this->attr_to_use = $this->config['attr_to_use'];
	}


	/**
	 *
	 * @throws \WC_Data_Exception  выбрасываем исключение при ошибке.
	 */
	public function run(): void {

		$this->product = wc_get_product_object( $this->type );

		$this->set_base_properties();
		$this->set_meta_data();

		WoocommerceUtils::set_random_price( $this->product );

		if ( ! $this->product->is_type( ProductType::VARIABLE ) ) {
			WoocommerceUtils::set_random_stock( $this->product );
		}

		$this->set_attributes();

		$this->product->set_sku( WoocommerceUtils::generate_sku( $this->type ) );

		$this->product_id = $this->product->save();

		WoocommerceUtils::set_random_images( $this->product );
		WoocommerceUtils::set_random_upsells( $this->product );

		foreach ( WoocommerceUtils::get_allowed_taxonomies() as $taxonomy ) {

			$limit = match ( $taxonomy ) {
				'product_cat'                  => 4,
				'product_tag', 'product_brand' => 2,
				default                        => 1,
			};

			WoocommerceUtils::set_random_taxonomy( $this->product, $taxonomy, $limit );
		}

		WoocommerceUtils::set_random_upsells( $this->product );

		if ( $this->product->is_type( ProductType::VARIABLE ) ) {
			( new CreateProductVariations( $this->product, $this->product_id ) )->run();
		}
	}


	/**
	 * @param  null $product
	 *
	 * @return void
	 */
	protected function set_attributes( $product = null ): void {

		if ( is_null( $product ) ) {
			$product = $this->product;
		}

		$attributes = ( new AttachmentAttributes( $this->config['attributes'] ) )->generate_attributes( $this->get_count_attributes_to_use() );

		$product->set_attributes( $attributes );
	}


	protected function get_count_attributes_to_use(): string {

		$count_attr_to_use = array_map( 'trim', explode( ',', $this->attr_to_use ) );

		$min_attributes_to_use = $count_attr_to_use[0];
		$max_attributes_to_use = '-1' === $count_attr_to_use[1] ? null : $count_attr_to_use[1];

		if ( $this->product->is_type( ProductType::VARIABLE ) ) {
			$max_attributes_to_use = 5;
		}

		return implode( ',', [ $min_attributes_to_use, $max_attributes_to_use ] );
	}


	/**
	 * @param  null $product
	 *
	 * @return void
	 * @throws \WC_Data_Exception  выбрасываем исключение при ошибке.
	 */
	protected function set_base_properties( $product = null ): void {

		if ( is_null( $product ) ) {
			$product = $this->product;
		}

		$name = StringUtils::get_name( RandomUtils::get_random( $this->config['titles'] ) );

		$product->set_name( $name );
		$product->set_description( RandomUtils::get_random( $this->config['descriptions'] ) );
		$product->set_short_description( RandomUtils::get_random( $this->config['short_descriptions'] ) );
		$product->set_status( 'publish' );
		$product->set_catalog_visibility( RandomUtils::get_random_item( array_keys( wc_get_product_visibility_options() ) ) );
	}


	/**
	 * @return void
	 */
	protected function set_meta_data(): void {

		$this->product->update_meta_data( StringUtils::META_KEY, 1 );

		$this->product->save();
	}
}
