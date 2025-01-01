<!-- <div class="ws_cron_page_info">
	<p>Below is the list of renewal subscriptions that did not receive a ticket in the current active giveaway.<br>
	Here are the filters we apply to check each subscription: </p>
	<ul>
		<li>Verify if the subscription is still active.</li>
		<li>Check if the subscription does not have the metadata <strong>_entries_added_[giveaway_n_id]</strong></li>
		<li>Ensure the last renewal order for the subscription is marked as completed.</li>
	</ul><br>
	<p>If all filters are passed, the subscription will be included in the cron queue.</p>
</div>
<br> -->
<?php
// $subs_args = array(
// 	'post_type' => 'ywsbs_subscription',
//   		'posts_per_page' => 100,
//   		'meta_query' => array(
// 		'relation'      => 'AND',
// 		$filter_giveaway,
// 		array(
// 			'key'   => 'status',
// 			'value' => 'active',
// 			'compare' => 'LIKE',
// 		),
// 		array(
// 			'key'   => 'renew_order',
// 			'value' => '0',
// 			'compare' => '==',
// 		),
// 	),
// 	'orderby' => 'date',
// 	'order' => 'DESC'
// );
// $subs_query = new WP_Query($subs_args);
// $subs_posts = $subs_query->have_posts() ? $subs_query->get_posts() : false;




// $subscriptions_counter = 0;
// foreach($subs_posts as $subs_post_n){
// 	echo "========================================================";
// 	echo "<br>";
// 	$subs_id = $subs_post_n->ID;
// 	echo $subs_id."<br>";
// 	$renew_order = get_post_meta( $subs_id, 'renew_order',true);
// 	echo "<pre>";
// 	print_r($renew_order);
// 	echo "</pre>";
// }

// $subs_id = '21392';
// $subs_id = '2796';

// $giveaway_21546 =  get_post_meta( $subs_id, '_entries_added_21546', true );
// echo "giveaway_21546 : ".$giveaway_21546;
// echo "<br>";
// $giveaway_17791 =  get_post_meta( $subs_id, '_entries_added_17791', true );
// echo "giveaway_17791 : ".$giveaway_17791;
// echo "<br>";

// $renew_order =  get_post_meta( $subs_id, 'renew_order', true );
// echo "renew_order : ".$renew_order;
// echo "<br>";



?>

<div class="ws_entrydata_co_row">
	<div class="ws_entrydata_co_col">
<?php
global $wpdb;
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

		?>
		
		<div class="ws_entrydata_co">
		<h2> Entry Data in giveaway : <?php echo "[".$giveaway_n_id."] ".$giveaway_n_title; ?></h2>
		<table class="ws_subs_table">
			<tr>
				<th>No.</th>
				<th>Subs. ID</th>
				<th>Name</th>
				<th>Email</th>
				<th>Order ID</th>
				<th>Entries to generate</th>
				<!-- <th>Giveaway name</th> -->
				<!-- <th>Curent Ticket</th> -->
			</tr>
		
		<?php
		// $subs_args = array(
		// 	'post_type' => 'ywsbs_subscription',
   	  	// 	'posts_per_page' => 200,
   	  	// 	'meta_query' => array(
		// 		'relation'      => 'AND',
		// 		array(
		// 			'key'   => '_entries_added_'.$giveaway_n_id,
		// 			'compare' => 'NOT EXISTS',
		// 		),
		// 		array(
		// 			'key'   => 'status',
		// 			'value' => 'active',
		// 			'compare' => 'LIKE',
		// 		),
		// 	),
		// 	'orderby' => 'date',
		// 	'order' => 'DESC'
		// );
		// $subs_query = new WP_Query($subs_args);
		// $subs_posts = $subs_query->have_posts() ? $subs_query->get_posts() : false;

		$data_subscription_raw = $this->get_subscription_with_no_entry($giveaway_n_id);
		$data_subscription_list = $data_subscription_raw['full_data'];
		$subscriptions_counter = 0;
		foreach($data_subscription_list as $item_n){
			$subscriptions_counter +=1;

			$subs_id = $item_n["sub_id"];
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

			/*-- check last order status  --*/
			$subscription_obj = ywsbs_get_subscription($subs_id);
			$last_order_id = $subscription_obj->get( 'renew_order' );
			$last_order_obj = wc_get_order($last_order_id);
			if ($last_order_obj) {
				 $order_status = $last_order_obj->get_status();
				 if($order_status != "completed"){
				 	continue;
				 }
			}
			/*-- check last order status  end--*/
			

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
				$giveaway_ticket = $results[0]['entries']; 
			}
			

			?>
			<tr>
	   			<td><?php echo $subscriptions_counter; ?></td>
	   			<td><?php echo $subs_id; ?></td>
	   			<td><?php echo "[".$user_id."] ".$user_name; ?></td>
	   			<td><?php echo $user_email; ?></td>
	   			<td><?php echo $order_id; ?></td>
	   			<td><?php echo $total_entries; ?></td>
	   			<!-- <td><?php //echo "[".$giveaway_n_id."] ".$giveaway_n_title; ?></td> -->
	   			<!-- <td><?php //echo $giveaway_ticket ?></td> -->
	   		</tr>
			<?php
		}
		?>
		</table>
		</div>
		<?php

	}
}
?>
	</div>

	<div class="ws_entrydata_co_col">
		<div class="ws_entrydata_co">

			<?php
			$data_subscription_raw = $this->get_subscription_with_no_entry();
			$data_subscription_list = false;
			$data_subscription_entry_to_generate = false;
			$data_subscription_subs_id = false;
			if($data_subscription_raw){
				$data_subscription_list = $data_subscription_raw['full_data'];
				$data_subscription_entry_to_generate = $data_subscription_raw['entry_to_generate'];
				$data_subscription_subs_id = $data_subscription_raw['subs_id'];
			}
			
			?>
			<h2>Entry Generate Queue</h2>
			<p>Next Data Entry to generate is : <strong><?php echo $data_subscription_entry_to_generate; ?></strong></p>
			<span id="ws_prosess_queue_entry">Process Queue Manually</span><br>
			<?php 
			$timezone = get_option('timezone_string'); // Returns a timezone string like "America/New_York"
			if (!$timezone) {
			    $timezone = get_option('gmt_offset'); // Returns a numeric offset (e.g., -5 for UTC-5)
			}

			$timestamp = wp_next_scheduled('custom_cron_hook');
			if ($timestamp) {
				$datetime = new DateTime();
				$datetime->setTimestamp($timestamp);
				$datetime->modify('+'.$timezone.' hours');
    			echo '<p>The next scheduled time is: ' . $datetime->format('Y-m-d H:i:s').'</p>';
			}

			?>
			<!-- <p>Below is the detail data:</p> -->
			<table class="ws_subs_table">
			<tr>
				<th>No.</th>
				<th>Subs. ID</th>
				<th>Name</th>
				<th>Email</th>
				<th>Order ID</th>
				<th>Entries to generate</th>
				<th>Giveaway name</th>
				<th>Curent Ticket</th>
			</tr>
			<?php 
			foreach ($data_subscription_list as $item_n) {
				?>
				<tr>
		   			<td><?php echo $item_n["no"]; ?></td>
		   			<td><?php echo $item_n["sub_id"]; ?></td>
		   			<td><?php echo "[".$item_n["user_id"]."] ".$item_n["name"]; ?></td>
		   			<td><?php echo $item_n["email"]; ?></td>
		   			<td><?php echo $item_n["order_id"]; ?></td>
		   			<td><?php echo $item_n["entry_to_gen"]; ?></td>
		   			<td><?php echo "[".$item_n["giveaway_id"]."] ".$item_n["giveaway_name"]; ?></td>
		   			<td><?php echo $item_n["current_ticket"]; ?></td>

		   		</tr>
				<?php
			}
			?>
			</table>
		</div>
	</div>

</div>