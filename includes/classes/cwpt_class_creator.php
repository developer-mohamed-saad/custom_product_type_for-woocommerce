<?php

defined('ABSPATH') or die;

add_action( 'save_post', 'cwpt_check_new_types', 10, 3 );
function cwpt_check_new_types($ID, $post, $update){
    // var_dump($post);
    if($post->post_type == 'custom-product-type'){
        $plugin= new CWPT_Plugin();
        $cwpt_types = $plugin->get_cwpt_settings('single');
        $cwpt_types_ids = $plugin->get_cwpt_settings('ID');
        $cwpt_types_classes = $plugin->get_cwpt_settings('class');

        $classes_files_checker = [];
        $current_exists_classes = array_diff(scandir(__DIR__), array('..', '.'));
    
        // if(count($cwpt_types) > count($current_exists_classes) -1 ||  count($cwpt_types) < count($current_exists_classes) -1){
            if(!empty($cwpt_types)&& is_array($cwpt_types)){
                foreach ($cwpt_types as $key => $type) {
                
                    // var_dump($cwpt_types_classes[$key]);
                    $classes_files_checker[] =  'class-' . $cwpt_types_classes[$key]. '.php';
                    if (!file_exists(plugin_dir_path(__FILE__) . '/class-' . $cwpt_types_classes[$key]. '.php')) {
        
                        $classes_creator = fopen(plugin_dir_path(__FILE__) . '/class-' . $cwpt_types_classes[$key] . '.php', 'w') or die('Unable to Create cwpt file!');
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
                            
                                 
                            }
                            
                            
                            ';
                        fwrite($classes_creator, $code);
                        fclose($classes_creator);
                    }
                }
                // print_r($classes_files_checker);
                foreach ($current_exists_classes as $file) {
                    if (!in_array($file, $classes_files_checker) && $file !== 'cwpt_class_creator.php') {
                        unlink(plugin_dir_path(__FILE__) . '/' . $file);
                    }
                }
                }
            // }
        }

}

