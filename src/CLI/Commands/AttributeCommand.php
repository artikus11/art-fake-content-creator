<?php

namespace Art\FakeContent\CLI\Commands;

class AttributeCommand extends BaseCommand {

	protected function get_entity_type_key(): string {

		return 'attribute';
	}


	protected function get_label_output_create(): string {

		return 'создание атрибутов';
	}


	protected function get_label_output_delete(): string {

		return 'удаление атрибутов';
	}
}
