<?php

namespace Art\FakeContent\CLI\Commands;

class ImageCommand extends BaseCommand {

	protected function get_entity_type_key(): string {

		return 'image';
	}


	protected function get_label_output_create(): string {

		return 'создание изображений';
	}


	protected function get_label_output_delete(): string {

		return 'удаление изображений';
	}
}
