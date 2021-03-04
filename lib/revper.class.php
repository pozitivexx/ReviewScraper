<?php

/**
 * Created by PhpStorm.
 * User: alper
 * Date: 25-Feb-21
 * Time: 11:39
 */
class revper extends RevperController {

	public function __construct() {
		parent::__construct();

		add_action( 'init', function () {
			//first init
			$this->AppendTemplate();
		} );
	}

	public function AppendTemplate() {
		$TemplateCode = "<?php if(method_exists('revper', 'AppendView')){revper::AppendView();}?>";

		$dir = get_template_directory() . "/templates/single-list";
		if ( is_dir( $dir ) ) {
			foreach ( scandir( $dir ) AS $name ) {
				if ( substr( $name, 0, strlen( "listing-details-" ) ) == "listing-details-" ) {
					$file = $dir . "/" . $name . "/content/list-reviews.php";
					if ( file_exists( $file ) ) {
						$file_contents = file_get_contents( $file );
						if ( substr( $file_contents, 0, strlen( $TemplateCode ) ) != $TemplateCode ) {
							file_put_contents( $file, $TemplateCode . $file_contents );
						}
					}

				}
			}
		}
	}

	public static function AppendView() {
		include WP_PLUGIN_DIR . '/listingpro-reviewscraper/views/reviews.php';
	}

	public function revper_get_reviews_details( $post_id ) {
		return get_post_meta( $post_id, 'revper_review_details', true );
	}

	public function revper_list_reviews( $post_id, $domain ) {
		$formatted_reviews = [];
		//pr($domain);
		$reviews = $this->revper_get_reviews_by_domain( $post_id, $domain );

		foreach ( $reviews as $review ) {
			$formatted_reviews[] = [
				'review_id'      => $review->comment_ID,
				'reviewer'       => get_comment_meta( $review->comment_ID, 'revper_author_name', true ),
				'profile_image'  => get_comment_meta( $review->comment_ID, 'revper_author_image', true ),
				'review_score'   => get_comment_meta( $review->comment_ID, 'revper_review_score', true ),
				'review_content' => $review->comment_content,
			];
		}

		return $formatted_reviews;
		/*wp_send_json( $formatted_reviews );
		wp_die();*/
	}
}

?>