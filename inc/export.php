<?php
defined( 'ABSPATH' ) or exit;
class SWOEI_Export {
	
	public function __construct() {
		
		require_once plugin_dir_path( __DIR__ ) . 'woocommerce/includes/class-wc-data-store.php';
		require_once plugin_dir_path( __DIR__ ) . 'woocommerce/includes/wc-order-item-functions.php';
		require_once plugin_dir_path( __DIR__ ) . 'woocommerce/includes/wc-conditional-functions.php';
		require_once plugin_dir_path( __DIR__ ) . 'woocommerce/includes/abstracts/abstract-wc-product.php';
		
	}
	public static function SWOEI_simple_exporter($expdata){	
		ob_end_clean();
		
		global  $woocommerce, $wp;  global $wpdb;  			
		$format = $expdata['format'];
		$exportType = $expdata['exportType'];
		
		if(!empty($expdata['orders_requested']))
			$orders_requested = $expdata['orders_requested'];
		else
			$orders_requested = 'any';
		
		if(!empty($expdata['products_requested']))
			$products_requested = $expdata['products_requested'];		
		else
			$products_requested = '';
		
		/* set default format to excel */
		($format == '' ) ? $format = 'xls' : '';
		
		$filters = array(
			
			'post_type' => 'shop_order',
			'posts_per_page' => -1,
			'fields' => 'ids',			
			'post_status' => $orders_requested,			
		);		
		
		$loop = get_posts($filters); /*get all orders id*/	
		
		//filter orders for requested categories
		if(is_array($products_requested) && !empty($products_requested)){
					
			$order_id_list    = implode(',', $loop );
			$product_cat_list = implode(',', $products_requested );
			$loop = SWOEI_Export::SWOEI_filter_orderByCategory($order_id_list,$product_cat_list);
			 
		}
				
		$meta = SWOEI_Export::SWOEI_generate_order_meta_keys();	/* get order meta keys */ 
		$meta_v = SWOEI_Export::SWOEI_generate_order_variation_keys();	/* get order meta keys */ 
			
		$sdata = array();
				
		 foreach($loop as $id){
			
			$order = wc_get_order( $id );	
			
			$product_data = $order->get_items();  
			
			//order item meta
			foreach($product_data as $key=>$product_d){	
				
				$meta_v = array();
				
				if ( version_compare( $woocommerce->version, '3.1.0', '<=' ) ) {
					$meta_v['order_id'] = $order->id;		
				}else{				
					$meta_v['order_id'] = $order->get_id();	
				}
				
				$meta_v['status'] = $order->get_status();	
					
				//order product meta 
				foreach($meta as $key){	
					if($key != '_customer_user_agent'){			
						$meta_v[$key]  =  SWOEI_Export::SWOEI_get_meta($id,$key,true);
					}
				}
			
				$meta_v['order_item_id'] = $key;
				$meta_v['product_id'] = $product_d['product_id'];
				$meta_v['variation_id'] = $product_d['variation_id'];
				$meta_v['qty'] = $product_d['quantity'];
				$meta_v['name'] = $product_d['name'];
				$meta_v['subtotal'] = $product_d['subtotal'];
				$meta_v['total'] = $product_d['total'];
				$meta_v['subtotal'] = $product_d['subtotal_tax'];
				$meta_v['total_tax'] = $product_d['total_tax'];	
							
				//get order shipping meta 	
				$shipping_meta = SWOEI_Export::SWOEI_get_shippingData($id); //echo '<pre>'; print_r($shipping_meta); //die;
				$shipping_meta_keys = array('method_id','cost','taxes','order_item_id_shipping',);
				foreach($shipping_meta_keys as $value){
					$meta_v[$value] = '';
					foreach($shipping_meta as $s_val){
						if($s_val->meta_key == $value){
							$meta_v[$value] = $s_val->meta_value; 
						}					
					}
					
				 }	

				$post_data = get_post( $id ); 
				$meta_v['post_date'] = $post_data->post_date;
				
				$sdata[] =  $meta_v;
			}				
		 }
	
		//echo '<pre>'; print_r($sdata); 
		//die();
		
		if($format == 'xls'){	
			
			SWOEI_Export::SWOEI_export_excel($sdata);
			
		}elseif($format == 'csv'){			
			
			SWOEI_Export::SWOEI_export_csv($sdata);
			
		}elseif($format == 'json'){			
			
			SWOEI_Export::SWOEI_export_json($sdata);
			
		}elseif($format == 'xml'){
			
			SWOEI_Export::SWOEI_export_xml($sdata);
		}
		
	}
	 
	public static function SWOEI_generate_order_meta_keys(){
		global $wpdb;
		$post_type = 'shop_order';
		$query = "SELECT DISTINCT( $wpdb->postmeta.meta_key ) 
			FROM {$wpdb->posts} 
			LEFT JOIN {$wpdb->postmeta} 
			ON $wpdb->posts.ID = $wpdb->postmeta.post_id
			WHERE $wpdb->posts.post_type = '%s' 
			AND $wpdb->postmeta.meta_key != '' 
		   
		";
		$meta_keys = $wpdb->get_col($wpdb->prepare($query, $post_type)); 
		
		return $meta_keys;
	}
	public static function SWOEI_generate_order_variation_keys(){
		global $wpdb;
		$post_type = 'product_variation';
		$query = "
			SELECT DISTINCT($wpdb->postmeta.meta_key) 
			FROM {$wpdb->posts} 
			LEFT JOIN {$wpdb->postmeta} 
			ON $wpdb->posts.ID = $wpdb->postmeta.post_id 
			WHERE $wpdb->posts.post_type = '%s' 
			AND $wpdb->postmeta.meta_key != '' 
		   
		";
		$meta_keys = $wpdb->get_col($wpdb->prepare($query, $post_type)); 
		
		return $meta_keys;
	}
	
	public static function SWOEI_get_meta($object_id,$key,$single=true){
		
		return $value = get_post_meta( $object_id, $key, $single );
		
	}
	
	public static function SWOEI_export_excel($sdata){
		$filename = date('dMY_Hi').'-export.xls';
		header("Content-Type: Application/vnd.ms-excel; charset=utf-8");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header('Content-Description: File Transfer');
		header("Content-Disposition: Attachment; Filename=\"$filename\"");
		header("Expires: 0");
		header("Pragma: public");
		
		 $flag = false;
		  foreach($sdata as $row) {
			if(!$flag) {
			  // display field/column names as first row
			  echo implode("\t", array_keys($row)) . "\r\n";
			  $flag = true;
			}
			echo implode("\t", array_values($row)) . "\r\n";
		  }
		exit;
	}
	
	public static function SWOEI_export_csv($sdata){
		ob_clean();		
		$filename = date('dMY_Hi').'-export.csv';
		header("Pragma: public");
		header("Expires: 0");
		header("Content-Type: text/csv;");		
		header("Content-Disposition: Attachment; Filename=\"$filename\"");
		header("Content-Transfer-Encoding: UTF-8");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");	
		header("Content-Transfer-Encoding: binary");		
		 
		$fp = fopen('php://output', 'w');
		fputcsv($fp, array_keys($sdata['0']));
		foreach($sdata AS $values){
			fputcsv($fp, $values);			 
		}
		fclose($fp);
		 
		ob_flush();			
		exit;
		
	}
	public static function SWOEI_export_json($sdata){
		ob_clean();		
		$filename = date('dMY_Hi').'-export.json';
		header("Pragma: public");
		header("Expires: 0");
		header('Content-Type: application/json; charset=utf-8');	
		header("Content-Disposition: Attachment; Filename=\"$filename\"");	
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");	
		header("Content-Transfer-Encoding: binary");		
		 
		$fp = fopen('php://output', 'w');
		fwrite($fp, json_encode($sdata, JSON_PRETTY_PRINT));  
		fclose($fp);
		 
		ob_flush();
		
		exit;
	}
	
	public static function SWOEI_export_xml($sdata){
		
		ob_clean();
			
			$filename = date('dMY_Hi').'-export.xml';
			header("Pragma: public");
			header("Expires: 0");
			header('Content-type: text/xml');
			header("Content-Disposition: Attachment; Filename=\"$filename\"");
			$string  = "<?xml version='1.0' encoding='UTF-8'?>";
			$string  .= "<orders>";
				foreach($sdata as $items){
					$string  .= "<order>";
					
					foreach($items as $key=>$item){
						$string  .= "<$key>".$item."</$key>";						
					}
					
					$string  .= "</order>";
				}
			$string  .= "</orders>";
			
			$xml = new SimpleXMLElement($string);
			
			echo $xml->asXML();
			ob_flush();
			
			exit;		
	}
	
	public function SWOEI_get_shippingData($id){
		
		$s_id = SWOEI_Export::SWOEI_get_shippingMetaID($id);
		
		global $wpdb;	
		$table = $wpdb->prefix.'woocommerce_order_itemmeta';
		$query = "SELECT *  
				FROM {$table} 			
				WHERE order_item_id = {$s_id}	   
				";
		$shipping_meta = $wpdb->get_results(($query)); 
		$shipping_meta[] = (object) array('meta_key'=>'order_item_id_shipping','meta_value'=>$s_id);
		return $shipping_meta;
	}	
	
	public function SWOEI_get_shippingMetaID($id){
		global $wpdb;	
		$table = $wpdb->prefix.'woocommerce_order_items';
		$query = "SELECT order_item_id  
				FROM {$table} 			 
				WHERE order_id = {$id} AND order_item_type = 'shipping'    
				 ";
		$s_id = $wpdb->get_results($query); 
		return $s_id[0]->order_item_id;		
	}
	
	public function SWOEI_filter_orderByCategory($order_id_list,$product_cat_list){
	   global $wpdb;
	   return $wpdb->get_col( "SELECT DISTINCT order_id
				FROM {$wpdb->prefix}woocommerce_order_items items
				LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta im ON items.order_item_id = im.order_item_id
				LEFT JOIN {$wpdb->term_relationships} tr ON im.meta_value = tr.object_id
				LEFT JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
				WHERE items.order_id IN ( {$order_id_list} )
				AND items.order_item_type = 'line_item'
				AND im.meta_key = '_product_id'
				AND tt.taxonomy = 'product_cat'
				AND tt.term_id IN ( {$product_cat_list} )
			" );
	}
	
	
}
 ?>