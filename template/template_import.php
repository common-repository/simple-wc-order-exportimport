<div class="wrap woocommerce">
	<form method="post" id="mainform" action="" enctype="multipart/form-data">	
		<div class="form_fields">
			<label><?php _e("Import Type","orem-woo-export-import");?></label>
			<select name="option" required >
				<option value="order">Order</option>				
			</select>
		</div>
		<div class="form_fields">
			<label><?php _e("Upload file","orem-woo-export-import");?></label>
			<input name="woo_import" type="file" required />
		</div>
		<br>
		<input type="submit" id="submit" name="import" class="button button-primary" value="Import">
		<?php wp_nonce_field( 'importorder_nonce', 'importorder_nonce' ); ?>
	</form>
</div>