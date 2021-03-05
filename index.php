<?php
/*
Plugin Name: ListingPro Review Scraper
Plugin URI:
Description: This plugin only compatible with listingpro Theme By FOL.
Version: 1.0
Author: FOL (Dev Team)
Author URI: https://williamsmedia.co
Author Email: alperxx@gmail.com
Copyright 2021 FOL
*/

include WP_PLUGIN_DIR . '/listingpro-reviewscraper/lib/abstract.class.revper.php';

function dbg( $msg ) {
	if (is_array($msg)) $msg = var_export($msg, true);
	$debugfile = WP_PLUGIN_DIR . '/listingpro-reviewscraper/debug_reviewscraper.log';
	$msg       = date( 'Y-m-d H:i:s' ) . " $msg\n";
	//echo $msg;
	file_put_contents( $debugfile, $msg, FILE_APPEND );
	//file_put_contents( $debugfile, $msg . PHP_EOL, FILE_APPEND );
}

if ( ! function_exists( 'pr' ) ) {
	function pr( $veri = "", $die = 1 ) {
		echo "<pre>";
		print_r( $veri );
		echo "\n\n\n\n";
		print_r( ( new \Exception )->getTraceAsString() );
		echo "</pre>";
		if ( $die ) {
			die();
		}
	}

}
if ( ! function_exists( 'vd' ) ) {
	function vd( $veri = "", $die = 1 ) {
		echo "<pre>";
		var_dump( $veri );
		echo "\n\n\n\n";
		print_r( ( new \Exception )->getTraceAsString() );
		echo "</pre>";
		if ( $die ) {
			die();
		}
	}

}

class revper_load
{
	protected static $instance = NULL;
	public static function get_instance()
	{
		if ( null === self::$instance )
		{
			if ( is_admin() ) {
				include WP_PLUGIN_DIR . '/listingpro-reviewscraper/lib/revper.admin.class.php';
				self::$instance = new revper_admin();
			} else {
				include WP_PLUGIN_DIR . '/listingpro-reviewscraper/lib/revper.class.php';
				self::$instance = new revper();
			}
		}
		return self::$instance;
	}
}

$revper = revper_load::get_instance();