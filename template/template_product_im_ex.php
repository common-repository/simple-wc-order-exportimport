<div class="wrap woocommerce">	
	<div class="">
			<?php	global $woocommerce; 
				
		if ( !version_compare( $woocommerce->version, '3.0.9', '<=' ) ) { ?>
			
			<button class="button button-primary" onClick="location.href='<?php echo  get_admin_url() ?>edit.php?post_type=product&page=product_importer';"><?php _e("Product Import","orem-woo-export-import") ?></button>
			<button class="button button-primary" onClick="location.href='<?php echo  get_admin_url() ?>edit.php?post_type=product&page=product_exporter';"><?php _e("Product Export","orem-woo-export-import") ?></button>
			
		<?php
		}else{
			_e("<p>Woocommerce v3.1.0 or later version is required for Product Import/Export. </p>","orem-woo-export-import");
		}
		?>
	</div>
	
</div>