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


	   	add_action('wp_ajax_proses_generate_entry', [$this, 'handle_ajax_proses_generate_entry']);
	   	add_action('wp_ajax_nopriv_proses_generate_entry', [$this, 'handle_ajax_proses_generate_entry']);


	   	add_action('woocommerce_order_status_changed', [$this, 'wc_order_changed_delete'], 50, 3);
	   	// add_action('woocommerce_order_status_changed', [$this, 'wc_order_changed'], 100, 3); 
	   	

	   	/*CRON SCHEDULE ================================================================================*/
	   	add_filter('cron_schedules', [$this,'custom_cron_interval']);
	   	add_action('wp', [$this,'custom_schedule_cron']);
	   	add_action('custom_cron_hook', [$this,'custom_cron_function']);
	   	/*CRON SCHEDULE END ============================================================================*/


	   	/*CRON PAGE=====================================================================================*/
	   	add_action('admin_menu', [$this, 'ws_entry_cron_setting']);
	   	add_action('admin_init', [$this,'auto_entry_cron_fn']);
	   	/*CRON PAGE END ================================================================================*/

	   	// add_action('admin_init', [$this,'my_custom_settings_init']);

	   	add_shortcode('check_order', [$this,'check_order_fun']);
	   	add_shortcode('ws_delete_entry', [$this,'ws_delete_entry_fx']);

	}
	public function init(){
		add_action( 'wp_enqueue_scripts', [$this,'enq_script'], 100 );
		add_action( 'admin_enqueue_scripts', [$this,'enq_script_admin'],100 );
	}

	

	public function enq_script(){
		wp_enqueue_style( $this->this_folder.'_css', $this->this_uri.'/'.$this->this_folder.'.css', array(),rand());
		wp_enqueue_script( $this->this_folder.'_js', $this->this_uri.'/'.$this->this_folder.'.js', array(), rand(), true ); 
		wp_localize_script( $this->this_folder.'_js', 'ws_ajax_object', array( 
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'ws_ajax_nonce' ), 
      ));
	}

	public function enq_script_admin(){
		wp_enqueue_style( $this->this_folder.'_admin_css', $this->this_uri.'/'.$this->this_folder.'_admin.css', array(),rand());
		wp_enqueue_script( $this->this_folder.'_admin_js', $this->this_uri.'/'.$this->this_folder.'_admin.js', array(), rand(), true ); 
		wp_localize_script( $this->this_folder.'_admin_js', 'ws_admin_ajax_object', array( 
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'ws_admin_ajax_nonce' ), 
      ));
	}

	public function ws_delete_entry_fx($atts){
		global $wpdb;

		$a = shortcode_atts( array(
			'subs_id' => false,
			'giveaway_id' => false,
		), $atts );
		

		$subs_id = $a['subs_id'];
		$giveaway_id = $a['giveaway_id'];
		if(!$subs_id){
			return "<p> add 'subs_id'</p>";
		}
		if(!$giveaway_id){
			return "<p> add 'giveaway_id'</p>";
		}
		$subscription_id = $subs_id;

		$main_order_id = get_post_meta($subscription_id, 'order_id', true);

		$giveaway_ticket_table = $wpdb->prefix . "giveaway_entries_tickets";
        $entries_table = $wpdb->prefix . "giveaway_entries";

        $wpdb->delete( $giveaway_ticket_table, array( 'order_id' => $main_order_id, 'giveaway_id' => $giveaway_id), array('%d','%d'));
        $wpdb->delete( $entries_table, array( 'order_id' => $main_order_id, 'giveaway_id' => $giveaway_id), array('%d','%d'));

        delete_post_meta($subscription_id, '_entries_added_'.$giveaway_id, $giveaway_id);

        return "<p>delete success</p>";

	}

	function check_order_fun(){
		
		// $order = wc_get_order('21727');
		// $renew_subscriptions = $order->get_meta( 'subscriptions' );
		ob_start();
		$renew_order = get_post_meta('21727', 'renew_order');
		print_r($renew_order);
		// echo "<br>";
		// $renew_subscriptions2 = get_post_meta('21697', '_entries_added_21546', true);
		// print_r($renew_subscriptions2);
		// echo "<br>";
		return ob_get_clean();

		

	}


	/*CRON SCHEDULE ================================================================================*/
	/*==================================================================================================*/
	/*==================================================================================================*/
	/*==================================================================================================*/
	function custom_cron_interval($schedules) {
    	$schedules['ten_minutes'] = array(
    		'interval' => 600, // 600 seconds = 10 minutes
        	'display'  => __('Every 10 Minutes')
    	);
    	return $schedules;
	}
	function custom_schedule_cron() {
		if (!wp_next_scheduled('custom_cron_hook')) {
        wp_schedule_event(time(), 'ten_minutes', 'custom_cron_hook');
    	}
	}
	function custom_cron_function() {
    	// Your task logic here
   		error_log('Cron job -- proses_generate_entry -- executed at ' . current_time('mysql'));
   		// temporary disabled
   		// $this->handle_ajax_proses_generate_entry();
	}


	/*==================================================================================================*/
	/*==================================================================================================*/
	/*==================================================================================================*/
	/*CRON SCHEDULE END ================================================================================*/
	










	/*CRON PAGE=====================================================================================*/
	/*==================================================================================================*/
	/*==================================================================================================*/
	/*==================================================================================================*/

	function ws_entry_cron_setting(){
		add_menu_page(
        'WS Entries Cron Queue', // Page title
        'WS Entries Cron Queue', // Menu title
        'manage_options',  // Capability
        'ws-entry-cron', // Menu slug
        [$this,'ws_setting_page_html'], // Callback function
        'dashicons-admin-generic', // Icon (Dashicons)
        20 // Position
    	);
	}

	// Callback function to render the settings page
	function ws_setting_page_html() {
	    // Check if the user has the required capability
	    if (!current_user_can('manage_options')) {
	        return;
	    }
	    ?>
	    <div class="wrap">
	        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
	        <form method="post" action="options.php">
	            <?php
	            // Output security fields for the registered setting
	            settings_fields('custom_settings_group');
	            // Output setting sections and their fields
	            do_settings_sections('ws-entry-cron');
	            // Output save settings button
	            submit_button('Save Settings');
	            ?>
	        </form>
	    </div>
	    <?php
	}

	function auto_entry_cron_fn(){
		// Register a new setting for "custom-settings"
		register_setting('ws_entry_cron_settings_group', 'wsentrycron_settings_option');

		// Add a new section to the "custom-settings" page
		add_settings_section(
			'ws_entry_cron_section', // Section ID
			'WS Entries Cron Queue', // Title
			[$this,'ws_entry_cron_section_callback'], // Callback
			'ws-entry-cron' // Page slug
		);
	}

	// Section callback
	function ws_entry_cron_section_callback() {
		include('ws_cron_page_html.php');
		// include('ws_cron_page_html_test.php');

	}

	function get_subscription_with_no_entry($query_giveaway_id = false){
		global $wpdb;
		
		/*-- data giveaway --*/
		$now = date('Y-m-d');
		$givaway_arg = array(
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
		if($query_giveaway_id){
			$givaway_arg['post__in'] = [$query_giveaway_id];
		}
		$data_giveaway = array();
		$giveaway_query = new WP_Query($givaway_arg);
		$giveaway_posts = $giveaway_query->have_posts() ? $giveaway_query->get_posts() : false;
		if($giveaway_posts){
			foreach($giveaway_posts as $giveaway_post_n){				
				$giveaway_n_id = $giveaway_post_n->ID;
				$giveaway_n_title = get_the_title( $giveaway_post_n->ID);

				$giveaway_n = array();
				$giveaway_n["id"] = $giveaway_n_id;
				$giveaway_n["title"] =$giveaway_n_title;
				array_push($data_giveaway, $giveaway_n);
			}
		}
		else{
			return false;	
		}
		/*-- data giveaway end --*/
		

		$data_result_all = [];
		$data_result_subs_full_data = [];
		$data_result_entry_to_generate = 0;
		$data_result_subs_id = [];


		$filter_giveaway = [];
		if($query_giveaway_id){
			$filter_giveaway_n["key"] ='_entries_added_'.$query_giveaway_id;
			$filter_giveaway_n["compare"] = 'NOT EXISTS';
			array_push($filter_giveaway, $filter_giveaway_n);
		}else{
			if(count($data_giveaway)>1){
				$filter_giveaway['relation'] = 'OR';
				foreach ($data_giveaway as $giveaway_n) {
					$filter_giveaway_n = array();
					$filter_giveaway_n["key"] ='_entries_added_'.$giveaway_n["id"];
					$filter_giveaway_n["compare"] = 'NOT EXISTS';
					array_push($filter_giveaway, $filter_giveaway_n);
				}
			}else{
				$filter_giveaway_n["key"] ='_entries_added_'.$data_giveaway[0]["id"];
				$filter_giveaway_n["compare"] = 'NOT EXISTS';
				array_push($filter_giveaway, $filter_giveaway_n);
			}
		}
		$filter_renew_order = [];
		$filter_renew_order['relation'] = 'OR';
		$filter_renew_order_n1 = array();
		$filter_renew_order_n1["key"] ='renew_order';
		$filter_renew_order_n1["compare"] = 'NOT EXISTS';
		$filter_renew_order_n2 = array();
		$filter_renew_order_n2["key"] ='renew_order';
		$filter_renew_order_n2["value"] ='0';
		$filter_renew_order_n2["compare"] = '==';

		array_push($filter_renew_order, $filter_renew_order_n1);
		array_push($filter_renew_order, $filter_renew_order_n2);
		
		$subs_args = array(
			'post_type' => 'ywsbs_subscription',
		  	'posts_per_page' => 100,
		  	'meta_query' => array(
				'relation'      => 'AND',
				$filter_giveaway,
				$filter_renew_order,
				array(
					'key'   => 'status',
					'value' => 'active',
					'compare' => 'LIKE',
				),
				// array(
				// 	'key'   => 'renew_order',
				// 	'value' => '0',
				// 	'compare' => '==',
				// ),
			),
			'orderby' => 'date',
			'order' => 'DESC'
		);

		$subs_query = new WP_Query($subs_args);
		$subs_posts = $subs_query->have_posts() ? $subs_query->get_posts() : false;

		// echo "<pre>";
		// print_r($subs_posts);
		// echo "</pre>";
		
		$subscriptions_counter = 0;
		foreach($subs_posts as $subs_post_n){
			$subscriptions_counter +=1;
			$subs_id = $subs_post_n->ID;
			$user_id = get_post_meta( $subs_id, 'user_id', true );
			$order_id = get_post_meta( $subs_id, 'order_id', true);
			$product_id = get_post_meta( $subs_id, 'product_id', true );
			$rates_payed = get_post_meta( $subs_id, 'rates_payed', true );
			$entries = get_field('entries', $product_id);
			$total_intervals = $rates_payed;
			$total_entries = $entries*$total_intervals;
			$user_name = "-";
			$user_email = "-";
			$user_info = get_userdata($user_id);
			if ($user_info) {
				$first_name = $user_info->first_name;
				$last_name = $user_info->last_name;
				$user_name = $first_name.' '.$last_name;
				$user_email = $user_info->user_email;
			}
			$temp_result_entry_to_generate = 0;
			$temp_list_data = array();

			$order = wc_get_order( $order_id );

			// get last order
			$subscription_yith = ywsbs_get_subscription( $subs_id );
			if($subscription_yith){
				$orders = (array) $subscription_yith->get( 'payed_order_list' );	
			}
		    if ( ! empty( $orders ) && is_array( $orders ) ) {
		        $last_order_id = end( $orders ); 
		        $order = wc_get_order( $last_order_id );
		    }
		    // get last order end


			if($order){
				foreach ( $order->get_items() as $item_id => $item ) {
					$product_id = $item->get_product_id();
					$order_qty  = $item->get_quantity();
					$item_type = $item->get_meta('_type', true);
					if(!$item_type ){
						continue;
					}
					if($item_type != "subscription"){
						continue;
					}

					$entries = get_field('entries', $product_id);

					for($i=1; $i<=$order_qty; $i++){

						/*check data giveaway*/ 
						foreach ($data_giveaway as $data_giveaway_n) {

							$giveaway_n_id = $data_giveaway_n['id'];
							$giveaway_n_title = $data_giveaway_n['title'];

							$total_entries = $entries * $total_intervals;

							/*check table entry*/ 
							$table_name = $wpdb->prefix . "giveaway_entries";
							$giveaway_ticket = 0;
							$results = $wpdb->get_results(
							    $wpdb->prepare(
							        "SELECT * FROM $table_name WHERE giveaway_id = %d and order_id = %d",
							        $giveaway_n_id, 
							        $order_id
							    ),ARRAY_A
							);
							if(count($results)>0){
								

								foreach ($results as $row) {
									$giveaway_ticket = $entries; 
									if($giveaway_ticket>0){
										continue;
										// temporary
									}

									$data_result_n["no"] = $subscriptions_counter;
									$data_result_n["sub_id"] = $subs_id;
									$data_result_n["user_id"] = $user_id;
									$data_result_n["name"] = $user_name;
									$data_result_n["email"] = $user_email;
									$data_result_n["order_id"] = $order_id;
									// $data_result_n["order_id"] = $last_order_id;
									$data_result_n["entry_to_gen"] = $total_entries;
									$data_result_n["giveaway_id"] = $giveaway_n_id;
									$data_result_n["giveaway_name"] = $giveaway_n_title;
									$data_result_n["current_ticket"] = $giveaway_ticket;

									// $data_result_entry_to_generate+= $total_entries;
									$temp_result_entry_to_generate += $total_entries;
									array_push($temp_list_data, $data_result_n);
								}
							}else{

								$data_result_n["no"] = $subscriptions_counter;
								$data_result_n["sub_id"] = $subs_id;
								$data_result_n["user_id"] = $user_id;
								$data_result_n["name"] = $user_name;
								$data_result_n["email"] = $user_email;
								$data_result_n["order_id"] = $order_id;
								// $data_result_n["order_id"] = $last_order_id;
								$data_result_n["entry_to_gen"] = $total_entries;
								$data_result_n["giveaway_id"] = $giveaway_n_id;
								$data_result_n["giveaway_name"] = $giveaway_n_title;
								$data_result_n["current_ticket"] = $giveaway_ticket;
								// $data_result_entry_to_generate+= $total_entries;
								$temp_result_entry_to_generate += $total_entries;
								array_push($temp_list_data, $data_result_n);
							}
						}
						/*check data giveaway end */ 
					}
				}
			}
			

			

			if(($temp_result_entry_to_generate + $data_result_entry_to_generate) < 2400){
				foreach ($temp_list_data as $temp_list_data_n) {
					array_push($data_result_subs_full_data, $temp_list_data_n);
				}
				$data_result_entry_to_generate += $temp_result_entry_to_generate;
				$data_result_subs_id = [$subs_id];
			}else{
				break;
			}

		}
		$data_result_all["full_data"] = $data_result_subs_full_data;
		$data_result_all["entry_to_generate"] = $data_result_entry_to_generate;
		$data_result_all["subs_id"] = $data_result_subs_id;
		
		return $data_result_all;

	}

	function handle_ajax_proses_generate_entry(){
		global $wpdb;
		$data_subscription_raw = $this->get_subscription_with_no_entry();
		$data_subscription_list = $data_subscription_raw['full_data'];
		if(count($data_subscription_list)<1){
			wp_send_json_error( array( 'message' => "there is no data to process"));
			wp_die();
			return;
		}


		foreach($data_subscription_list as $item_n){
			$subscriptions = get_post($item_n['sub_id']);
			$giveaway_id = $item_n['giveaway_id'];

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

		wp_send_json_success( array( 'message' => "Process done, page will reloaded after 3second"));
		wp_die();
		return;

	}

	/*======================================================================================================*/
	/*======================================================================================================*/
	/*======================================================================================================*/
	/*CRON PAGE END=====================================================================================*/

	


	function wc_order_changed_delete($order_id, $old_status, $new_status){
		global $wpdb, $woocommerce;
		$order = wc_get_order($order_id);

		// We create this in case there is 1 new order with 2 subscription product;
		// old function delete data ticket in both subscription but only delete this meta _entries_added_$giveaway_id in 1 subscription
		// that make the system only create 1 entry 1 subscription
		// now we delete the other meta in this other subscription
		// delete_post_meta($subscription_id, '_entries_added_'.$giveaway->ID, $giveaway_id);

	   	$is_a_renew = $order->get_meta( 'is_a_renew' );
	   	$renew_subscriptions = $order->get_meta( 'subscriptions' );

		if($is_a_renew && $renew_subscriptions && ($new_status == 'on-hold' || $new_status =='completed')){
			$main_order_id = false;
			if(is_array($renew_subscriptions)){
				$main_order_id = get_post_meta($renew_subscriptions[0], 'order_id', true);
			}

			if($main_order_id){

				$current_time = new DateTime("now", new DateTimeZone('Australia/Sydney') );
		        $now = $current_time->format('Y-m-d H:i:s');
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
		        $current_giveaways = get_posts($arg);
		        if($current_giveaways){
		        	// $main_order_id
		        	$main_order = wc_get_order($main_order_id);
		        	if($main_order){
		        		$renew_subscriptions = $main_order->get_meta( 'subscriptions' );	
		        		if($renew_subscriptions && is_array($renew_subscriptions)){
		        			foreach ($renew_subscriptions as $renew_subscription_n) {
		        				foreach($current_giveaways as $giveaway){
					                $giveaway_id = $giveaway->ID;
									delete_post_meta($renew_subscription_n, '_entries_added_'.$giveaway->ID, $giveaway_id);

					            }
					            
		        			}
		        		}		        			
		        	}
	         	}

			}
		}
	}

	// function wc_order_changed($order_id, $old_status, $new_status){
	// 	global $wpdb, $woocommerce;
	// 	$order = wc_get_order($order_id);

	//    $is_a_renew = $order->get_meta( 'is_a_renew' );
	//    $renew_subscriptions = $order->get_meta( 'subscriptions' );

	//    if($is_a_renew && $renew_subscriptions && $new_status =='completed'){
	//    	$user_id = $order->get_user_id();

	//    	$subscription_id = $renew_subscriptions[0];
	//    	print_r('renew_subscriptions--------');
	//    	print_r($renew_subscriptions);
	//    	$this->generate_tickets_for_user_func($user_id, $subscription_id, $order_id);
	//    }
	// }

	// function generate_tickets_for_user_func($user_id, $subscription_id, $subs_order_id = false) {
	// 	error_log( print_r( 'generate_tickets_for_user_func-----------------------', true ) );
	//    	global $wpdb;

	// 	$now = date('Y-m-d');
	// 	$arg = array(
	//      'post_type' => 'giveaway',
	//      'posts_per_page' => -1,
	//      'meta_query' => array (
	//      	'relation'      => 'AND',
	//          array(
	//            'key'       => 'end_date',
	//            'value'     => $now,
	//            'compare'   => '>=',
	//            'type'      => 'DATE'
	//          ),
	//          array(
	//            'key'       => 'start_date',
	//            'value'     => $now,
	//            'compare'   => '<=',
	//            'type'      => 'DATE'
	//          ),
	//       )
	//    	);

	// 	error_log( print_r('--------------------------', true ) );
	// 	error_log( print_r('subscription_id', true ) );
	// 	error_log( print_r($subscription_id, true ) );

	//    $items = get_posts($arg);

	//    foreach($items as $item){
	//        $start_date = get_field('start_date', $item->ID);
	//        $end_date = get_field('end_date', $item->ID);

	//        $today_strot = strtotime(date('Y-m-d h:i:s'));
	//        $start_strot = strtotime($start_date);
	//        $end_strot = strtotime($end_date);

	//        $get_total_winners = get_post_meta( $item->ID, 'get_total_winners', true );

	//        if(!$get_total_winners){
    //    	  		$giveaway_id = $item->ID;
	//        	  	$args = array(
	//        	  		'post_type' => 'ywsbs_subscription',
	//        	  		'p' => $subscription_id,
	//        	  	);
    //    	  		$subscriptions = get_posts($args);
	// 			foreach($subscriptions as $subscriptions){
	// 				$wps_subscription_status = get_post_meta( $subscriptions->ID, 'status', true );

	// 				if($wps_subscription_status == 'active'){
	// 					$wps_schedule_start = get_post_meta( $subscriptions->ID, 'start_date', true );
	// 					// $product_id            = get_post_meta( $subscriptions->ID, 'product_id', true );
	// 					$user_id            = get_post_meta( $subscriptions->ID, 'user_id', true );
	// 					$order_id            = get_post_meta( $subscriptions->ID, 'order_id', true );
	// 					// $order = wc_get_order( $order_id );
	// 					$order = false;
	// 					$orders = false;
	// 					$subscription_yith = ywsbs_get_subscription( $subscriptions->ID );
	// 					if($subscription_yith){
	// 						$orders = (array) $subscription_yith->get( 'payed_order_list' );	
	// 					}
						
	// 					error_log( print_r('subscriptions->ID', true ) );
	// 					error_log( print_r($subscriptions->ID, true ) );

	// 				    if ( ! empty( $orders ) && is_array( $orders ) ) {
	// 				        $last_order_id = $orders[0]; 
	// 				        $order = wc_get_order( $last_order_id );
	// 				    }

	// 					error_log( print_r( 'generate_tickets_for_user_func-----------------------', true ) );
	// 					error_log( print_r('--------------', true ) );
	// 					error_log( print_r('order_id', true ) );
	// 					error_log( print_r($order_id, true ) );

	// 					error_log( print_r('order', true ) );
	// 					error_log( print_r($order, true ) );

	// 					if($order){
	// 						foreach ( $order->get_items() as $item_id => $item ) {
	// 							$product_id = $item->get_product_id();
	// 							$order_qty  = $item->get_quantity();
	// 							$item_type = $item->get_meta('_type', true);
	// 							if(!$item_type ){
	// 								continue;
	// 							}
	// 							if($item_type != "subscription"){
	// 								continue;
	// 							}

	// 							$entries = get_field('entries', $product_id);

	// 							for($i=1; $i<=$order_qty; $i++){
	// 								$rates_payed = get_post_meta( $subscriptions->ID, 'rates_payed', true );
	// 								$entries = get_field('entries', $product_id);
	// 								$order_qty = 1;
	// 								$total_intervals = $rates_payed;
	// 								$total_entries = $entries*$total_intervals;

									


	// 								$table_name = $wpdb->prefix . "giveaway_entries";
	// 								$dou_str = date('Y').'-'.date('m').'-'.date('d').' '.date('h').':'.date('i').':'.date('s');
	// 				            	$wpdb->insert( $table_name,
	// 				                array( 
	// 				                    'user_id' => $user_id,
	// 				                    'product_id' => $product_id,
	// 				                    'entries' => $total_entries,
	// 				                    'giveaway_id' => $giveaway_id,
	// 				                    'order_id' => $order_id,
	// 				                    'order_item_id' => 0,
	// 				                    'order_qty' => $order_qty,
	// 				                    'generate_ticket' => 1,
	// 				                    'created_at' => $dou_str,
	// 				                    'updated_at' => $dou_str,),

	// 				                array( '%d', '%d','%d','%d', '%d','%d','%d', '%d', '%s', '%s' ) );
	// 				            	$ticket_table_name = $wpdb->prefix . "giveaway_entries_tickets";
	// 							   	$max = 10 - strlen((string) $order_id);
	// 							   	$tickets = [];
	// 					   			for($i = 1; $i <= $total_entries; $i++){
	// 								    $ticket_no = $order_id.''.str_pad('', $max - strlen((string) $i), '0', STR_PAD_LEFT) . $i;
	// 								    $tickets[] = $ticket_no;

	// 								    $dou_str = date('Y').'-'.date('m').'-'.date('d').' '.date('h').':'.date('i').':'.date('s');
	// 								    $wpdb->insert( $ticket_table_name,
	// 					                array( 
	// 					                    'user_id' => $user_id,
	// 					                    'giveaway_id' => $giveaway_id,
	// 					                    'order_id' => $order_id,
	// 					                    'ticket_no' => $ticket_no,
	// 					                    'entry_type' => 1,
	// 					                    'created_at' => $dou_str,
	// 					                    'updated_at' => $dou_str,),

	// 					                array( '%d', '%d','%d' ,'%d' ,'%d' , '%s', '%s' ) );
	// 								}
	// 							}
	// 						}
	// 					}
	// 					update_post_meta($subscriptions->ID, '_entries_added_'.$giveaway_id, $giveaway_id);
	// 				}
	// 			}
	//        	}
	//    	}
	// }

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

			return "cancelled";
		}
		return $new_status;
	}


	

   function handle_ajax_change_auto_renewal(){
   	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'ws_ajax_nonce' ) ) {
            wp_send_json_error( array( 'message' => 'Invalid nonce.' ) );
      }
      $subscription_id = isset($_POST['subscription_id']) ? $_POST['subscription_id'] : false;
      $disable_auto_renewal = isset($_POST['intend_status']) ? $_POST['intend_status'] : "no";

      $update_result = update_post_meta($subscription_id, 'disable_auto_renewal', $disable_auto_renewal);
      
      // error_log( print_r( "----------------------", true ) );

      if ( $update_result ) {
      	$disable_text = 'Disable Auto Renewal';
			$disable_new_status = "yes";
			$disable_auto_renewal_result = get_post_meta( $subscription_id, 'disable_auto_renewal', true);
			if($disable_auto_renewal_result && $disable_auto_renewal_result == "yes"){
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
		
		$disable_text = 'Disable Auto Renewal';
		$disable_new_status = "yes";
		$disable_auto_renewal_result = get_post_meta( $subscription->id, 'disable_auto_renewal', true);
		if($disable_auto_renewal_result && $disable_auto_renewal_result == "yes"){
			$disable_text = 'Enable Auto Renewal';
			$disable_new_status = "no";
		}

		?>
		<div class="ywsbs-subscription-info-item">
			<strong>Renewal Action :</strong> 
			<span href="#" id="ywsbs_subscription_disable_renewal" class="stop_renewal" intend_status="<?php echo $disable_new_status; ?>" sub_id="<?php echo $subscription->id; ?>"> <?php echo $disable_text; ?> </span>
		</div>
		<?php
	}

}
$obj_webstein_customize = new class_webstein_customize();
$obj_webstein_customize->init();


