<?php

namespace Art\FakeContent\Utils;

final class StringUtils {

	const  META_KEY = '_created_for_fake_content';


	/**
	 * Первая буква строки заглавная
	 *
	 * @param  string $str
	 *
	 * @return string
	 */
	public static function capitalize_first_letter( string $str ): string {

		if ( empty( $str ) ) {
			return $str;
		}

		return sprintf(
			'%s%s',
			mb_strtoupper( mb_substr( $str, 0, 1, 'UTF-8' ), 'UTF-8' ),
			mb_substr( $str, 1, null, 'UTF-8' )
		);
	}


	/**
	 * Получить имя c префиксом плагина
	 *
	 * @param  string $name заголовк сущности.
	 *
	 * @return string
	 */
	public static function get_name( string $name ): string {

		return sprintf( '%s [%s]', $name, PluginUtils::get_plugin_prefix() );
	}
}
