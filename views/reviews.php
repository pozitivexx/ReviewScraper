<h3><a id="reviews"></a>
	Most Recent <?php single_post_title(); ?> Reviews
</h3>

<?php
echo "<style id='revper-stylesheet'>".(file_get_contents(WP_PLUGIN_DIR.'/listingpro-reviewscraper/assests/css/revper-custom-reviews.css'))."</style>";
//echo "<style id='revper-stylesheet'>".(include_once plugins_url( '', __FILE__ ) . '/../assests/css/revper-custom-reviews.css')."</style>";

//wp_enqueue_style( 'revper-stylesheet', plugins_url( '', __FILE__ ) . '/../assests/css/revper-custom-reviews.css', array( '' ), '20170106' );
global $revper;
$reviews_domains = $revper->revper_get_reviews_details( get_the_ID() );
//pr($revper->revper_meta_fields);
//$reviews_domains = $revper->revper_get_reviews_details( get_the_ID() );

//listingpro_get_all_reviews(get_the_ID());

?>

<div id="submitreview" class="clearfix">
	<div class="revper__reviews-buttons">
		<ul>
			<li for="default" class="active">Reviews</li>
			<?php
			$reviews=[];
			foreach ( $revper->revper_meta_fields as $domain => $data ) {
				if ($data['isActive']){
					$reviews[$domain] = $revper->revper_list_reviews( get_the_ID(), $domain );
					if (!count($reviews[$domain])) continue;

					echo '<li for="'.$domain.'">';
					echo ucfirst($domain);
					echo '<span>'.($data['reviews_score']??5).'</span>';
					echo '<i class="fa fa-star"></i>';
					echo '</li>';

				}
			}
			?>
		</ul>
	</div>
	<div class="revper__reviews-tabs">
		<ul>
			<li class="default active">
				<?php  listingpro_get_all_reviews(get_the_ID()); ?>
			</li>
			<?php
			foreach ( $revper->revper_meta_fields as $domain => $data ) {
				if (!$data['isActive']) continue;
				if (!count($reviews[$domain])) continue;

				?>
				<li class="<?php echo $domain; ?>">
					<h4>
						Recent Reviews On
						<span class="revper__heading-domain">
                                <?php echo ucfirst($domain) ?>
                            </span>

						<span  class="revper__heading-total-reviews">
                                (Total Reviews: <?php echo ($data['total_reviews']??0) ?> )
                            </span>
					</h4>
					<div class="reviews-section">
						<?php foreach( $reviews[$domain] as $review ) { ?>

							<article class="review-post">
								<figure>
									<div class="review-thumbnail">
										<img src="<?php echo $review['profile_image']?>">
									</div>
									<figcaption>
										<h4><?php echo $review['reviewer']?></h4>
									</figcaption>
								</figure>
								<section class="details">
									<div class="revper__top-section">
										<div class="revper__review-score" data-count="<?php echo (int) $review['review_score'] ?>">
											<span> <?php echo $review['review_score']; ?></span>
											<i class="fa fa-star"></i>
										</div>
									</div>
									<div class="content-section">
										<p><?php echo $review['review_content']?></p>
									</div>
								</section>
							</article>

						<?php } ?>
					</div>
				</li>
			<?php } ?>
		</ul>
	</div>
</div>
<script id="reviewscript">
	jQuery( function($) {

		var tabs_buttons = $('.revper__reviews-buttons ul li');
		var tabs_content = '.revper__reviews-tabs ul li';

		tabs_buttons.on('click', function() {
			tabs_buttons.removeClass('active');
			$(tabs_content).removeClass('active');

			$(this).addClass('active');
			var cur_tab = tabs_content+'.'+$(this).attr('for');
			$(cur_tab).addClass('active');

		});

		$('#reviewscript').next('.lp-listing-reviews').hide();
	});
</script>