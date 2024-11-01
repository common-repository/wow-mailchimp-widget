<?php 
/*
*
* Plugin Name: WOW Mailchimp Widget
* Description: This plugin is wordpress mailchimp subscription form widget. 
* Author: Wings of Web
* Text Domain: wow-mailchimp-widget
* Version: 1.0
*/


// don't load directly
if (!defined('ABSPATH')) {
    die('-1');
}

class WOW_mail extends WP_Widget {

	/**
	* Register widget with WordPress.
	*/
	function __construct() {
	parent::__construct(
	'WOW_mail', // Base ID
	__( 'WOW Mailchimp', 'WOW' ), // Name
	array( 'description' => __( 'WordPress mailchimp newsletter widget.', 'WOW' ), ) 
	);
		add_action("wp_ajax_my_subscribe_mail", array($this, "WOW_my_subscribe_mail"));
		add_action("wp_ajax_nopriv_my_subscribe_mail", array($this, "WOW_my_subscribe_mail"));
	}

	
	 
 public function WOW_my_subscribe_mail()
 {
	$WOW_a = $_REQUEST['data'];
	$WOW_apiKey = sanitize_text_field($WOW_a['apiid']);
	$WOW_listId = sanitize_text_field($WOW_a['listid']);
	$WOW_email_valid = sanitize_email($WOW_a['email']);
	$WOW_name = sanitize_text_field($WOW_a['name']);
	$WOW_status = 'subscribed'; // subscribed, cleaned, pending
	
	if (is_email($WOW_email_valid)) 
	{
		$WOW_email = $WOW_email_valid;	
	}
	else
	{
		echo "Invalid Email";
	}
	
	if($WOW_email)
	{
		$WOW_allname= explode(" ", $WOW_name, 2);
		$WOW_fname = $WOW_allname[0];
		$WOW_lname = $WOW_allname[1];
		$args = array(
			'method' => 'PUT',
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode( 'user:'. $WOW_apiKey )
			),
			'body' => json_encode(array(
					'email_address' => $WOW_email,
					'merge_fields'  => array(
							'FNAME' => $WOW_fname,
							'LNAME' => $WOW_lname,
						),
					'status' => $WOW_status
			))
		);
		$WOW_response = wp_remote_post( 'https://' . substr($WOW_apiKey,strpos($WOW_apiKey,'-')+1) . '.api.mailchimp.com/3.0/lists/' . $WOW_listId . '/members/' . md5(strtolower($email)), $args );
		 
		$WOW_body = json_decode( $WOW_response['body'] );
		 
		if ( $WOW_response['response']['code'] == 200 && $WOW_body->status == $WOW_status ) {
			echo 'The user has been successfully ' . $WOW_status . '.';
		} else {
			echo '<b>' . $WOW_response['response']['code'] . $WOW_body->title . ':</b> ' . $WOW_body->detail;
		}
	}
			 
	die;
		
 }
 
	/**
	* Front-end display of widget.
	* @param array $args  Widget arguments.
	* @param array $instance Saved values from database.
	*/
	public function widget( $args, $instance ) { 
	$output ='';
	
	if (!isset($args['widget_id'])) {
	$args['widget_id'] = null;
	}
	extract($args);
	$mc_title     = $instance['mc_title'];
	$mail_api_key    = $instance['mail_api_key'];
	$mail_list_id	 = $instance['mail_list_id'];
	$output .= $args['before_widget'];
	$rand = rand();
	
	if ( ! empty( $instance['mc_title'] ) ) {
		$output .=  $args['before_title'] . apply_filters( 'widget_title', $instance['mc_title'] ) . $args['after_title'];
	}
	
    $output .='<p><input type="text"  placeholder="<First Name> <Last Name>"  id="WOW_name'.$rand.'" name="WOW_name" required></p>          
              <p><input type="text" placeholder="<Email Address>" id="WOW_email'.$rand.'" name="WOW_email" ></p>
                <button type="button" id="WOW_submit'.$rand.'">Submit</button>

            </div>
			<script>
	        var ajaxurl = "'. admin_url("admin-ajax.php").'";
			jQuery("#WOW_submit'.$rand.'").click(function(){
				
			var apiid ="'.$instance["mail_api_key"].'";
			var listid = "'. $instance["mail_list_id"].'";
		
			
			var WOW_name = jQuery("#WOW_name'.$rand.'").val();
			var WOW_email = jQuery("#WOW_email'.$rand.'").val();
			
			
			jQuery.post(
			ajaxurl, 
			{
				"action": "my_subscribe_mail",
				"data":   { email:WOW_email, name:WOW_name, apiid:apiid, listid:listid},
			}, 
			function(response){
				alert(response);
			}
			
		); 
		})
			</script>';
	
	
	$output .= $args['after_widget'];
	echo $output;
	}

	/**
	* Back-end widget form.
	*
	* @see WP_Widget::form()
	*
	* @param array $instance Previously saved values from database.
	*/
	public function form( $instance ) {
	if (!isset($args['widget_id'])) {
	$args['widget_id'] = null;
	}
	extract($args);
	$instance        = wp_parse_args( (array) $instance, array( 'mc_title' => '', 'mail_api_key' => '', 'mail_list_id' => '',) );
	$mc_title     = $instance['mc_title'];
	$mail_api_key    = $instance['mail_api_key'];
	$mail_list_id	 = $instance['mail_list_id'];
	?>
	<p><label for="<?php echo $this->get_field_id('mc_title'); ?>"><?php _e('Title:', 'WOW'); ?> </label>
	<input class="widefat" id="<?php echo $this->get_field_id('mc_title'); ?>" name="<?php echo $this->get_field_name('mc_title'); ?>" type="text" value="<?php echo esc_attr($mc_title); ?>" /></p>
	
	<p><label for="<?php echo $this->get_field_id('mail_api_key'); ?>"><?php _e('API key:', 'WOW'); ?> </label>
	<input class="widefat" id="<?php echo $this->get_field_id('mail_api_key'); ?>" name="<?php echo $this->get_field_name('mail_api_key'); ?>" type="text" value="<?php echo esc_attr($mail_api_key); ?>" /></p>
	
	<p><label for="<?php echo $this->get_field_id('mail_list_id'); ?>"><?php _e('List Id:', 'WOW'); ?> </label>
	<input class="widefat" id="<?php echo $this->get_field_id('mail_list_id'); ?>" name="<?php echo $this->get_field_name('mail_list_id'); ?>" type="text" value="<?php echo esc_attr($mail_list_id); ?>" /></p>
	
	
	<?php
	
	}

	/**
	* Sanitize widget form values as they are saved.
	*
	* @see WP_Widget::update()
	*
	* @param array $new_instance Values just sent to be saved.
	* @param array $old_instance Previously saved values from database.
	*
	* @return array Updated safe values to be saved.
	*/
	public function update( $new_instance, $old_instance ) {
	$instance = $old_instance;
	$instance['mc_title']     = $new_instance['mc_title'];
	$instance['mail_api_key']     = $new_instance['mail_api_key'];
	$instance['mail_list_id']     = $new_instance['mail_list_id'];

	return $instance;
	}

	} 
	/* registering WOW widget */
	function register_WOW_mail_widget() {
	register_widget( 'WOW_mail' );
	}
	add_action( 'widgets_init', 'register_WOW_mail_widget' );
