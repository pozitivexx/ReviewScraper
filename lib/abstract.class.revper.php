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
		'facebook'    => [
			'name'     => 'Facabeook Page',
			'domain'   => 'facebook',
			'isActive' => true,
		],
		'iheartjane'  => [
			'name'     => 'iheartjane Page',
			'domain'   => 'iheartjane',
			'isActive' => true,
		],
		'google'      => [
			'name'     => 'Google Business Page',
			'domain'   => 'google',
			'isActive' => true,
		],
		'yelp'        => [
			'name'     => 'Yelp Listing',
			'domain'   => 'yelp',
			'isActive' => true,
		],
		'tripadvisor' => [
			'name'     => 'Tripadvisor',
			'domain'   => 'tripadvisor',
			'isActive' => true,
		],
		'weedmaps'    => [
			'name'     => 'Weedmaps Listing',
			'domain'   => 'weedmaps',
			'isActive' => true,
		],
		'youtube'     => [
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

	public function revper_check_reviews() {
		dbg('revper_check_reviews');
		global $wpdb;
		$results = $wpdb->get_results( "SELECT post_id,meta_key,meta_value FROM {$wpdb->prefix}postmeta WHERE meta_key LIKE 'revper%'",
			ARRAY_A );
		if ( $results && count( $results ) ) {
			$URLs = array_map( function ( $s ) {
				if ( substr( $s['meta_key'], - 4 ) == "_key" ) {
					$s['meta_value'] = json_decode( $s['meta_value'], true );
				}

				return $s;
			}, array_filter( $results, function ( $s ) {
				return ! empty( $s['meta_value'] );
			} ) );

			if ( count( $URLs ) ) {
				$content = [];
				foreach ( $URLs AS $key => $val ) {
					if ( substr( $val['meta_key'], - 4 ) == "_key" ) {
						$key                        = substr( $val['meta_key'], 0, - 4 );
						$content[ $key ]['product'] = str_replace( "revper_", "", $key );
						$content[ $key ]['post_id'] = $val['post_id'];
						$content[ $key ]['key']     = $val['meta_value'];

						if ( $content[ $key ]['key']['result'] ) {

							$content[ $key ]['result'] = $this->revper_get_review(
								$content[ $key ]['key']['content'],
								$content[ $key ]['post_id'],
								$content[ $key ]['product']
							);
						}
					} else {
						$key                    = $val['meta_key'];
						$content[ $key ]['url'] = $val['meta_value'];
					}
				}

				pr( $content );

			}
		}
	}

	public function revper_get_review( $key = "", $post_id = "", $product = "" ) {
		$URL      = $this->ApiURL . 'get?key=' . $key;
		$response = json_decode(
			wp_remote_retrieve_body(
				wp_remote_get( $URL )
			), true );

		if ( ! $response || ! $response['result'] ) {
			return $response;
		}

		$count = $this->SaveReviews(
			$response['content']['reviews'],
			$post_id,
			$product
		);

		return [ 'result' => true, 'content' => $count ];
	}

	public function SaveReviews( $reviews, $post_id, $product ) {
		$count = [
			'total'    => 0,
			'imported' => 0,
			'exist'    => 0,
		];

		foreach ( $reviews AS $key => $val ) {
			$count['total'] ++;

			if ( ! count( get_comments( array( 'meta_key' => 'revper_id', 'meta_value' => $val['ID'] ) ) ) ) {

				$review_args = [
					'comment_post_ID' => $post_id,
					'comment_content' => wp_strip_all_tags( $val['v_review'] ),
					'comment_type'    => 'revper_' . $product,
					'comment_meta'    => [
						'revper_id'           => $val['ID'],
						'revper_author_name'  => $val['v_name'],
						'revper_author_image' => $val['v_image'],
						'revper_review_score' => $val['v_score'],
					]
				];
				wp_insert_comment( $review_args );

				$count['imported'] ++;
			} else {
				//pr($val['ID'],0);
				$count['exist'] ++;
			}
		}

		return $count;
	}
}