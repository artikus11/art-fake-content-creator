<?php

namespace Art\FakeContent\Services;

use Art\FakeContent\CLI\Commands\AttributeCommand;
use Art\FakeContent\CLI\Commands\ImageCommand;
use Art\FakeContent\CLI\Commands\ProductCommand;
use Art\FakeContent\CLI\Commands\TaxonomyCommand;
use Art\FakeContent\Services\Managers\AttributeManager;
use Art\FakeContent\Services\Managers\ImageManager;
use Art\FakeContent\Services\Managers\ProductManager;
use Art\FakeContent\Services\Managers\TaxonomyManager;

class EntityTypeRegistry {

	private static array $types = [];


	public static function register(): void {

		self::$types = [
			'taxonomy'  => [
				'label'     => 'Таксономии',
				'config'    => 'taxonomies',
				'available' => true,
				'service'   => TaxonomyManager::class,
				'command'   => TaxonomyCommand::class,
			],
			'image'     => [
				'label'     => 'Изображения',
				'config'    => 'images',
				'available' => true,
				'service'   => ImageManager::class,
				'command'   => ImageCommand::class,
			],
			'product'   => [
				'label'     => 'Товары',
				'config'    => '',
				'available' => class_exists( 'WooCommerce' ),
				'service'   => ProductManager::class,
				'command'   => ProductCommand::class,
			],
			'attribute' => [
				'label'     => 'Атрибуты',
				'config'    => 'attributes',
				'available' => class_exists( 'WooCommerce' ),
				'service'   => AttributeManager::class,
				'command'   => AttributeCommand::class,
			],
		];

		self::$types = array_filter( self::$types, fn( $type ) => $type['available'] );
	}


	public static function get_types(): array {

		if ( empty( self::$types ) ) {
			self::register();
		}

		return self::$types;
	}


	public static function get_type( string $name ): ?array {

		return self::get_types()[ $name ] ?? null;
	}


	public static function is_valid_type( string $name ): bool {

		return self::get_type( $name ) !== null;
	}
}
