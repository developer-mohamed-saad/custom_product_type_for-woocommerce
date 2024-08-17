<?php

/**
 * @package cwpt
 * @author Mohamed Saad
 * @link https://wpsaad.com
 * @since 1.0.0
 */
/**
 * Plugin Name: Custom Product Type for WooCommerce
 * plugin URI: https://wpsaad.com/custom-product-type-for-woocommerce/
 * Description: This Plugin Add WooCommerce Custom Product types with awesome features.
 * Version: 1.2.4
 * Author: Mohamed Saad
 * Author URI: https://wpsaad.com
 * License: GPLv2 or later
 * Text Domain: custom-product-type-for-woocommerce
 * Domain Path: /languages
 */
defined( 'ABSPATH' ) or die;

if ( function_exists( 'cwpt_fs' ) ) {
    cwpt_fs()->set_basename( false, __FILE__ );
} else {
    // DO NOT REMOVE THIS IF, IT IS ESSENTIAL FOR THE `function_exists` CALL ABOVE TO PROPERLY WORK.
    
    if ( !function_exists( 'cwpt_fs' ) ) {
        // Create a helper function for easy SDK access.
        function cwpt_fs()
        {
            global  $cwpt_fs ;
            
            if ( !isset( $cwpt_fs ) ) {
                // Include Freemius SDK.
                require_once dirname( __FILE__ ) . '/freemius/start.php';
                $cwpt_fs = fs_dynamic_init( array(
                    'id'             => '7849',
                    'slug'           => 'custom-product-type-for-wooCommerce',
                    'type'           => 'plugin',
                    'navigation'     => 'tabs',
                    'public_key'     => 'pk_ccdd126fcd113a8aa9aca59e4e684',
                    'is_premium'     => false,
                    'premium_suffix' => 'Premium',
                    'has_addons'     => false,
                    'has_paid_plans' => true,
                    'menu'           => array(
                    'slug'   => 'cwpt-settings',
                    'parent' => array(
                    'slug' => 'options-general.php',
                ),
                ),
                    'is_live'        => true,
                ) );
            }
            
            return $cwpt_fs;
        }
        
        // Init Freemius.
        cwpt_fs();
        // Signal that SDK was initiated.
        do_action( 'cwpt_fs_loaded' );
    }
    
    class CWPT_Plugin
    {
        /**
         * Build the instance
         */
        public function __construct()
        {
            register_activation_hook( __FILE__, array( $this, 'install' ) );
            add_action( 'init', [ $this, 'cwpt_functions' ] );
            add_action( 'admin_enqueue_scripts', [ $this, 'cwpt_admin_scripts' ] );
            add_action( 'woocommerce_loaded', array( $this, 'load_plugin' ) );
            add_filter( 'product_type_selector', array( $this, 'cwpt_add_type' ) );
            add_filter(
                'woocommerce_product_class',
                [ $this, 'woocommerce_product_class' ],
                10,
                4
            );
            add_filter( 'body_class', [ $this, 'cwpt_add_wc_class' ] );
            add_shortcode( 'cwpt_products', [ $this, 'cwpt_products_shortcode' ] );
            add_action( 'admin_footer', array( $this, 'enable_js_on_wc_product' ) );
            add_filter( 'rwmb_meta_boxes', [ $this, 'cwpt_post_settings' ] );
            add_action( 'woocommerce_product_query', [ $this, 'cwpt_shop_disabled' ] );
            add_action( 'woocommerce_single_product_summary', [ $this, 'custom_product_add_to_cart' ], 60 );
            add_action( 'woocommerce_before_shop_loop_item', [ $this, 'cwpt_badge' ], 100 );
        }
        
        /* CWPT Settings */
        public function get_cwpt_settings( $settings )
        {
            $product_types_ids = get_posts( array(
                'fields'         => 'ids',
                'posts_per_page' => -1,
                'post_type'      => 'custom-product-type',
            ) );
            
            if ( !empty($product_types_ids) && function_exists( 'rwmb_meta' ) ) {
                foreach ( $product_types_ids as $type_id ) {
                    $product_type_single = sanitize_text_field( rwmb_meta( 'cwpt_product_type_name_singular', [], $type_id ) );
                    $product_type_id = preg_replace( '/\\s+/', '_', trim( preg_replace( '/[^\\da-z ]/i', '', strtolower( $product_type_single ) ) ) );
                    $product_type_class = preg_replace( '/\\s+/', '-', trim( preg_replace( '/[^\\da-z ]/i', '', strtolower( $product_type_single ) ) ) );
                    $product_type_plural = sanitize_text_field( rwmb_meta( 'cwpt_product_type_name_plural', [], $type_id ) );
                    $product_type_tax_status = rwmb_meta( 'cwpt_custom_taxonomy', [], $type_id );
                    $product_type_badge = rwmb_meta( 'cwpt_enable_badge', [], $type_id );
                    $product_type_custom_tab = rwmb_meta( 'cwpt_enable_custom_tab', [], $type_id );
                    $product_type_custom_tab_fields = rwmb_meta( 'cwpt_custom_tab_text_fields', [], $type_id );
                    
                    if ( $product_type_badge == 'Enabled' ) {
                        $product_badge_text = rwmb_meta( 'cwpt_badge_text', [], $type_id );
                        $product_badge_background = rwmb_meta( 'cwpt_badge_background_color', [], $type_id );
                        $product_badge_text_color = rwmb_meta( 'cwpt_badge_text_color', [], $type_id );
                        $cwpt_badges[$product_type_id] = [
                            'text'       => $product_badge_text,
                            'background' => $product_badge_background,
                            'color'      => $product_badge_text_color,
                        ];
                    }
                    
                    
                    if ( $product_type_custom_tab == 'Enabled' ) {
                        $product_tab_label_text = rwmb_meta( 'cwpt_custom_tab_label', [], $type_id );
                        $cwpt_tab[$product_type_id] = [
                            'text'   => $product_tab_label_text,
                            'fields' => $product_type_custom_tab_fields,
                        ];
                    }
                    
                    $types_names_single[] = $product_type_single;
                    $types_names_plural[] = $product_type_plural;
                    $types_tax_status[] = $product_type_tax_status;
                    $types_ids[] = $product_type_id;
                    $types_classes[] = $product_type_class;
                }
                switch ( $settings ) {
                    case 'single':
                        return $types_names_single;
                    case 'ID':
                        return $types_ids;
                    case 'class':
                        return $types_classes;
                    case 'plural':
                        return $types_names_plural;
                    case 'status':
                        return $types_tax_status;
                    case 'badges':
                        return $cwpt_badges;
                    case 'custom_tab':
                        if ( isset( $cwpt_tab ) ) {
                            return $cwpt_tab;
                        }
                        break;
                }
            }
            
            // return $types_names_single;
        }
        
        function cwpt_badge()
        {
            
            if ( $this->get_cwpt_settings( 'badges' ) !== null && !empty($this->get_cwpt_settings( 'badges' )) ) {
                global  $product ;
                $cwpt_badges = $this->get_cwpt_settings( 'badges' );
                
                if ( isset( $cwpt_badges[$product->get_type()] ) ) {
                    $badge_text = $cwpt_badges[$product->get_type()]['text'];
                    $badge_background = $cwpt_badges[$product->get_type()]['background'];
                    $badge_color = $cwpt_badges[$product->get_type()]['color'];
                    ?>
          <?php 
                    echo  '<div class="cwpt_badge" style="position: absolute; top: 5%;  right: 5%; left: auto; z-index: 999; background-color: ' . $badge_background . '; color:' . $badge_color . ';
       padding: 0 5px; border-radius: 5px;">' . $badge_text . '</div>' ;
                }
            
            }
        
        }
        
        function custom_product_add_to_cart()
        {
            global  $product ;
            // Make sure it's our custom product type
            
            if ( in_array( $product->get_type(), $this->get_cwpt_settings( 'ID' ) ) ) {
                // do_action( 'woocommerce_before_add_to_cart_button' );
                wc_get_template( 'single-product/add-to-cart/simple.php' );
                //  do_action( 'woocommerce_after_add_to_cart_button' );
            }
        
        }
        
        function cwpt_front_fields()
        {
            global  $product ;
            $current_hook = current_action();
            
            if ( $this->get_cwpt_settings( 'custom_tab' ) !== null && !empty($this->get_cwpt_settings( 'custom_tab' )) ) {
                $cwpt_tabs = $this->get_cwpt_settings( 'custom_tab' );
                // 				print_r($this->get_cwpt_settings( 'ID' ));
                // 				print_r($product->get_type());
                $before_cart = [];
                if ( in_array( $product->get_type(), $this->get_cwpt_settings( 'ID' ) ) ) {
                    
                    if ( !empty($cwpt_tabs[$product->get_type()]['fields']) ) {
                        echo  '<div>' ;
                        foreach ( $cwpt_tabs[$product->get_type()]['fields'] as $field ) {
                            $field_label = $field['label'];
                            $field_key = preg_replace( '/\\s+/', '_', trim( preg_replace( '/[^\\da-z ]/i', '', strtolower( $field_label ) ) ) );
                            $field_class = $product->get_type() . '-field-' . preg_replace( '/\\s+/', '-', trim( preg_replace( '/[^\\da-z ]/i', '', strtolower( $field_label ) ) ) );
                            $field_global_class = $product->get_type() . '-field';
                            if ( !empty($field['url']) ) {
                                $field_url = $field['url'];
                            }
                            $field_type = $field['type'];
                            $in_front = $field['show_front'];
                            $product_id = $product->get_id();
                            $data = get_post_meta( $product_id, $field_key );
                            $link = get_post_meta( $product_id );
                            // 							print_r($data[0]);
                            echo  '<div class="cwpt-field">' ;
                            
                            if ( $in_front == 'Before Add To Cart Button' && !empty($data[0]) && $current_hook == 'woocommerce_before_add_to_cart_button' ) {
                                
                                if ( $field_url == 'Enabled' ) {
                                    echo  '<a class="' . $field_global_class . ' ' . $field_class . '-url' . '" href="' . $data[0] . '" style=" clear: both; display: block; margin: 0.5rem 0; display: block; ">' . $field_label . '</a>' ;
                                } else {
                                    echo  '<label style="clear: both; display: block; margin: 0.5rem 0;" class="' . $field_global_class . ' ' . $field_class . '-label' . '">' . $field_label . '</label>' . '<span class="' . $field_global_class . ' ' . $field_class . '-value" ' . 'style="font-weight: 300;">' . $data[0] . '</span>' ;
                                }
                            
                            } elseif ( $in_front == 'After Add To Cart Button' && !empty($data[0]) && $current_hook == 'woocommerce_after_add_to_cart_button' ) {
                                
                                if ( $field_url == 'Enabled' ) {
                                    echo  '<a class="' . $field_global_class . ' ' . $field_class . '-url' . '" href="' . $data[0] . '" style=" clear: both; display: block; margin: 0.5rem 0; display: block;">' . $field_label . '</a>' ;
                                } else {
                                    echo  '<label style="clear: both; display: block; margin: 0.5rem 0;" class="' . $field_global_class . ' ' . $field_class . '-label' . '">' . $field_label . '</label>' . '<span class="' . $field_global_class . ' ' . $field_class . '-value" ' . 'style="font-weight: 300;">' . $data[0] . '</span>' ;
                                }
                            
                            } elseif ( $in_front == 'Before Product Meta' && !empty($data[0]) && $current_hook == 'woocommerce_product_meta_end' ) {
                                
                                if ( $field_url == 'Enabled' ) {
                                    echo  '<a class="' . $field_global_class . ' ' . $field_class . '-url' . '" href="' . $data[0] . '" style=" clear: both; display: block; margin: 0.5rem 0; display: block; ">' . $field_label . '</a>' ;
                                } else {
                                    echo  '<label style="clear: both; display: block; margin: 0.5rem 0;" class="' . $field_global_class . ' ' . $field_class . '-label' . '">' . $field_label . '</label>' . '<span class="' . $field_global_class . ' ' . $field_class . '-value" ' . 'style="font-weight: 300;">' . $data[0] . '</span>' ;
                                }
                            
                            } elseif ( $in_front == 'After Product Meta' && !empty($data[0]) && $current_hook == 'woocommerce_product_meta_start' ) {
                                
                                if ( $field_url == 'Enabled' ) {
                                    echo  '<a class="' . $field_global_class . ' ' . $field_class . '-url' . '" href="' . $data[0] . '" style=" clear: both; display: block; margin: 0.5rem 0; display: block;display: block; ">' . $field_label . '</a>' ;
                                } else {
                                    echo  '<label style="clear: both; display: block; margin: 0.5rem 0;" class="' . $field_global_class . ' ' . $field_class . '-label' . '">' . $field_label . '</label>' . '<span class="' . $field_global_class . ' ' . $field_class . '-value" ' . 'style="font-weight: 300;">' . $data[0] . '</span>' ;
                                }
                            
                            } elseif ( $in_front == 'After Product Title' && !empty($data[0]) && $current_hook == 'woocommerce_single_product_summary' ) {
                                
                                if ( $field_url == 'Enabled' ) {
                                    echo  '<a class="' . $field_global_class . ' ' . $field_class . '-url' . '" href="' . $data[0] . '" style=" clear: both; display: block; margin: 0.5rem 0; display: block; ">' . $field_label . '</a>' ;
                                } else {
                                    echo  '<label style="clear: both; display: block; margin: 0.5rem 0;" class="' . $field_global_class . ' ' . $field_class . '-label' . '">' . $field_label . '</label>' . '<span class="' . $field_global_class . ' ' . $field_class . '-value" ' . 'style="font-weight: 300;">' . $data[0] . '</span>' ;
                                }
                            
                            }
                            
                            echo  "</div>" ;
                        }
                        echo  "</div>" ;
                    }
                
                }
            }
        
        }
        
        function cwpt_admin_scripts()
        {
            wp_enqueue_script(
                'cwpt_admin_js',
                plugins_url( '/assets/js/cwpt_admin.js', __FILE__ ),
                [],
                null,
                true
            );
            wp_enqueue_style( 'cwpt_admin_css', plugins_url( '/assets/css/cwpt_admin.css', __FILE__ ) );
        }
        
        function cwpt_shop_disabled( $q )
        {
            $product_types_hidden = get_posts( array(
                'fields'         => 'ids',
                'posts_per_page' => -1,
                'post_type'      => 'custom-product-type',
                'meta_key'       => 'cwpt_show_in_shop',
                'meta_value'     => 'Hide',
            ) );
            $disabled_types = [];
            foreach ( $product_types_hidden as $type_id ) {
                if ( function_exists( 'rwmb_meta' ) ) {
                    $disabled_types[] = preg_replace( '/\\s+/', '_', sanitize_text_field( rwmb_meta( 'cwpt_product_type_name_singular', [], $type_id ) ) );
                }
            }
            $tax_query = $q->get( 'tax_query' );
            $tax_query[] = array(
                'taxonomy' => 'product_type',
                'field'    => 'slug',
                'terms'    => $disabled_types,
                'operator' => 'NOT IN',
            );
            $q->set( 'tax_query', $tax_query );
        }
        
        /**
         * Load WC Dependencies
         *
         * @return void
         */
        public function load_plugin()
        {
            foreach ( glob( plugin_dir_path( __FILE__ ) . "includes/classes/*.php" ) as $filename ) {
                require_once $filename;
            }
            require_once plugin_dir_path( __FILE__ ) . 'includes/cwpt_functions.php';
            // if ( is_admin() ) {
            include 'meta-box/meta-box.php';
            // }
            // var_dump($this->get_cwpt_settings('status'));
            add_action( 'woocommerce_product_options_general_product_data', function () {
                $ids = '';
                foreach ( $this->get_cwpt_settings( 'ID' ) as $ID ) {
                    $ids .= ' show_if_' . $ID;
                }
                echo  '<div class="options_group ' . $ids . ' clear"></div>' ;
            } );
        }
        
        public function cwpt_add_type( $types )
        {
            if ( !empty($this->get_cwpt_settings( 'ID' )) && is_array( $this->get_cwpt_settings( 'ID' ) ) ) {
                foreach ( $this->get_cwpt_settings( 'ID' ) as $key => $name ) {
                    $types[$name] = ucfirst( $this->get_cwpt_settings( 'single' )[$key] ) . ' product';
                }
            }
            return $types;
        }
        
        function cwpt_add_wc_class( $classes )
        {
            
            if ( !is_admin() ) {
                global  $post ;
                
                if ( !empty($post) && is_a( $post, 'WP_Post' ) ) {
                    $current_page = get_post( $post->ID );
                    if ( has_shortcode( $current_page->post_content, 'cwpt_products' ) ) {
                        $classes[] = 'woocommerce';
                    }
                }
                
                return $classes;
            }
        
        }
        
        function cwpt_products_shortcode( $cwpt_type )
        {
            if ( wp_is_json_request() ) {
                return;
            }
            
            if ( !is_admin() ) {
                do_action( 'woocommerce_before_shop_loop' );
                woocommerce_product_loop_start();
                ?>
        <style>
.entry-content ul.products {
    max-width: 100%!important;
    max-width: 1600px;
    padding: 4vw 6vw;
    margin: 0 auto;
}
</style>
          <?php 
                $args = array(
                    'post_type'      => 'product',
                    'posts_per_page' => 12,
                    'tax_query'      => array( array(
                    'taxonomy' => 'product_type',
                    'field'    => 'slug',
                    'terms'    => $cwpt_type['product_type'],
                ) ),
                );
                $loop = new WP_Query( $args );
                
                if ( $loop->have_posts() ) {
                    while ( $loop->have_posts() ) {
                        $loop->the_post();
                        wc_get_template_part( 'content', 'product' );
                    }
                    wp_reset_postdata();
                    woocommerce_product_loop_end();
                    do_action( 'woocommerce_after_shop_loop' );
                } else {
                    do_action( 'woocommerce_no_products_found' );
                }
                
                do_action( 'woocommerce_after_main_content' );
                /**
                 * Hook: woocommerce_sidebar.
                 *
                 * @hooked woocommerce_get_sidebar - 10
                 */
                do_action( 'woocommerce_sidebar' );
                get_footer( 'shop' );
                // wp_reset_postdata();
                ?>
        
      <?php 
            }
        
        }
        
        public function cwpt_functions()
        {
            /* settings generator */
            
            if ( isset( $_POST['CWPT_Generate'] ) ) {
                // $plugin= new CWPT_Plugin();
                $cwpt_types = $this->get_cwpt_settings( 'single' );
                $cwpt_types_ids = $this->get_cwpt_settings( 'ID' );
                $cwpt_types_classes = $this->get_cwpt_settings( 'class' );
                // var_dump($cwpt_types);
                $classes_files_checker = [];
                $current_exists_classes = array_diff( scandir( __DIR__ . '/includes/classes' ), array( '..', '.' ) );
                // if(count($cwpt_types) > count($current_exists_classes) -1 ||  count($cwpt_types) < count($current_exists_classes) -1){
                
                if ( !empty($cwpt_types) && is_array( $cwpt_types ) ) {
                    foreach ( $cwpt_types as $key => $type ) {
                        // var_dump($cwpt_types_classes[$key]);
                        $classes_files_checker[] = 'class-' . $cwpt_types_classes[$key] . '.php';
                        $classes_creator = fopen( plugin_dir_path( __FILE__ ) . '/includes/classes/class-' . $cwpt_types_classes[$key] . '.php', 'w' ) or die( 'Unable to Create cwpt file!' );
                        $code = '<?php

                        defined( \'ABSPATH\' ) || exit;

                        class WC_CWPT_Product_Type_' . $cwpt_types_ids[$key] . ' extends WC_Product_Simple {
                            /**
                             * Return the product type
                             * @return string
                             */
                            public function get_type() {
                                return \'' . $cwpt_types_ids[$key] . '\';
                            }
                        
                             
                        }';
                        fwrite( $classes_creator, $code );
                        fclose( $classes_creator );
                    }
                    // print_r($classes_files_checker);
                    foreach ( $current_exists_classes as $file ) {
                        if ( !in_array( $file, $classes_files_checker ) && $file !== 'cwpt_class_creator.php' ) {
                            unlink( plugin_dir_path( __FILE__ ) . '/includes/classes/' . $file );
                        }
                    }
                    $_POST['CWPT_Generate'] = 'success';
                } elseif ( empty($cwpt_types) ) {
                    $_POST['CWPT_Generate'] = 'empty';
                } else {
                    $_POST['CWPT_Generate'] = 'failed';
                }
                
                // }
            }
            
            // var_dump(get_post_meta( '4821'));
            // delete_post_meta( '4821', 'product-type' );
            //   var_dump(get_terms( 'product_type', array(
            //     'hide_empty' => false,
            // ) ));
            // require_once 'includes/classes/cwpt_class_creator.php';
            // require_once 'includes/classes/cwpt_class_creator.php';
            $current_user = wp_get_current_user();
            if ( user_can( $current_user, 'manage_options' ) ) {
                include_once plugin_dir_path( __FILE__ ) . 'includes/cwpt_admin.php';
            }
            $types_plural = $this->get_cwpt_settings( 'plural' );
            // print_r($types_plural);
            $types_tax_status = $this->get_cwpt_settings( 'status' );
            if ( is_array( $this->get_cwpt_settings( 'single' ) ) ) {
                foreach ( $this->get_cwpt_settings( 'single' ) as $key => $name_single ) {
                    
                    if ( $types_tax_status[$key] == 'Enabled' ) {
                        $args = [
                            'label'                => esc_html__( ucfirst( $name_single ) . ' category', 'cwpt' ),
                            'labels'               => [
                            'menu_name'                  => esc_html__( ucfirst( $types_plural[$key] ) . ' categories', 'cwpt' ),
                            'all_items'                  => esc_html__( 'All ' . ucfirst( $types_plural[$key] ) . ' categories', 'cwpt' ),
                            'edit_item'                  => esc_html__( 'Edit ' . ucfirst( $name_single ) . ' category', 'cwpt' ),
                            'view_item'                  => esc_html__( 'View ' . ucfirst( $name_single ) . ' category', 'cwpt' ),
                            'update_item'                => esc_html__( 'Update ' . ucfirst( $name_single ) . ' category', 'cwpt' ),
                            'add_new_item'               => esc_html__( 'Add new ' . ucfirst( $name_single ) . ' category', 'cwpt' ),
                            'new_item'                   => esc_html__( 'New ' . ucfirst( $name_single ) . ' category', 'cwpt' ),
                            'parent_item'                => esc_html__( 'Parent ' . ucfirst( $name_single ) . ' category', 'cwpt' ),
                            'parent_item_colon'          => esc_html__( 'Parent ' . ucfirst( $name_single ) . ' category', 'cwpt' ),
                            'search_items'               => esc_html__( 'Search ' . ucfirst( $types_plural[$key] ) . ' categories', 'cwpt' ),
                            'popular_items'              => esc_html__( 'Popular ' . ucfirst( $types_plural[$key] ) . ' categories', 'cwpt' ),
                            'separate_items_with_commas' => esc_html__( 'Separate ' . ucfirst( $types_plural[$key] ) . ' categories' . ' with commas', 'cwpt' ),
                            'add_or_remove_items'        => esc_html__( 'Add or remove ' . ucfirst( $types_plural[$key] ) . ' categories', 'cwpt' ),
                            'choose_from_most_used'      => esc_html__( 'Choose most used ' . ucfirst( $types_plural[$key] ) . ' categories', 'cwpt' ),
                            'not_found'                  => esc_html__( 'No ' . ucfirst( $types_plural[$key] ) . ' categories' . ' found', 'cwpt' ),
                            'name'                       => esc_html__( ucfirst( $types_plural[$key] ) . ' categories', 'cwpt' ),
                            'singular_name'              => esc_html__( ucfirst( $name_single ) . ' category', 'cwpt' ),
                        ],
                            'public'               => true,
                            'show_ui'              => true,
                            'show_in_menu'         => true,
                            'show_in_nav_menus'    => true,
                            'show_tagcloud'        => true,
                            'show_in_quick_edit'   => true,
                            'show_admin_column'    => false,
                            'show_in_rest'         => true,
                            'hierarchical'         => true,
                            'query_var'            => true,
                            'sort'                 => true,
                            'rewrite_no_front'     => false,
                            'rewrite_hierarchical' => false,
                            'rewrite'              => true,
                        ];
                        register_taxonomy( ucfirst( $types_plural[$key] ) . ' categories', [ 'product' ], $args );
                    }
                
                }
            }
        }
        
        /**
         * Add Product Class to WooCommerce classes
         */
        function woocommerce_product_class( $classname, $product_type )
        {
            // print_r($this->get_cwpt_settings( 'ID' ));
            
            if ( class_exists( 'WC_CWPT_Product_Type_' . $product_type ) ) {
                // notice the checking here.
                $classname = 'WC_CWPT_Product_Type_' . $product_type;
                // print_r($classname);
            }
            
            // if(!class_exists('WC_CWPT_Product_Type_'.$product_type)){
            // echo '<pre>';
            // print_r('not found'. $classname);
            // echo '</pre>';
            // }else{
            // echo '<pre>';
            // print_r('WC_CWPT_Product_Type_'.$product_type.' loaded'). $classname;
            // echo '</pre>';
            // }
            // $classname = 'WC_CWPT_Product_Type_Hoodie';
            //     if ( class_exists( 'WC_CWPT_Product_Type_Hoodie' ) ){
            //     print_r($this->get_cwpt_settings( 'ID' ));
            //     print_r($product_type);
            // }
            return $classname;
        }
        
        /**
         * Installing on activation
         *
         * @return void
         */
        public function install()
        {
            // if ( ! get_term_by( 'slug', 'book', 'product_type' ) ) {
            //   wp_insert_term( 'book', 'product_type' );
            //       }
            flush_rewrite_rules();
        }
        
        /**
         * Add the pricing
         * @return void
         */
        public function enable_js_on_wc_product()
        {
            global  $post, $product_object ;
            if ( !$post ) {
                return;
            }
            if ( 'product' != $post->post_type ) {
                return;
            }
            $is_cwpt_type = ( $product_object && in_array( $product_object->get_type(), $this->get_cwpt_settings( 'ID' ) ) ? true : false );
            ?>
      <script type='text/javascript'>
        jQuery(document).ready(function() {
          <?php 
            foreach ( $this->get_cwpt_settings( 'ID' ) as $type ) {
                ?>

            jQuery('#general_product_data .pricing').addClass('show_if_<?php 
                echo  $type ;
                ?>');
            jQuery('.product_data_tabs .inventory_options').addClass('show_if_<?php 
                echo  $type ;
                ?>');
																	 
            jQuery('#inventory_product_data .options_group').addClass('show_if_<?php 
                echo  $type ;
                ?>');
            jQuery('#inventory_product_data .options_group .form-field').addClass('show_if_<?php 
                echo  $type ;
                ?>');


            jQuery('#inventory_product_data .form-field._manage_stock_field').addClass('show_if_<?php 
                echo  $type ;
                ?>');

            jQuery('.postbox-header .tips').addClass('show_if_<?php 
                echo  $type ;
                ?>');

<?php 
            }
            ?>
          //for Price tab
          // jQuery('#general_product_data .pricing').addClass('show_if_advanced');

          <?php 
            if ( $is_cwpt_type ) {
                ?>
            jQuery('#general_product_data .pricing').show();
            jQuery('.product_data_tabs .inventory_options').show();
            jQuery('.postbox-header .tips').show();

            jQuery('#inventory_product_data .options_group').show();
            jQuery('#inventory_product_data .options_group .form-field').show();
            jQuery('#inventory_product_data .form-field._manage_stock_field').show();


            

          <?php 
            }
            ?>

        });
      </script>
      <?php 
        }
        
        /**
         * Add Experience Product Tab.
         *
         * @param array $tabs
         *
         * @return mixed
         */
        public function add_product_tab( $tabs )
        {
            // var_dump($tabs);
            $cwpt_tabs = $this->get_cwpt_settings( 'custom_tab' );
            if ( !empty($cwpt_tabs) && is_array( $cwpt_tabs ) ) {
                foreach ( $cwpt_tabs as $tab => $label ) {
                    // var_dump($tab);
                    $tabs[$tab] = array(
                        'label'  => $label['text'],
                        'target' => $tab . '_type_product_options',
                        'class'  => 'show_if_' . $tab,
                    );
                    ?>
            <style>
                #woocommerce-product-data ul.wc-tabs li.<?php 
                    echo  $tab ;
                    ?>_options a::before {
                    font-family: Dashicons;
                    content: '\f513';
                        }
            </style>
            <?php 
                }
            }
            return $tabs;
        }
        
        /**
         * Add Content to Product Tab
         */
        public function add_product_tab_content()
        {
            global  $product_object ;
            // echo '<pre>';
            // print_r($product_object);
            // echo '</pre>';
            $cwpt_tabs = $this->get_cwpt_settings( 'custom_tab' );
            
            if ( !empty($cwpt_tabs) && is_array( $cwpt_tabs ) ) {
                $cwpt_avilable_tabs = array_keys( $this->get_cwpt_settings( 'custom_tab' ) );
                // var_dump($cwpt_tabs);
                // var_dump($cwpt_avilable_tabs);
                foreach ( $cwpt_avilable_tabs as $tab_name ) {
                    ?>
            <div id='<?php 
                    echo  $tab_name ;
                    ?>_type_product_options' class='panel woocommerce_options_panel hidden'>
              <div class='options_group'>
                <?php 
                    // var_dump($cwpt_tabs[$tab_name]['fields']);
                    // echo $tab_name;
                    foreach ( $cwpt_tabs[$tab_name]['fields'] as $field ) {
                        // var_dump($cwpt_tabs[$tab]['fields']);
                        // print_r(count($tab));
                        $field_label = $field['label'];
                        $field_type = $field['type'];
                        if ( !empty($field['url']) ) {
                            $field_url = $field['url'];
                        }
                        // var_dump($field_url);
                        // var_dump($field_url);
                        $field_type_options = explode( '|', $field['options'] );
                        $options = [];
                        foreach ( $field_type_options as $option ) {
                            $options[trim( $option )] = $option;
                        }
                        // var_dump($field_type_options);
                        // var_dump($options);
                        $field_placeholder = $field['description'];
                        $field_id = preg_replace( '/\\s+/', '_', trim( preg_replace( '/[^\\da-z ]/i', '', strtolower( $field_label ) ) ) );
                        $field_url_value = $product_object->get_meta( $field_id . '_url', true );
                        $field_value = $product_object->get_meta( $field_id, true );
                        switch ( $field_type ) {
                            case 'Text':
                                woocommerce_wp_text_input( array(
                                    'id'          => $field_id,
                                    'label'       => $field_label,
                                    'value'       => $field_value,
                                    'default'     => '',
                                    'placeholder' => $field_placeholder,
                                ) );
                                break;
                            case 'Select':
                                woocommerce_wp_select( array(
                                    'id'          => $field_id,
                                    'label'       => $field_label,
                                    'options'     => $options,
                                    'description' => $field_placeholder,
                                ) );
                                break;
                            case 'Radio':
                                woocommerce_wp_radio( array(
                                    'id'          => $field_id,
                                    'label'       => $field_label,
                                    'options'     => $options,
                                    'description' => $field_placeholder,
                                ) );
                                break;
                            case 'Textarea':
                                woocommerce_wp_textarea_input( array(
                                    'id'          => $field_id,
                                    'label'       => $field_label,
                                    'value'       => $field_value,
                                    'default'     => '',
                                    'placeholder' => $field_placeholder,
                                ) );
                                break;
                        }
                        // woocommerce_wp_text_input(
                        //   array(
                        //     'id'          => $field_id,
                        //     'label'       => $field_label,
                        //     'value'       => $field_value,
                        //     'default'     => '',
                        //     'placeholder' => $field_placeholder,
                        //   )
                        // );
                        //                         if ( $field_url == 'Enabled' ) {
                        //                             woocommerce_wp_text_input( array(
                        //                                 'id'          => $field_id . '_url',
                        //                                 'label'       => $field_label . ' url',
                        //                                 'value'       => $field_url_value,
                        //                                 'default'     => '',
                        //                                 'placeholder' => $field_placeholder . ' url',
                        //                             ) );
                        //                         }
                    }
                    // $tabs[$tab] = array(
                    //   'label'    => $label['text'],
                    //   'target' => $tab.'_type_product_options',
                    //   'class'  => 'show_if_'.$tab,
                    // );
                    // woocommerce_wp_text_input(
                    //   array(
                    //     'id'          => '_some_data',
                    //     'label'       => __('Data', 'cwpt'),
                    //     'value'       => $product_object->get_meta('_some_data', true),
                    //     'default'     => '',
                    //     'placeholder' => 'Enter data',
                    //   )
                    // );
                    ?>
              </div>
            </div>
    <?php 
                }
            }
        
        }
        
        /**
         * @param $post_id
         */
        public function save_cwpt_single_product_settings( $post_id )
        {
            $cwpt_tabs = $this->get_cwpt_settings( 'custom_tab' );
            foreach ( $cwpt_tabs as $tab => $fields ) {
                foreach ( $cwpt_tabs[$tab]['fields'] as $key => $data ) {
                    $field_label = $cwpt_tabs[$tab]['fields'][$key]['label'];
                    $field_url = $cwpt_tabs[$tab]['fields'][$key]['url'];
                    $field_id = preg_replace( '/\\s+/', '_', trim( preg_replace( '/[^\\da-z ]/i', '', strtolower( $field_label ) ) ) );
                    if ( isset( $_POST[$field_id] ) ) {
                        update_post_meta( $post_id, $field_id, sanitize_text_field( $_POST[$field_id] ) );
                    }
                    if ( isset( $_POST[$field_id . '_url'] ) && $field_url == 'Enabled' ) {
                        update_post_meta( $post_id, $field_id . '_url', esc_url_raw( $_POST[$field_id . '_url'] ) );
                    }
                }
            }
            // $price = isset($_POST['book_name']) ? sanitize_text_field($_POST['book_name']) : '';
            // $some_data = isset($_POST['_some_data']) ? sanitize_text_field($_POST['_some_data']) : '';
            // update_post_meta($post_id, 'book_name', $price);
            // update_post_meta($post_id, '_some_data', $some_data);
        }
        
        function cwpt_post_settings( $fields )
        {
            $prefix = 'cwpt_';
            $fields[] = [
                'title'      => esc_html__( 'Custom WC Product Type Settings', 'custom-product-type-for-woocommerce' ),
                'id'         => 'cwpt',
                'post_types' => [ 'custom-product-type' ],
                'context'    => 'normal',
                'fields'     => [
                [
                'type'     => 'custom_html',
                'callback' => [ $this, 'cwpt_display_shortcode' ],
            ],
                [
                'type'        => 'text',
                'name'        => esc_html__( 'Singular name', 'custom-product-type-for-woocommerce' ),
                'id'          => $prefix . 'product_type_name_singular',
                'desc'        => esc_html__( 'Product Type Singular Name', 'custom-product-type-for-woocommerce' ),
                'placeholder' => esc_html__( 'EX:(\'Book\',\'Shirt\',\'Demo\',…)', 'custom-product-type-for-woocommerce' ),
            ],
                [
                'type'        => 'text',
                'name'        => esc_html__( 'Plural name', 'custom-product-type-for-woocommerce' ),
                'id'          => $prefix . 'product_type_name_plural',
                'desc'        => esc_html__( 'Product Type Plural Name', 'custom-product-type-for-woocommerce' ),
                'placeholder' => esc_html__( 'EX:(\'Books\',\'Shirts\',\'Demos\',…)', 'custom-product-type-for-woocommerce' ),
            ],
                [
                'type'    => 'radio',
                'name'    => esc_html__( 'Custom Taxonomy', 'custom-product-type-for-woocommerce' ),
                'id'      => $prefix . 'custom_taxonomy',
                'desc'    => esc_html__( '(Enable/Disable) Custom Taxonomy', 'custom-product-type-for-woocommerce' ),
                'options' => [
                'Enabled'  => esc_html__( 'Enabled', 'custom-product-type-for-woocommerce' ),
                'Disabled' => esc_html__( 'Disabled', 'custom-product-type-for-woocommerce' ),
            ],
                'std'     => 'Disabled',
            ],
                [
                'type'    => 'radio',
                'name'    => esc_html__( 'Display In Shop', 'custom-product-type-for-woocommerce' ),
                'id'      => $prefix . 'show_in_shop',
                'desc'    => esc_html__( '(Show/Hide) From Shop Page', 'custom-product-type-for-woocommerce' ),
                'options' => [
                'Show' => esc_html__( 'Show', 'custom-product-type-for-woocommerce' ),
                'Hide' => esc_html__( 'Hide', 'custom-product-type-for-woocommerce' ),
            ],
                'std'     => 'Show',
            ],
                [
                'type'    => 'radio',
                'name'    => esc_html__( 'Enable Badge', 'custom-product-type-for-woocommerce' ),
                'id'      => $prefix . 'enable_badge',
                'desc'    => esc_html__( 'Enable Product Badge', 'custom-product-type-for-woocommerce' ),
                'options' => [
                'Enabled'  => esc_html__( 'Enabled', 'custom-product-type-for-woocommerce' ),
                'Disabled' => esc_html__( 'Disabled', 'custom-product-type-for-woocommerce' ),
            ],
                'class'   => 'enable-badge',
                'std'     => 'Disabled',
            ],
                [
                'type'        => 'text',
                'name'        => esc_html__( 'Badge Text', 'custom-product-type-for-woocommerce' ),
                'id'          => $prefix . 'badge_text',
                'desc'        => esc_html__( 'Badge Text', 'custom-product-type-for-woocommerce' ),
                'placeholder' => esc_html__( 'Badge Text', 'custom-product-type-for-woocommerce' ),
                'class'       => 'badge-text',
            ],
                [
                'type'  => 'color',
                'name'  => esc_html__( 'Badge Background Color', 'custom-product-type-for-woocommerce' ),
                'id'    => $prefix . 'badge_background_color',
                'desc'  => esc_html__( 'Choose Badge Background Color', 'custom-product-type-for-woocommerce' ),
                'std'   => '#0095ebd4',
                'class' => 'badge-background-color',
            ],
                [
                'type'  => 'color',
                'name'  => esc_html__( 'Badge Text Color ', 'custom-product-type-for-woocommerce' ),
                'id'    => $prefix . 'badge_text_color',
                'desc'  => esc_html__( 'Choose Badge Text Color', 'custom-product-type-for-woocommerce' ),
                'std'   => '#ffff',
                'class' => 'badge-color',
            ]
            ],
            ];
            // var_dump($fields);
            return $fields;
        }
        
        function cwpt_display_shortcode()
        {
            global  $post ;
            $cwpt_post_name = sanitize_text_field( rwmb_meta( 'cwpt_product_type_name_singular', [], $post->ID ) );
            $cwpt_post_id = preg_replace( '/\\s+/', '_', trim( preg_replace( '/[^\\da-z ]/i', '', strtolower( $cwpt_post_name ) ) ) );
            echo  '<div class="cwpt-shortcode"><code>[cwpt_products product_type="' . $cwpt_post_id . '"]</code></div>' ;
        }
    
    }
    new CWPT_Plugin();
}
