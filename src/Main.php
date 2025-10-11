<?php

namespace Art\FakeContent;

use Art\FakeContent\CLI\CommandManager;

/**
 * Class Main
 */
class Main {

	/**
	 * @var \Art\FakeContent\CLI\CommandManager
	 */
	protected CommandManager $cli_manager;


	public function __construct() {

		$this->cli_manager = new CommandManager();
	}


	public function init(): void {

		add_action( 'plugins_loaded', [ $this, 'initialize' ], PHP_INT_MAX );
	}


	/**
	 * @throws \Exception выбрасываем исключение при ошибке.
	 */
	public function initialize(): void {

		$this->cli_manager->register_commands();
	}
}
