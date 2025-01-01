<?php
global $wpdb;
function get_paginated_orders_by_date_range($start_date, $end_date, $page = 1, $per_page = 10, $query_for = false) {
    // Ensure dates are in 'YYYY-MM-DD' format
    $start_date = date('Y-m-d', strtotime($start_date));
    $end_date = date('Y-m-d', strtotime($end_date));

    // Calculate offset for pagination
    // $offset = ($page - 1) * $per_page;

    // Query orders
    $args = [
        'limit'        => $per_page, // Number of orders per page
        'status'  => array( 'completed', 'processing' ),
        'page'	=>$page,
        'return'       => 'ids', // Return order IDs only
        'date_created' => $start_date . '...' . $end_date, // Date range
        'meta_query' => array(
        	array(
				'key'   => 'is_a_renew',
				'compare' => 'NOT EXISTS'
			)
        ),
        'orderby'      => 'date',    // Order by date
        'order'        => 'DESC',     // Ascending order (oldest first)
    ];

    // $args_pagination = $args;
    // unset($args_pagination['limit']);
    // unset($args_pagination['page']);
    if($query_for == "pagination"){
	    $args['limit'] = -1;
	    unset($args['page']);
    }

    $query = new WC_Order_Query($args);
    $orders = $query->get_orders();

    return $orders;
}

// =====================================================================================================================

$giveaway_id_m = isset($_GET['ws_giveaway']) ? $_GET['ws_giveaway'] : ''; 
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';
$show_correct = isset($_GET['show_correct']) ? $_GET['show_correct'] : 'true';
$page = isset($_GET['npage']) ? intval($_GET['npage']) : 1; 
$per_page = 100;

if($start_date == ""){
	$start_date = current_time('Y-m-d');
	$t_date = new DateTime($start_date);
	$t_date->modify('-3 days');
	$start_date = $t_date->format('Y-m-d');
}
if($end_date == ""){
	$end_date = current_time('Y-m-d');
}



// $page = isset($_GET['npage']) ? intval($_GET['npage']) : 1; 

$arg_giveaway = array(
    'post_type'      => 'giveaway',
    'post_status'    => 'publish',
    'posts_per_page' => -1, // Retrieve all published posts.
);

$giveaway_query = new WP_Query($arg_giveaway);
$giveaway_posts = $giveaway_query->have_posts() ? $giveaway_query->get_posts() : false;
$data_giveaway_arr = array();
if($giveaway_posts){
	foreach($giveaway_posts as $giveaway_post_n){				
		$giveaway_n_id = $giveaway_post_n->ID;
		$giveaway_n_title = get_the_title( $giveaway_post_n->ID);

		$giveaway_n = array();
		$giveaway_n["id"] = $giveaway_n_id;
		$giveaway_n["title"] =$giveaway_n_title;
		array_push($data_giveaway_arr, $giveaway_n);
	}
}


?>
<div class="ws_input_fields">
	<div class="input_field_item">
		<label>Giveaway</label>
		<select name="ws_giveaway">
			<?php 
			foreach ($data_giveaway_arr as $key => $value) {
				$selected = "";
				$gw_id = $value["id"];
				$gw_title = $value["title"];
				if($gw_id == $giveaway_id_m){
					$selected = "selected";
				}
				?>
				<option <?php echo $selected; ?> value="<?php echo $gw_id ?>"> <?php echo $gw_title; ?> </option>
				<?php
			}
			?>
		</select>
	</div>
	<div class="input_field_item">
		<label>Start Date</label>
		<input type="text" name="ws_start_date" placeholder="yyyy-mm-dd" value="<?php echo $start_date ?>">
	</div>
	<div class="input_field_item">
		<label>End Date</label>
		<input type="text" name="ws_end_date" placeholder="yyyy-mm-dd" value="<?php echo $end_date ?>">
	</div>
	<div class="input_field_item">
		<label>Show correct list</label>
		<select name="show_correct">
			<?php
			if($show_correct=="true"){
				?>
					<option selected value="true">Show </option>
					<option value="false">hide </option>
				<?php
			}else{
				?>
					<option value="true">Show </option>
					<option selected value="false">hide </option>
				<?php
			}
			?>
		</select>
	</div>
	<div class="input_field_submit">
		<a href="#" id="ws_direct_orderlist_filter">Filter</a>
	</div>
	
</div>

<?php

$order_ids = get_paginated_orders_by_date_range($start_date, $end_date, $page, $per_page);

$counter_n = $page;
if($page >1){
	$counter_n = ($page - 1) * $per_page;
	$counter_n += 1;
}

if (!empty($order_ids)) {
	?>
	<div class="ws_subs_table_co">
	<table class="ws_subs_table">
	<tr>
		<th>No.</th>
		<th>Order ID</th>
		<th>Name</th>
		<th>Email</th>
		<th>Entries in Order</th>
		<th>Entries Generated</th>
		<!-- <th>Type</th> -->
		<th>Product Type</th>
		<th>Product Detail</th>
		<th>Date</th>
		<th>Entry Status</th>
		<th>Process</th>
	</tr>
	<?php

	foreach ($order_ids as $order_id) {
		$order = wc_get_order($order_id);
		$billing_first_name = $order->get_billing_first_name();
        $billing_last_name = $order->get_billing_last_name();
        $billing_email = $order->get_billing_email();
        $order_date = $order->get_date_created();
        $is_a_renew = $order->get_meta( 'is_a_renew' );


        /*check table entry*/ 
		$table_name = $wpdb->prefix . "giveaway_entries";
		$giveaway_ticket = 0;
		$results = $wpdb->get_results(
		    $wpdb->prepare(
		        "SELECT * FROM $table_name WHERE giveaway_id = %d and order_id = %d",
		        $giveaway_id_m, 
		        $order_id
		    ),ARRAY_A
		);

		$order_items = $order->get_items();

		//- compare order item qty to entry row  -------------------------
		$compare_status = true;
		$compare_status_text = "";
		$order_item_qty_count = 0;
		foreach ( $order_items as $item_id => $item ) {
			$product_type = $item->get_meta('_type', true);
			$order_qty  = $item->get_quantity();
			if($product_type =='giveaway_product' || $product_type == 'subscription'){
				for($x=0; $x<$order_qty; $x++){
					$order_item_qty_count+=1;
				}
			}

		}
		if(count($results) != $order_item_qty_count){
			$compare_status = false;
			$compare_status_text = "order:".$order_item_qty_count." | entry:".count($results);
		}

		//- compare order item qty to entry row  -------------------------

		foreach ( $order_items as $item_id => $item ) {
			$product_id = $item->get_product_id();
			$order_qty  = $item->get_quantity();
			$product_type = $item->get_meta('_type', true);
			// $entries = get_field('entries', $product_id);
			// $entries = wc_get_order_item_meta( $item_id, '_entries', true ); 
			if($product_type !='giveaway_product'){
				continue;
			}
			$product_obj = wc_get_product($product_id);
			$product_title = "-";
			if($product_obj){
				$product_title = $product_obj->get_name();
			}

			/*-----------------------------------------*/
			$meta_item_id = $item->get_id();
			$giveaway_id = wc_get_order_item_meta( $meta_item_id, '_giveaway_id', true );
			$product_id = wc_get_order_item_meta( $meta_item_id, '_product_id', true );
			$entries = wc_get_order_item_meta( $meta_item_id, '_entries', true );
			$related_products = [];
         	$related_giveaways = [];

			foreach ( $order->get_items() as $item_id => $order_item ) {
			 	$related_giveaway = wc_get_order_item_meta( $item_id, '_related_giveaway', true );
			 	$related_product = wc_get_order_item_meta( $item_id, '_related_product', true );
			 	if($related_giveaway && $related_product){
			     	$related_products[] = $related_product;
			     	$related_giveaways[] = $related_giveaway;
			 	}
			}
			$order_entry = $entries;
			if($giveaway_id && $related_giveaways && in_array($giveaway_id, $related_giveaways) && $related_products && in_array($product_id, $related_products)){
				$order_entry = $entries*3;
			}
			/*-----------------------------------------*/



			
			for($x=0; $x<$order_qty; $x++){
				$giveaway_entries_result = isset($results[$x]['entries']) ? intval($results[$x]['entries']) : '-'; 
				$entry_status = "Correct";
				if($order_entry != $giveaway_entries_result ){
					$entry_status = "<span style='color:red;font-weight:700;'>Wrong</span>";
				}
				if($compare_status == false){
					$entry_status = "<span style='color:red;font-weight:700;'>Compare:".$compare_status_text."</span>";
				}
				if($show_correct=="false"){
					if($order_entry == $giveaway_entries_result &&  $compare_status== true){
						continue;
					}
				}
				
				?>
				<tr>
					<td> <?php echo $counter_n; ?> </td>
					<td><?php echo $order_id; ?></td>
					<td><?php echo $billing_first_name." ".$billing_last_name; ?></td>
					<td><?php echo $billing_email; ?></td>
					<td><?php echo $order_entry; ?></td>
					<td><?php echo $giveaway_entries_result; ?></td>
					<td><?php echo $product_id.' - '.$product_title; ?></td>
					<!-- <td><?php echo $is_a_renew ?></td> -->
					<td><?php echo $product_type; ?></td>
					<td><?php echo $order_date ?></td>
					<td><?php echo $entry_status ?></td>
					<td><span class="process_order_entry" order_id="<?php echo $order_id; ?>" email="<?php echo $billing_email; ?>" giveaway_id="<?php echo $giveaway_id_m; ?>">process</span></td>
				</tr>
				<?php
			}
		}
		$counter_n+=1;
    }
    echo "</table>";

    //pagination

    $order_ids_pagination = get_paginated_orders_by_date_range($start_date, $end_date, $page, $per_page, "pagination");
    if (!empty($order_ids_pagination)) {
		$pagination_count = ceil(count($order_ids_pagination) / $per_page);
		// echo "<br>";
		// echo count($order_ids_pagination);
		// echo "<br>";
		// echo $per_page;
		// echo "<br>";
		// echo $pagination_count;
		echo "<div class='we_pagination'>";
		echo "<ul class='we_pagination_ul'>";
		for ($a=0; $a < $pagination_count; $a++) { 
			$page_no = $a + 1;
			$class_active = "";
			if($page == $page_no){
				$class_active = "active";
			}

			// Get the current URL
		    $current_url = "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		    $parsed_url = parse_url($current_url);
		    parse_str($parsed_url['query'] ?? '', $query_params);
		    $query_params['npage'] = $page_no;
		    $new_query_string = http_build_query($query_params);
		    $new_url = $parsed_url['scheme'] . '://' . $parsed_url['host'] . $parsed_url['path'] . '?' . $new_query_string;

			echo "<li class='".$class_active."'><a href='".$new_url."'>".$page_no."</a></li>";
		}
		echo "</ul>";
		echo "</div>";
    }

    echo "</div>";
	

} else {
    echo 'No orders found in the given date range.';
}
?>
<div class="ws_entries_popup" style="visibility: hidden;">
	<div class="ws_entries_popup_overlay">
	<div class="ws_entries_popup_content">
		<div class="ws_entries_popup_close">x</div>
		<div class="ws_entries_popup_content_in">
			<div class="ws_entries_popup_sec">
				<span class="btn" id="ws_entries_popup_generate_new_entry">Generate Entries in new row</span> 
			</div>
		</div>
	</div>
</div>

