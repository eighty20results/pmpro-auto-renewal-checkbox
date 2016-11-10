<?php
global $wpdb, $pmpro_msg, $pmpro_msgt, $current_user;
global $pmpro_levels;
global $pmpro_level_order;
global $post;

if ( empty( $pmpro_levels ) ) {
	$pmpro_levels = pmpro_getAllLevels( false, true );
}

if ( empty( $pmpro_level_order ) ) {
	$pmpro_level_order = pmpro_getOption( 'level_order' );
}

if ( ! empty( $pmpro_level_order ) ) {
	$order = explode( ',', $pmpro_level_order );

	//reorder array
	$reordered_levels = array();
	foreach ( $order as $level_id ) {
		foreach ( $pmpro_levels as $key => $level ) {
			if ( $level_id == $level->id ) {
				$reordered_levels[] = $pmpro_levels[ $key ];
			}
		}
	}

	$pmpro_levels = $reordered_levels;
}

if ( $pmpro_msg ) {
	?>
	<div class="pmpro_message <?php echo $pmpro_msgt ?>"><?php echo $pmpro_msg ?></div>
	<?php
}

/**
 * action hook: pmpro-pre-level-list-display
 *
 * Triggered before the table of available levels is shown
 *
 * @since 1.8.11
 *
 * @param array $pmpro_levels {
 *      Array of  membership level definitions
 *
 * @type array $pmpro_level {
 *          The individual level definition
 * @type   int         id              The Level ID
 * @type   string      name            The membership level name
 * @type   string      description     The level description text
 * @type   string      confirmation    The confirmation text
 * @type   float       initial_payment The initial charge (payment)
 * @type   float       billing_amount  The recurring payment amount
 * @type   int         cycle_number    The number of cycles per billing
 * @type   string      cycle_period    The period (day/week/month/year)
 * @type   int         billing_limit   The max number of cycles to bill
 * @type   float       trial_amount    The payment for the trial period (often 0)
 * @type   int         trial_limit     The number of billing cycles to run the trial for
 * @type   int         allow_signups   Whether to allow (1) or disallow and hide (0) signups for the membership level
 * @type   int         expiration_number   The number of expiration periods to keep the membership active
 * @type   string      expiration_period   The period (day/week/month/year) to keep the membership active
 *      }
 * }
 *
 *
 * @param array $pmpro_level_order {
 *     Short description about this hash.
 *
 * @type type $var Description.
 * @type type $var Description.
 * }
 *
 * @param type $var Description.
 */
do_action( 'pmpro-pre-level-list-display' );

$pmpro_levels = apply_filters( "pmpro_levels_array", $pmpro_levels );

if ( ! function_exists( 'pmprommpu_add_group' ) &&
     ( ! function_exists( 'pmpro_advanced_levels_shortcode' ) || false === stripos( $post->post_content, '[pmpro_advanced_levels' ) )
) {
	?>
	<table id="pmpro_levels_table" class="pmpro_checkout">
	<thead>
	<tr>
		<th><?php _e( 'Level', 'pmpro' ); ?></th>
		<th><?php _e( 'Price', 'pmpro' ); ?></th>
		<th>&nbsp;</th>
	</tr>
	</thead>
	<tbody>
	<?php
	$count = 0;
	foreach ( $pmpro_levels as $level ) {
		if ( isset( $current_user->membership_level->ID ) ) {
			$current_level = ( $current_user->membership_level->ID == $level->id );
		} else {
			$current_level = false;
		}

		?>
		<tr class="<?php if ( $count ++ % 2 == 0 ) { ?>odd<?php } ?><?php if ( $current_level == $level ) { ?> active<?php } ?>">
			<input type="hidden" class="pmpro-level-id" name="pmpro-level-id" value="<?php echo $level->id ?>"/>
			<td><?php echo $current_level ? "<strong>{$level->name}</strong>" : $level->name ?></td>
			<td>
				<?php
				if ( pmpro_isLevelFree( $level ) ) {
					$cost_text = "<strong>" . __( "Free", "pmpro" ) . "</strong>";
				} else {
					$cost_text = pmpro_getLevelCost( $level, true, true );
				}
				$expiration_text = pmpro_getLevelExpiration( $level );
				if ( ! empty( $cost_text ) && ! empty( $expiration_text ) ) {
					echo $cost_text . "<br />" . $expiration_text;
				} elseif ( ! empty( $cost_text ) ) {
					echo $cost_text;
				} elseif ( ! empty( $expiration_text ) ) {
					echo $expiration_text;
				}
				?>
			</td>
			<td>
				<?php if ( empty( $current_user->membership_level->ID ) ) { ?>
					<a class="pmpro_btn pmpro_btn-select"
					   href="<?php echo pmpro_url( "checkout", "?level=" . $level->id, "https" ) ?>"><?php _e( 'Select', 'pmpro' ); ?></a>
				<?php } elseif ( ! $current_level ) { ?>
					<a class="pmpro_btn pmpro_btn-select"
					   href="<?php echo pmpro_url( "checkout", "?level=" . $level->id, "https" ) ?>"><?php _e( 'Select', 'pmpro' ); ?></a>
				<?php } elseif ( $current_level ) { ?>

					<?php
					//if it's a one-time-payment level, offer a link to renew
					if ( pmpro_isLevelExpiringSoon( $current_user->membership_level ) && $current_user->membership_level->allow_signups ) {
						?>
						<a class="pmpro_btn pmpro_btn-select"
						   href="<?php echo pmpro_url( "checkout", "?level=" . $level->id, "https" ) ?>"><?php _e( 'Renew', 'pmpro' ); ?></a>
						<?php
					} else {
						?>
						<a class="pmpro_btn disabled"
						   href="<?php echo pmpro_url( "account" ) ?>"><?php _e( 'Your&nbsp;Level', 'pmpro' ); ?></a>
						<?php
					}
					?>

				<?php } ?>
			</td>
		</tr>
		<?php
	}
	?>
	</tbody>
	</table><?php
}

if ( function_exists( 'pmprommpu_add_group' ) ) {

	add_filter( 'pmpro_pages_custom_template_path', 'e20r_remove_annual_levels_page', 99, 5 );

	echo pmpro_loadTemplate( 'levels', 'local', 'pages', 'php' );
}
?>
<nav id="nav-below" class="navigation" role="navigation">
	<div class="nav-previous alignleft">
		<?php if ( ! empty( $current_user->membership_level->ID ) ) { ?>
			<a href="<?php echo pmpro_url( "account" ) ?>"><?php _e( '&larr; Return to Your Account', 'pmpro' ); ?></a>
		<?php } else { ?>
			<a href="<?php echo home_url() ?>"><?php _e( '&larr; Return to Home', 'pmpro' ); ?></a>
		<?php } ?>
	</div>
</nav>
<?php
function e20r_remove_annual_levels_page( $default_templates, $page_name, $type, $location, $ext = 'php' ) {

	foreach ( $default_templates as $k => $path ) {

		if ( false !== stripos( $path, 'e20r-annual-pricing-choice' ) ) {

			if ( WP_DEBUG ) {
				error_log( "Removing {$path} from list of template locations" );
			}
			unset( $default_templates[ $k ] );
		}
	}

	if ( WP_DEBUG ) {
		error_log( "Now using {$page_name} template from {$location}: " . print_r( $default_templates, true ) );
	}

	return $default_templates;
}
