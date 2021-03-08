<?php

global $post;
?>

<div class="revper__container">
    <div class="revper__loading-screen hide"><p></p></div>
    <div class="revper__meta-box-content">
        <table class="form-table lp-metaboxes" style="margin-bottom:10px">
            <?php wp_nonce_field( '_revper_reviews_nonce', 'revper_reviews_nonce' )?>
            <?php
            foreach( $this->revper_meta_fields as $field ):
            ?>
                <tr data-domainName="<?php echo $field['domain']; ?>" >
                    <th>
                        <label
                            for="<?php echo $field['slug']; ?>"
                            style="display:block;margin-bottom:5px"
                        >
                            <strong>
                                <?php _e( $field['name'] . ( in_array($field['domain'], ['iheartjane']) ? ' ID' : ' URL' ), 'revper_reviews' ); ?>
                            </strong>
                        </label>
                        <?php
						switch($field['domain']){
							case "google":echo '<span>https://www.google.com/maps/place/****</span>';break;
							case "iheartjane":echo '<span>3333</span>';break;
							case "weedmaps":echo '<span>https://weedmaps.com/****</span>';break;
							case "yelp":echo '<span>https://www.yelp.com/biz/****</span>';break;
							case "tripadvisor":echo '<span>https://www.tripadvisor.com/****</span>';break;
							case "facebook":echo '<span>https://www.facebook.com/****</span>';break;
							case "youtube":echo '<span>https://www.youtube.com/****</span>';break;
						}
                        ?>
                    </th>
                    <td>
						<input
								class="form-control revper-key"
								type="text"
		                    <?php if (!$field['isActive']) { echo "readonly placeholder='coming soon'"; } ?>
								name="<?php echo $field['slug']; ?>"
								data-product="<?php echo $field['domain']; ?>"
								id="<?php echo $field['slug']; ?>"
								value="<?php echo get_post_meta( $post->ID, $field['slug'], true ); ?>" />
						<div></div>
					</td>
                </tr>
            <?php endforeach; ?>
        </table>
        <a id="revper_scrape_reviews" href="#" class="button button-primary" style="margin-top:10px">Scrape Reviews</a>;
    </div>
</div>