=== Simple WC order Export/Import ===
Contributors: oremtech 
Tags: Woocommerce,Export,Import,Order,Csv,Xls,Json,Excel
Requires at least: 4.4
Tested up to: 4.9
Requires PHP: 5.2.4
Stable tag: 1.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

== Description ==


Simple WC order Export/Import is a plugin for export and import orders of woocommerce. While importing Products sometime products get new ID's so this plugin will perform order mapping to assign right products to orders. Order mapping only update product and variation not other information of orders.
It is recommended to import products first before importing the orders. Products import/export is done by woocommerce default functionality. 


Steps (Recommended):

= OREM ORDER EXPORT/IMPORT (Woocommerce->Order Export/Import) = 

* Export Products :

	a) please Check "Export custom meta" field.

* Import Products :
	a)   Choose exported 
	b)  Check "Update existing products" if to update the existing product if ID matches.
        c) Change Map to field of ID  to 'Do not import' and click 'Run the importer'.

* Export Orders :
	a) Just select the format and click Export.

* Import Orders : 
	b) Choose exported file and click Import.


= OREM ORDER MAPPING  (Woocommerce->Order Maping) = 
While importing the products and orders in another website if item are already exist with same ID's then importing process assign new ID's. For assigning the correct product/variation this process need to be done. 
While performing this process update the order meta data and cann't be undo so its recommended to backup the database. You just need to click 'Start Backup' button on Mapping page for backing up the database. Backup will stord in wp-content/uploads/backups/woo_orderexport/database-{ unique id }.sql 

= Database Backup = 
NOTE: Cross check and select right product and varient. and variation can only selected while select products.
This plugin is fully comapatible with woocommerce v3.1.0 and later versions.

= Product import/export  = 
Provided link in plugin for product import/export are of woocommerce functionality which require v3.1.0 or later version. So adviced to read about woocommerce import before starting the process.
	

== Installation ==

Upload the plugin, and just Activate it.

