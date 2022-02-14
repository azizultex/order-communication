<?php
/**
 *
 * @link              https://wppool.dev
 * @since             1.0.0
 * @package           order-communication
 *
 * @wordpress-plugin
 * Plugin Name:       Hoodslyhub Order Communication
 * Plugin URI:        https://wppool.dev
 * Description:       This plugin adds agent to agent communication functionality to order details page
 * Version:           1.0.0
 * Author:            Saiful Islam
 * Author URI:        https://wppool.dev
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       order-communication
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}


defined( 'ORDER_COMMUNICATION_PLUGIN_NAME' ) or define( 'ORDER_COMMUNICATION_PLUGIN_NAME', 'Order Communication' );
defined( 'ORDER_COMMUNICATION_PLUGIN_VERSION' ) or define( 'ORDER_COMMUNICATION_PLUGIN_VERSION', '1.0.0' );
defined( 'ORDER_COMMUNICATION_BASE_NAME' ) or define( 'ORDER_COMMUNICATION_BASE_NAME', plugin_basename( __FILE__ ) );
defined( 'ORDER_COMMUNICATION_ROOT_PATH' ) or define( 'ORDER_COMMUNICATION_ROOT_PATH', plugin_dir_path( __FILE__ ) );
defined( 'ORDER_COMMUNICATION_ROOT_URL' ) or define( 'ORDER_COMMUNICATION_ROOT_URL', plugin_dir_url( __FILE__ ) );

final class OderCommunication {
    /**
     * Undocumented function
     */
    public function __construct(){
        add_shortcode( 'order-comment-field', [$this, 'order_details_comment_field'] );
        add_action('wp_enqueue_scripts', [$this, 'enqueueing_scripts_file']);
    }
    /**
     * init function for single tone approach
     *
     * @return false|HoodslyHub
	 */
    public static function init(){
        static $instance = false;
        if (!$instance) {
            $instance = new self();
        }
        return $instance;
    }

    public function enqueueing_scripts_file(){
        wp_enqueue_style( 'mentiony', ORDER_COMMUNICATION_ROOT_URL.'assets/css/jquery.mentiony.css', array(), time());
        wp_enqueue_style( 'order-communication', ORDER_COMMUNICATION_ROOT_URL.'assets/css/order-communication.css', array(), time());
        wp_enqueue_script('mentiony-js', ORDER_COMMUNICATION_ROOT_URL.'assets/js/jquery.mentiony.js', array('jquery'), time(), true);
        wp_enqueue_script( 'order-communication-js', ORDER_COMMUNICATION_ROOT_URL.'assets/js/order-communication.js', array('jquery'), time(), true );
        $users = get_users( array( 'fields' => array( 'user_login', 'display_name' ) ) );
        $users_array = array();
        
        foreach($users as $value){
            $new_array = array();
            $new_array['id'] = $value->user_login;
            $new_array['name'] = $value->user_login;
            $new_array['info'] = $value->display_name;
            $new_array['href'] = '#';
            $users_array[] = $new_array;
        }

        $obj_data = array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'users_array' => $users_array
        );
        wp_localize_script('order-communication-js', 'hub_obj', $obj_data);
    }

    /**
     * Adding shortcode contents for order detials comnment section
     *
     * @param [type] $atts
     * @return void
     */
    public function order_details_comment_field($atts){
        $attributes = shortcode_atts( array(
            'order_id' => false
        ), $atts );

        ob_start();
        ?>
        <form class="comment-field-area" action="" method="POST">
            <div class="form-group">
                <textarea class="form-control" id="hub_comment_field" rows="3"></textarea>
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-primary mb-2">Reply</button>
            </div>
        </form>
        <?php
        return ob_get_clean();
    }

}

/**
 * initialise the main function
 *
 * @return false|OderCommunication
 */
function order_communication()
{
    return OderCommunication::init();
}

// let's start the plugin
order_communication();