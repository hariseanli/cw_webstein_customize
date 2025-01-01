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

$data_giveaway = [];
$giveaway_query = new WP_Query($givaway_arg);
$giveaway_posts = $giveaway_query->have_posts() ? $giveaway_query->get_posts() : false;
if($giveaway_posts){
	foreach($giveaway_posts as $giveaway_post_n){
		$giveaway_n = array();
		$giveaway_n["id"] = $giveaway_post_n->ID;
		$giveaway_n["title"] = get_the_title( $giveaway_post_n->ID);
		array_push($data_giveaway, $giveaway_n);
	}
}

$subs_args = array(
	'post_type' => 'ywsbs_subscription',
	'posts_per_page' => 3,
	'meta_query' => array(
		// 'relation'      => 'AND',
		// array(
		// 	'key'   => 'status',
		// 	'value' => 'active',
		// 	'compare' => 'LIKE',
		// ),
	),
	'orderby'        => 'date', // Order by publish date
    'order'          => 'DESC'
);

$subs_query = new WP_Query($subs_args);
$subs_posts = $subs_query->have_posts() ? $subs_query->get_posts() : false;

?>
<table class="ws_subs_table">
	<tr>
		<th>No.</th>
		<th>Subs. ID</th>
		<th>Name</th>
		<th>Email</th>
		<th>Order ID</th>
		<th>Entry to generate</th>
		<th>Giveaway name</th>
		<th>Curent Ticket</th>
	</tr>
<?php
if($subs_posts){
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

		/*check data giveaway*/ 
		foreach ($data_giveaway as $data_giveaway_n) {
			$giveaway_n_id = $data_giveaway_n['id'];
			$giveaway_n_title = $data_giveaway_n['title'];
			
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
			// error_log( print_r( "SELECT * FROM $table_name WHERE giveaway_id = ".$giveaway_n_id, true ) );

			if(count($results)>0){
				foreach ($results as $row) {
					$giveaway_ticket = $row['entries']; 
				?>
				<tr>
		   			<td><?php echo $subscriptions_counter; ?></td>
		   			<td><?php echo $subs_id; ?></td>
		   			<td><?php echo "[".$user_id."] ".$user_name; ?></td>
		   			<td><?php echo $user_email; ?></td>
		   			<td><?php echo $order_id; ?></td>
		   			<td><?php echo $total_entries; ?></td>
		   			<td><?php echo "[".$giveaway_n_id."] ".$data_giveaway_n['title']; ?></td>
		   			<td><?php echo $giveaway_ticket ?></td>
		   		</tr>
			   	<?php
				}	
			}else{
				?>
				<tr>
		   			<td><?php echo $subscriptions_counter; ?></td>
		   			<td><?php echo $subs_id; ?></td>
		   			<td><?php echo "[".$user_id."] ".$user_name; ?></td>
		   			<td><?php echo $user_email; ?></td>
		   			<td><?php echo $order_id; ?></td>
		   			<td><?php echo $total_entries; ?></td>
		   			<td><?php echo "[".$giveaway_n_id."] ".$data_giveaway_n['title']; ?></td>
		   			<td><?php echo $giveaway_ticket ?></td>
		   		</tr>
			   	<?php
			}
			
			/*check table entry end*/ 
			
		}
		/*check data giveaway end*/ 
		

		
	   	
	}
}
?>
</table>