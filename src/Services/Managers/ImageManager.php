<?php

namespace Art\FakeContent\Services\Managers;

use Art\FakeContent\Utils\QueryUtils;
use Art\FakeContent\Utils\StringUtils;

class ImageManager extends BaseManager {

	public function __construct( array $config ) {

		parent::__construct( $config );

		$this->entities = QueryUtils::get_uploaded_images();
	}


	public function create(): void {

		$this->import_image_from_urls( $this->config );
	}


	protected function import_image_from_urls( array $files_url ) {

		if ( empty( $files_url ) ) {
			return;
		}

		foreach ( $files_url as $file_url ) {
			if ( empty( $file_url ) ) {
				$this->log( "Empty URL: $file_url" );

				continue;
			}

			if ( ! filter_var( $file_url, FILTER_VALIDATE_URL ) ) {
				$this->log( "Invalid URL: $file_url" );

				continue;
			}

			if ( ! empty( QueryUtils::get_uploaded_images( '_fake_content_original_url', $file_url ) ) ) {
				continue;
			}

			$this->import_image_from_url( $file_url );

			$this->add_create_step( 'images' );
			$this->record_creation( 'images' );

			$this->tick();
		}
	}


	protected function import_image_from_url( string $file_url ) {

		$file_info = pathinfo( $file_url );
		$file_name = $file_info['basename'];

		$tmp_name = download_url( $file_url, false );

		if ( is_wp_error( $tmp_name ) ) {
			$this->record_creation( 'images', - 1 );

			return;
		}

		$file = [
			'name'     => $file_name,
			'type'     => mime_content_type( $tmp_name ),
			'tmp_name' => $tmp_name,
			'error'    => 0,
			'size'     => filesize( $tmp_name ),
		];

		$attachment_id = media_handle_sideload( $file );

		if ( is_wp_error( $attachment_id ) ) {
			$this->log( "Media upload failed for $file_url: " . $attachment_id->get_error_message() );
			wp_delete_file( $tmp_name );

			return;
		}

		update_post_meta( $attachment_id, '_fake_content_original_url', $file_url );
		update_post_meta( $attachment_id, StringUtils::META_KEY, 1 );
	}


	public function prepare_create_operations(): void {

		if ( $this->has_exists() ) {
			$this->add_create_step( 'images', 0 );

			$this->log( 'Изображения уже загружены... . Пропускаем' );

			return;
		}

		$this->add_create_step( 'images', count( $this->config ) );
	}


	public function delete(): void  {

		foreach ( $this->entities as $image_id ) {
			wp_delete_attachment( $image_id, true );

			$this->add_delete_step( 'images' );
			$this->record_deletion( 'images' );

			$this->tick();
		}
	}


	public function prepare_delete_operations(): void  {

		if ( ! $this->has_exists() ) {
			$this->add_delete_step( 'images', 0 );

			$this->log( 'Нет изображений для удаления' );

			return;
		}

		$this->add_delete_step( 'images', count( $this->entities ) );
	}
}
