<?php
require __DIR__ . '/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';

// WooCommerce first; its installer creates the product tables and taxonomies.
if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
	$err = activate_plugin( 'woocommerce/woocommerce.php' );
	echo is_wp_error( $err ) ? "woo activate FAILED: " . $err->get_error_message() . "\n" : "woocommerce activated\n";
} else {
	echo "woocommerce already active\n";
}

if ( class_exists( 'WC_Install' ) ) {
	WC_Install::install();
	echo "woocommerce installer run, version " . get_option( 'woocommerce_version' ) . "\n";
}

// Product slug at the root, category with its full path — the live structure.
update_option( 'permalink_structure', '/%postname%/' );
update_option( 'woocommerce_permalinks', [
	'product_base'           => '/product',
	'category_base'          => 'product-category',
	'tag_base'               => 'product-tag',
	'attribute_base'         => '',
	'use_verbose_page_rules' => false,
] );

update_option( 'woocommerce_currency', 'EUR' );
update_option( 'woocommerce_price_thousand_sep', '.' );
update_option( 'woocommerce_price_decimal_sep', ',' );
update_option( 'woocommerce_hide_out_of_stock_items', 'no' );

// Global attributes, so the filter panel has real taxonomies to face onto.
if ( function_exists( 'wc_create_attribute' ) ) {
	foreach ( [ 'Merk' => 'merk', 'Kleur' => 'kleur', 'Gewicht' => 'gewicht' ] as $name => $slug ) {
		$exists = false;

		foreach ( wc_get_attribute_taxonomies() as $tax ) {
			if ( $tax->attribute_name === $slug ) {
				$exists = true;
				break;
			}
		}

		if ( $exists ) {
			echo "attribute {$slug}: already there\n";
			continue;
		}

		$id = wc_create_attribute( [ 'name' => $name, 'slug' => $slug, 'type' => 'select', 'order_by' => 'menu_order' ] );
		echo is_wp_error( $id ) ? "attribute {$slug} FAILED: " . $id->get_error_message() . "\n" : "attribute {$slug} created (#{$id})\n";
	}
}

if ( ! is_plugin_active( 'pfh-bricks-widgets/pfh-bricks-widgets.php' ) ) {
	$err = activate_plugin( 'pfh-bricks-widgets/pfh-bricks-widgets.php' );
	echo is_wp_error( $err ) ? "pfh activate FAILED: " . $err->get_error_message() . "\n" : "pfh-bricks-widgets activated\n";
}

flush_rewrite_rules( false );
echo "done\n";
