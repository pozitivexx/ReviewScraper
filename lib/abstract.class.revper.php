<?php

/**
 * Created by PhpStorm.
 * User: alper
 * Date: 24-Feb-21
 * Time: 16:14
 */
abstract class RevperController {
	protected $ApiURL = "https://api.wpreviewscraper.com/";
	public $revper_meta_fields = [
		'facebook'   => [
			'name'     => 'Facabeook Page',
			'domain'   => 'facebook',
			'isActive' => false,
		],
		'iheartjane' => [
			'name'     => 'iheartjane Page',
			'domain'   => 'iheartjane',
			'isActive' => true,
		],
		'google'     => [
			'name'     => 'Google Business Page',
			'domain'   => 'google',
			'isActive' => true,
		],
		'yelp'       => [
			'name'     => 'Yelp Listing',
			'domain'   => 'yelp',
			'isActive' => false,
		],
		'weedmaps'   => [
			'name'     => 'Weedmaps Listing',
			'domain'   => 'weedmaps',
			'isActive' => true,
		],
		'youtube'    => [
			'name'     => 'Youtube ID',
			'domain'   => 'youtube',
			'isActive' => false,
		],
	];

	public function __construct() {
		$this->revper_meta_fields = array_map( function ( $s ) {
			$s['slug'] = 'revper_' . sanitize_title( $s['domain'] );

			return $s;
		}, $this->revper_meta_fields );
	}

	public function revper_get_reviews_by_domain( $post_id, $domain ) {
		$review_args = [
			'post_id' => $post_id,
			'type'    => 'revper_' . $domain,
		];

		// print_r(  get_comments( $review_args ) );
		return get_comments( $review_args );
	}
}