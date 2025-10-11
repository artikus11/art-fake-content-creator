<?php

namespace Art\FakeContent\CLI;

use Art\FakeContent\Services\EntityTypeRegistry;
use Exception;
use WP_CLI;

class CommandManager {

	/**
	 * @throws Exception выбросываем исключение при неудачной регистрации команд.
	 */
	public function register_commands(): void {

		if ( ! defined( 'WP_CLI' ) || ! constant( 'WP_CLI' ) ) {
			return;
		}

		$types = EntityTypeRegistry::get_types();

		foreach ( $types as $type => $config ) {
			if ( ! $config['available'] ) {
				continue;
			}

			try {
				WP_CLI::add_command( "fcc {$type}", $config['command'] );
			} catch ( Exception $e ) {
				WP_CLI::warning( 'CLI command registration failed: ' . $e->getMessage() );
			}
		}
	}
}
