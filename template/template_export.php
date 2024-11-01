<div class="wrap woocommerce">
	<form method="post" id="mainform" action="" enctype="multipart/form-data">					
		<div class="form_fields">
			<label><?php _e("Export Type","orem-woo-export-import");?></label>
			<select name="option" required >
				<option value="order">Order</option>				
			</select>
		</div>
		
		<div class="form_fields">
		<label><?php _e("Export Format","orem-woo-export-import");?></label>
			<select name="format" required >
				<option value="xls">Excel</option>
				<option value="csv">Csv</option>
				<option value="json">Json</option>
				<option value="xml">Xml</option>			
			</select>
		</div>
		
		<?php  
			$export_options = $this->SWOEI_get_export_options(); 
			if(!empty($export_options)){ 
			
			foreach($export_options as $key=>$export_option){ 
				
				if($key =='order_statuses'){ ?>
				
					<div class="form_fields">
					<label><?php _e("Order Statuses","orem-woo-export-import");?></label>
					<select name="order_status" id="order_status" >
						<option value=" ">Select</option>
						<?php	
						foreach($export_option as $v=>$name){
							echo "<option value=".$v.">". $name ."</option>";
						}
						?>
					</select>
					<span class="select_multipleselect_main">
						<span class="select_multipleselect_area">
							<ul readonly name="order_status_queue" id="order_status_queue">
																	
							</ul>
						</span>
					</span>
					</div>						
						
			<?php }elseif($key =='product_categories'){ ?>
			
					<div class="form_fields">
					<label><?php _e("Product Categories","orem-woo-export-import");?></label>
					<select name="product_cats" id="product_cats" >
						<option value=" ">Select</option>
						<?php	
						foreach($export_option as $v=>$name){
							echo "<option value=".$v.">". $name ."</option>";
						}
						?>
					</select>
					<span class="select_multipleselect_main">
					<span class="select_multipleselect_area">
						<ul readonly name="product_cats_queue" id="product_cats_queue">						
						</ul>
					</span>
					</span>
					</div>
			<?php }	?>		
		<?php } } ?>
		<br>
		<div class="form_fields">		
			<input type="submit" id="submit" name="export" class="button button-primary" value="Export">
			<?php wp_nonce_field( 'exportorder_nonce', 'exportorder_nonce' ); ?>
		</div>
	</form>
</div>