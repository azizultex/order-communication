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
     * Class construct file
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

    /**
     * Enqueing style and script file
     *
     * @return void
     */
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
            <div class="form-group button-group">
                <button type="submit" name="create_reply" class="btn btn-primary mb-2">Reply</button>
            </div>
        </form>
        <?php
        if(isset($_POST['create_reply'])){
            $comments = isset($_POST['agent_replies']) ? $_POST['agent_replies'] : '';
            $comment_post = get_page_by_title( $attributes['order_id'], OBJECT, 'order_communication' );
            if($comment_post){
                $comment_id = $comment_post->ID;
            }else{
                $comment_id = wp_insert_post(
                    array(
                        'post_title'=> intval($attributes['order_id']),
                        'post_type'=>'order_communication',
                        'post_status'  => 'publish',
                        )
                );
            }

            $meta_data = new \stdClass();
            $meta_data->comment = stripslashes(str_replace('"', "'", $comments));
            $users_wp = preg_match_all('/data-item-id="(.*?)"/', stripslashes($comments), $matches);
            $resultEmpty = array_filter(array_map('array_filter', $matches));
            if(!empty($resultEmpty)):
                write_log($resultEmpty);
                $meta_data->mentioned_user = $matches[1];
                $author_obj = get_user_by('login', $matches[1][0]);

                $notification_count = get_user_meta($author_obj->data->ID, 'notification_count', true);
                $notification_count += 1;
                update_user_meta($author_obj->data->ID, 'notification_count', $notification_count);

                $mentioned_order_ids = get_user_meta($author_obj->data->ID, 'mentioned_order_ids', true);
                $mentioned_order_ids = json_decode($mentioned_order_ids);
                if(!empty($mentioned_order_ids) && $mentioned_order_ids != ''){
                    array_push($mentioned_order_ids, $attributes['order_id']);
                }else{
                    $mentioned_order_ids[] = $attributes['order_id']; 
                }
                update_user_meta($author_obj->data->ID, 'mentioned_order_ids', json_encode($mentioned_order_ids));

                $mentioned_comment_ids = get_user_meta($author_obj->data->ID, 'mentioned_comment_ids', true);
                $mentioned_comment_ids = json_decode($mentioned_comment_ids);
                if(!empty($mentioned_comment_ids) && $mentioned_comment_ids != ''){
                    array_push($mentioned_comment_ids, $comment_id);
                }else{
                    $mentioned_comment_ids[] = $comment_id; 
                }
                update_user_meta($author_obj->data->ID, 'mentioned_comment_ids', json_encode($mentioned_comment_ids));

            endif;
            $meta_data->agent_replied = get_current_user_id();

            $history = new \stdClass();
            $history->name = 'Sent On: ';
            $meta_data->time = date('Y-m-d H:i:s', time());

            $mentioned_user_meta_data = get_post_meta($comment_id, 'mentioned_user_meta_data', true);
            $mentioned_user_meta_data = json_decode($mentioned_user_meta_data);
            if(!empty($mentioned_user_meta_data) && $mentioned_user_meta_data != ''){
                array_push($mentioned_user_meta_data, $meta_data);
            }else{
                $mentioned_user_meta_data[] = $meta_data; 
            }
            $mentioned_user_meta_data = array_values($mentioned_user_meta_data);
            update_post_meta($comment_id, 'mentioned_user_meta_data', json_encode($mentioned_user_meta_data));

        }

        $comment_post = get_page_by_title( $attributes['order_id'], OBJECT, 'order_communication' );
        if($comment_post){
            $comment_id = $comment_post->ID;

            $mentioned_user_meta_data = get_post_meta($comment_id, 'mentioned_user_meta_data', true);
            $mentioned_user_meta_data = json_decode(trim($mentioned_user_meta_data), true);
            // echo '<pre>';
            // print_r($mentioned_user_meta_data);
            if($mentioned_user_meta_data){
                foreach($mentioned_user_meta_data as $meta_data){
                    $agent_replied = $meta_data['agent_replied'];
                    ?>
                    <div class="coversation_timeline">
                        <div class="replied_by">
                            <img src="<?php echo get_avatar_url($agent_replied); ?>" >
                            <div class="agent_reply_time">
                                <?php
                                $user_obj = get_user_by('ID', $agent_replied);
                                echo '<span class="agent_name">'.$user_obj->data->display_name.'</span>';
                                echo '<span class="comment_date"><i>'.$meta_data['time'].'</i></span>';
                                ?>
                            </div>
                            <hr>
                        </div>
                        <div class="replied_content">
                            <?php echo $meta_data['comment']; ?>
                        </div>
                    </div>
                    <hr>
                    <?php
                }
            }
            
        }
        return ob_get_clean();
    }

    /**
     * Reset notification number when click on notification icon
     *
     * @return void
     */
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