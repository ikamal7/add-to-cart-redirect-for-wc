<?php
	/**
	 * Plugin Name:  Add to Cart Redirect Link for WooCommerce
	 * Plugin URI:   https://github.com/ikamal7/add-to-cart-redirect-for-wc
	 * Description:  Redirect to checkout page or any other page after successfully product added to cart.
	 * Version:      1.0.0
	 * Author:       Kamal Hosen
	 * Text Domain:  woo-add-to-cart-redirect
	 * Requires at least: 5.0
	 * Tested up to: 6.0
	 * WC requires at least: 2.7
	 * WC tested up to: 6.9.3
	 * Domain Path:  /languages
	 * Author URI:   https://kamalhosen.me/
	 * License:      GPLv3
	 * License URI:  http://www.gnu.org/licenses/gpl-3.0.html
	 */
	
	defined( 'ABSPATH' ) or die( 'Keep Quit' );
	
	if ( ! class_exists( 'Add_Cart_Redirect_Woo' ) ):
		
		final class Add_Cart_Redirect_Woo {
			
			protected $_version = '1.0.0';
			
			protected static $_instance = null;
			
			public static function instance() {
				if ( is_null( self::$_instance ) ) {
					self::$_instance = new self();
				}
				
				return self::$_instance;
			}
			
			public function __construct() {
				$this->constants();
				$this->hooks();
				do_action( 'woo_cart_redirect_to_checkout_page_loaded', $this );
			}
			
			public function constants() {
				$this->define( 'WOO_ATCR_URL', plugin_dir_url( __FILE__ ) );
				$this->define( 'WOO_ATCR_DIR', plugin_dir_path( __FILE__ ) );
				$this->define( 'WOO_ATCR_DIRNAME', dirname( plugin_basename( __FILE__ ) ) );
				$this->define( 'WOO_ATCR_BASENAME', plugin_basename( __FILE__ ) );
				$this->define( 'WOO_ATCR_FILE', __FILE__ );
			}
			
			public function define( $name, $value, $case_insensitive = false ) {
				if ( ! defined( $name ) ) {
					define( $name, $value, $case_insensitive );
				}
			}
			
			public function hooks() {
				
				// Init
				add_action( 'init', array( $this, 'language' ) );
				
				// Product Settings Option
				add_filter( 'woocommerce_product_settings', array( $this, 'settings' ) );
				
				// Cart Redirect
				add_filter( 'woocommerce_add_to_cart_redirect', array( $this, 'cart_redirect' ) );
				
				// Ajax Add to cart redirect
				
				if ( version_compare( WC()->version, '3.0.0', '<' ) ) {
					add_filter( 'wc_add_to_cart_params', array( $this, 'add_to_cart_js_params' ) );
				}
				
				add_filter( 'woocommerce_get_script_data', array( $this, 'add_script_data' ), 10, 2 );
				
				// add_action( 'admin_notices', array( $this, 'feed' ) );
				// Plugin Row Meta
				// add_filter( 'plugin_action_links_' . WOO_ATCR_BASENAME, array( $this, 'plugin_action_links' ) );
				
				// add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2 );
			}
			
			public function language() {
				load_plugin_textdomain( 'woo-add-to-cart-redirect', false, WOO_ATCR_DIRNAME . '/languages' );
			}
			
			// @TODO: OLD WC
			public function add_to_cart_js_params( $data ) {
				
				$data[ 'cart_redirect_after_add' ] = (bool) get_option( 'woo_cart_redirect_to_page' ) ? 'yes' : 'no';
				
				return $data;
			}
			
			public function add_script_data( $params, $handle ) {
				if ( 'wc-add-to-cart' == $handle ) {
					$params = array_merge( $params, array(
						'cart_redirect_after_add' => (bool) get_option( 'woo_cart_redirect_to_page' ) ? 'yes' : 'no'
					) );
				}
				
				return $params;
			}
			
			public function cart_redirect( $url ) {
				
				if ( (bool) get_option( 'woo_cart_redirect_to_page' ) ) {
					$url = get_permalink( get_option( 'woo_cart_redirect_to_page' ) );
				}
				
				return apply_filters( 'woo_cart_redirect_to_page', $url );
			}
			
			public function plugin_action_links( $links ) {
				
				$settings_link = esc_url( add_query_arg( array( 'page' => 'wc-settings', 'tab' => 'products', 'section' => 'display' ), admin_url( 'admin.php' ) ) );
				
				$new_links[ 'settings' ] = sprintf( '<a href="%1$s" title="%2$s">%2$s</a>', $settings_link, esc_attr__( 'Settings', 'woo-add-to-cart-redirect' ) );
				
				if ( ! class_exists( 'Add_Cart_Redirect_Woo_Pro' ) ):
					$pro_link = esc_url( add_query_arg( array( 'utm_source' => 'wp-admin-plugins', 'utm_medium' => 'go-pro', 'utm_campaign' => 'woo-add-to-cart-redirect' ), '' ) );
					
					$new_links[ 'go-pro' ] = sprintf( '<a target="_blank" style="color: #45b450; font-weight: bold;" href="%1$s" title="%2$s">%2$s</a>', $pro_link, esc_attr__( 'Go Pro', 'woo-add-to-cart-redirect' ) );
				endif;
				
				return array_merge( $links, $new_links );
			}
			
			public function basename() {
				return WOO_ATCR_BASENAME;
			}
			
			public function plugin_row_meta( $links, $file ) {
				if ( $file == $this->basename() ) {
					
					$report_url = add_query_arg( array(
						                             'utm_source'   => 'wp-admin-plugins',
						                             'utm_medium'   => 'row-meta-link',
						                             'utm_campaign' => 'woo-add-to-cart-redirect'
					                             ), 'https://wpbranch.com/tickets/' );
					
					$documentation_url = add_query_arg( array(
						                                    'utm_source'   => 'wp-admin-plugins',
						                                    'utm_medium'   => 'row-meta-link',
						                                    'utm_campaign' => 'woo-add-to-cart-redirect'
					                                    ), 'https://wpbranch.com/documentation/single_docs' );
					
					$row_meta[ 'documentation' ] = sprintf( '<a target="_blank" href="%1$s" title="%2$s">%2$s</a>', esc_url( $documentation_url ), esc_html__( 'Read Documentation', 'woo-add-to-cart-redirect' ) );
					$row_meta[ 'issues' ]        = sprintf( '%2$s <a target="_blank" href="%1$s">%3$s</a>', esc_url( $report_url ), esc_html__( 'Facing issue?', 'woo-add-to-cart-redirect' ), '<span style="color: red">' . esc_html__( 'Please open a ticket.', 'woo-add-to-cart-redirect' ) . '</span>' );
					
					return array_merge( $links, $row_meta );
				}
				
				return (array) $links;
			}
			
			public function version() {
				return esc_attr( $this->_version );
			}
			
			public function feed() {
				
				$api_url = '';
				
				if ( apply_filters( 'stop_gwp_live_feed', false ) ) {
					return;
				}
				
				if ( isset( $_GET[ 'raw_wpb_com_live_feed' ] ) ) {
					delete_transient( "wpb_com_live_feed" );
				}
				
				if ( false === ( $body = get_transient( 'wpb_com_live_feed' ) ) ) {
					$response = wp_remote_get( $api_url, $args = array(
						'sslverify' => false,
						'timeout'   => 45,
						'body'      => array( 'item' => 'woo-add-to-cart-redirect', 'version' => $this->version() ),
					) );
					
					if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) == 200 ) {
						$body = json_decode( wp_remote_retrieve_body( $response ), true );
						set_transient( "wpb_com_live_feed", $body, 6 * HOUR_IN_SECONDS );
						
						if ( isset( $_GET[ 'raw_wpb_com_live_feed' ] ) && isset( $body[ 'id' ] ) ) {
							delete_transient( "wpb_com_live_feed_seen_{$body[ 'id' ]}" );
						}
					}
				}
				
				if ( isset( $body[ 'id' ] ) && false !== get_transient( "wpb_com_live_feed_seen_{$body[ 'id' ]}" ) ) {
					return;
				}
				
				if ( isset( $body[ 'message' ] ) && ! empty( $body[ 'message' ] ) ) {
					$user    = wp_get_current_user();
					$message = str_ireplace( array( '{user_login}', '{user_email}', '{user_firstname}', '{user_lastname}', '{display_name}', '{nickname}' ), array(
						$user->user_login,
						$user->user_email,
						$user->user_firstname,
						$user->user_lastname,
						$user->display_name,
						$user->nickname,
					), $body[ 'message' ] );
					
					echo wp_kses_post( $message );
				}
			}
			
			public function settings( $settings ) {
				
				unset( $settings[ 2 ] );
				unset( $settings[ 3 ][ 'checkboxgroup' ] );
				
				$settings[ 3 ][ 'title' ] = esc_html__( 'Add to cart behaviour', 'woocommerce' );
				
				array_splice( $settings, 3, 0, array(
					array(
						'title'    => esc_html__( 'Added to cart redirect to', 'woo-add-to-cart-redirect' ),
						'id'       => 'woo_cart_redirect_to_page',
						'selected' => absint( get_option( 'woocommerce_checkout_page_id' ) ),
						'type'     => 'single_select_page',
						'class'    => 'wc-enhanced-select-nostd', // Means with cross icon
						'css'      => 'min-width:300px;',
						'desc_tip' => esc_html__( 'After item added to cart page will redirect to a specific page.', 'woo-add-to-cart-redirect' ),
					)
				) );
				
				return apply_filters( 'woo_cart_redirect_to_checkout_page_settings', $settings );
			}
		}
		
		function woo_cart_redirect_to_checkout_page() {
			return Add_Cart_Redirect_Woo::instance();
		}
		
		add_action( 'plugins_loaded', 'woo_cart_redirect_to_checkout_page' );
		
		register_activation_hook( __FILE__, function () {
			update_option( 'woo_cart_redirect_default', get_option( 'woocommerce_cart_redirect_after_add' ) );
			update_option( 'woocommerce_cart_redirect_after_add', 'no' );
		} );
		
		register_deactivation_hook( __FILE__, function () {
			update_option( 'woocommerce_cart_redirect_after_add', get_option( 'woo_cart_redirect_default' ) );
			delete_option( 'woo_cart_redirect_default' );
		} );
	endif;