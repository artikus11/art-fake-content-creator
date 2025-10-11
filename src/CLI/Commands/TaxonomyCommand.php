<?php

namespace Art\FakeContent\CLI\Commands;

use Art\FakeContent\Utils\WoocommerceUtils;
use WP_CLI;

class TaxonomyCommand extends BaseCommand {

	/**
	 * Создать таксономии и термины
	 *
	 * ## OPTIONS
	 *
	 * [--tax=<names>]
	 * : Список таксономий через запятую: product_cat,post_tag
	 *
	 * [--profile=<name>]
	 * : Имя профиля
	 *
	 * [--recreate]
	 * : Удалить старые перед созданием
	 *
	 * ## EXAMPLES
	 *      wp fcc taxonomy create --tax=product_cat,post_tag
	 *      wp fcc taxonomy create --tax=product_cat,post_tag --recreate
	 *      wp fcc taxonomy create --profile=industrial --tax=product_cat,post_tag
	 *
	 * @throws \WP_CLI\ExitException выбрасываем исключение при ошибке.
	 */
	public function create( $args, $assoc_args ) {

		$tax = $this->get_flag_value( $assoc_args, 'tax' );

		if ( ! $tax ) {
			WP_CLI::error( 'Не указано название таксономии' );

			return;
		}

		$profile = $this->get_flag_value( $assoc_args, 'profile' );
		$config  = $this->get_config( $profile );

		$allowed = array_map( 'trim', explode( ',', $tax ) );

		$woo_taxonomies = WoocommerceUtils::get_allowed_taxonomies();
		$woo_requested  = array_intersect( $woo_taxonomies, $allowed );

		if ( ! empty( $woo_requested ) && ! class_exists( 'WooCommerce' ) ) {
			$list = implode( ', ', $woo_requested );
			WP_CLI::error( "Таксономии WooCommerce ($list) требуют установленного и активного плагина WooCommerce." );

			return;
		}

		$config = array_intersect_key( $config, array_flip( $allowed ) );

		if ( empty( $config ) ) {
			WP_CLI::error( "❌ Нет данных для указанных таксономий: $tax" );

			return;
		}

		parent::create( $args, $assoc_args );
	}


	/**
	 * Удалить таксономии и термины
	 *
	 * ## OPTIONS
	 *
	 * [--tax=<names>]
	 * : Список таксономий через запятую: product_cat,post_tag
	 *
	 * ## EXAMPLES
	 *      wp fcc taxonomy delete --tax=product_cat,post_tag
	 *
	 * @throws \WP_CLI\ExitException выбрасываем исключение при ошибке.
	 */
	public function delete( $args, $assoc_args ) {

		$tax = $this->get_flag_value( $assoc_args, 'tax' );

		if ( ! $tax ) {
			WP_CLI::error( 'Не указано название таксономии' );

			return;
		}

		parent::delete( $args, $assoc_args );
	}


	protected function prepare_service( object $service, array $assoc_args ): void {

		$tax = $this->get_flag_value( $assoc_args, 'tax' );

		if ( method_exists( $service, 'set_tax' ) ) {
			$service->set_tax( $tax );
		}
	}


	/**
	 * Передача значений команд и флагов в сервис
	 *
	 * @param  object $service
	 * @param  array  $assoc_args
	 *
	 * @return void
	 */
	protected function prepare_service_for_create( object $service, array $assoc_args ): void {

		$this->prepare_service( $service, $assoc_args );
	}


	/**
	 * Передача значений команд и флагов в сервис
	 *
	 * @param  object $service
	 * @param  array  $assoc_args
	 *
	 * @return void
	 */
	protected function prepare_service_for_delete( object $service, array $assoc_args ): void {

		$this->prepare_service( $service, $assoc_args );
	}


	protected function get_entity_type_key(): string {

		return 'taxonomy';
	}


	protected function get_label_output_create(): string {

		return 'создание таксономий';
	}


	protected function get_label_output_delete(): string {

		return 'удаление таксономий';
	}
}
