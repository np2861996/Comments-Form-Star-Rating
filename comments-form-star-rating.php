<?php

/**
 * 
 * @link              https://www.nikhilpatel.com/
 * @since             1.0.0
 * @package           Comments_Form_Star_Rating
 * 
 * 
 * @wordpress-plugin
 * Plugin Name:       Comments Form Star Rating for WordPress
 * Plugin URI:        https://www.github.com/nikhilpatel
 * Description:       Add star ratings in post comment form.
 * Version:           1.0.0
 * Author:            BeyondN
 * Author URI:        https://www.nikhilpatel.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       cfsr
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'CFSR_PLUGIN_VERSION', '1.0.0' );

// Plugin path.
if ( ! defined( 'CFSR_PLUGIN_PATH' ) ) {
	define( 'CFSR_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
}

// Plugin URL.
if ( ! defined( 'CFSR_PLUGIN_URL' ) ) {
	define( 'CFSR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

//Enqueue the plugin's styles.
add_action( 'wp_enqueue_scripts', 'cfsr_styles' );
function cfsr_styles() {

	wp_register_style( 'cfsr-stucture', plugins_url( '/', __FILE__ ) . 'assets/stucture.css' );

	wp_enqueue_style( 'dashicons' );
	wp_enqueue_style( 'cfsr-stucture' );
}

//Create the rating interface.
add_action( 'comment_form_logged_in_after', 'cfsr_field' );
add_action( 'comment_form_after_fields', 'cfsr_field' );
function cfsr_field () {
	?>
	<label for="rating">Rating<span class="required">*</span></label>
	<fieldset class="comments-rating">
		<span class="rating-container">
			<?php for ( $i = 5; $i >= 1; $i-- ) : ?>
				<input type="radio" id="rating-<?php echo esc_attr( $i ); ?>" name="rating" value="<?php echo esc_attr( $i ); ?>" /><label for="rating-<?php echo esc_attr( $i ); ?>"><?php echo esc_html( $i ); ?></label>
			<?php endfor; ?>
			<input type="radio" id="rating-0" class="star-cb-clear" name="rating" value="0" /><label for="rating-0">0</label>
		</span>
	</fieldset>
	<?php
}

//Save the rating submitted by the user.
add_action( 'comment_post', 'cfsr_save_comment_rating' );
function cfsr_save_comment_rating( $comment_id ) {
	if ( ( isset( $_POST['rating'] ) ) && ( '' !== $_POST['rating'] ) )
	$rating = intval( $_POST['rating'] );
	add_comment_meta( $comment_id, 'rating', $rating );
}

//Make the rating required.
add_filter( 'preprocess_comment', 'cfsr_require_rating' );
function cfsr_require_rating( $commentdata ) {
	if ( ! is_admin() && ( ! isset( $_POST['rating'] ) || 0 === intval( $_POST['rating'] ) ) )
	wp_die( __( 'Error: You did not add a rating. Hit the Back button on your Web browser and resubmit your comment with a rating.' ) );
	return $commentdata;
}

//Display the rating on a submitted comment.
add_filter( 'comment_text', 'cfsr_display_rating');
function cfsr_display_rating( $comment_text ){

	if ( $rating = get_comment_meta( get_comment_ID(), 'rating', true ) ) {
		$stars = '<p class="stars">';
		for ( $i = 1; $i <= $rating; $i++ ) {
			$stars .= '<span class="dashicons dashicons-star-filled"></span>';
		}
		$stars .= '</p>';
		$comment_text = $comment_text . $stars;
		return $comment_text;
	} else {
		return $comment_text;
	}
}

//Get the average rating of a post.
function cfsr_get_average_ratings( $id ) {
	$comments = get_approved_comments( $id );

	if ( $comments ) {
		$i = 0;
		$total = 0;
		foreach( $comments as $comment ){
			$rate = get_comment_meta( $comment->comment_ID, 'rating', true );
			if( isset( $rate ) && '' !== $rate ) {
				$i++;
				$total += $rate;
			}
		}

		if ( 0 === $i ) {
			return false;
		} else {
			return round( $total / $i, 1 );
		}
	} else {
		return false;
	}
}

//Display the average rating above the content.
add_filter( 'the_content', 'cfsr_display_average_rating' );
function cfsr_display_average_rating( $content ) {

	global $post;

	if ( false === cfsr_get_average_ratings( $post->ID ) ) {
		return $content;
	}
	
	$stars   = '';
	$average = cfsr_get_average_ratings( $post->ID );

	for ( $i = 1; $i <= $average + 1; $i++ ) {
		
		$width = intval( $i - $average > 0 ? 20 - ( ( $i - $average ) * 20 ) : 20 );

		if ( 0 === $width ) {
			continue;
		}

		$stars .= '<span style="overflow:hidden; width:' . $width . 'px" class="dashicons dashicons-star-filled"></span>';

		if ( $i - $average > 0 ) {
			$stars .= '<span style="overflow:hidden; position:relative; left:-' . $width .'px;" class="dashicons dashicons-star-empty"></span>';
		}
	}
	
	$custom_content  = '<p class="average-rating">This post\'s average rating is: ' . $average .' ' . $stars .'</p>';
	$custom_content .= $content;
	return $custom_content;
}