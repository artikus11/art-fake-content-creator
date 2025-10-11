<?php

namespace Art\FakeContent\Services\Managers;

use Art\FakeContent\Services\Handlers\CreateProduct;
use Art\FakeContent\Utils\QueryUtils;

class ProductManager extends BaseManager {

	protected int $count;


	protected string $product_type;


	public function __construct( array $config ) {

		parent::__construct( $config );

		$this->count = $this->config['count'] ?? 1;

		$this->entities = QueryUtils::get_posts( 'product' );
	}


	/**
	 * @throws \WC_Data_Exception  выбрасываем исключение при ошибке.
	 */
	public function create(): void {

		foreach ( $this->get_configured_product_type() as $product_type ) {
			for ( $i = 0; $i < $this->count; $i ++ ) {

				( new CreateProduct( $this->config, $product_type ) )->run();

				$this->add_create_step( $product_type );
				$this->record_creation( $product_type );

				$this->tick();
			}
		}
	}


	public function prepare_create_operations(): void {

		foreach ( $this->get_configured_product_type() as $product_type ) {
			$this->add_create_step( $product_type, $this->count );
		}
	}


	public function delete(): void {

		foreach ( $this->entities as $product_id ) {
			wp_delete_post( $product_id, true );

			$this->add_delete_step( 'products' );
			$this->record_deletion( 'products' );

			$this->tick();
		}
	}


	public function prepare_delete_operations(): void {

		if ( ! $this->has_exists() ) {
			$this->add_delete_step( 'products', 0 );

			$this->log( 'Нет товаров для удаления' );

			return;
		}

		$this->add_delete_step( 'products', count( $this->entities ) );
	}


	protected function get_configured_product_type(): array {

		$taxes = explode( ',', $this->get_product_type() );

		return array_map( 'trim', $taxes );
	}


	/**
	 * @return string
	 */
	public function get_product_type(): string {

		return $this->product_type;
	}


	/**
	 * @param  string $product_type
	 */
	public function set_product_type( string $product_type ): void {

		$this->product_type = $product_type;
	}
}
