<?php

namespace Art\FakeContent\Traits;

use WP_CLI;

trait ProvidesCli {

	/**
	 * @var callable(string): void
	 */
	protected $progress_tick = null;


	/**
	 * @var callable(string): void
	 */
	protected $logger = null;


	/**
	 * @var callable(string): void
	 */
	protected $warn_logger = null;


	protected array $create_stats = [];


	protected array $delete_stats = [];


	protected array $create_operations = [];


	protected array $delete_operations = [];


	public function set_progress_callback( ?callable $tick ): void {

		$this->progress_tick = $tick;
	}


	public function set_logger( ?callable $log ): void {

		$this->logger = $log ? : fn( $msg ) => WP_CLI::log( $msg );
	}


	public function set_warning( ?callable $warn ): void {

		$this->warn_logger = $warn ? : fn( $msg ) => WP_CLI::warning( $msg );
	}


	protected function log( string $msg ): void {

		( $this->logger )( $msg );
	}


	protected function warn( string $msg ): void {

		( $this->warn_logger )( $msg );
	}


	protected function tick(): void {

		if ( $this->progress_tick ) {
			( $this->progress_tick )();
		}
	}


	protected function add_create_step( string $type, int $count = 1 ): void {

		$this->create_operations[ $type ] = ( $this->create_operations[ $type ] ?? 0 ) + $count;
	}


	protected function add_delete_step( string $type, int $count = 1 ): void {

		$this->delete_operations[ $type ] = ( $this->delete_operations[ $type ] ?? 0 ) + $count;
	}


	public function get_create_operation_count(): int {

		return array_sum( $this->create_operations );
	}


	public function get_delete_operation_count(): int {

		return array_sum( $this->delete_operations );
	}


	protected function record_creation( string $type, int $count = 1 ): void {

		$this->create_stats[ $type ] = ( $this->create_stats[ $type ] ?? 0 ) + $count;
	}


	protected function record_deletion( string $type, int $count = 1 ): void {

		$this->delete_stats[ $type ] = ( $this->delete_stats[ $type ] ?? 0 ) + $count;
	}


	public function get_create_stats(): array {

		return $this->create_stats;
	}


	public function get_delete_stats(): array {

		return $this->delete_stats;
	}
}
