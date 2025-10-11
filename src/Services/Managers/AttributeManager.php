<?php

namespace Art\FakeContent\Services\Managers;

use Art\FakeContent\Utils\QueryUtils;
use Art\FakeContent\Utils\StringUtils;

class AttributeManager extends BaseManager {

	public function __construct( array $config ) {

		parent::__construct( $config );

		$this->entities = wc_get_attribute_taxonomies();
	}


	public function create(): void {

		foreach ( $this->config as $slug => $data ) {
			$this->ensure_attribute( $slug, $data );
		}
	}


	public function prepare_create_operations(): void {

		if ( $this->has_exists() ) {
			$this->add_create_step( 'attributes', 0 );
			$this->add_create_step( 'terms', 0 );

			$this->log( 'Атрибуты уже существуют... Пропускаем' );

			return;
		}

		foreach ( $this->config as $data ) {
			$this->add_create_step( 'attributes' );
			$this->add_create_step( 'terms', count( $data['terms'] ?? [] ) );
		}
	}


	protected function ensure_attribute( string $slug, array $data ): void {

		$attribute_id = wc_attribute_taxonomy_id_by_name( $slug );

		if ( 0 === $attribute_id ) {
			$result = wc_create_attribute( [
				'name'         => StringUtils::get_name( $data['name'] ),
				'slug'         => $slug,
				'type'         => $data['type'] ?? 'select',
				'order_by'     => $data['order_by'] ?? 'menu_order',
				'has_archives' => $data['has_archives'] ?? true,
			] );

			if ( is_wp_error( $result ) ) {
				$this->log( "Ошибка создания атрибута $slug: " . $result->get_error_message() );

				return;
			}

			$this->add_create_step( 'attributes' );
			$this->record_creation( 'attributes' );

			delete_transient( 'wc_attribute_taxonomies' );
		}

		$this->register_taxonomy_if_not_exists( $slug );

		if ( ! empty( $data['terms'] ) ) {
			$this->create_terms( $slug, $data['terms'] );
		}

		$this->tick();
	}


	private function create_terms( string $taxonomy, array $terms ): void {

		foreach ( $terms as $term_name ) {
			$term_slug = sanitize_title( $term_name );

			if ( term_exists( $term_slug, $taxonomy ) ) {
				continue;
			}

			$result = wp_insert_term( $term_name, $taxonomy, [ 'slug' => $term_slug ] );

			if ( ! is_wp_error( $result ) ) {
				$this->add_create_step( 'terms' );
				$this->record_creation( 'terms' );

				update_term_meta( (int) $result['term_id'], StringUtils::META_KEY, true );
			}

			$this->tick();
		}
	}


	private function register_taxonomy_if_not_exists( string $taxonomy ): void {

		if ( taxonomy_exists( $taxonomy ) ) {
			return;
		}

		$id = wc_attribute_taxonomy_id_by_name( $taxonomy );
		if ( ! $id ) {
			return;
		}

		$attr = wc_get_attribute( $id );
		if ( ! $attr ) {
			return;
		}

		register_taxonomy(
			$taxonomy,
			[ 'product' ],
			[
				'labels'       => wc_get_attribute_taxonomy_labels( $attr ),
				'hierarchical' => true,
				'show_ui'      => false,
				'query_var'    => true,
				'rewrite'      => false,
			]
		);

		wp_cache_flush();
	}


	public function delete() {

		foreach ( $this->entities as $attr ) {
			$taxonomy = 'pa_' . $attr->attribute_name;

			$terms = QueryUtils::get_terms( $taxonomy );

			if ( ! is_wp_error( $terms ) ) {
				foreach ( $terms as $term_id ) {
					wp_delete_term( $term_id, $taxonomy );

					$this->add_delete_step( 'terms' );
					$this->record_deletion( 'terms' );
					$this->tick();
				}
			}

			if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
				wc_delete_attribute( $attr->attribute_id );
			}

			$this->add_delete_step( 'attributes' );
			$this->record_deletion( 'attributes' );
			$this->tick();
		}

		delete_transient( 'wc_attribute_taxonomies' );
		wp_cache_flush();
	}


	public function prepare_delete_operations(): void {

		if ( ! $this->has_exists() ) {
			$this->add_delete_step( 'attributes', 0 );
			$this->add_delete_step( 'terms', 0 );

			$this->log( 'Нет атрибутов для удаления' );

			return;
		}

		foreach ( $this->entities as $attr ) {
			$taxonomy   = 'pa_' . $attr->attribute_name;
			$terms      = QueryUtils::get_terms( $taxonomy );
			$has_terms  = ! is_wp_error( $terms ) && ! empty( $terms );
			$term_count = $has_terms ? count( $terms ) : 0;

			$this->add_delete_step( 'attributes', 0 === (int) $term_count ? 0 : 1 );
			$this->add_delete_step( 'terms', $term_count );
		}
	}


	public function has_exists(): bool {

		$exists_attribute = false;

		foreach ( wc_get_attribute_taxonomies() as $attr ) {
			$taxonomy = 'pa_' . $attr->attribute_name;
			$terms    = QueryUtils::get_terms( $taxonomy );

			$exists_attribute = ! is_wp_error( $terms ) && ! empty( $terms );
		}

		return $exists_attribute;
	}
}
