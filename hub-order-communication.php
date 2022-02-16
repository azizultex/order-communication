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
        add_action('wp_ajax_reset_notification_count', [$this, 'reset_notification_count']);
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
            'order_id' => false,
            'post_id' => false
        ), $atts );

        ob_start();
        ?>
        <form class="comment-field-area" action="" method="POST">
            <div class="form-group">
                <textarea class="form-control" id="hub_comment_field" name="agent_replies" rows="3" required></textarea>
            </div>
            <div class="form-group">
                <button type="submit" name="create_reply" class="btn btn-primary mb-2">Reply</button>
            </div>
        </form>
        <?php
        if(isset($_POST['create_reply'])){
            $comments = isset($_POST['agent_replies']) ? $_POST['agent_replies'] : '';
            $comment_id = wp_insert_post(
                array(
                    'post_title'=> intval($attributes['order_id']),
                    'post_type'=>'order_communication',
                    'post_content'=> $comments,
                    'post_status'  => 'publish',
                    )
            );
            $users_wp = preg_match_all('/data-item-id="(.*?)"/', stripslashes($comments), $matches);
            add_post_meta($comment_id, 'order_id', intval($attributes['order_id']));
            add_post_meta($comment_id, 'mentioned_user', $matches[1]);
            add_post_meta($comment_id, 'agent_replied', get_current_user_id());
            add_post_meta($comment_id, 'post_id', intval($attributes['post_id']));
            $author_obj = get_user_by('login', $matches[1][0]);
            $notification_count = get_user_meta($author_obj->data->ID, 'notification_count');
            $notification_count[0] += 1;
            update_user_meta($author_obj->data->ID, 'notification_count', $notification_count[0]);
        }
        
        $args = array(
            'posts_per_page' => -1,
            'post_type' => 'order_communication',
            'meta_query' => array(
                array(
                    'key'     => 'order_id',
                    'value'   => intval($attributes['order_id']),
                    'compare' => 'LIKE',
                ),
            ),
        );
        $query = new WP_Query($args);
        if ( $query->have_posts() ) {
            while ($query->have_posts()) {
                $query->the_post();
                $agent_replied = get_post_meta(get_the_ID(), 'agent_replied', true);
                ?>
                <div class="coversation_timeline">
                    <div class="replied_by">
                        <img src="<?php echo get_avatar_url($agent_replied); ?>" >
                        <div class="agent_reply_time">
                            <?php
                            $agent_replied = get_post_meta(get_the_ID(), 'agent_replied', true);
                            $user_obj = get_user_by('ID', $agent_replied);
                            echo '<span class="agent_name">'.$user_obj->data->display_name.'</span>';
                            echo '<span class="comment_date"><i>'.get_the_date('Y-m-d h:i:sa').'</i></span>';
                            ?>
                        </div>
                        <hr>
                    </div>
                    <div class="replied_content">
                        <?php echo get_the_content(); ?>
                    </div>
                </div>
                <hr>
                <?php
                $mentioned_user = get_post_meta(get_the_ID(), 'mentioned_user', true);
                $author_obj = get_user_by('login', $mentioned_user[0]);
                $notification_count = !empty(get_user_meta($author_obj->data->ID, 'notification_count'))? get_user_meta($author_obj->data->ID, 'notification_count'): 0;
                //write_log($author_obj->data->ID);
            }
        }
        return ob_get_clean();
    }

    public function reset_notification_count(){
        update_user_meta(get_current_user_id() ,'notification_count', "0");
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