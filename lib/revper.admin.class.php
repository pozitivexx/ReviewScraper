<?php

/**
 * Created by PhpStorm.
 * User: alper
 * Date: 25-Feb-21
 * Time: 11:39
 */
class revper_admin extends RevperController {

	public function __construct() {
		parent::__construct();
		// enqueue admin side css and js
		add_action( 'admin_enqueue_scripts', function () {
			wp_enqueue_style( 'revpercustomreviews',
				plugins_url( '', __FILE__ ) . '/../assests/css/revper-custom-reviews.css', [],
				'1.0.0' );
			wp_enqueue_script( 'revpercustomreviews',
				plugins_url( '', __FILE__ ) . '/../assests/js/revper-custom-reviews.js', [],
				'2.0.0' );
		} );

		add_action( 'init', function () {
			//first init
			add_action( 'add_meta_boxes', [ $this, 'revper_add_meta_box' ] );
			add_action( 'save_post_listing', [ $this, 'revper_save_meta_box' ] );

			add_action( 'wp_ajax_revper_get_reviews', [ $this, 'revper_get_reviews' ] );


		} );
	}

	public function revper_meta_box_content() {
		include WP_PLUGIN_DIR . '/listingpro-reviewscraper/views/revper-meta-box-display.php';
	}

	public function revper_save_meta_box( $post_id ) {
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		foreach ( $this->revper_meta_fields as $field ) {
			if ( isset( $_POST[ $field['slug'] ] ) ) {
				$url = esc_attr( $_POST[ $field['slug'] ] );

				if ( get_post_meta( $post_id, $field['slug'], true ) != $url ) {
					// send url to api if it changed,
					update_post_meta( $post_id, $field['slug'], $url );

					$this->GetApiKey( str_replace( 'revper_', '', $field['slug'] ), $post_id, true );
				};

			}
		}
	}

	public function revper_add_meta_box() {

		add_meta_box(
			'revper_reviews-revper-reviews',
			__( 'External Reviews', 'revper_reviews' ),
			[ $this, 'revper_meta_box_content' ],
			'listing',
			'normal',
			'core'
		);
	}

	public $revirews_Handler;


	public function revper_get_reviews() {
		$post_id = $_POST['post_id'];
		$product = $_POST['product'];

		if ( ! in_array( $product, array_keys( $this->revper_meta_fields ) ) ) {
			wp_send_json( [ 'result' => false, 'content' => "product is incorrect" ] );
			wp_die();
		}

		$key = json_decode( get_post_meta( $post_id, $this->revper_meta_fields[ $product ]['slug'] . "_key", true ),
			true );
		if ( ! $key || ! isset( $key['result'] ) || ! $key['result'] ) {
			dbg( $product . " key is invalid. requesting again: " . var_export( $key, true ) );
			$key = $this->GetApiKey( $product, $post_id, true );
			dbg( $key );
			dbg( $product . " response requested new key: " . var_export( $key, true ) );
			//wp_send_json( [ 'result' => false, 'content' => 'key is incorrect' ] );
			//wp_die();

			if ( ! $key['result'] ) {
				wp_send_json( [ 'result' => false, 'content' => $key['content']??'key is incorrect' ] );
				wp_die();
			}
		}

		$URL      = $this->ApiURL . 'get?key=' . $key['content'];
		$response = json_decode(
			wp_remote_retrieve_body(
				wp_remote_get( $URL )
			), true );

		if ( ! $response || ! $response['result'] ) {

			if ( isset( $response['key'] ) && ! $response['key'] ) {
				// key is invalid
				delete_post_meta( $post_id, $product . "_key" );
				dbg( $product . ": removed invalid api key" );
			}

			wp_send_json( [ 'result' => false, 'content' => $response['content']??null ] );
			wp_die();
		}

		$count = [
			'total'    => 0,
			'imported' => 0,
			'exist'    => 0,
		];

		foreach ( $response['content']['reviews'] AS $key => $val ) {
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

		wp_send_json( [
			'result'  => true,
			'content' => $count['imported'] . "/" . $count['total'] . " imported successfully (" . $count['exist'] . " exists)",
			'count'   => $count
		] );
		wp_die();

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
					dbg( $product . ": new key generation is unsuccessfully: " . var_export( $response,
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