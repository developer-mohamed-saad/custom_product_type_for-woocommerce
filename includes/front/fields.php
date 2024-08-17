<?php 

global $product;
$current_hook= current_action();

$cwpt_tabs= $this->get_cwpt_settings( 'custom_tab' );
$before_cart = [];
if ( in_array($product->get_type(), $this->get_cwpt_settings('single')   ) ) {    
foreach($cwpt_tabs[$product->get_type()]['fields'] as $field){
$field_label= $field['label'];
$field_key = strtolower(preg_replace('/\s+/', '_', $field_label));
$field_class = strtolower(preg_replace('/\s+/', '-', $field_label));

$field_type= $field['type'];
$in_front= $field['show_front'];
$data = get_post_meta( $product->id , $field_key);
if($in_front == 'Before Add To Cart Button' && !empty($data[0]) && $current_hook == 'woocommerce_before_add_to_cart_button'){
  echo '<label style="clear: both;" class="'.$field_class.'">'.$field_label.': '.'<span style="font-weight: 300;">'.$data[0].'</span>'.'</label>';
}elseif($in_front == 'After Add To Cart Button' && !empty($data[0]) && $current_hook == 'woocommerce_after_add_to_cart_button'){
  echo '<label style="clear: both;" class="'.$field_class.'">'.$field_label.': '.'<span style="font-weight: 300;">'.$data[0].'</span>'.'</label>';
}
}

}
