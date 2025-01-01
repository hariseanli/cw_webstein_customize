<?php
class class_webstein_customize{
	public $this_dir = NULL; 
	public $this_uri = NULL;
	private $this_folder = 'webstein_customize';
	public function __construct( $params = null ){
		$this->this_dir = get_stylesheet_directory() . '/'.$this->this_folder;
		$this->this_uri = get_stylesheet_directory_uri() . '/'.$this->this_folder;

		add_action('ywsbs_before_close_subscription_info_box', [$this,'my_account_subscribe_template_fn'],10, 1);
		add_filter( 'ywsbs_renew_order_status', [$this,'ywsbs_renew_order_status_custom_fn'],10, 2 );
		// add_filter( 'ywsbs_after_create_renew_order', [$this,'ywsbs_can_be_create_a_renew_order_custom_fn'], 10, 2 );

		add_action('wp_ajax_change_auto_renewal', [$this, 'handle_ajax_change_auto_renewal']);
   	add_action('wp_ajax_nopriv_change_auto_renewal', [$this, 'handle_ajax_change_auto_renewal']);

   	add_action('woocommerce_order_status_changed', [$this, 'wc_order_changed'], 100, 3);

	}
	public function init(){
		add_action( 'wp_enqueue_scripts', [$this,'enq_script'], 100 );
	}

	public function enq_script(){
		wp_enqueue_style( $this->this_folder.'_css', $this->this_uri.'/'.$this->this_folder.'.css', array(),rand());
		wp_enqueue_script( $this->this_folder.'_js', $this->this_uri.'/'.$this->this_folder.'.js', array(), rand(), true ); 
		// wp_localize_script( $this->this_folder.'_js', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' )) );
		wp_localize_script( $this->this_folder.'_js', 'ws_ajax_object', array( 
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'ws_ajax_nonce' ), 
      ));
	}
	function wc_order_changed($order_id, $old_status, $new_status){
		global $wpdb, $woocommerce;
		$order = wc_get_order($order_id);

	   $is_a_renew = $order->get_meta( 'is_a_renew' );
	   $renew_subscriptions = $order->get_meta( 'subscriptions' );

	   if($is_a_renew && $renew_subscriptions && $new_status =='completed'){
	   	$user_id = $order->get_user_id();
	   	$this->generate_tickets_for_user_func($user_id);
	   }
	}

	function generate_tickets_for_user_func($user_id) {
		error_log( print_r( 'generate_tickets_for_user_func-----------------------', true ) );
	   
	   global $wpdb;

			$now = date('Y-m-d');
			$arg = array(
		     'post_type' => 'giveaway',
		     'posts_per_page' => -1,
		     'meta_query' => array (
		     	'relation'      => 'AND',
		         array(
		           'key'       => 'end_date',
		           'value'     => $now,
		           'compare'   => '>=',
		           'type'      => 'DATE'
		         ),
		         array(
		           'key'       => 'start_date',
		           'value'     => $now,
		           'compare'   => '<=',
		           'type'      => 'DATE'
		         ),
		      )
		   );

		   $items = get_posts($arg);

		   foreach($items as $item){
	       $start_date = get_field('start_date', $item->ID);
	       $end_date = get_field('end_date', $item->ID);

	       $today_strot = strtotime(date('Y-m-d h:i:s'));
	       $start_strot = strtotime($start_date);
	       $end_strot = strtotime($end_date);

	       $get_total_winners = get_post_meta( $item->ID, 'get_total_winners', true );

	       if(!$get_total_winners){

	       	  $giveaway_id = $item->ID;


	       	  	$args = array(
	       	  		'post_type' => 'ywsbs_subscription',
	       	  		'posts_per_page' => 5,
	       	  		'meta_query' => array(
							'relation'      => 'AND',
							array(
								'key'   => '_entries_added_'.$giveaway_id,
								'compare' => 'NOT EXISTS',
							),
							array(
								'key'   => 'status',
								'value' => 'active',
								'compare' => 'LIKE',
							),
							array(
								'key'   => 'user_id',
								'value' => $user_id,
							),
						)
	       	  	);

	       	  	$subscriptions = get_posts($args);

					foreach($subscriptions as $subscriptions){
						$wps_subscription_status = get_post_meta( $subscriptions->ID, 'status', true );

						if($wps_subscription_status == 'active'){
							$wps_schedule_start = get_post_meta( $subscriptions->ID, 'start_date', true );
							$product_id            = get_post_meta( $subscriptions->ID, 'product_id', true );
							$user_id            = get_post_meta( $subscriptions->ID, 'user_id', true );
							$order_id            = get_post_meta( $subscriptions->ID, 'order_id', true );

							$rates_payed = get_post_meta( $subscriptions->ID, 'rates_payed', true );

							$entries = get_field('entries', $product_id);

							$order_qty = 1;

							$total_intervals = $rates_payed;

							$total_entries = $entries*$total_intervals;

							$table_name = $wpdb->prefix . "giveaway_entries";

							$dou_str = date('Y').'-'.date('m').'-'.date('d').' '.date('h').':'.date('i').':'.date('s');

			            $wpdb->insert( $table_name,
			                array( 
			                    'user_id' => $user_id,
			                    'product_id' => $product_id,
			                    'entries' => $total_entries,
			                    'giveaway_id' => $giveaway_id,
			                    'order_id' => $order_id,
			                    'order_item_id' => 0,
			                    'order_qty' => $order_qty,
			                    'generate_ticket' => 1,
			                    'created_at' => $dou_str,
			                    'updated_at' => $dou_str,),

			                array( '%d', '%d','%d','%d', '%d','%d','%d', '%d', '%s', '%s' ) );


			            $ticket_table_name = $wpdb->prefix . "giveaway_entries_tickets";

						   $max = 10 - strlen((string) $order_id);

						   $tickets = [];

				   		for($i = 1; $i <= $total_entries; $i++){
							    $ticket_no = $order_id.''.str_pad('', $max - strlen((string) $i), '0', STR_PAD_LEFT) . $i;
							    $tickets[] = $ticket_no;

							    $dou_str = date('Y').'-'.date('m').'-'.date('d').' '.date('h').':'.date('i').':'.date('s');
							    $wpdb->insert( $ticket_table_name,
				                array( 
				                    'user_id' => $user_id,
				                    'giveaway_id' => $giveaway_id,
				                    'order_id' => $order_id,
				                    'ticket_no' => $ticket_no,
				                    'entry_type' => 1,
				                    'created_at' => $dou_str,
				                    'updated_at' => $dou_str,),

				                array( '%d', '%d','%d' ,'%d' ,'%d' , '%s', '%s' ) );
							}

			            update_post_meta($subscriptions->ID, '_entries_added_'.$giveaway_id, $giveaway_id);
	    				
						}
					}


	       }
	   }
		
	}

	function ywsbs_renew_order_status_custom_fn($new_status, $subscription){
		$subscription_id = $subscription->id;
		$disable_auto_renewal = get_post_meta( $subscription_id, 'disable_auto_renewal', true);
		if($disable_auto_renewal && $disable_auto_renewal == "yes"){
			global $woocommerce;
		  	$payment_due_date = get_post_meta($subscription_id, 'payment_due_date', true);
		  	update_post_meta($subscription_id, 'end_date', $payment_due_date);
		  	update_post_meta($subscription_id, 'payment_due_date', '');
		  	update_post_meta($subscription_id, 'cancelled_date', time());
		  	update_post_meta($subscription_id, 'status', 'cancelled');
		  	update_post_meta($subscription_id, 'cancelled_by', 'user');

		  	// $renew_order = update_post_meta($subscription_id, 'renew_order', true);
		  	// if ( $renew_order ) {
			// 	$order = wc_get_order( $renew_order );
			// 	if ( $order ) {
			// 		$order->update_status( 'cancelled' );
			// 	}
			// }
			return "cancelled";
		}
		return $new_status;
	}
	// function ywsbs_can_be_create_a_renew_order_custom_fn($order, $subscription_obj ){
		
	// 	error_log( print_r( "ywsbs_can_be_create_a_renew_order_custom_fn", true ) );
	// 	// error_log( print_r( $subscription_obj, true ) );
	// 	$subscription_id = $subscription_obj->id;
	// 	$disable_auto_renewal = get_post_meta( $subscription_id, 'disable_auto_renewal', true);
	// 	if($disable_auto_renewal && $disable_auto_renewal == "yes"){
			
	// 		//-- previous cancel function
	// 		global $woocommerce;
	// 	  	$payment_due_date = get_post_meta($subscription_id, 'payment_due_date', true);
	// 	  	update_post_meta($subscription_id, 'end_date', $payment_due_date);
	// 	  	update_post_meta($subscription_id, 'payment_due_date', '');
	// 	  	update_post_meta($subscription_id, 'cancelled_date', time());
	// 	  	update_post_meta($subscription_id, 'status', 'cancelled');
	// 	  	update_post_meta($subscription_id, 'cancelled_by', 'user');

	// 	  	error_log( print_r($order, true ) );
	// 	  	$order->update_status('cancelled', 'Order canceled programmatically.');
			
	// 		return false;
	// 	}
	// 	return $can_be_create_a_renew;
	// }

	

   function handle_ajax_change_auto_renewal(){
   	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'ws_ajax_nonce' ) ) {
            wp_send_json_error( array( 'message' => 'Invalid nonce.' ) );
      }
      $subscription_id = isset($_POST['subscription_id']) ? $_POST['subscription_id'] : false;
      $disable_auto_renewal = isset($_POST['intend_status']) ? $_POST['intend_status'] : "no";

      $update_result = update_post_meta($subscription_id, 'disable_auto_renewal', $disable_auto_renewal);
      
      // error_log( print_r( "----------------------", true ) );
      // error_log( print_r( "update_result", true ) );
      // error_log( print_r( $update_result, true ) );

      // error_log( print_r( "subscription_id", true ) );
      // error_log( print_r( $subscription_id, true ) );

      // error_log( print_r( "disable_auto_renewal", true ) );
      // error_log( print_r( $disable_auto_renewal, true ) );

      if ( $update_result ) {
      	$disable_text = 'Disable Auto Renewal';
			$disable_new_status = "yes";
			$disable_auto_renewal_result = get_post_meta( $subscription_id, 'disable_auto_renewal', true);
			if($disable_auto_renewal_result && $disable_auto_renewal_result == "yes"){
				// error_log( print_r( "disable_auto_renewal_result", true ) );
      		// error_log( print_r( $disable_auto_renewal_result, true ) );
				$disable_text = 'Enable Auto Renewal';
				$disable_new_status = "no";
			}

         wp_send_json_success( array( 'message' => 'disable renewal updated to '.$disable_auto_renewal, 'new_text'=>$disable_text, 'new_status'=>$disable_new_status));
     	} else {
         wp_send_json_error( array( 'message' => 'update error' ) );
     	}
     	wp_die();
   }

	function my_account_subscribe_template_fn($subscription){
		$status = $subscription->get_status(); //phpcs:ignore
		// if(trim($status) != "active"){
		// 	return;
		// }
		// echo "<pre>";
		// print_r($subscription);
		// echo "</pre>";
		$disable_text = 'Disable Auto Renewal';
		$disable_new_status = "yes";
		$disable_auto_renewal_result = get_post_meta( $subscription->id, 'disable_auto_renewal', true);
		if($disable_auto_renewal_result && $disable_auto_renewal_result == "yes"){
			$disable_text = 'Enable Auto Renewal';
			$disable_new_status = "no";
		}

		//echo $disable_auto_renewal_result;
		?>
		<div class="ywsbs-subscription-info-item">
			<strong>Renewal Action :</strong> 
			<span href="#" id="ywsbs_subscription_disable_renewal" class="stop_renewal" intend_status="<?php echo $disable_new_status; ?>" sub_id="<?php echo $subscription->id; ?>"> <?php echo $disable_text; ?> </span>
		</div>
		<?php
		// echo "xxx";
	}

}
$obj_webstein_customize = new class_webstein_customize();
$obj_webstein_customize->init();