<?php
function SWOEI_orderMapping(){
	
	$user_permission = current_user_can( 'update_core' );
	if ($user_permission == true && (isset( $_POST['order_mapping_nonce'] ) || wp_verify_nonce( $_POST['order_mapping_nonce'], 'order_mapping_nonce' ))  ){
		
	if ( isset($_POST) && $_POST['data'] !='' ){
		
		foreach($_POST['data'] as $key=>$order_item){
			
			$order_item = explode('&',$order_item);			
			
			$productassign = explode( '=', $order_item[0] );
			$variantassign = explode( '=', $order_item[1] );  
			$order_item_id = explode( '=', $order_item[2] );
			$item_id 	   = explode( '=', $order_item[3] );				
			
			$productassign = (int)$productassign[1];
			$variantassign = (int)$variantassign[1];
			$order_item_id = $order_item_id[1];
			$item_id = $item_id[1];	
				
			if($productassign){
				 
				 $response = wc_update_order_item_meta($item_id, '_product_id', $productassign,'');
				 if($response){
					 $result[] = true;					 
				 }
				 
				if($variantassign){
					 $result[] = wc_update_order_item_meta($item_id, '_variation_id', $variantassign,'');				  				
				}
			}else{
				
				$result[] = false;
			}
			
		}		
		echo json_encode($result);
				
	}
	
	}else{
		$error[] = 'User do not have permission OR Nonce not matched !';
		echo json_encode($error);
	}
	exit;
	
}
add_action('wp_ajax_SWOEI_orderMapping', 'SWOEI_orderMapping');
add_action('wp_ajax_nopriv_SWOEI_orderMapping', 'SWOEI_orderMapping');

function SWOEI_dbBackup(){
	
	parse_str($_POST['data'], $data);
	$nonce = $data['dbbackup_nonce'];
	
	if( wp_verify_nonce( $nonce, 'dbbackup_nonce' ) || isset( $nonce ) ){
		
	global $wpdb;

	// Get a list of the tables
	$tables = $wpdb->get_results('SHOW TABLES');

	$upload_dir = wp_upload_dir();
	$sql_filename = 'database-' . strtotime(date('Y-m-d G:i:s')) . '.sql';
	$file_path = $upload_dir['basedir'] . '/backups/woo_orderexport/'.$sql_filename;
	$dirname = dirname($file_path);
	if (!is_dir($dirname)){
		mkdir($dirname, 0755, true);
	}
	$file = fopen($file_path, 'w');


	foreach ($tables as $table){
		
		$table = (array) $table;		
		$table_name = current($table);
		
		$schema = $wpdb->get_row('SHOW CREATE TABLE ' . $table_name, ARRAY_A);
		fwrite($file, $schema['Create Table'] . ';' . PHP_EOL);

		$rows = $wpdb->get_results('SELECT * FROM ' . $table_name, ARRAY_A);

		if($rows){
			fwrite($file, 'INSERT INTO ' . $table_name . ' VALUES ');

			$total_rows = count($rows);
			$counter = 1;
			foreach ($rows as $row => $fields){
				$line = '';
				foreach ($fields as $key => $value){
					$value = addslashes($value);
					$line .= '"' . $value . '",';
				}

				$line = '(' . rtrim($line, ',') . ')';

				if ($counter != $total_rows){
					$line .= ',' . PHP_EOL;
				}

				fwrite($file, $line);
				$counter++;
			}
			fwrite($file, '; ' . PHP_EOL);
		}
	}

	fclose($file);
	$return['status'] = fclose(___);
	$return['filename'] = $sql_filename;	
  }else{
	$return['status'] = false;	
  }
	echo json_encode($return);
	exit;
}

add_action('wp_ajax_SWOEI_dbBackup', 'SWOEI_dbBackup');
add_action('wp_ajax_nopriv_SWOEI_dbBackup', 'SWOEI_dbBackup');
?>