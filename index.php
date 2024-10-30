<?php
/**
 * BTSE's Crypto Widget
 *
 * @package   btse-crypto-widget-plugin
 * @author    BTSE <support@btse.com>
 * @copyright © 2022 btse.com All Rights Reserved
 * @license   GPLv2 or later
 * @link      https://www.btse.com/
 *
 * Plugin Name:     BTSE's Crypto Widget
 * Plugin URI:      https://www.btse.com
 * Description:     Calling all WordPress users! BTSE's Crypto WordPress Widget is finally here and free of charge! You can now earn a commission not only by bringing new traders to BTSE through the one and only BTSE's Crypto WordPress Widget but also through your traders’ referees and their referees’ referees. On BTSE, there is no limit to your referral levels.
 * Version:         1.0.2
 * Author:          BTSE
 * Author URI:      https://btse.com
 * Text Domain:     btse-crypto-widget-plugin
 * Domain Path:     /languages
 * Requires PHP:    7.1
 * Requires WP:     5.5.0
 * Namespace:       BTSE-WordPress-Plugin
 */


  define('BTSE_WIDGET_CONVERT_SLUG', 'btse-convert-widget');
  define('BTSE_WIDGET_CHART_SLUG', 'btse-chart-widget');
  define('BTSE_WIDGET_MARQUEE_SLUG', 'btse-marquee-widget');
  define('BTSE_WIDGET_HOT_LIST_SLUG', 'btse-crypto-list-widget');
  define('BTSE_WIDGET_TAB_LIST_SLUG', 'btse-crypto-tab-list-widget');

  
if ( ! class_exists( 'BtseWpPlugin' ) ) {
  class BtseWpPlugin {

       protected static $_instance = null;

    public static function get_instance()
    {
        if (!self::$_instance) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    private function __construct() {
      add_shortcode(BTSE_WIDGET_CONVERT_SLUG, [$this ,'TCP_shortcode']);
      add_shortcode(BTSE_WIDGET_CHART_SLUG, [$this ,'TCP_shortcode_2']);
      add_shortcode(BTSE_WIDGET_MARQUEE_SLUG, [$this ,'TCP_shortcode_3']);
      add_shortcode(BTSE_WIDGET_HOT_LIST_SLUG, [$this ,'TCP_shortcode_4']);
      add_shortcode(BTSE_WIDGET_TAB_LIST_SLUG, [$this ,'TCP_shortcode_5']);
      
      add_action('rest_api_init', function () {
        register_rest_route('btse' ,'/proxy', array(
          // By using this constant we ensure that when the WP_REST_Server changes our readable endpoints will work as intended.
          'methods'  => WP_REST_Server::READABLE,
          // Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
          'callback' => [$this, 'btse_api_proxy'],
        ));
        register_rest_route('btse', '/list', array(
          // By using this constant we ensure that when the WP_REST_Server changes our readable endpoints will work as intended.
          'methods'  => WP_REST_Server::READABLE,
          // Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
          'callback' => [$this, 'btse_api_list'],
        ));
        register_rest_route('btse', '/referral_code', array(
          // By using this constant we ensure that when the WP_REST_Server changes our readable endpoints will work as intended.
          'methods'  => WP_REST_Server::READABLE,
          // Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
          'callback' => [$this, 'btse_api_get_ref_code'],
        ));

        register_rest_route('btse', '/referral_code', array(
          'methods'  => WP_REST_Server::CREATABLE,
          'callback' => [$this, 'btse_api_update_settings'],
          'permission_callback' => [$this, 'btse_api_settings_permissions_check']
        ));
      });


    }

    function add_admin_menu() {
      // add to settings menu
      add_action('admin_menu', function () {
        global $btse_wp_plugin_settings_page;
        $btse_wp_plugin_settings_page = add_options_page('BTSE WP Plugin', 'BTSE WP Plugin', 'manage_options', 'btse-crypto-widget-plugin-settings', [$this, 'btse_wp_plugin_settings_do_page']);
      });
    }

    function btse_wp_plugin_settings_do_page() {
      $this->load_js_css(true);
      echo '<btse-admin-console-widget></btse-admin-console-widget>';
    }

    function TCP_shortcode($attr) {
      $this->load_js_css();
      return '<' . BTSE_WIDGET_CONVERT_SLUG . '></' . BTSE_WIDGET_CONVERT_SLUG . '>';
    }

    function TCP_shortcode_2($attr) {
      $this->load_js_css();

      $a = shortcode_atts( array(
        'symbol' => 'BTC-USD',
      ), $attr );
      
      return '<' . BTSE_WIDGET_CHART_SLUG . ($a['symbol'] ? ' symbol="' . $a['symbol'] . '" ': '') . '></' . BTSE_WIDGET_CHART_SLUG . '>';
    }
    function TCP_shortcode_3($attr) {
      $this->load_js_css();
      return '<' . BTSE_WIDGET_MARQUEE_SLUG . '></' . BTSE_WIDGET_MARQUEE_SLUG . '>';
    }
    function TCP_shortcode_4($attr) {
      $this->load_js_css();

      $a = shortcode_atts( array(
        'type' => 'hot',
      ), $attr );
      
      return '<' . BTSE_WIDGET_HOT_LIST_SLUG . ($a['type'] ? ' type="' . $a['type'] . '" ': '') . '></' . BTSE_WIDGET_HOT_LIST_SLUG . '>';
    }
    function TCP_shortcode_5($attr) {
      $this->load_js_css();

      $a = shortcode_atts( array(
        'type' => 'normal',
      ), $attr );
      
      return '<' . BTSE_WIDGET_TAB_LIST_SLUG . ($a['type'] ? ' type="' . $a['type'] . '" ': '') . '></' . BTSE_WIDGET_TAB_LIST_SLUG . '>';
    }

    function load_js_css($is_admin = false) {
        $js_to_load = plugin_dir_url( __FILE__ ) . '/static/js/btse-crypto-widget-plugin.js';
        $css_to_load = plugin_dir_url( __FILE__ ) . '/static/css/btse-crypto-widget-plugin.css';
        $media_url = plugin_dir_url( __FILE__ ) . 'assets';
        wp_enqueue_script('btse_frontend_react', $js_to_load, '', mt_rand(10,1000), true);
        wp_enqueue_style('btse_frontend_style', $css_to_load);
        wp_localize_script('btse_frontend_react', 'btse_ajax', array(
          'urls'    => array(
            'proxy'    => rest_url('btse/proxy'),
            'list'    => rest_url('btse/list'),
            'referral_code'    => rest_url('btse/referral_code'),
          ),
          'referral_code' => get_option('btse_wp_plugin_referral_code', false ),
          'media_url' => $media_url,
          'nonce'   => $is_admin ? wp_create_nonce('wp_rest') : null,
        ));
    }

    function is_local_dev() {
      return false;
    }

    function btse_api_proxy($request) {
      $params = $request->get_query_params();
      $endpoint = $params['endpoint'];
      unset($params['endpoint']);
      $query = http_build_query($params);
      $request = wp_remote_get("https://api.btse.com/spot$endpoint?$query");
      return json_decode(wp_remote_retrieve_body($request));
    }

    function btse_api_get_ref_code($request) {
      $ref_code = get_option('btse_wp_plugin_referral_code', false );
      $result = array(
        'referral_code' => $ref_code
      );
      $response = new WP_REST_Response($result, 200);
      $response->set_headers(array('Cache-Control' => 'max-age=3600'));
      return $response;
    }

    function btse_api_list($request) {
      $result = get_option('btse_wp_plugin_convert_list', false );

      $expired = false;
      if ($result && isset($result['time'])) {
        $time = $result['time'];
        $now = new DateTime();
        $diff = $now->diff($time);
        if($diff->days > 2) {
          $expired = true;
        }
      }

      // print_r($listLeft);
      if (!$result || $expired) {
        $request = wp_remote_get("https://api.btse.com/spot/api/v3.2/price");
        $data = json_decode(wp_remote_retrieve_body($request));
        $result = array(
          'data' => $data,
          'time' => new DateTime(),
        );
        add_option( 'btse_wp_plugin_convert_list', $result);
      }
      unset($result->time);
      $response = new WP_REST_Response($result, 200);
      $response->set_headers(array('Cache-Control' => 'max-age=3600'));
      return $response;
    }
    
    function btse_api_update_settings($request) {
      $json = $request->get_json_params();
      // store the values in wp_options table
      $updated_referral_code = update_option('btse_wp_plugin_referral_code', $json['referral_code']);
      $ref_code = get_option('btse_wp_plugin_referral_code', false );
      return new WP_REST_RESPONSE(array(
        'success' => isset($updated_referral_code),
        'value'   => array('referral_code' => $ref_code)
      ), 200);
    }

    // check permissions
    function btse_api_settings_permissions_check() {
      // Restrict endpoint to only users who have the capability to manage options.
      if (current_user_can('manage_options')) {
        return true;
      }

      return new WP_Error('rest_forbidden', esc_html__('You do not have permissions to view this data.', 'btse-crypto-widget-plugin'), array('status' => 401));;
    }
  }
}

// cleanup data on uninstall
if (! function_exists('btse_wp_plugin_uninstall')) {
  function btse_wp_plugin_uninstall () {
    delete_option('btse_wp_plugin_referral_code');
    delete_option('btse_wp_plugin_convert_list');
  }
}

register_uninstall_hook(__FILE__, 'btse_wp_plugin_uninstall');

$instance = BtseWpPlugin::get_instance();
$instance->add_admin_menu();

