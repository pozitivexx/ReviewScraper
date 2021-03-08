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
		dbg( 'revper_check_reviews' );

		if ( $_SERVER['REMOTE_ADDR'] != "178.242.194.31" ) {
			return;
		}

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
			//pr( $URLs, 0 );

			if ( count( $URLs ) ) {
				$content = [];
				foreach ( $URLs AS $key => $val ) {
					if ( substr( $val['meta_key'], - 4 ) == "_key" ) {
						$product      = substr( $val['meta_key'], 0, - 4 );
						$product_name = str_replace( "revper_", "", $product );

						$content[ $val['post_id'] ][ $product_name ]['product'] = $product_name;
						$content[ $val['post_id'] ][ $product_name ]['post_id'] = $val['post_id'];
						$content[ $val['post_id'] ][ $product_name ]['key']     = $val['meta_value'];

						if ( $content[ $val['post_id'] ][ $product_name ]['key']['result'] ) {

							$content[ $val['post_id'] ][ $product_name ]['result'] = $this->revper_get_review(
								$content[ $val['post_id'] ][ $product_name ]['key']['content'],
								$content[ $val['post_id'] ][ $product_name ]['post_id'],
								$content[ $val['post_id'] ][ $product_name ]['product']
							);
						}
					} else {
						$product      = $val['meta_key'];
						$product_name = str_replace( "revper_", "", $product );

						$content[ $val['post_id'] ][ $product_name ]['post_id'] = $val['post_id'];
						$content[ $val['post_id'] ][ $product_name ]['product'] = $product_name;
						$content[ $val['post_id'] ][ $product_name ]['url']     = $val['meta_value'];
					}
				}

				//pr( $content, 1 );
				// Let's identify those without API keys.
				if ( count( $content ) ) {
					foreach ( $content AS $post_id => $products ) {
						foreach($products AS $key=>$val){
							if ( ! isset( $val['key'] ) ) {

								$ApiKey = $this->GetApiKey( $val['product'], $val['post_id'], true );
								//pr( $ApiKey, 0 );

								if ( $ApiKey['result'] ) {

									$content[ $key ]['result'] = $this->revper_get_review(
										$ApiKey['content'],
										$val['post_id'],
										$val['product']
									);

									//pr( $content[ $key ]['result'], 0 );
								}
								//pr( $val, 0 );

							}
						}
					}
				}

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

	public function GetApiKey( $product = "", $IDPOST = "", $RequestAgain = false ) {
		if ( in_array( $product, array_keys( $this->revper_meta_fields ) ) ) {
			dbg( $product . ": requested api key (ReqeustAgain:" . ( $RequestAgain ? 'True' : 'False' ) . ")" );
			$title = get_the_title( $IDPOST );
			$value = get_post_meta( $IDPOST, $this->revper_meta_fields[ $product ]['slug'], true );
			$key   = json_decode( get_post_meta( $IDPOST, $this->revper_meta_fields[ $product ]['slug'] . "_key",
				true ), true );

			if ( ! $this->revper_meta_fields[ $product ]['isActive'] ) {
				dbg( $product . ": is not active" );

				return null;
			}

			if ( ( ! empty( $value ) && ! $key ) || $RequestAgain ) {
				//send remove request if value is empty and RequestAgain is true

				dbg( $product . ": requesting new key..." );
				// create new key
				$url = $this->ApiURL . 'get/key';

				$post_data = [
					'email'   => get_option( 'admin_email' ),
					'url'     => site_url(),//get_option( 'home' ),
					'product' => $product,
					'title'   => $title,
					'idpost'  => $IDPOST,
					'value'   => $value,
				];

				$response = json_decode( wp_remote_retrieve_body(
					wp_remote_post(
						$url,
						array( 'body' => $post_data )
					)
				), true );

				if ( $response['result'] ) {
					dbg( $product . ": new key got successfully: " . var_export( $response, true ) . "\n\n" );
					update_post_meta(
						$IDPOST,
						$this->revper_meta_fields[ $product ]['slug'] . "_key",
						wp_slash( json_encode( $response ) )
					);

				} else {
					dbg( $product . ": new key generation is unsuccessfully: " .
					     var_export( $response,
						     true ) . "\n\n" );
				}
				$key = $response;

			} else {
				dbg( $product . ": has a api key already." );
			}

			return $key;

		} else {
			dbg( $product . ": invalid product requested" );
		}

		return null;
	}

}