<?php
/*
Plugin Name: E20R Annual Pricing Choice for Paid Memberships Pro
Plugin URI: http://eighty20results.com/wp-plugins/e20r-annual-pricing-choice
Description: Allow selecting annual or monthly payment, if Membership levels are configured to support it
Version: 1.0.1
Requires: 4.5.0
Author: Thomas Sjolshagen <thomas@eighty20results.com>
Author URI: http://www.eighty20results.com/thomas-sjolshagen/
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Domain Path: /languages
Text Domain: e20rapc
*/
/**
 * Copyright (C) 2016  Thomas Sjolshagen - Eighty / 20 Results by Wicked Strong Chicks, LLC
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

define( 'E20R_ANNUAL_PRICING_VER', '1.0' );

class e20rAnnualPricing {

	private $annual_levels = array();
	private $settings;

	public function __construct() {

	}

	/**
	 * Loads an instance of the plugin and configures key actions,.
	 */
	public static function register() {

		if ( ! function_exists( 'pmpro_getAllLevels' ) ) {
			return;
		}

		$plugin = new self;

		$plugin->load_filters();

		add_action( 'init', array( $plugin, 'load_translation' ) );
		add_action( 'wp_enqueue_scripts', array( $plugin, 'load_javascript' ) );
		add_action( 'wp_enqueue_scripts', array( $plugin, 'load_css' ) );
		add_action( 'admin_enqueue_scripts', array( $plugin, 'load_css' ) );
		add_action( 'pmpro-pre-level-list-display', array( $plugin, 'show_renewal_options' ) );

		add_action( 'pmpro_membership_level_after_other_settings', array( $plugin, 'add_level_settings' ) );
		add_action( 'pmpro_save_membership_level', array( $plugin, 'save_level_settings' ) );
		add_action( 'pmpro_delete_membership_level', array( $plugin, 'delete_level_settings' ) );

		if ( 'end_of_period' === $plugin->get_setting( 'terminate' ) &&
		     ! has_action( 'pmpro_after_change_membership_level', array( $plugin, 'after_change_membership_level' ) )
		) {

			add_action( 'pmpro_after_change_membership_level', array(
				$plugin,
				'after_change_membership_level'
			), 10, 2 );
		}

		if ( 'end_of_period' === $plugin->get_setting( 'terminate' ) &&
		     ! has_action( 'pmpro_before_change_membership_level', array( $plugin, 'before_change_membership_level' ) )
		) {

			add_action( 'pmpro_before_change_membership_level', array(
				$plugin,
				'before_change_membership_level'
			), 10, 2 );
		}

	}

	public function save_level_settings( $level_id ) {

		foreach ( $_REQUEST as $key => $value ) {

			if ( false !== strpos( $key, 'e20r-pricing_' ) ) {

				$rk_arr = explode( '_', $key );

				$real_key = $rk_arr[ ( count( $rk_arr ) - 1 ) ];
				$this->save_setting( $real_key, sanitize_text_field( $_REQUEST[ $key ] ), $level_id );
			}
		}
	}

	public function delete_level_settings( $level_id ) {

		if ( !empty( $this->settings[$level_id])) {
			unset($this->settings[$level_id]);
		}

		update_option( 'e20r_annual_pricing', $this->settings, false );
	}

	/**
	 * @param null $key
	 * @param null $value
	 * @param string $level_id
	 */
	public function save_setting( $key = null, $value = null, $level_id = 'default' ) {

		// Configure default setting(s).
		if ( empty( $this->settings ) ) {

			$this->settings = $this->default_settings();
		}

		// Append the settings array for the level ID if it doesn't exits
		if ( ! is_array( $this->settings[ $level_id ] ) ) {
			$this->settings[ $level_id ] = array();
		}

		// Assign the key/value pair
		$this->settings[ $level_id ][ $key ] = $value;

		if ( false == update_option( 'e20r_annual_pricing', $this->settings, false ) ) {
			$this->set_message( sprintf( __( "Unable to save Annual Pricing Choices settings for %s", "e20rapc" ), $key ), "error" );
		}
	}

	/**
	 * @param $msg
	 * @param $class
	 */
	private function set_message( $msg, $class ) {
		?>
		<div class="<?php echo $class; ?>">
			<?php echo $msg; ?>
		</div>
		<?php
	}

	/**
	 * @return array
	 */
	private function default_settings() {

		return array(
			'default' => array(
				'choice'    => apply_filters( 'e20r-renewal-choice-default', 'monthly' ),
				'terminate' => apply_filters( 'e20r-renewal-choice-end-membership', 'immediately' ),
				'annual'    => -1,
			),
		);
	}

	/**
	 * @param $key
	 * @param string $level_id
	 *
	 * @return null
	 */
	public function get_setting( $key, $level_id = 'default' ) {

		if ( empty( $this->settings ) ) {
			$this->settings = get_option( 'e20r_annual_pricing', $this->default_settings() );
		}

		return ( isset( $this->settings[ $level_id ][ $key ] ) ? $this->settings[ $level_id ][ $key ] : null );
	}

	/**
	 * Load the required locale/translation file for the plugin
	 */
	function load_translation() {

		$locale = apply_filters( "plugin_locale", get_locale(), "e20rapc" );
		$mo     = "e20rapc-{$locale}.mo";

		//paths to local (plugin) and global (WP) language files
		$local_mo  = plugin_dir_path( __FILE__ ) . "/languages/{$mo}";
		$global_mo = WP_LANG_DIR . "/e20rapc/{$mo}";

		//load global first
		load_textdomain( "e20rapc", $global_mo );

		//load local second
		load_textdomain( "e20rapc", $local_mo );
	}

	/**
	 * Load filter hooks we'll need to use
	 */
	public function load_filters() {

		$this->get_annual_levels();
		add_filter( 'pmpro_pages_custom_template_path', array( $this, 'level_page_path' ), 10, 5 );

		if ( 'end_of_period' === $this->get_setting( 'terminate' ) && ! has_filter( 'pmpro_email_body', array(
				$this,
				'cancellation_email_body'
			) )
		) {

			add_filter( 'pmpro_email_body', array( $this, 'cancellation_email_body' ), 10, 2 );
		}
	}

	/**
	 * Enqueue and configure JavaScript functionality for the plugin (front or back end)
	 */
	public function load_javascript() {

		global $post;
		global $pmpro_pages;

		// Only load the annual/monthly levels selection when on the PMPro Levels page
		if ( ! is_admin() && isset( $post->ID ) && $post->ID == $pmpro_pages['levels'] ) {

			$this->get_annual_levels();

			wp_register_script( 'e20r-annual-pricing', plugins_url( 'js/e20r-annual-pricing-choice.js', __FILE__ ), array( 'jquery' ), E20R_ANNUAL_PRICING_VER, true );

			wp_localize_script( 'e20r-annual-pricing', 'e20r_annual_pricing', array(
					'levels'  => $this->annual_levels,
					'level_map' => $this->get_level_map(),
					'free_levels' => $this->get_free_levels(),
					'default' => $this->get_setting( 'choice' ),
				)
			);

			wp_enqueue_script( 'e20r-annual-pricing' );
		}

		// Load wp-admin specific JavaScript
		if ( is_admin() && defined( 'DOING_AJAX' ) && true !== DOING_AJAX ) {

		}
	}

	/**
	 * Enqueue Style sheets
	 */
	public function load_css() {

		global $post;
		global $pmpro_pages;

		// Only load the annual/monthly levels style sheets when on the PMPro Levels page
		// if ( ! is_admin() && isset( $post->ID ) && $post->ID == $pmpro_pages['levels'] ) {
			wp_enqueue_style( 'e20r-annual-pricing', plugins_url( 'css/e20r-annual-pricing-choice.css', __FILE__ ), null, E20R_ANNUAL_PRICING_VER );
		//}
	}

	/**
	 * Display (echo) the renewal option HTML
	 */
	public function show_renewal_options() {

		echo $this->annual_renewal_html();
	}

	/**
	 * Generate radio button(s) to select the renewal payment frequency
	 *
	 * @param   string|int    $level_id     - The ID of the membership level, or 'default'
	 *
	 * @return  string                      - The HTML containing the radio buttons.
	 *
	 */
	private function annual_renewal_html( $level_id = 'default' ) {

		$renewal_choice = $this->get_setting( 'choice', $level_id );

		ob_start();
		?>
		<div class="e20r-annual-pricing-choice">
			<h3 class="e20r-annual-pricing-choice-header"><?php echo apply_filters( 'e20r-renewal-choice-header', __( "Payment choice", "e20rapc" ) ); ?>: <span class="e20r-annual-pricing-choices">
			<input type="radio" id="e20r-monthly-pricing-choice" name="e20r-renewal_choice" class="e20r-annual-pricing"
			       value="monthly" <?php checked( $renewal_choice, 'monthly' ); ?>/>
			<label class="e20r-annual-pricing-header"
			       for="e20r-monthly-pricing-choice"><?php _e( 'Monthly', 'e20rapc' ); ?></label>
			<input type="radio" id="e20r-annual-pricing-choice" name="e20r-renewal_choice" class="e20r-annual-pricing"
			       value="annually" <?php checked( $renewal_choice, 'annually' ); ?>/>
			<label class="e20r-annual-pricing-header"
			       for="e20r-annual-pricing-choice"><?php _e( 'Annual', 'e20rapc' ); ?></label>
			</span></h3>
		</div>
		<?php
		$html = ob_get_clean();

		return $html;
	}

	/**
	 * @return array
	 */
	private function get_level_map() {

		$level_map = array();
		$all_levels = pmpro_getAllLevels( true );

		// Find all monthly levels & map their annual equivalent
		foreach( $all_levels as $id => $level ) {

			if ( $level->cycle_number > 0 && 'month' === strtolower( $level->cycle_period) ) {
				$level_map[$level->id] = $this->get_setting( 'annual', $level->id );
			}
		}
		return $level_map;
	}

	/**
	 * Generate the level setting HTML
	 */
	public function add_level_settings() {

		$level_id = isset($_REQUEST['edit'] ) ? intval($_REQUEST['edit']) : 'default';
		$is_monthly = false;

		$this->get_annual_levels();

		if ( is_numeric( $level_id ) ) {

			$levels = pmpro_getAllLevels( true );
			$is_monthly = ( $levels[$level_id]->cycle_number > 0 && 'month' === strtolower( $levels[$level_id]->cycle_period) ? true : false );
		}
		?>
		<hr />
		<h3 class="e20r-annual-pricing-choice-header"><?php _e("Annual Pricing Settings", "e20rapc"); ?></h3>
		<div class="e20r-annual-pricing-choice-settings">
			<div class="e20r-settings-body">
				<div class="e20r-settings-row">
					<div class="e20r-settings-cell">
						<label for="e20r-pricing_choice"><?php _e( "Default selection", "e20rapc" ); ?>:</label>
					</div>
					<div class="e20r-settings-cell">
						<select name="e20r-pricing_choice" class="e20r-pricing_choice">
							<option
								value="-1" <?php selected( -1, $this->get_setting( 'choice', $level_id ) ); ?>><?php _e( "Not Applicable" ); ?></option>
							<option
								value="monthly" <?php selected( 'monthly', $this->get_setting( 'choice', $level_id ) ); ?>><?php _e( "Monthly" ); ?></option>
							<option
								value="annual" <?php selected( 'annual', $this->get_setting( 'choice', $level_id ) ); ?>><?php _e( "Annual" ); ?></option>
						</select>
					</div>
				</div>
				<?php if ( true === $is_monthly ) { ?>
				<div class="e20r-settings-row">
					<div class="e20r-settings-cell">
						<label for="e20r-pricing_choice"><?php _e( "Level to pair with:", "e20rapc" ); ?>:</label>
					</div>
					<div class="e20r-settings-cell">
						<select name="e20r-pricing_annual" class="e20r-pricing_annual">
							<option value="-1" <?php selected( -1, $this->get_setting( 'annual', $level_id ) ); ?>><?php _e( "Not Applicable" ); ?></option>
							<?php
							foreach( $this->annual_levels as $a_id ) {
								$level_info = pmpro_getLevel( $a_id ); ?>
							<option value="<?php echo $a_id; ?>" <?php selected( $a_id, $this->get_setting( 'annual', $level_id ) ); ?>>
								<?php esc_attr_e( $level_info->name ); ?>
							</option>
							<?php
							}

							?>

						</select>
					</div>
				</div>
				<?php } ?>
				<div class="e20r-settings-row">
					<div class="e20r-settings-cell">
						<label for="e20r-pricing_terminate"><?php _e( "Membership ends", "e20rapc" ); ?>:</label>
					</div>
					<div class="e20r-settings-cell">
						<select name="e20r-pricing_terminate" class="e20r-pricing_terminate">
							<option
								value="immediately" <?php selected( 'immediately', $this->get_setting( 'terminate', $level_id ) ); ?>><?php _e( "Immediately" ); ?></option>
							<option
								value="end_of_period" <?php selected( 'end_of_period', $this->get_setting( 'terminate', $level_id ) ); ?>><?php _e( "At the end of the billing period" ); ?></option>
						</select>
					</div>
				</div>

			</div>
		</div>
		<?php
	}

	/**
	 * Load the custom levels page for this plugin
	 *
	 * @param array $default_templates - Path to the template files
	 * @param string $page_name - 'levels', 'checkout', etc
	 * @param string $type - 'pages', 'email', 'adminpages', etc
	 * @param string $location - 'local' or 'url' (local == on the local file system)
	 * @param string $ext - 'html' or 'php' typically, but can be any valid file extension
	 *
	 * @return array    - Array of paths to look for the page(s) in.
	 */
	public function level_page_path( $default_templates, $page_name, $type, $location, $ext = 'php' ) {

		$template_path = plugin_dir_path( __FILE__ ) . "{$type}/{$page_name}.{$ext}";

		if ( ( 'levels' === $page_name ) && ( file_exists( $template_path )) ){
			$default_templates = array(
				$template_path
			);
		} else {
			if(WP_DEBUG) {
				error_log("Unable to load the {$page_name}.{$ext} template from {$location}");
			}
		}

		if (WP_DEBUG) {
			error_log("Loading {$page_name} template from {$location}: " . print_r($default_templates, true));
		}

		return $default_templates;
	}

	/**
	 * Extract all membership levels with an annualized renewal cycle
	 *
	 * @return array - List of level IDs saved as class variable $annual_levels
	 */
	public function get_annual_levels() {

		// Update if the annual levels array hasn't been loaded yet
		if ( empty( $this->annual_levels ) ) {

			$levels = pmpro_getAllLevels( false, true );

			foreach ( $levels as $level ) {

				if ( 'year' === strtolower( $level->cycle_period ) &&
				     1 >= $level->cycle_number &&
				     1 == $level->allow_signups
				) {

					// Annualized membership cycle
					$this->annual_levels[] = $level->id;
				}
			}
		}
	}

	/**
	 * Fetch all free membership levels
	 *
	 * @return array    Array of level ID's that are free.
	 */
	public function get_free_levels() {

		$levels = pmpro_getAllLevels(true);
		$free_levels = array();

		foreach( $levels as $level ) {
			if (true === pmpro_isLevelFree( $level ) ) {
				$free_levels[] = $level->id;
			}
		}

		return $free_levels;
	}

	/**
	 * Preserve the timestamp for the next scheduled payment (before it's removed/cancelled as part of the cancellation action)
	 *
	 * @param int $level_id The ID of the membership level we're changing to for the user
	 * @param int $user_id The User ID we're changing membership information for
	 *
	 * @return int  $pmpro_next_payment_timestamp   - The UNIX epoch value for the next payment (as a global variable)
	 */
	public function before_change_membership_level( $level_id, $user_id ) {

		global $pmpro_pages;
		global $pmpro_stripe_event;
		global $pmpro_next_payment_timestamp;

		// Only process this hook if we're on the PMPro cancel page (and it's not triggered from the user's profile page)
		if ( 0 == $level_id &&
		     ( is_page( $pmpro_pages['cancel'] ) || (
				     is_admin() && ( empty( $_REQUEST['from'] ) || 'profile' != trim( $_REQUEST['from'] ) ) )
		     )
		) {

			// Retrieve the last succssfully paid-for order
			$order = new MemberOrder();
			$order->getLastMemberOrder( $user_id, "success" );

			// When using PayPal Express or Stripe, use their API to get the 'end of this subscription period' value
			if ( ! empty( $order->id ) && 'stripe' == $order->gateway ) {

				if ( ! empty( $pmpro_stripe_event ) ) {

					// The Stripe WebHook is asking us to cancel the membership
					if ( ! empty( $pmpro_stripe_event->data->object->current_period_end ) ) {

						$pmpro_next_payment_timestamp = $pmpro_stripe_event->data->object->current_period_end;
					}

				} else {

					//User initiated cancellation event, request the data from the Stripe.com service
					$pmpro_next_payment_timestamp = PMProGateway_stripe::pmpro_next_payment( "", $user_id, "success" );
				}

			} elseif ( ! empty( $order->id ) && "paypalexpress" == $order->gateway ) {
				if ( ! empty( $_POST['next_payment_date'] ) && 'N/A' != $_POST['next_payment_date'] ) {

					// PayPal server initiated the cancellation (via their IPN hook)
					$pmpro_next_payment_timestamp = strtotime( $_POST['next_payment_date'], current_time( 'timestamp' ) );
				} else {

					// User initiated cancellation event, request the data from the PayPal service
					$pmpro_next_payment_timestamp = PMProGateway_paypalexpress::pmpro_next_payment( "", $user_id, "success" );
				}
			}
		}
	}

	/**
	 * Process the cancellation and set the expiration (enddate value) date to be the last day of the subscription period
	 *
	 * @param int $level_id The ID of the membership/subscription level they're currently at
	 * @param int $user_id The ID of the user on this system
	 *
	 * @return bool
	 */
	public function after_change_membership_level( $level_id, $user_id ) {

		global $pmpro_pages;
		global $pmpro_next_payment_timestamp;

		global $wpdb;

		// Only process this if we're on the cancel page
		if ( 0 === $level_id && (
				is_page( $pmpro_pages['cancel'] ) || (
					is_admin() && ( empty( $_REQUEST['from'] ) || 'profile' != $_REQUEST['from'] ) )
			)
		) {

			// Search the databas for the most recent order object
			$order = new MemberOrder();
			$order->getLastMemberOrder( $user_id, "cancelled" );

			// Nothing to do if there's no order found
			if ( empty( $order->id ) ) {
				return false;
			}

			// Fetch the most recent membership level definition for the user
			$sql = $wpdb->prepare( "
				SELECT * 
				FROM {$wpdb->pmpro_memberships_users} 
				WHERE membership_id = %d 
					AND user_id = %d 
				ORDER BY id 
				DESC LIMIT 1",
				intval( $order->membership_id ),
				intval( $user_id )
			);

			$level = $wpdb->get_row( $sql );

			// Return error if the last level wasn't a recurring one
			if ( isset( $level->cycle_number ) && $level->cycle_number >= 1 ) {
				return false;
			}

			// Return error if there's no level found
			if ( ! isset( $level->id ) || empty( $level ) ) {
				return false;
			}

			// Format the date string for the the last time the order was processed
			$lastdate = date_i18n( "Y-m-d", $order->timestamp );


			/**
			 * Find the timestamp indicating when the next payment is supposed to occur
			 * For PayPal Express and Stripe, we'll use their native gateway look-up functionality
			 */
			if ( ! empty( $pmpro_next_payment_timestamp ) ) {

				// Stripe or PayPal would have configured this global
				$nextdate = $pmpro_next_payment_timestamp;

			} else {

				// Calculate when the next scheduled payment is estimated to happen
				$nextdate_sql = $wpdb->prepare( "
					SELECT 
						UNIX_TIMESTAMP( %s + INTERVAL %d {$level->cycle_period})
					",
					$lastdate,
					intval( $level->cycle_number )
				);

				$next_payment = $wpdb->get_var( $nextdate_sql );
			}

			/**
			 * Process this if the next payment date is in the future
			 */
			if ( $next_payment - current_time( 'timestamp' ) > 0 ) {

				// Fetch their previous membership level info
				$old_level_sql = $wpdb->prepare( "
					SELECT * 
					FROM {$wpdb->pmpro_memberships_users} 
					WHERE membership_id = %d 
						AND user_id = %d 
					ORDER BY id DESC 
					LIMIT 1",
					intval( $order->membership_id ),
					intval( $user_id )
				);

				$old_level = $wpdb->get_row( $old_level_sql, ARRAY_A );

				// Only makes sense to do this if the user has an old payment level.
				if ( ! empty( $old_level ) ) {

					$old_level['enddate'] = date_i18n( "Y-m-d H:i:s", $next_payment );

					// Remove action so we won't cause ourselves to loop indefinitely (or for 255 loops, whichever comes first).
					remove_action( 'pmpro_after_change_membership_level', array(
						$this,
						'after_change_membership_level'
					), 10, 2 );

					// Remove action in case it's here.
					if ( function_exists( 'my_pmpro_cancel_previous_subscriptions' ) &&
					     has_filter( 'pmpro_cancel_previous_subscriptions', 'my_pmpro_cancel_previous_subscriptions' )
					) {

						remove_filter( 'pmpro_cancel_previous_subscriptions', 'my_pmpro_cancel_previous_subscriptions' );
					}

					// Change the membership level for the user to the new "old level"
					pmpro_changeMembershipLevel( $old_level, $user_id );

					// Reattach this action (function)
					add_action( 'pmpro_after_change_membership_level', array(
						$this,
						'after_change_membership_level'
					), 10, 2 );

					// Add the backwards compatible filter (if it exists)
					if ( ! has_filter( 'pmpro_cancel_previous_subscriptions', 'my_pmpro_cancel_previous_subscriptions' ) &&
					     function_exists( 'my_pmpro_cancel_previous_subscriptions' )
					) {

						add_filter( 'pmpro_cancel_previous_subscriptions', 'my_pmpro_cancel_previous_subscriptions' );
					}

					// Change the cancelleation message shown on cancel confirmation page
					add_filter( 'gettext', array( $this, 'update_cancel_text' ), 10, 3 );
				}
			}
		}
	}

	/**
	 * Filter to replace the "your membership has been cancelled" message with something to indicate when it'll be cancelled from.
	 *
	 * @param string $translated_text
	 * @param string $text
	 * @param string $domain
	 *
	 * @return      string      - Updated/modified 'translation' of the text (always)
	 */
	public function change_cancel_text( $translated_text, $text, $domain ) {

		global $current_user;

		// Update the membership cancellation text if needed
		if ( 'pmpro' === $domain && 'Your membership has been cancelled.' === $text ) {

			$next_payment_date = date_i18n( get_option( "date_format" ), pmpro_next_payment( $current_user->ID, "cancelled" ) );
			$translated_text   = sprintf( __( "Your subscription has been cancelled. Your access will expire on %s", "e20rapc" ), $next_payment_date );
		}

		return $translated_text;
	}

	/**
	 * Update the body of the email message on cancellation to reflect the actual cancellation date for the subscription
	 *
	 * @param   string $body - Body of the email message
	 * @param   PMProEmail $email - The PMPro Email object
	 *
	 * @return  string          - The new body of the email message
	 */
	function cancellation_email_body( $body, $email ) {

		if ( $email->template == "cancel" ) {

			$user = get_user_by( 'email', $email->email );

			if ( ! empty( $user->ID ) ) {

				$expiration_date = pmpro_next_payment( $user->ID );

				//if the date in the future?
				if ( $expiration_date - time() > 0 ) {

					$enddate = date_i18n( get_option( "date_format" ), $expiration_date );

					$body .= "<p>" . sprintf( __( "Your subscription has been cancelled. Your access will expire on %s", "e20rapc" ), $enddate ) . "</p>";
				}
			}
		}

		return $body;
	}

}

/**
 * Load this plugin
 */
add_action( 'plugins_loaded', 'e20rAnnualPricing::register', 5 );
