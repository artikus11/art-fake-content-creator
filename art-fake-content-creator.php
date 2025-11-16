<?php
/**
 * Plugin Name: Art Fake Content Creator
 * Text Domain: art-fake-content-creator
 * Domain Path: /languages
 * Description: Плагин для генерации контента
 * Version: 1.0.2
 * Author: Artem Abramovich, Campusboy
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 *
 * RequiresWP: 6.0
 * RequiresPHP: 8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const FCC_PLUGIN_DIR   = __DIR__;
const FCC_PLUGIN_AFILE = __FILE__;

const FCC_PLUGIN_VER    = '1.0.2';
const FCC_PLUGIN_NAME   = 'Fake Content Creator';
const FCC_PLUGIN_SLUG   = 'art-fake-content-creator';
const FCC_PLUGIN_PREFIX = 'fcc';

define( 'FCC_PLUGIN_URI', untrailingslashit( plugin_dir_url( FCC_PLUGIN_AFILE ) ) );
define( 'FCC_PLUGIN_FILE', plugin_basename( __FILE__ ) );

require FCC_PLUGIN_DIR . '/vendor/autoload.php';

( new \Art\FakeContent\Main() )->init();
