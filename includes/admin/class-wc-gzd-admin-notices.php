<?php
/**
 * Display notices in admin.
 *
 * @author        Vendidero
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( 'WC_GZD_Admin_Notices' ) ) :

	/**
	 * Adds Notices after Install / Update to Admin
	 *
	 * @class        WC_GZD_Admin_Notices
	 * @version        1.0.0
	 * @author        Vendidero
	 */
	class WC_GZD_Admin_Notices {

		/**
		 * Single instance current class
		 *
		 * @var object
		 */
		protected static $_instance = null;

		/**
		 * Ensures that only one instance of this class is loaded or can be loaded.
		 *
		 * @static
		 */
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}

			return self::$_instance;
		}

		protected function __construct() {
			add_action( 'admin_init', array( $this, 'check_notice_hide' ) );

			add_action( 'after_switch_theme', array( $this, 'remove_theme_notice_hide' ) );
			add_action( 'admin_print_styles', array( $this, 'add_notices' ), 1 );
		}

		public function enable_notices() {
			$enabled = true;

			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				$enabled = false;
			}

			if ( get_option( 'woocommerce_gzd_disable_notices' ) === 'yes' ) {
				$enabled = false;
			}

			/**
			 * Filter to enable or disable admin notices in WP-Admin.
			 *
			 * @param bool $enabled Whether notices are enabled or disabled.
			 *
			 * @since 1.8.5
			 *
			 */
			return apply_filters( 'woocommerce_gzd_enable_notices', $enabled );
		}

		/**
		 * Add notices + styles if needed.
		 */
		public function add_notices() {
			$screen          = get_current_screen();
			$screen_id       = $screen ? $screen->id : '';
			$show_on_screens = array(
				'dashboard',
				'plugins',
			);

			$wc_screen_ids = function_exists( 'wc_get_screen_ids' ) ? wc_get_screen_ids() : array();

			// Notices should only show on WooCommerce screens, the main dashboard, and on the plugins screen.
			if ( ! in_array( $screen_id, $wc_screen_ids, true ) && ! in_array( $screen_id, $show_on_screens, true ) ) {
				return;
			}

			if ( get_option( '_wc_gzd_needs_update' ) == 1 ) {
				if ( current_user_can( 'manage_woocommerce' ) ) {
					$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
					wp_enqueue_style( 'woocommerce-gzd-activation', WC_germanized()->plugin_url() . '/assets/css/admin-activation' . $suffix . '.css', array(), WC_GERMANIZED_VERSION );

					add_action( 'admin_notices', array( $this, 'update_notice' ) );
				}
			}

			if ( ! get_option( '_wc_gzd_hide_theme_notice' ) && ! WC_germanized()->is_pro() && $this->enable_notices() ) {
				if ( $this->is_theme_supported_by_pro() ) {
					add_action( 'admin_notices', array( $this, 'theme_supported_notice' ) );
				}
			}

			if ( ! get_option( '_wc_gzd_hide_review_notice' ) && ! get_option( '_wc_gzd_disable_review_notice' ) && $this->enable_notices() ) {
				add_action( 'admin_notices', array( $this, 'add_review_notice' ) );
			}

			if ( ! get_option( '_wc_gzd_hide_template_outdated_notice' ) ) {
				add_action( 'admin_notices', array( $this, 'add_template_outdated_notice' ) );
			}

			if ( ! get_option( '_wc_gzd_hide_pro_notice' ) && ! WC_germanized()->is_pro() && $this->enable_notices() ) {
				add_action( 'admin_notices', array( $this, 'add_pro_notice' ) );
			}

			if ( isset( $_GET['page'] ) && $_GET['page'] === 'wc-gzd-about' ) {
				remove_action( 'admin_notices', array( $this, 'theme_supported_notice' ) );
			}

			if ( ! get_option( '_wc_gzd_hide_dhl_importer_notice' ) ) {
				add_action( 'admin_notices', array( $this, 'dhl_importer_notice' ) );
			}
		}

		public function dhl_importer_notice() {
			if ( class_exists( 'Vendidero\Germanized\DHL\Admin\Importer' ) && Vendidero\Germanized\DHL\Admin\Importer::is_plugin_enabled() && Vendidero\Germanized\DHL\Admin\Importer::is_available() ) {
				include( 'views/html-notice-dhl.php' );
			}
		}

		public function add_template_outdated_notice() {
			$templates = WC_GZD_Admin::instance()->get_template_version_check_result();
			$show      = false;

			foreach ( $templates as $plugin => $data ) {
				if ( $data['has_outdated'] ) {
					$show = true;
					break;
				}
			}

			if ( $show ) {
				include( 'views/html-notice-templates-outdated.php' );
			}
		}

		public function remove_theme_notice_hide() {
			delete_option( '_wc_gzd_hide_theme_notice' );
			delete_option( '_wc_gzd_hide_template_outdated_notice' );
		}

		/**
		 * Show the install notices
		 */
		public function update_notice() {
			// If we need to update, include a message with the update button
			if ( get_option( '_wc_gzd_needs_update' ) == 1 ) {
				include( 'views/html-notice-update.php' );
			}
		}

		public function check_notice_hide() {
			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				return;
			}

			$notices = array(
				'wc-gzd-hide-theme-notice',
				'wc-gzd-disable-review-notice',
				'wc-gzd-hide-review-notice',
				'wc-gzd-hide-pro-notice',
				'wc-gzd-hide-dhl-importer-notice',
				'wc-gzd-hide-template-outdated-notice'
			);

			if ( ! empty( $notices ) ) {
				foreach ( $notices as $notice ) {
					if ( isset( $_GET['notice'] ) && $_GET['notice'] == $notice && isset( $_GET['nonce'] ) && check_admin_referer( $notice, 'nonce' ) ) {
						update_option( '_' . str_replace( '-', '_', $notice ), true );
						$redirect_url = remove_query_arg( 'notice', remove_query_arg( 'nonce', $_SERVER['REQUEST_URI'] ) );

						wp_safe_redirect( $redirect_url );
						exit();
					}
				}
			}
		}

		public function theme_supported_notice() {
			$current_theme = wp_get_theme();
			include( 'views/html-notice-theme-supported.php' );
		}

		public function is_theme_ready() {
			$stylesheet = get_stylesheet_directory() . '/style.css';
			$data       = get_file_data( $stylesheet, array( 'wc_gzd_compatible' => 'wc_gzd_compatible' ) );

			if ( ! $data['wc_gzd_compatible'] && ! current_theme_supports( 'woocommerce-germanized' ) ) {
				return false;
			}

			return true;
		}

		public function is_theme_supported_by_pro() {
			$supporting = array(
				'enfold',
				'flatsome',
				'storefront',
				'virtue',
				'shopkeeper',
				'astra'
			);

			$current = wp_get_theme();

			if ( in_array( $current->get_template(), $supporting ) ) {
				return true;
			}

			return false;
		}

		public function add_review_notice() {
			if ( get_option( 'woocommerce_gzd_activation_date' ) ) {
				$this->queue_notice( 3, 'html-notice-review.php' );
			}
		}

		public function add_pro_notice() {
			if ( get_option( 'woocommerce_gzd_activation_date' ) ) {
				$this->queue_notice( 4, 'html-notice-pro.php' );
			}
		}

		public function queue_notice( $days, $view ) {
			if ( get_option( 'woocommerce_gzd_activation_date' ) ) {

				$activation_date = ( get_option( 'woocommerce_gzd_activation_date' ) ? get_option( 'woocommerce_gzd_activation_date' ) : date( 'Y-m-d' ) );
				$diff            = WC_germanized()->get_date_diff( $activation_date, date( 'Y-m-d' ) );

				if ( $diff['d'] >= absint( $days ) ) {
					include( 'views/' . $view );
				}
			}
		}

		/**
		 * Checks if current theme is woocommerce germanized compatible
		 *
		 * @return boolean
		 */
		public function is_theme_compatible() {
			$templates_to_check = WC_germanized()->get_critical_templates();

			if ( ! empty( $templates_to_check ) ) {
				foreach ( $templates_to_check as $template ) {
					$template_path = trailingslashit( 'woocommerce' ) . $template;

					$theme_template = locate_template( array(
						$template_path,
						$template
					) );

					if ( $theme_template ) {
						return false;
					}
				}
			}

			return true;
		}
	}

endif;

return WC_GZD_Admin_Notices::instance();
