<?php
add_action( 'admin_menu', 'cwpt_setting');
function cwpt_setting(){
    add_options_page( 'Custom WC Product Type Settings', 'Product Types', 'manage_options', 'cwpt-settings', 'cwpt_settings_admin' );
   }
function cwpt_settings_admin(){
    ?>
    <div class="wrap fs-section">
<h2 class="nav-tab-wrapper">
 <a href="#" class="nav-tab fs-tab nav-tab-active home">Settings</a>
</h2>
    <h1 class="cwpt-heading"><span class="dashicons dashicons-store"></span>
    <?php _e('Custom Product Types Settings','custom-product-type-for-woocommerce')?></h1>
    <form method="post" action="options-general.php?page=cwpt-settings">
    <input type="hidden" value="s">
    <?php submit_button('Regenrate Product Types','primary','CWPT_Generate');?>

    </form>
    <?php
if(isset($_POST['CWPT_Generate']) && $_POST['CWPT_Generate'] == 'success'){
    echo '<span style=" background: #9b5c8f91; color: #ffffff; padding: 5px; ">'.__('Custom Product Types Generated Successfully','custom-product-type-for-woocommerce').'</span>';
}elseif(isset($_POST['CWPT_Generate']) && $_POST['CWPT_Generate'] == 'empty'){
    echo '<span style=" background: #9b5c8f91; color: #5a430091; padding: 5px; ">'.__('No Custom Product Types Found','custom-product-type-for-woocommerce').'</span>';
}elseif(isset($_POST['CWPT_Generate']) && $_POST['CWPT_Generate'] == 'failed'){
    echo '<span style=" background: #9b5c8f91; color: #ff00009c; padding: 5px; ">'.__('Custom Product Types Generated Failed','custom-product-type-for-woocommerce').'</span>';
}
?>
    </div>
    <?php

}

