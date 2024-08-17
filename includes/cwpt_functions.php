<?php
add_action( 'init', 'cwpt_register_post_type' );
function cwpt_register_post_type() {
	$args = [
		'label'  => esc_html__( 'Product Types', 'custom-product-type-for-woocommerce' ),
		'labels' => [
			'menu_name'          => esc_html__( ' Product Types', 'custom-product-type-for-woocommerce' ),
			'name_admin_bar'     => esc_html__( 'Product Type', 'custom-product-type-for-woocommerce' ),
			'add_new'            => esc_html__( 'Add Product Type', 'custom-product-type-for-woocommerce' ),
			'add_new_item'       => esc_html__( 'Add new Product Type', 'custom-product-type-for-woocommerce' ),
			'new_item'           => esc_html__( 'New Product Type', 'custom-product-type-for-woocommerce' ),
			'edit_item'          => esc_html__( 'Edit Product Type', 'custom-product-type-for-woocommerce' ),
			'view_item'          => esc_html__( 'View Product Type', 'custom-product-type-for-woocommerce' ),
			'update_item'        => esc_html__( 'View Product Type', 'custom-product-type-for-woocommerce' ),
			'all_items'          => esc_html__( 'All Product Types', 'custom-product-type-for-woocommerce' ),
			'search_items'       => esc_html__( 'Search Product Types', 'custom-product-type-for-woocommerce' ),
			'parent_item_colon'  => esc_html__( 'Parent Product Type', 'custom-product-type-for-woocommerce' ),
			'not_found'          => esc_html__( 'No Product Types found', 'custom-product-type-for-woocommerce' ),
			'not_found_in_trash' => esc_html__( 'No Product Types found in Trash', 'custom-product-type-for-woocommerce' ),
			'name'               => esc_html__( 'Product Types', 'custom-product-type-for-woocommerce' ),
			'singular_name'      => esc_html__( 'Product Type', 'custom-product-type-for-woocommerce' ),
		],
        'public'              => false,
		'exclude_from_search' => true,
		'publicly_queryable'  => false,
		'show_ui'             => true,
		'show_in_nav_menus'   => false,
		'show_in_admin_bar'   => false,
		'show_in_rest'        => true,
		'capability_type'     => 'post',
		'hierarchical'        => false,
		'has_archive'         => true,
		'query_var'           => false,
		'can_export'          => true,
		'rewrite_no_front'    => false,
		'show_in_menu'        => true,
		'menu_icon'           => 'dashicons-store',
		'supports' => [
			'title',
		],		
		'rewrite' => true
	];

	register_post_type( 'custom-product-type', $args );
}

