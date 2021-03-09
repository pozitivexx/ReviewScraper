<?php
/**
 * Created by PhpStorm.
 * User: alper
 * Date: 25-Feb-21
 * Time: 11:39
 */

register_activation_hook( __FILE__, array( 'revper', 'activated' ) );
register_deactivation_hook( __FILE__, array( 'revper', 'deactivated' ) );

class revper extends RevperController {

	public function __construct() {
		parent::__construct();

		$this->BindCron();

		add_action( 'init', function () {
			//first init
			$this->AppendTemplate();
		} );
	}

	public function BindCron() {
		add_filter( 'cron_schedules', function ( $schedules ) {
			if ( ! isset( $schedules["revper_timing"] ) ) {
				$schedules["revper_timing"] = [
					'interval' => 70 * 60,
					'display'  => "70 mins"
				];
			}

			return $schedules;
		} );

		add_action( 'revper_croncheck', array( $this, 'revper_check_reviews' ) );
		if ( ! wp_next_scheduled( 'revper_croncheck' ) ) {
			wp_schedule_event( time(), 'revper_timing', 'revper_croncheck' );
		}
	}

	public static function activated() {
		dbg( 'activated' );
	}

	public static function deactivated() {
		dbg( 'deactivated' );
		wp_clear_scheduled_hook( 'revper_croncheck' );
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
		return json_decode(get_post_meta( $post_id, 'revper_review_details', true ), true);
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