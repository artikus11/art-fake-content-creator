<?php

namespace Art\FakeContent\Services;

use Art\FakeContent\Utils\PluginUtils;
use WP_CLI;

class ConfigLoader {

	private string $config_dir;


	public function __construct( ?string $config_dir = null ) {

		$this->config_dir = $config_dir ?? PluginUtils::get_plugin_dir() . '/config';
	}


	/**
	 * @throws \WP_CLI\ExitException выбрасываем исключение при ошибке.
	 */
	public function load( string $name, $profile = null ): array {

		if ( ! $name ) {
			return [];
		}

		$path = $this->build_path( $profile, $name );

		if ( ! file_exists( $path ) ) {
			WP_CLI::error( "Конфиг не найден: $path" );
		}

		return $this->parse_json_config( $path );
	}


	/**
	 * @param  string|null $profile
	 * @param  string      $name
	 *
	 * @return string
	 */
	protected function build_path( ?string $profile, string $name ): string {

		$profile = $profile ? '/' . $profile : '/default';

		return sprintf( '%s%s/%s.json', $this->config_dir, $profile, $name );
	}


	/**
	 * @param  string $path
	 *
	 * @return mixed
	 * @throws \WP_CLI\ExitException выбрасываем исключение при ошибке.
	 */
	protected function parse_json_config( string $path ): mixed {

		$content = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

		if ( false === $content ) {
			WP_CLI::error( "Не удалось прочитать конфигурационный файл: $path" );
		}

		$data = json_decode( $content, true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			WP_CLI::error( "Ошибка JSON в $path: " . json_last_error_msg() );
		}

		return $data;
	}
}
