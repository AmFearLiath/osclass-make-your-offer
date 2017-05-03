<?php
/*
Plugin Name: Make your offer
Plugin URI: http://amfearliath.tk/osclass-make-your-offer
Description: User can make an offer on items 
Version: 1.3.0
Author: Liath
Author URI: http://amfearliath.tk
Short Name: make-your-offer
Plugin update URI: make-your-offer
*/

require_once('classes/myo.class.php');

if (Params::getParam('myo_action')) {
    m_y_o::newInstance()->myo_handle(Params::getParamsAsArray());        
}

function myo_install() {
    m_y_o::newInstance()->myo_install();
}

function myo_uninstall() {
    m_y_o::newInstance()->myo_uninstall();
}

function myo_show($id = false) {
    if (!$id) { $id = osc_item_id(); }
    if (osc_item_price() && osc_item_price() > 0) {
        m_y_o::newInstance()->myo_show_offer($id);
    }
}

function myo_has_price($id) {
    m_y_o::newInstance()->myo_show_price($id);
}

function myo_clear($itemId) {
    m_y_o::newInstance()->myo_clear_offer($itemId);
}

function myo_checkbox($title) {
    if (m_y_o::newInstance()->myo_get('myo_showcheck') != '1') {
        m_y_o::newInstance()->myo_show_checkbox(false, osc_item_id(), $title);
    }
}

function myo_checkbox_edit($cat, $item) {
    m_y_o::newInstance()->myo_show_checkbox($cat, $item);
}

function myo_checkbox_post($item) {
    m_y_o::newInstance()->myo_show_checkbox(false, $item);
}

function myo_activate($item) {
    m_y_o::newInstance()->myo_activate_item($item['pk_i_id']);
}

function myo_style() {
    osc_enqueue_style('myo-styles', osc_plugin_url('make-your-offer/assets/css/myo.css').'myo.css');
    osc_enqueue_style('myo-styles-tipso', osc_plugin_url('make-your-offer/assets/css/tipso.min.css').'tipso.min.css');
}

function myo_script() {
    echo '<script type="text/javascript" src="'.osc_plugin_url('make-your-offer/assets/js/myo.js').'myo.js"></script>';
    echo '<script type="text/javascript" src="'.osc_plugin_url('make-your-offer/assets/js/tipso.min.js').'myo.js"></script>';
}

function myo_configuration() {
    osc_admin_render_plugin(osc_plugin_path(dirname(__FILE__)) . '/admin/config.php');
}

if (osc_version() < 311) {
    osc_add_hook('footer', 'myo_script');
} else {
    osc_register_script('myo-script', osc_plugin_url('make-your-offer/assets/js/myo.js') . 'myo.js', array('jquery'));
    osc_enqueue_script('myo-script');
    osc_register_script('myo-tipso', osc_plugin_url('make-your-offer/assets/js/tipso.min.js') . 'tipso.min.js', array('jquery'));
    osc_enqueue_script('myo-tipso');
}
    
osc_register_plugin(osc_plugin_path(__FILE__), 'myo_install') ;

//Plugin un/installation and configuration
osc_add_hook('header', 'myo_style');
osc_add_hook(osc_plugin_path(__FILE__) . '_configure', 'myo_configuration');
osc_add_hook(osc_plugin_path(__FILE__) . '_uninstall', 'myo_uninstall');

//Clear database if item is deleted
osc_add_hook('delete_item', 'myo_clear');

//Show checkbox on new/edit item page
if (m_y_o::newInstance()->myo_get('myo_showcheck')) {                        
    osc_add_hook('item_form', 'myo_checkbox_post', 0);                        
    osc_add_hook('item_edit', 'myo_checkbox_edit', 0);
}
//Processing if item is posted/edited                        
osc_add_hook('posted_item', 'myo_activate');                        
osc_add_hook('edited_item', 'myo_activate');                        
?>