<?php 
global $wpdb, $woocommerce;

$giveaway_n_id = '17791';
$table_name = $wpdb->prefix . "giveaway_entries";
$giveaway_ticket = 0;
$results = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM $table_name WHERE giveaway_id = %d",
        $giveaway_n_id, 
    ),ARRAY_A
);
// $results = false;
$counter = 0;
?>
 <table>
<?php
foreach ($results as $result_n) {

	$order_id = $result_n['order_id'];
	$order = wc_get_order($order_id);
	$renew_subscriptions = "";
	if($order){
		$renew_subscriptions = $order->get_meta( 'subscriptions' );
		
		if(!$renew_subscriptions){
			
			continue;
			// $counter+=1;
			// echo "<tr>";
			// echo "<td>".$counter."</td>";
			// echo "<td>".$order_id."</td>";
			// echo "</tr>";
			
		}
	}
	// continue;

	$counter+=1;

	$renew_subscriptions_str = $renew_subscriptions[0];
	if(is_array($renew_subscriptions)){
		$renew_subscriptions_str = $string = implode(' ', $renew_subscriptions);
	}
	// continue;

	// echo "<pre>";
	// echo $counter."<br>";
	echo "<tr>";
	echo "<td>".$counter."</td>";
	echo "<td>".$order_id."</td>";
	echo "<td>";
	print_r($renew_subscriptions_str);
	echo "</td>";
	echo "<td>";
	echo $result_n['entries'];
	echo "</td>";
	echo "</tr>";
	// print_r($result_n);
	// echo "</pre>";
}
?>

</table>