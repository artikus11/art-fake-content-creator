<?php

namespace Art\FakeContent\Utils;

final class RandomUtils {

	/**
	 * Возвращает рандомное значение из списка переданных.
	 *
	 * @param  array $items Список, например ['base', 'advanced', 'master'].
	 *
	 * @return mixed Рандомный значение из списка, например advanced.
	 */
	public static function get_random_item( array $items ): mixed {

		shuffle( $items );

		return $items[0] ?? null;
	}


	/**
	 * Возвращает рандомные значения из списка переданных.
	 *
	 * @param  int   $count Максимальное количество возвращаемых значений.
	 * @param  array $items Список, например ['base', 'advanced', 'master'].
	 *
	 * @return array Рандомные значения списка, например ['base', 'master'].
	 */
	public static function get_random_items( int $count, array $items ): array {

		shuffle( $items );

		return array_slice( $items, 0, $count );
	}


	public static function get_random( array $array_items ) {

		return $array_items[ array_rand( $array_items ) ];
	}


	/**
	 * Возвращает рандомный массив из переданного.
	 *
	 * @param  array    $array_items
	 * @param  int      $min
	 * @param  int|null $max
	 *
	 * @return array Рандомный массив.
	 *
	 * @example get_random_array( ['a' => 1, 'b' => 2, 'c' => 3], 2, 3 );
	 */
	public static function get_random_array( array $array_items, int $min = 1, int $max = null ): array {

		if ( null === $max ) {
			$max = count( $array_items );
		}

		$keys  = array_keys( $array_items );
		$count = wp_rand( $min, min( $max, count( $keys ) ) );

		if ( 0 === $count ) {
			return [];
		}

		$random_keys = array_rand( $keys, $count );

		if ( ! is_array( $random_keys ) ) {
			$random_keys = [ $random_keys ];
		}

		$result = [];
		foreach ( $random_keys as $key_index ) {
			$key            = $keys[ $key_index ];
			$result[ $key ] = $array_items[ $key ];
		}

		return $result;
	}


	/**
	 * Возвращает рандомное значение true или false.
	 *
	 * @return bool
	 */
	public static function get_random_bool(): bool {

		return boolval( wp_rand( 0, 1 ) );
	}


	/**
	 * Возвращает рандомное значение от $min_price до $max_price.
	 *
	 * @param  int $min_price
	 * @param  int $max_price
	 * @param  int $null_probability
	 * @param  int $zero_probability
	 *
	 * @return float|null
	 *
	 * @example get_random_int( 1, 25000, 5, 10 );
	 */
	public static function get_random_int( int $min_price = 1, int $max_price = 25000, int $null_probability = 5, int $zero_probability = 10 ): ?float {

		$random = wp_rand( 1, 100 );

		if ( $random <= $zero_probability ) {
			return 0;
		} elseif ( $random <= ( $zero_probability + $null_probability ) ) {
			return null;
		} else {
			return wp_rand( $min_price, $max_price );
		}
	}


	/**
	 * Возвращает рандомный id загруженного изображения.
	 *
	 * @return int
	 */
	public static function get_random_attachment_id(): int {

		$images = QueryUtils::get_uploaded_images();

		return ! empty( $images ) ? (int) self::get_random_item( $images ) : 0;
	}
}
