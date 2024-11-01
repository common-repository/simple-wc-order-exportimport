<?php
/*
 * Plugin Name: Simple WC order Export/Import 
 * Plugin URI: http://webmantechnologies.com/
 * Description: Toolkit for import, export and mapping the woocommerce orders
 * Author: Webman Technologies
 * Text Domain: orem-woo-export-import
 * Version: 1.1
 * Requires at least: 4.4
 * Tested up to: 4.9
 */
 

defined( 'ABSPATH' ) or exit;

//WC check
$active_plugins = get_option( 'active_plugins', array() );
if( !in_array( 'woocommerce/woocommerce.php',$active_plugins ) ){
	require_once( ABSPATH . 'wp-admin/includes/plugin.php' ); 
	deactivate_plugins('simple-wc-order-exportimport/woo_order_imex.php');
	if( isset( $_GET['activate'] ))
      unset( $_GET['activate'] );
}



class SWOEI_wc_export_order {	
  
	protected static $instance;
	protected $adminpage;
	protected $export_page;
	protected $template;
	protected $order_maping_template;
	
	public function __construct() {
		
		
		register_activation_hook(__FILE__, array( $this, 'SWOEI_activation_execution_time') );

		register_deactivation_hook(__FILE__, array( $this, 'SWOEI_deactivation_execution_time') );
		
		//woocommerce compatibility check
		add_action( 'admin_init', array( $this, 'SWOEI_woo_version_check' ) );
		
		//add admin page
		add_action('admin_menu', array($this, 'SWOEI_add_menulink'));
		
		//add script and style
		add_action( 'admin_enqueue_scripts', array( $this, 'SWOEI_WC_export_order_enqueue' ) );

		// Include the main ajax request file.
		include_once dirname( __FILE__ ) . '/inc/ajax_request.php';
		
		
	}
	
	public function SWOEI_woo_version_check() {
			
		global $woocommerce; 
					
		if ( version_compare( $woocommerce->version, '3.0.9', '<=' ) ) {
			add_action( 'admin_notices', array($this,'SWOEI_admin_notice_msg') );
			
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' ); 
			deactivate_plugins( plugin_basename( __FILE__ ) );
			
			if( isset( $_GET['activate'] ))
			unset( $_GET['activate'] );
		
			return false;
		}
	}
	
	public function SWOEI_admin_notice_msg() {			
			global $woocommerce;
			?>
			<div class="notice notice-error is-dismissible">
				<p><?php   _e("<b>Simple WC order Export/Import is inactive</b>. Simple WC order Export/Import requires a minimum of WooCommerce v3.1.0 ","orem-woo-export-import"); ?></p>
			</div>
			<?php
	}
	
    public function SWOEI_add_menulink() {

		 $this->adminpage = add_submenu_page(
					'woocommerce',
					__('Order Export/Import','orem-woo-export-import'),
					__('Order Export/Import','orem-woo-export-import'), 
					'manage_woocommerce',
					'woo_order_export',
					array($this, 'SWOEI_render_submenu_pages' ),
					'dashicons-format-video'
				);
		$this->adminpage = add_submenu_page(
					'woocommerce',
					__('Order Maping','orem-woo-export-import'),
					__('Order Maping','orem-woo-export-import'), 
					'manage_woocommerce',
					'woo_order_mapping',
					array($this, 'SWOEI_render_order_mapping' ),
					'dashicons-format-video'
				);
	}	
	
	public function SWOEI_render_submenu_pages() {		
			
			$user_permission = current_user_can( 'edit_posts' );
			
		if ( $user_permission == true ) {		
			
			$nonce = sanitize_text_field($_POST['exportorder_nonce']);
			$nonce_import = sanitize_text_field($_POST['importorder_nonce']);
			
			$fieldcheck_export = sanitize_text_field($_POST['export']); 
			$fieldcheck_import = sanitize_text_field($_POST['import']);
	
			$data['exportType'] = sanitize_text_field($_POST['option']);		

			if( (wp_verify_nonce( $nonce, 'exportorder_nonce' ) || isset( $nonce ) ) && (isset($fieldcheck_export) && $fieldcheck_export != NULL) && ( isset( $data['exportType'] ) && ($data['exportType'] != '' )) ){
				
				$data['format'] = sanitize_text_field($_POST['format']);			

				if(!empty($_POST['order_status_requested']))
				$data['orders_requested'] = $this->SWOEI_recursive_sanitize_text_field($_POST['order_status_requested']); 
			
				if(!empty($_POST['product_cats_requested']))
				$data['products_requested'] = $this->SWOEI_recursive_sanitize_text_field($_POST['product_cats_requested']); 
				
				// Include the main export file.
				include_once dirname( __FILE__ ) . '/inc/export.php';
						
				SWOEI_Export::SWOEI_simple_exporter($data);	
					
			}elseif( ( wp_verify_nonce( $nonce_import, 'exportorder_nonce' ) || isset( $nonce_import ) ) && (isset($fieldcheck_import) && $fieldcheck_import != NULL) ){ 
				
				$allowed =  array('xls','csv' ,'json','xml');
				$filename = sanitize_file_name($_FILES['woo_import']['name']);
				$format = pathinfo($filename, PATHINFO_EXTENSION);
				if(!in_array($format,$allowed) ) { 
					$notice_msg = '"'.$format .'" File type not allowed!!';
					$active_tab = 'import';
					//return;
				}else{					
					
					
					if ( (isset( $data['exportType'] ) && ( $data['exportType'] != '' )) ){	
							
							// Include the main import file.
							include_once dirname( __FILE__ ) . '/inc/import.php';
							
						    $temp_name = ($_FILES["woo_import"]["tmp_name"]);	
							
							SWOEI_Import::SWOEI_simple_importer($format,$data['exportType'],$temp_name);														
							
					}else{
						
						echo "User do not have permission OR Nonce not matched ! ";
					}					
				}
			}else{
				
			}
		}
		?>
		<?php 
			if(isset($notice_msg) && $notice_msg != '') { $print_msg = $notice_msg; $notice_class=' notice-red'; }else $print_msg = 'NOTE:  Its recommended to import products before importing the orders.';		
		?>
		<div class="SWOEI_notice notice <?php echo sanitize_html_class($notice_class) ?>">
			<center>
				<label>
				 <strong>
					<?php echo esc_html($print_msg); ?>
				 </strong>
				</label>
			</center>
		</div>		
		<div class="SWOEI_export_wrapper" >
		<?php echo $this->SWOEI_get_msgbox();  ?>
			
			<div class="tab">
			  <button class="tablinks <?php echo ( (!isset($active_tab)) || (isset($active_tab) && $active_tab =='export')) ? sanitize_html_class('active') : ''; ?>" onclick="SWOEI_openTab(event, 'export')"><?php echo esc_html("Export") ?></button>
			  <button class="tablinks <?php echo ( (isset($active_tab) && $active_tab =='import')) ? sanitize_html_class('active') : ''; ?>" onclick="SWOEI_openTab(event, 'import')"><?php echo esc_html("Import") ?></button>		  
			  <button class="tablinks <?php echo ( (isset($active_tab) && $active_tab =='product')) ? sanitize_html_class('active') : ''; ?>" onclick="SWOEI_openTab(event, 'product_im_ex')"><?php echo esc_html("Product") ?></button>		  
			</div>
			<div id="export" class="tabcontent " <?php echo ( (!isset($active_tab)) || (isset($active_tab) && $active_tab =='export')) ? ' style="display: block;"' : ''; ?> >
				<?php $this->template = $this->SWOEI_get_template('export'); ?> 		
			</div> 
			<div id="import" class="tabcontent " <?php echo ( (isset($active_tab) && $active_tab =='import')) ? ' style="display: block;"' : ''; ?>>
				<?php $this->template = $this->SWOEI_get_template('import'); ?> 
			</div>
			<div id="product_im_ex" class="tabcontent " <?php echo ( (isset($active_tab) && $active_tab =='product')) ? ' style="display: block;"' : ''; ?>>
				<?php $this->template = $this->SWOEI_get_template('product_im_ex'); ?> 
			</div>
		</div>
		<?php
	}
	
	public function SWOEI_render_order_mapping() { ?>				
		
		<div class= "SWOEI_export_wrapper" >
		<?php echo $this->SWOEI_get_msgbox(); ?>		
		
		<div class="db_backup_wrapper">		
			
			<form method="post" id="dbbackupform" action="" enctype="multipart/form-data">
				<div>
					<label><?php echo esc_html("Before mapping it is recommended to backup your database.") ?></label>
					<input type="submit" class="dbbackup_button button button-primary" name="dbbackup_button" value="Start Backup">
				</div>
				<?php wp_nonce_field( 'dbbackup_nonce', 'dbbackup_nonce' ); ?>	
			</form>
		</div>
		
			<div class="tab">
			  <button class="tablinks active" onclick="SWOEI_openTab(event, 'order_mapping')"><?php echo esc_html("Order Mapping"); ?></button>  	  
			  	  
			</div>
			<form method="post" id="mainform" action="" enctype="multipart/form-data">
			<div id="order_mapping" class="tabcontent order_mapping" cellpadding="5" style="display: block;">
			<?php  echo $this->SWOEI_get_loader(); ?>
				<table>
					<thead>
						<tr>
							<th><?php _e("NO",'orem-woo-export-import') ?> </th>
							<th><?php _e("Order Id",'orem-woo-export-import') ?></th>
							<th><?php _e("Product Name",'orem-woo-export-import') ?></th>
							<th><?php _e("Amount",'orem-woo-export-import') ?></th>
							<th><?php _e("Order Date",'orem-woo-export-import') ?></th>
							<th><?php _e("Email",'orem-woo-export-import') ?></th>
							<th><?php _e("Select Product (*)",'orem-woo-export-import') ?></th>
							<th><?php _e("Select Variation",'orem-woo-export-import') ?></th>
							
						</tr>	
					</thead>
					<tbody>
					<?php  
						//get order without product existance
						$orders = json_decode($this->SWOEI_getOrderWithoutProduct()); 
						
						//get order product variations
						$variations = json_decode($this->SWOEI_getProductVariation());

						//get all product
						$products = json_decode($this->SWOEI_getProducts()); 
						
						//get default date and time format
						$date_format = get_option( 'date_format' ).' '.get_option('time_format');
						
						if(!empty($orders)){
							foreach($orders as $key=>$order){ ?>
							
							<tr>
								<td><?php echo esc_html($key + 1) ?> </td>
								<td><?php echo esc_html($order->id) ?></td>
								<td><?php echo esc_html($order->name) ?></td>
								<td><?php echo esc_html($order->order_total) ?></td>
								<td><?php echo esc_html(date($date_format,strtotime($order->date))) ?></td>
								<td><?php echo esc_html($order->billing_email) ?></td>								
								<td>
									<select name="productassign_<?php echo $key ?>">
										<option value="">Select</option>										
										<?php
											foreach($products as $product):
												echo "<option value='$product->id'>$product->title</option>";
											endforeach;
										?>
									</select>
								</td>
								<td>
									<select name="variantassign_<?php echo $key ?>">
										<option value="">Select</option>
										<?php
											foreach($variations as $variation):
												echo "<option value='$variation->id'>$variation->title</option>";
											endforeach;
										?>
									</select>
								</td>							
								<td hidden>
									<input type="hidden" name="orderid_<?php echo $key ?>" value="<?php echo $order->id ?>" />							
									<input type="hidden" name="orderitemid_<?php echo $key ?>" value="<?php echo $order->order_item_id ?>" />							
								</td>							
															
							</tr>
							
					<?php	}
						}else{	?>
							<tr><td colspan="8" align="center"> <?php _e("No order for mapping!!",'orem-woo-export-import') ?></td></tr>
						
						<?php } ?>	
					</tbody>
				</table>
			</div>   
			<br>
			<div>  
				<input type="submit" id="order_map_submit" name="order_map_submit" class="button button-primary" value="Submit">				
				<button id="refresh_orderlist" class="button button-primary" ><?php _e('Refresh List','orem-woo-export-import')?></button>
			</div>
			<?php wp_nonce_field( 'order_mapping_nonce', 'order_mapping_nonce' ); ?>			
			<br>
			</form> 
			 
		</div>
	<?php
	}
	
	public function SWOEI_WC_export_order_enqueue() {	
	
		wp_enqueue_style('export_order-style', plugins_url('/assets/css/woo_orderexpor.css', __FILE__ ) );			
		wp_enqueue_script('export_order-script',  plugins_url('/assets/js/woo_orderexpor.js', __FILE__ ) , array('jquery'), '', true);
		
		wp_localize_script( 'export_order-script', 'plajax', array(
			'ajax_url' => admin_url( 'admin-ajax.php' )
		));
	}
	
	public function SWOEI_get_plugin_dir(){
		
		 return dirname( __FILE__ );	
		 
	}
	
	public function SWOEI_get_template($template){ 
	
		$template_name = 'template_'.$template.'.php';			
		include  $this->SWOEI_get_plugin_dir().'/template/'.$template_name;
	}
	
	public function SWOEI_getOrderWithoutProduct(){
			
		global $wpdb;		
		$prefix = $wpdb->prefix;		
		$query = "SELECT  $wpdb->posts.ID ,$wpdb->posts.post_date 
				  FROM $wpdb->posts 			
				  WHERE $wpdb->posts.post_type = 'shop_order' ";
		$orders =  $wpdb->get_results( $query , 'OBJECT' );			
		
		//filter order without product(order) existance
		$exclude_product = array();
		foreach($orders as $order){ 
			$query = "SELECT itemmeta.meta_value ,items.order_id,items.order_item_name,items.order_item_id  
				FROM ".$prefix."woocommerce_order_items  as items
				LEFT JOIN ".$prefix."woocommerce_order_itemmeta as itemmeta ON itemmeta.order_item_id = items.order_item_id 
				WHERE items.order_item_type = 'line_item' AND items.order_id = '$order->ID' 
				AND itemmeta.meta_key  = '_product_id' ";
				$order_item_ids =  $wpdb->get_results( $query , 'OBJECT' );
								
				foreach($order_item_ids as $order_item){
				
					//check product existance
					$query1 = "SELECT $wpdb->posts.ID
						  FROM $wpdb->posts 
						  WHERE $wpdb->posts.post_type = 'product' AND  $wpdb->posts.ID = $order_item->meta_value ";
					$has_product =  $wpdb->get_row( $query1 , 'OBJECT' );
					
					//filter order without product(order) existance
					if(!$has_product) { 
					
						$order_total = get_post_meta($order->ID,'_order_total');					 
						$_billing_email = get_post_meta($order->ID,'_billing_email');	
						
						$prod_d = array();
						$prod_d['id'] = $order_item->order_id ;
						$prod_d['name'] = $order_item->order_item_name ;
						$prod_d['order_total'] = $order_total[0] ;
						$prod_d['billing_email'] = $_billing_email[0] ;
						$prod_d['date'] = $order->post_date ;
						$prod_d['order_item_id'] = $order_item->order_item_id ;
						$exclude_product[] = $prod_d ;
					}
					
				}
		}
				
		return json_encode($exclude_product);
		
	}
	
	public function SWOEI_getProducts(){
		global $wpdb;		
		$prefix = $wpdb->prefix;		
		$query = "SELECT  $wpdb->posts.ID ,$wpdb->posts.post_title  
				  FROM $wpdb->posts 			
				  WHERE $wpdb->posts.post_type = 'product' AND $wpdb->posts.post_status ='publish' ";
		$products =  $wpdb->get_results( $query , 'OBJECT' );		
		
		$products_data = array(); 
		if($products): 
			foreach($products as $key=>$product):
				$item_single = array();		
				
				$item_single['id'] = $product->ID;
				$item_single['title'] = $product->post_title;				
				
				$products_data[] = $item_single;
			endforeach;
		endif;
		
		return json_encode($products_data);
		
	}
	
	public function SWOEI_getProductVariation(){
		global $wpdb;		
		$prefix = $wpdb->prefix;		
		$query = "SELECT  $wpdb->posts.ID ,$wpdb->posts.post_title  
				  FROM $wpdb->posts 			
				  WHERE $wpdb->posts.post_type = 'product_variation' ";
		$variations =  $wpdb->get_results( $query , 'OBJECT' );		
		
		$variations_data = array(); 
		if($variations): 
			foreach($variations as $key=>$variation):
				$item_single = array();		
				
				$item_single['id'] = $variation->ID;
				$item_single['title'] = $variation->post_title;				
				
				$variations_data[] = $item_single;
			endforeach;
		endif;
		
		return json_encode($variations_data);
		
	}
	
	public function SWOEI_get_loader() {
		$img = plugin_dir_url( __FILE__ ) .'assets/img/loader.gif';
		$html = "<div class='SWOEI_loader' style='display:none;' ><center><img src=".$img." /><label>Refreshing ...</label></center></div>";
		return $html;
	}
	
	public function SWOEI_get_msgbox() {		
		$html = "<div class='msg_box'></div>";
		return $html;
	}
	
	public function SWOEI_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
	
	public function SWOEI_get_export_options() {

		$options['order_statuses'] = wc_get_order_statuses();
		$product_categories = array();

		foreach ( get_terms( 'product_cat' ) as $term ) {
			$product_categories[ $term->term_id ] = $term->name;
		}
		
		$options['product_categories'] = $product_categories;
		
		return $options ;
	}
	
	public function SWOEI_recursive_sanitize_text_field($array) {
		
		foreach ( $array as $key => &$value ) {
			if ( is_array( $value ) ) {
				$value = SWOEI_recursive_sanitize_text_field($value);
			}
			else {
				$value = sanitize_text_field( $value );
			}
		}

		return $array;
	}
	
	public function SWOEI_activation_execution_time(){

		$SWOEI_code = "
		# WP Maximum Execution Time Exceeded
		<IfModule mod_php5.c>
			php_value max_execution_time 3000
		</IfModule>";

		$htaccess = get_home_path().'.htaccess';
		$contents = @file_get_contents($htaccess);
		if(!strpos($htaccess,$SWOEI_code))
		file_put_contents($htaccess,$contents.$SWOEI_code);
	}

	public function SWOEI_deactivation_execution_time(){

		$SWOEI_code = "
		# WP Maximum Execution Time Exceeded
		<IfModule mod_php5.c>
			php_value max_execution_time 3000
		</IfModule>";

		$htaccess = get_home_path().'.htaccess';
		$contents = @file_get_contents($htaccess);
		file_put_contents($htaccess,str_replace($SWOEI_code,'',$contents));
		
	}
	
}

function SWOEI_wc_export_order() {
	return SWOEI_wc_export_order::SWOEI_instance();
}
SWOEI_wc_export_order();

?>