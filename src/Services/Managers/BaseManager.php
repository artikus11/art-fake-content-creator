<?php

namespace Art\FakeContent\Services\Managers;

use Art\FakeContent\Traits\ProvidesCli;

abstract class BaseManager {

	use ProvidesCli;

	protected array $config;


	public array $entities = [];


	public function __construct( array $config = [] ) {

		$this->config = $config;
	}


	/**
	 * Обработка команды создания сущности.
	 *
	 * @return void
	 */
	abstract public function create(): void;


	/**
	 * Обработка команды удаления сущности.
	 *
	 * @return void
	 */
	abstract public function delete(): void;


	/**
	 * Подсчет количества сущностей для создания. Требуется для отображения прогреcc-бара и статистики.
	 *
	 * @return void
	 */
	abstract public function prepare_create_operations(): void;


	/**
	 * Подсчет количества сущностей для удаления. Требуется для отображения прогреcc-бара и статистики.
	 *
	 * @return void
	 */
	abstract public function prepare_delete_operations(): void;


	public function has_exists(): bool {

		if ( empty( $this->entities ) ) {
			return false;
		}

		return true;
	}
}
