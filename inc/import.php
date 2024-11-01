<?php
defined( 'ABSPATH' ) or exit;
class SWOEI_Import {		
	
	public function __construct() { 
	 	require_once plugin_dir_path( __DIR__ ) . 'woocommerce/includes/class-wc-data-store.php';
		require_once plugin_dir_path( __DIR__ ) . 'woocommerce/includes/wc-order-item-functions.php';
		require_once plugin_dir_path( __DIR__ ) . 'woocommerce/includes/wc-conditional-functions.php';
		require_once plugin_dir_path( __DIR__ ) . 'woocommerce/includes/abstracts/abstract-wc-product.php';		
	}
	
	public static function SWOEI_simple_importer($format,$exportType,$temp_name){	
		ob_end_clean();
		global  $woocommerce, $wp;   		
		
		$sdata = file_get_contents($temp_name); 		
		
		if($format == 'xls'){	
			
			SWOEI_Import::SWOEI_import_excel($sdata);
			
		}elseif($format == 'csv'){			
			
			SWOEI_Import::SWOEI_import_csv($sdata);
			
		}elseif($format == 'json'){			
			
			SWOEI_Import::SWOEI_import_json($sdata);
			
		}elseif($format == 'xml'){
			
			SWOEI_Import::SWOEI_import_xml($sdata);
		}
		
		
		//redirect to order page
		$u = site_url().'/wp-admin/edit.php?post_type=shop_order';
		wp_redirect($u);
		exit;
		
	}
	 	
	public function SWOEI_import_excel($sdata){
		 
		$lines = array_map("rtrim", explode("\r\n", $sdata));
		$keys = array_map("rtrim", explode("\t", $lines[0]));
		unset($lines[0]); 		
		foreach($lines as $key=>$line){ 
			if($line[0] != ''){	
				$sig = array();			
				$sig[] = array_map("rtrim", explode("\t", $line)); 
				
				$final_values = array();
				foreach($sig as $val){ 	
					$item_array = array();	
					foreach($val as $single_item){						
						$item_array[] = trim($single_item,'"');
					}
					$final_values[] = $item_array;
				}				
				$final=	array_combine($keys, $final_values[0]);  
				$orders[] = $final;
			}	
		}	
	
		unset($keys); 
		
	//echo '<pre>';	print_r($orders);		die;	
		SWOEI_Import::SWOEI_import_orders($orders);
		
				
	}
	 
	public function SWOEI_import_csv($sdata){ 
		
		$lines = preg_split("/\\r\\n|\\r|\\n/", $sdata);
		$keys =  explode(PHP_EOL, $lines[0]);
		unset($lines[0]);		
		
		foreach($lines as $line){
			if($line[0] != ''){
				$line = explode(PHP_EOL, $line);
				$f_keys = explode(',', $keys[0]);	
				$f_line= explode(',', $line[0]);
				
				//remove double quotes from data
				$final_values = array();
				foreach($f_line as $val){
					$final_values[] = trim($val,'"');
				}
				
				$final = array_combine($f_keys,$final_values);
				$orders[] = $final	;		 
			}					
		}
		unset($keys);
	
		SWOEI_Import::SWOEI_import_orders($orders);
		
			
	}
	
	public function SWOEI_import_json($sdata){
		
		$orders = json_decode($sdata, true);		
		SWOEI_Import::SWOEI_import_orders($orders);		

	}
	
	public function SWOEI_import_xml($sdata){				
		$xml = simplexml_load_string($sdata, 'SimpleXMLElement', LIBXML_NOCDATA);
		$lines = SWOEI_Import::SWOEI_xml2array($xml); 
		$lines = $lines['order'];
		$keys = array_keys($lines[0]);
		
		foreach($lines as $line){
			$order = array();
			foreach($line as $k=>$v){
				$order[$k] = $v['@value'];		
			}
			$orders[] = $order;	
		}
		
		SWOEI_Import::SWOEI_import_orders($orders);
		
	}
	
	public function SWOEI_import_orders($data){			
		
	//	echo '<pre>'; print_r($data); exit;
		global $wpdb;
		$order_id_exist = array();
		$new_order_id = array();
		foreach($data as $key=>$order){   
			
			
			$product_id = $wpdb->get_var("SELECT post_id FROM $wpdb->postmeta WHERE post_id=$order[product_id] LIMIT 1");				
			
			if(!$product_id){			
									
			}
			
			$post_title = 'Order &ndash; '.date('F d, Y @ h:i A',strtotime($order['post_date']));
			$post_name = 'order-'.date('M-d-Y-hi-A');	
		 	$post_date = date('Y-m-d h:i:s',strtotime(str_replace('"','',$order['post_date'])));
			
			//for multiple products in order
			if(!in_array($order['order_id'],$order_id_exist)){			
			
			$args = array(
				'post_author' => get_current_user_id(),
				'post_name' => $post_name,
				'post_content_filtered' => '',
				'post_title' => $post_title ,
				'post_date' => $post_date,
				'post_status' => 'wc-'.$order['status'],
				'post_type' => 'shop_order',
				'comment_status' => '',
				'ping_status' => '',
				'post_password' => '',
				'to_ping' =>  '',
				'pinged' => '',
				'post_parent' => 0,
				'menu_order' => 0,
				'guid' => '',
				'import_id' => 0,
				'context' => '',
			);
			
			$p_id = wp_insert_post($args);
			$new_order_id[$order['order_id']] = $p_id;
			}else{
				$p_id = $new_order_id[$order['order_id']];
			}
			
			foreach($order as $key=>$meta_value){ 
			
				if(substr($key,0,1) === '_'){
			
					//insert post meta					
					update_post_meta($p_id, $key, $meta_value, $prev_value = '' );
					
				}				 
			}	
				
			//insert order
			$order_itemshipping_name = explode(':',$order['method_id']);		
							
			$item = array( 'order_item_name' => $order['name'], 'order_item_type' => 'line_item'  );					
			$item_id = wc_add_order_item($p_id,$item);					
		
			//insert order meta	
			wc_add_order_item_meta( $item_id, '_qty', absint( $order['qty'] ) );
			wc_add_order_item_meta( $item_id, '_tax_class', '' );
			wc_add_order_item_meta( $item_id, '_product_id', absint($order['product_id'] ));
			wc_add_order_item_meta( $item_id, '_variation_id', absint($order['variation_id'] ));
			wc_add_order_item_meta( $item_id, '_line_subtotal', wc_format_decimal( $order['subtotal'] ) );
			wc_add_order_item_meta( $item_id, '_line_subtotal_tax', wc_format_decimal( $order['total_tax'] ) );
			wc_add_order_item_meta( $item_id, '_line_total', wc_format_decimal( $order['total'] ) );
			wc_add_order_item_meta( $item_id, '_line_tax', wc_format_decimal( $order['total_tax'] ) );
			
			$item = array( 'order_item_name' => $order_itemshipping_name[0],  'order_item_type'       => 'shipping'  );
			$item_id = wc_add_order_item($p_id,$item);	
			
			//for multiple products in order
			if(!in_array($order['order_id'],$order_id_exist)){
				//insert shipping meta	
				wc_add_order_item_meta( $item_id, 'method_id', absint( $order['method_id'] ) );
				wc_add_order_item_meta( $item_id, 'cost', $order['cost'] );
				wc_add_order_item_meta( $item_id, 'taxes', $order['taxes'] );
				wc_add_order_item_meta( $item_id, 'total_tax', number_format($order['total_tax'],2));
				wc_add_order_item_meta( $item_id, 'Items', trim($order['name'].' &times; '.$order['qty'],'"')  );  
			}
			
			//for multiple products in order
			$order_id_exist[]= $order['order_id'];
			
		}
		
	}
	
	public function SWOEI_xml2array($xmlObject, $out = array()){
		
		foreach($xmlObject->attributes() as $attr => $val)		
			$out['@attributes'][$attr] = (string)$val;
 
		$has_childs = false; 
		foreach($xmlObject as $index => $node)
		{
			$has_childs = true;
			$out[$index][] = SWOEI_Import::SWOEI_xml2array($node);
		}
		if (!$has_childs && $val = (string)$xmlObject)
			$out['@value'] = $val;

		foreach ($out as $key => $vals)
		{
			if (is_array($vals) && count($vals) === 1 && array_key_exists(0, $vals))
				$out[$key] = $vals[0];
		}
		return $out;
	}

	
}
 ?>