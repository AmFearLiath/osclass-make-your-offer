<?php 
if (!defined('OC_ADMIN') || OC_ADMIN!==true) exit('Access is not allowed.');
if (Params::getParam('plugin_action') == 'done') {    
    $pref = m_y_o::newInstance()->myo_sect;
    $opts = array(
        'myo_showcheck'      => array(Params::getParam('myo_showcheck'), $pref, 'BOOLEAN')
    );
    
    if (m_y_o::newInstance()->myo_install($opts)) {        
        if(osc_version() < 300) {            
            echo '<div style="text-align:center; font-size:20px; background-color:#B0EFC0;"><p>'.__('<strong>All Settings saved.</strong> Your plugin ist now configured', 'make-your-offer').'.</p></div>' ;
            osc_reset_preferences();            
        } else {            
            ob_get_clean();
            osc_add_flash_ok_message(__('<strong>All Settings saved.</strong> Your plugin ist now configured', 'make-your-offer'), 'admin');
            osc_admin_render_plugin( osc_plugin_folder(__FILE__) . 'config.php');            
        }        
    } else {        
        if(osc_version() < 300) {            
            echo '<div style="text-align:center; font-size:20px; background-color:#EFB0B0;"><p>'.__('<strong>Error.</strong> Your settings can not be saved, please try again', 'make-your-offer').'.</p></div>' ;
            osc_reset_preferences();            
        } else {            
            ob_get_clean();
            osc_add_flash_error_message(__('<strong>Error.</strong> Your settings can not be saved, please try again', 'make-your-offer'), 'admin');
            osc_admin_render_plugin( osc_plugin_folder(__FILE__) . 'config.php');            
        }        
    }
} 
?>
<div class="myo_help">
    <form action="<?php echo osc_admin_render_plugin_url('make-your-offer/admin/config.php');; ?>" method="POST">
        <input type="hidden" name="page" value="plugins" />
        <input type="hidden" name="action" value="renderplugin" />
        <input type="hidden" name="file" value="<?php echo osc_plugin_folder(__FILE__); ?>config.php" />
        <input type="hidden" name="plugin_action" value="done" />
        
        <div class="myo_header">
            <h1><?php _e('Make your offer', 'make-your-offer'); ?></h1>
            <p><?php _e('This plugin allows the user, to make an offer on ads', 'make-your-offer'); ?></p>
        </div>
        <br /><br />
        <div class="myo_content">
            <h3 class="myo_title"><strong><?php _e('Offer box', 'make-your-offer'); ?></strong></h3>
            <p><?php _e('To display the Offer box, place this code anywhere you want (e.g. in item.php or item-sidebar.php of your theme)', 'make-your-offer'); ?></p>
            <br />
            <code>&lt;?php myo_show(); ?&gt;</code>    
        </div>
        <br /><br />
        <div class="myo_content">
            <h3 class="myo_title"><strong><?php _e('Checkbox on item post/edit', 'make-your-offer'); ?></strong></h3>
            <p><?php _e('To display a checkbox, where seller can activate the offer box, place the code below in item-post.php', 'make-your-offer'); ?></p>            
            <p><?php _e('true: show the title; false: show checkbox without title', 'make-your-offer'); ?></p>
            <code>&lt;?php myo_checkbox(true); ?&gt;</code>    
            <br /><br />
            <p><strong><?php _e('or', 'make-your-offer'); ?></strong></p>
            <br />
            <div class="form-group">
                <input type="checkbox" name="myo_showcheck" id="myo_showcheck" value="1" <?php if (m_y_o::newInstance()->myo_get('myo_showcheck')) { echo 'checked="checked"'; } ?> />
                <label for="myo_showcheck"><?php _e('<strong>Show Checkbox</strong> This option shows an checkbox on the end of the form on item post/edit page', 'make-your-offer'); ?></label>
            </div>
            <br />            
            <div class="form-group">
                <button class="btn btn-submit" type="submit"><?php _e('Save', 'make-your-offer'); ?></button>
            </div>
        </div>            
    </form>
</div>