<?php

namespace Art\FakeContent\CLI\Commands;

use Art\FakeContent\Services\ConfigLoader;
use Art\FakeContent\Services\EntityTypeRegistry;
use Art\FakeContent\Utils\StringUtils;
use Exception;
use WP_CLI;
use WP_CLI_Command;
use function WP_CLI\Utils\get_flag_value;
use function WP_CLI\Utils\make_progress_bar;
use function WP_CLI\Utils\format_items;

/**
 * Абстрактная база для всех сущностей.
 * Наследуй и реализуй get_entity_type().
 */
abstract class BaseCommand extends WP_CLI_Command {

	protected int $count;


	protected string $action_label = '';


	protected array $stats = [];


	protected float $start_time;


	protected ?string $label = null;


	protected ?int $total = null;


	/**
	 * Кэшированный тип сущности из реестра
	 *
	 * @var array|null
	 */
	private ?array $entity_type_data = null;


	/**
	 * Получить данные сущности из реестра (с кэшированием)
	 *
	 * @return array|null
	 * @throws \WP_CLI\ExitException выбрасываем ошибку, если сущность не найдена в реестре.
	 */
	protected function get_entity_type_data(): ?array {

		if ( null === $this->entity_type_data ) {
			$key = $this->get_entity_type_key();

			$this->entity_type_data = EntityTypeRegistry::get_type( $key );

			if ( ! $this->entity_type_data ) {
				WP_CLI::error( "❌ Сущность '{$key}' не найдена в реестре." );

				return null;
			}
		}

		return $this->entity_type_data;
	}


	/**
	 * Вернуть ключ сущности: 'post', 'product' и т.д.
	 *
	 * @return string
	 */
	abstract protected function get_entity_type_key(): string;


	/**
	 * @return string
	 */
	abstract protected function get_label_output_create(): string;


	/**
	 * @return string
	 */
	abstract protected function get_label_output_delete(): string;


	protected function prepare_service_for_create( object $service, array $assoc_args ): void {}


	protected function prepare_service_for_delete( object $service, array $assoc_args ): void {}


	/**
	 * Создать фейковые объекты
	 *
	 * ## OPTIONS
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
	 * @throws \WP_CLI\ExitException  выбрасываем исключение при ошибке.
	 */
	public function create( $args, $assoc_args ) {

		$type = $this->get_entity_type_data();

		if ( ! $type ) {
			return;
		}

		$profile     = $this->get_flag_value( $assoc_args, 'profile' );
		$this->count = (int) get_flag_value( $assoc_args, 'count', 1 );
		$recreate    = get_flag_value( $assoc_args, 'recreate', false );

		$config  = $this->get_config( $profile );
		$service = $this->get_service_instance( $config );

		$this->prepare_service_for_create( $service, $assoc_args );

		if ( $recreate ) {
			WP_CLI::log( '🔁 --recreate: удаляем старые...' );
			$service->delete( false );
		}

		$this->set_cli_logging( $service );

		$service->prepare_create_operations();

		$total = $service->get_create_operation_count();

		if ( 0 === (int) $total ) {
			return;
		}

		$this->label        = $type['label'];
		$this->start_time   = microtime( true );
		$this->action_label = StringUtils::capitalize_first_letter( $this->get_label_output_create() );

		$this->execute_with_progress( $service, 'create', 'get_create_stats', $total );
	}


	/**
	 * Удалить фейковые объекты
	 *
	 * ## OPTIONS
	 *
	 * [--force]
	 * : Принудительное удаление (без корзины)
	 *
	 * @throws \WP_CLI\ExitException выбрасываем исключение при ошибке.
	 */
	public function delete( $args, $assoc_args ) {

		$type = $this->get_entity_type_data();

		if ( ! $type ) {
			return;
		}

		$service = new $type['service']( [] );

		$this->prepare_service_for_create( $service, $assoc_args );

		$this->set_cli_logging( $service );

		$service->prepare_delete_operations();

		$total = $service->get_delete_operation_count();

		if ( 0 === (int) $total ) {
			return;
		}

		$this->start_time   = microtime( true );
		$this->action_label = StringUtils::capitalize_first_letter( $this->get_label_output_delete() );

		$this->label = $type['label'];

		$this->execute_with_progress( $service, 'delete', 'get_delete_stats', $total );
	}


	protected function output_stats_table( array $stats ): void {

		if ( empty( $stats ) ) {
			return;
		}

		$table_data = $this->prepare_stats( $stats );

		if ( ! empty( $table_data ) ) {
			format_items( 'table', $table_data, [ 'type', 'count' ] );
		}
	}


	protected function prepare_stats( array $stats ): array {

		$default_label = ucfirst( $this->label );
		$table_data    = [];

		if ( is_array( $stats ) ) {
			foreach ( $stats as $sub_type => $count ) {
				if ( $count > 0 ) {
					$type_label   = ucfirst( $sub_type );
					$table_data[] = [
						'type'  => $type_label,
						'count' => (string) $count,
					];
				}
			}
		} elseif ( $stats > 0 ) {
			$table_data[] = [
				'type'  => $default_label,
				'count' => (string) $stats,
			];
		}

		return $table_data;
	}


	protected function output_duration(): void {

		$duration = $this->calculate_duration( $this->start_time );

		WP_CLI::line( "Выполнено за: $duration сек" );
	}


	protected function output_success(): void {

		$duration = $this->calculate_duration( $this->start_time );

		WP_CLI::success( "$this->action_label завершено. Выполнено за: $duration сек" );
	}


	public function set_cli_logging( $service ): void {

		$log  = fn( $msg ) => WP_CLI::log( $msg );
		$warn = fn( $msg ) => WP_CLI::warning( $msg );

		$service->set_logger( $log );
		$service->set_warning( $warn );
	}


	protected function init_progress( int $total ): array {

		$progress = make_progress_bar( "$this->action_label ($total)", $total );
		$tick     = $progress ? fn() => $progress->tick() : fn() => null;

		return [ $progress, $tick ];
	}


	protected function calculate_duration( float $start_time ): float {

		$end_time = microtime( true );

		return round( $end_time - $start_time, 1 );
	}


	/**
	 * @throws \WP_CLI\ExitException выбрасываем исключение при ошибке.
	 */
	protected function get_service_instance( array $config ): object {

		$service_class = $this->get_entity_type_data()['service'];

		return new $service_class( $config );
	}


	/**
	 * @param  string|null $profile
	 *
	 * @return array
	 * @throws \WP_CLI\ExitException выбрасываем исключение при ошибке.
	 */
	protected function get_config( ?string $profile ): array {

		return ( new ConfigLoader() )->load( $this->get_entity_type_data()['config'], $profile );
	}


	/**
	 * @param  array<string,string|bool> $assoc_args    Arguments array.
	 * @param  string                    $flag          Flag to get the value.
	 * @param  mixed|null                $default_value Default value for the flag. Default: NULL.
	 *
	 * @return mixed
	 */
	protected function get_flag_value( array $assoc_args, string $flag, mixed $default_value = null ): mixed {

		return get_flag_value( $assoc_args, $flag, $default_value );
	}


	protected function execute_with_progress( object $service, string $method, string $stats_method, int $total ): void {

		[ $progress, $tick ] = $this->init_progress( $total );

		try {
			$service->set_progress_callback( $tick );
			$service->$method();

			$this->stats = $service->$stats_method();
		} catch ( Exception $e ) {
			if ( isset( $progress ) ) {
				$progress->finish();
			}

			WP_CLI::error( 'Ошибка: ' . $e->getMessage() );
		} finally {
			$progress->finish();

			$this->output_stats_table( $this->stats );
			$this->output_success();
		}
	}
}
