<?php

namespace Art\FakeContent\Services\Managers;

use Art\FakeContent\Utils\QueryUtils;
use Art\FakeContent\Utils\RandomUtils;
use Art\FakeContent\Utils\StringUtils;

class TaxonomyManager extends BaseManager {

	protected string $tax;


	public function create(): void {

		foreach ( $this->get_configured_taxonomies() as $taxonomy ) {

			$this->ensure_taxonomies( $taxonomy, $this->config[ $taxonomy ] );
		}
	}


	protected function ensure_taxonomies( string $taxonomy, array $tax_config ) {

		if ( ! isset( $tax_config['terms'] ) || ! is_array( $tax_config['terms'] ) ) {
			$this->log( "Некорректная конфигурация для таксономии '$taxonomy': отсутствует или не является массивом ключ 'terms'." );

			return;
		}

		$terms = $tax_config['terms'];

		$slug_to_term_id = [];

		foreach ( $terms as $name => $data ) {
			$slug = $this->get_term_slug( $data, $name );

			$description = ! empty( $data['description'] ) ? $data['description'] : '';

			$existing_term = get_term_by( 'slug', $slug, $taxonomy );

			if ( $existing_term && ! is_wp_error( $existing_term ) ) {
				continue;
			}

			$result = wp_insert_term(
				StringUtils::get_name( $name ),
				$taxonomy,
				[
					'slug'        => $slug,
					'description' => $description,
					'parent'      => 0,
				]
			);

			if ( is_wp_error( $result ) ) {
				$this->log( "Ошибка создания термина '$name': " . $result->get_error_message() );

				$this->record_creation( 'taxonomies', - 1 );
				continue;
			}

			$this->add_create_step( $taxonomy );
			$this->record_creation( $taxonomy );

			$term_id = $result['term_id'];

			$slug_to_term_id[ $slug ] = $term_id;

			update_term_meta( $term_id, StringUtils::META_KEY, true );
			update_term_meta( $term_id, 'thumbnail_id', RandomUtils::get_random_attachment_id() );

			$this->tick();
		}

		$this->update_parent( $terms, $slug_to_term_id, $taxonomy );
	}


	/**
	 * @param  mixed  $terms
	 * @param  array  $slug_to_term_id
	 * @param  string $taxonomy
	 *
	 * @return void
	 */
	protected function update_parent( $terms, array $slug_to_term_id, string $taxonomy ): void {

		foreach ( $terms as $name => $data ) {
			$slug        = $this->get_term_slug( $data, $name );
			$parent_slug = ! empty( $data['parent'] ) && 0 !== $data['parent'] ? $data['parent'] : null;

			if ( ! isset( $slug_to_term_id[ $slug ] ) ) {
				continue;
			}

			$term_id   = $slug_to_term_id[ $slug ];
			$parent_id = 0;

			if ( $parent_slug && isset( $slug_to_term_id[ $parent_slug ] ) ) {
				$parent_id = $slug_to_term_id[ $parent_slug ];
			} elseif ( $parent_slug ) {
				$this->log( "Родительская категория '$parent_slug' не найдена для '$name'" );
			}

			wp_update_term( $term_id, $taxonomy, [ 'parent' => $parent_id ] );
		}
	}


	public function prepare_create_operations(): void {

		$total = 0;
		foreach ( $this->get_configured_taxonomies() as $taxonomy ) {
			if ( ! taxonomy_exists( $taxonomy ) ) {
				$this->log( "Таксономия '$taxonomy' не существует." );
				continue;
			}

			if ( ! isset( $this->config[ $taxonomy ]['terms'] ) || ! is_array( $this->config[ $taxonomy ]['terms'] ) ) {
				continue;
			}

			$this->get_term_exiting( $taxonomy );

			if ( ! empty( $this->entities ) ) {
				$this->log( "Пропускаем '$taxonomy'... Уже существует." );
				continue;
			}

			$total += count( $this->config[ $taxonomy ]['terms'] );

			$this->add_create_step( $taxonomy, $total );
		}
	}


	public function delete() {

		foreach ( $this->get_configured_taxonomies() as $taxonomy ) {

			$this->get_term_exiting( $taxonomy );

			if ( empty( $this->entities ) ) {
				continue;
			}

			foreach ( $this->entities as $term_id ) {

				wp_delete_term( $term_id, $taxonomy );

				$this->add_delete_step( $taxonomy );
				$this->record_deletion( $taxonomy );
				$this->tick();
			}

			clean_taxonomy_cache( $taxonomy );
		}
	}


	public function prepare_delete_operations(): void {

		$total = 0;
		foreach ( $this->get_configured_taxonomies() as $taxonomy ) {
			$this->get_term_exiting( $taxonomy );

			if ( empty( $this->entities ) ) {
				$this->add_delete_step( $taxonomy, 0 );

				$this->log( "Пропускаем '$taxonomy'... Нет данных для удаления такcономии." );

				continue;
			}

			$total += count( $this->entities );

			$this->add_delete_step( $taxonomy, $total );
		}
	}


	public function has_exists(): bool {

		foreach ( $this->get_configured_taxonomies() as $taxonomy ) {
			$this->get_term_exiting( $taxonomy );

			if ( ! empty( $this->entities ) ) {
				return true;
			}
		}

		return false;
	}


	/**
	 * @return string
	 */
	public function get_tax(): string {

		return $this->tax;
	}


	/**
	 * @param  string $tax
	 */
	public function set_tax( string $tax ): void {

		$this->tax = $tax;
	}


	/**
	 * @return array
	 */
	protected function get_configured_taxonomies(): array {

		$taxes = explode( ',', $this->get_tax() );

		return array_map( 'trim', $taxes );
	}


	/**
	 * @param  array  $data
	 * @param  string $name
	 *
	 * @return string
	 */
	protected function get_term_slug( array $data, string $name ): string {

		return ! empty( $data['slug'] ) ? $data['slug'] : sanitize_title( $name );
	}


	/**
	 * @param  string $taxonomy
	 *
	 * @return void
	 */
	protected function get_term_exiting( string $taxonomy ): void {

		$this->entities = QueryUtils::get_terms( $taxonomy );
	}
}
