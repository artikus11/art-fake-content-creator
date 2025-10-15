<?php

namespace Art\FakeContent\CLI\Commands;

use Art\FakeContent\Services\ConfigLoader;
use Art\FakeContent\Services\Managers\AttributeManager;
use Art\FakeContent\Services\Managers\ImageManager;
use Art\FakeContent\Services\Managers\TaxonomyManager;
use Art\FakeContent\Utils\StringUtils;
use Art\FakeContent\Utils\WoocommerceUtils;
use WP_CLI;

class ProductCommand extends BaseCommand {

	protected array $attributes;


	protected array $images;


	protected array $brands;


	protected array $categories;


	protected array $titles;


	protected array $descriptions;


	protected array $short_descriptions;


	protected ?string $profile;


	protected array $taxonomies;


	protected ?string $attr_to_use;


	private function get_services(): array {

		return [
			[
				'instance' => new AttributeManager( $this->attributes ),
				'label'    => 'Создание атрибутов',
			],
			[
				'instance' => new ImageManager( $this->images ),
				'label'    => 'Загрузка изображений',
			],
			[
				'instance' => new TaxonomyManager( $this->taxonomies ),
				'label'    => 'Создание категорий',
			],
		];
	}


	/**
	 * Создать товары
	 *
	 * ## OPTIONS
	 * [--type=<type>]
	 * : Тип товара: simple,variable. По умолчанию: simple
	 *
	 * [--profile=<profile>]
	 * : Имя профиля из config/. По умолчанию: default
	 *
	 * [--count=<number>]
	 * : Количество объектов. По умолчанию: 1
	 *
	 * [--recreate]
	 * : Удалить старые фейковые объекты перед созданием
	 *
	 * [--seed]
	 * : Создавать связанные данные при создании товаров.
	 *
	 * [--attr-to-use]
	 * : Количество атрибутов для использования. Минимальное и максимальное значение: 1,5
	 * При указании значения максимального значения -1 , будут использованы все атрибуты из профиля
	 * При использовании типа variable используется занчение 1,5
	 *
	 * ## EXAMPLES
	 *      wp fcc product create
	 *      wp fcc product create --seed
	 *      wp fcc product create --type=variable
	 *      wp fcc product create --count=10 --type=variable --profile=industrial --seed
	 *      wp fcc product create --count=10 --type=simple,variable --profile=industrial --seed
	 *      wp fcc product create --count=10 --type=simple --profile=industrial --seed --attr-to-use=0,-1
	 *
	 * @throws \WP_CLI\ExitException выбрасываем исключение при ошибке.
	 */
	public function create( $args, $assoc_args ) {

		$this->profile     = $this->get_flag_value( $assoc_args, 'profile' );
		$this->attr_to_use = $this->get_flag_value( $assoc_args, 'attr-to-use', '1,5' );

		$this->set_loader_data();

		if ( isset( $assoc_args['seed'] ) ) {
			$this->run_product_seeders();
		}

		parent::create( $args, $assoc_args );
	}


	protected function get_config( ?string $profile ): array {

		return [
			'count'              => $this->count,
			'titles'             => $this->titles,
			'descriptions'       => $this->descriptions,
			'short_descriptions' => $this->short_descriptions,
			'attributes'         => $this->attributes,
			'attr_to_use'        => $this->attr_to_use,
			'images'             => $this->images,
			'brands'             => $this->taxonomies['product_brand'],
			'categories'         => $this->taxonomies['product_cat'],
			'tags'               => $this->taxonomies['product_tag'],
		];
	}


	/**
	 * @throws \WP_CLI\ExitException выбрасываем исключение при ошибке.
	 */
	protected function set_loader_data(): void {

		$loader = new ConfigLoader();

		$this->titles             = $loader->load( 'titles', $this->profile );
		$this->descriptions       = $loader->load( 'descriptions', $this->profile );
		$this->short_descriptions = $loader->load( 'short-descriptions', $this->profile );

		$this->attributes = $loader->load( 'attributes', $this->profile );
		$this->images     = $loader->load( 'images', $this->profile );
		$this->taxonomies = $loader->load( 'taxonomies', $this->profile );
	}


	protected function run_product_seeders(): void {

		WP_CLI::log( 'Запуск сидеров для товаров...' );

		foreach ( $this->get_services() as $data ) {
			$service     = $data['instance'];
			$this->label = $data['label'];

			$this->set_cli_logging( $service );

			if ( $service instanceof TaxonomyManager ) {
				$service->set_tax( implode( ',', WoocommerceUtils::get_allowed_taxonomies() ) );
			}

			$service->prepare_create_operations();

			$total = $service->get_create_operation_count();

			if ( 0 === $total ) {
				continue;
			}

			$this->start_time   = microtime( true );
			$this->action_label = StringUtils::capitalize_first_letter( $this->label );

			$this->execute_with_progress( $service, 'create', 'get_create_stats', $total );
		}

		WP_CLI::success( 'Сидеры для товаров созданы.' );
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

		$product_type = $this->get_flag_value( $assoc_args, 'type', 'simple' );

		if ( method_exists( $service, 'set_product_type' ) ) {
			$service->set_product_type( $product_type );
		}
	}


	protected function get_entity_type_key(): string {

		return 'product';
	}


	protected function get_label_output_create(): string {

		return 'создание товаров';
	}


	protected function get_label_output_delete(): string {

		return 'удаление товаров';
	}
}
