<?php 
class m_y_o extends DAO {
    
    private static $instance;
    
    public static function newInstance() {
        if (!self::$instance instanceof self) {
            self::$instance = new self ;
        }
        return self::$instance ;
    }
    
    function __construct() {    
        $this->myo_sect = 'plugin_make_your_offer';          
        $this->myo_table = '`'.DB_TABLE_PREFIX.'t_item_offers`';          
        parent::__construct();
    }
    
    function myo_install($opts = false) {
        if ($opts) {         
            foreach ($opts AS $k => $v) {
                if (!osc_set_preference($k, $v[0], $v[1], $v[2])) {
                    return false;    
                }
            }        
            return true;
        } else {             
            $opts = $this->myo_opt();
            $file = osc_plugin_resource('make-your-offer/assets/create_table.sql');
            $sql = file_get_contents($file);

            if (!$this->dao->importSQL($sql)) {
                throw new Exception( "Error importSQL::m_y_o<br>".$file ) ;
            }
        }                    
    }
    
    function myo_uninstall() {
        $pref = $this->myo_sect;        
        Preference::newInstance()->delete(array("s_section" => $pref));            
        $this->dao->query(sprintf('DROP TABLE %s', $this->myo_table));    
    }
    
    function myo_opt() {        
        $pref = $this->myo_sect;        
        $opts = array(
            'myo_showcheck' => array('0', $pref, 'BOOLEAN')
        );        
        return $opts;
    }

    function myo_get($opt) {        
        $pref = $this->myo_sect;
        return osc_get_preference($opt, $pref);
    }
    
    function myo_check($id) {
        $this->dao->select('*');
        $this->dao->from($this->myo_table);
        $this->dao->where('i_item_id', $id);

        $result = $this->dao->get();
        if ($result->numRows() == 0) { return false; }
        
        return $result->row();
    }
    
    function myo_handle($params) {
        
        $action = $params['myo_action'];
        $check = $this->myo_check($params['id']);
        
        switch($action) {
            case "save_offer":                
                $offer = ($params['myo_offer']*1000000);
                $this->myo_update(array('i_user_id' => osc_logged_user_id(), 's_offer_value' => $offer), array('i_item_id' => $params['id']));            
                if ($check['i_notifications_status'] == '1') {
                    $this->myo_notify($params['id']);
                }                
                break;
            case "open_offer":
                if (empty($check['i_item_id'])) {
                    $this->myo_insert(array('i_item_id' => $params['id'], 'i_offer_status' => '1'));
                }  else {
                    $this->myo_update(array('i_offer_status' => '1'), array('i_item_id' => $params['id']));    
                }
                break;
            case "close_offer":
                if (empty($check['i_item_id'])) {
                    $this->myo_insert(array('i_item_id' => $params['id'], 'i_offer_status' => '0'));
                }  else {
                    $this->myo_update(array('i_offer_status' => '0'), array('i_item_id' => $params['id']));    
                }
                break;
            case "open_notifications":
                if (empty($check['i_item_id'])) {
                    $this->myo_insert(array('i_item_id' => $params['id'], 'i_notifications_status' => '1'));
                }  else {
                    $this->myo_update(array('i_notifications_status' => '1'), array('i_item_id' => $params['id']));    
                }
                break;
            case "close_notifications":
                if (empty($check['i_item_id'])) {
                    $this->myo_insert(array('i_item_id' => $params['id'], 'i_notifications_status' => '0'));
                }  else {
                    $this->myo_update(array('i_notifications_status' => '0'), array('i_item_id' => $params['id']));    
                }
                break;
            case "myo_minimum":
                $minimum = ($params['myo_minimum']*1000000);
                $this->myo_update(array('s_offer_minimum' => $minimum), array('i_item_id' => $params['id']));    
                break;                
            default:
                break;    
        }        
    }
    
    function myo_clear_offer($itemId) {            
        $this->dao->query(sprintf('DELETE FROM %s WHERE i_item_id = %d', $this->myo_table, $itemId));    
    }
    
    function myo_activate_item($itemId) {
        if (Params::getParam('myo_activate') == '1') {
            $this->myo_insert(array('i_item_id' => $itemId, 'i_offer_status' => '1'));        
        }            
    }
    
    function myo_show_checkbox($cat = false, $item = false, $title = false) {
        $check = $this->myo_check($item); 
        
        $checked = '';
                
        if (isset($check['i_offer_status']) && $check['i_offer_status'] == '1') {
            $checked = ' checked="checked"';
        }
        
        echo ($title == true ? '<h2>'.__('Make your offer', 'make-your-offer').'</h2>' : '').'
            <label>
                <input type="checkbox" name="myo_activate" id="myo_activate"'.$checked.' value="1" />
                <small>'.__('User can make an alternate offer', 'make-your-offer').'</small>
            </label>
            <i class="fa fa-info tooltip tipso_style"
               data-tipso-title="'.__("Activate offering system", 'make-your-offer').'" 
               data-tipso="'.__("This option enables other user to make an offer on your ad. The offering system only appears, if price is set.", 'make-your-offer').'"></i>
            <script>
            $(document).ready(function(){
                $(".tipso_bubble").remove();
                var tooltip = $(".tooltip").tipso({
                    width: 300,
                    maxWidth: 400,
                    background: "#eeeeee",
                    color: "#000000",
                    titleBackground: "#3498db",
                    titleColor: "#ffffff",
                    showArrow: true,
                    position: "top-left",                            
                    animationIn: "bounceIn",
                    animationOut: "bounceOut"
                });
            });
            </script>';
    }
    
    function myo_show_price($id) {
        $offers = $this->myo_check($id);
        $user = User::newInstance()->findByPrimaryKey($offers['i_user_id']);        
        
        if (isset($offers['s_offer_value']) && $offers['s_offer_value'] != '0') {
            echo '
                <div class="btn btn-common" style="padding: 5px; margin-top: 10px;">
                    <a href="'.osc_user_public_profile_url($offers['i_user_id']).'" style="color: #fff;" title="'.osc_esc_html(sprintf(__("Offer by %s", 'make-your-offer')), $user['s_name']).'">
                        <small>
                            '.__("Last offer", 'make-your-offer').'                    
                            <strong>'.number_format(($offers['s_offer_value']/1000000),2).' '.osc_item_currency_symbol().'</strong>
                        </small>
                    </a>
                </div>
            ';
        } else {
            echo false;  
        }
    }
    
    function myo_show_offer($id = false) {
        
        $output = '<h5>'.__('Current offer', 'make-your-offer').':</h5>';
        $oprice = '';
        
        if ($id) {
            $offers = $this->myo_check($id);
            $user = User::newInstance()->findByPrimaryKey($offers['i_user_id']);
            $price   = number_format((osc_item_price()/1000000), 2, '.', '');
            $oprice  = number_format(($offers['s_offer_value']/1000000), 2, '.', '');
            $minimum = ($offers['s_offer_minimum'] ? number_format(($offers['s_offer_minimum']/1000000), 2, '.', '') : '0.1');    
        }        
        
        if (isset($offers['s_offer_value']) && $offers['s_offer_value'] != '0') {       
            $output .= '<div class="best_offer">'.osc_esc_html($oprice).' '.osc_item_currency_symbol().'</div><div style="clear: both;"></div>';
            $output .= $this->myo_offerFrom($offers, $user['s_name']);
            
        } else {             
            $output .= $this->myo_noOffer($offers);            
        }
        
        if (osc_is_web_user_logged_in() && osc_item_user_id() == osc_logged_user_id()) {
            $this->myo_buttons($offers);
            $output .= '
            <div class="control_offer">
                <form id="myo_submit" class="myoForm" method="post" action="'.osc_item_url().'">
                    <input type="hidden" name="page" value="item" />
                    <input type="hidden" name="id" value="'.osc_item_id().'" />
                    <input type="hidden" name="myo_action" value="'.$this->statusA.'" />
                    <input type="hidden" name="myo_status" value="'.$offers['i_offer_status'].'" />
                    <button class="myo_activate">'.$this->buttonA.'</button>
                </form>
                '.(isset($offers['i_offer_status']) && $offers['i_offer_status'] == '1' ? '
                <form id="myo_notifications" class="myoForm" method="post" action="'.osc_item_url().'">
                    <input type="hidden" name="page" value="item" />
                    <input type="hidden" name="id" value="'.osc_item_id().'" />
                    <input type="hidden" name="myo_action" value="'.$this->statusN.'" />
                    <input type="hidden" name="myo_status" value="'.$offers['i_notifications_status'].'" />
                    <button class="myo_notify">'.$this->buttonN.'</button>
                </form>
                <form id="myo_minimum" class="myoForm" method="post" action="'.osc_item_url().'">
                    <input type="hidden" name="page" value="item" />
                    <input type="hidden" name="id" value="'.osc_item_id().'" />
                    <input type="hidden" name="myo_action" value="myo_minimum" />
                    <div style="width: 100%; text-align: center; margin-top: 15px;">'.__('Set minimum offer value', 'make-your-offer').'</div>
                    <input type="number" step="0.10" max="'.$price.'" name="myo_minimum" value="'.$minimum.'" />
                    <button class="myo_save btn btn-info">'.__("Save", 'make-your-offer').'</button>
                </form>' : '').'
                <script>
                $(document).ready(function(){
                    $("input[name=myo_minimum]").attr("placeholder", "'.__('Set the minimum price...', 'make-your-offer').'");
                    $(".tipso_bubble").remove();    
                    $(".tooltip").tipso({
                        width: 300,
                        maxWidth: 400,
                        background: "#eeeeee",
                        color: "#000000",
                        titleBackground: "#3498db",
                        titleColor: "#ffffff",
                        showArrow: true,
                        position: "top-left",                            
                        animationIn: "bounceIn",
                        animationOut: "bounceOut"
                    });
                });
                </script>
            </div>';
        }
        
        $return = '
                <div class="inner-box" style="padding: 15px;">
                    <div class="widget-title">
                        <h4>'.__("Make your offer", 'make-your-offer').'</h4>
                        '.(osc_is_web_user_logged_in() != osc_item_user_id() ? '
                        <i class="fa fa-info tooltip tipso_style" 
                           data-tipso-title="'.__("Make your offer", 'make-your-offer').'" 
                           data-tipso="'.__("The creator of this ad has activated the offering system. You can submit an offer under the following conditions:<br /><ol><li>Your offer must be larger than the minimum bid</li><li>Your offer can not be larger than the normal price</li></ol>", 'make-your-offer').'" 
                           style="color: #3498db;"></i>
                        ' : '').'
                    </div>
                    <div id="make_your_offer" class="sidebar-box widget-box form-container form-vertical" style="position: relative;">
                    
                    '.$output;
                    
        if (osc_is_web_user_logged_in()) {
            if (osc_item_user_id() != osc_logged_user_id() && ($offers['i_user_id'] != osc_logged_user_id() || $oprice < $minimum) && $offers['i_offer_status'] == '1') {           
                
                $min = ($oprice ? ($oprice < $minimum ? $minimum : $oprice+0.1) : ($minimum ? $minimum+0.1 : '0.1'));
                $return .= '
                        <div class="control_offer">
                            <form id="myo_submit" class="myoForm" method="post" action="'.osc_item_url().'">
                                <input type="hidden" name="page" value="item" />
                                <input type="hidden" name="id" value="'.osc_item_id().'" />
                                <input type="hidden" name="myo_action" value="save_offer" />
                                <input type="hidden" name="myo_user" value="'.osc_logged_user_id().'" />
                                <input type="hidden" name="myo_price" value="'.$price.'" />
                                <input type="hidden" name="myo_prev_offer" value="'.$oprice.'" />
                                <input type="number" name="myo_offer" step="0.10" min="'.$min.'"'.($price > 0 ? ' max="'.$price.'"' : '').' />
                                <button class="myo_your_offer btn btn-info">'.__('Your offer', 'make-your-offer').'</button>
                            </form>
                        </div>';
            }
        }  else {
            $return .= '<div class="no_offer"><a href="'.osc_user_login_url().'"><button class="btn btn-info" style="padding: 5px;">'.__('Login to make an offer.', 'make-your-offer').'</button></a></div>';    
        }
        $return .= '
                    <div style="clear: both;"></div>
                    </div>
                </div>';
        
        if (isset($offers['i_offer_status']) && $offers['i_offer_status'] == '1' || osc_item_user_id() == osc_logged_user_id()) {
            echo $return;     
        } else {
            return;
        }        
    }
    
    function myo_buttons($offer) {
        
        if (isset($offer['i_offer_status']) && $offer['i_offer_status'] == '1') {
            $this->statusA  = 'close_offer';
            $this->buttonA  = '<i class="fa fa-circle-o-notch tooltip tipso_style" 
                                  data-tipso-title="'.__("Deactivate offers", 'make-your-offer').'" 
                                  data-tipso="'.__("Here you can deactivate the offers. No one can give an offer anymore.", 'make-your-offer').'" 
                                  style="color: green; font-size: 18px;"></i>';    
        } else {
            $this->statusA  = 'open_offer';
            $this->buttonA  = '<i class="fa fa-circle-o-notch tooltip tipso_style" 
                                  data-tipso-title="'.__("Activate offers", 'make-your-offer').'" 
                                  data-tipso="'.__("Here you can activate the offers. Now all can make an offer on this ad.", 'make-your-offer').'" 
                                  style="color: red; font-size: 18px;"></i>';    
        }
        
        if (isset($offer['i_notifications_status']) && $offer['i_notifications_status'] == '1') {
            $this->statusN  = 'close_notifications';
            $this->buttonN  = '<i class="fa fa-envelope tooltip tipso_style" 
                                  data-tipso-title="'.__("No notify", 'make-your-offer').'" 
                                  data-tipso="'.__("Here you can deactivate the notifications. If someone place an offer, you wouldn't be informed.", 'make-your-offer').'" 
                                  style="color: green; font-size: 18px;"></i>';    
        } else {
            $this->statusN  = 'open_notifications';
            $this->textN    = __("Notify me", 'make-your-offer');
            $this->textN    = __("Notify me", 'make-your-offer');
            $this->buttonN  = '<i class="fa fa-envelope tooltip tipso_style" 
                                  data-tipso-title="'.__("Notify me", 'make-your-offer').'" 
                                  data-tipso="'.__("Here you can activate the notifications. You will be informed about new offers on this ad.", 'make-your-offer').'" 
                                  style="color: red; font-size: 18px;"></i>';    
        }
    }
    
    function myo_offerFrom($offer, $user, $output = '') {
        
        $oprice  = number_format(($offer['s_offer_value']/1000000), 2, '.', '');
        $minimum = ($offer['s_offer_minimum'] ? number_format(($offer['s_offer_minimum']/1000000), 2, '.', '') : '0.1');
        
        if (osc_logged_user_id() == $offer['i_user_id']) {
            $output .=  '
            <div class="user_offer">
                '.__('Your offer', 'make-your-offer').'
                '.($oprice < $minimum ? '<br />'.__('Minimum price has changed, please check your offer.', 'make-your-offer') : '').'
            </div>
            <div style="clear: both;"></div>';
        } elseif (osc_logged_user_id() == osc_item_user_id()) {
            $output .= '<div class="user_offer">'.__('From', 'make-your-offer').': <a href="'.osc_user_public_profile_url($offer['i_user_id']).'">'.osc_esc_html($user).'</a></div><div style="clear: both;"></div>';
        } 
        
        if (osc_logged_user_id() != osc_item_user_id() && $offer['i_offer_status'] == '0') {
            $output .= '<div class="no_offer">'.__('You can\'t make an offer on this ad anymore.', 'make-your-offer').'</div><div style="clear: both;"></div>';    
        }
        
        return $output;
    }
    
    function myo_noOffer($offer) {
        if (osc_item_user_id() == osc_logged_user_id()) {            
            return '<div class="no_offer">'.__('No offers yet.', 'make-your-offer').'</div><div style="clear: both;"></div>';
        } else {
            if (osc_is_web_user_logged_in()) {
                if (isset($offer['i_offer_status']) && $offer['i_offer_status'] == '1') {
                    return '<div class="no_offer">'.__('No offers yet.', 'make-your-offer').'</div><div style="clear: both;"></div>';    
                }    
            } else {
                return '<div class="no_offer"><a href="'.osc_user_login_url().'"><button class="btn btn-info" style="padding: 5px;">'.__('Login to make an offer.', 'make-your-offer').'</button></a></div><div style="clear: both;"></div>';    
            }
        }
    }
    
    /* Mail Handling */
    function myo_notify($itemID) {
        $offer = $this->myo_check($itemID);
        
        $from  = User::newInstance()->findByPrimaryKey($offer['i_user_id']);        
        $item  = Item::newInstance()->findByPrimaryKey($itemID);
        View::newInstance()->_exportVariableToView('item', $item);
        
        $value = ($offer['s_offer_value']/1000000).' '.osc_item_currency();
        $link  = '<a href="'.osc_item_url().'" >'.osc_item_url().'</a>';
        
        $content = array();
        $content[] = array('{TO_NAME}', '{TO_EMAIL}', '{ITEM_TITLE}', '{ITEM_URL}', '{FROM_NAME}', '{OFFER_VALUE}', '{PAGE_TITLE}');
        
        $content[] = array(osc_esc_html(osc_item_contact_name()), osc_esc_html(osc_item_contact_email()), osc_item_title(), $link, osc_esc_html($from['s_name']), $value, osc_page_title());
        
        $title = __('{PAGE_TITLE} - New offer', 'make-your-offer');    
        $title = osc_mailBeauty($title, $content);
        $body = __('Hello {TO_NAME},','make-your-offer').'<br /><br />
'.__('There is a new offer for you','make-your-offer').'<br /><br />
{ITEM_URL}<br /><br />
'.__('From','make-your-offer').': {FROM_NAME}<br />
'.__('Value','make-your-offer').': {OFFER_VALUE}<br /><br />
'.__('Best regards','make-your-offer').'<br />
{PAGE_TITLE}'; 
        $body = osc_mailBeauty($body, $content);

        $emailParams = array(
            'subject' => $title
            , 'to' => osc_esc_html(osc_item_contact_email())
            , 'to_name' => osc_esc_html(osc_item_contact_name())
            , 'body' => $body
            , 'alt_body' => $body
                );
                
        require_once osc_lib_path() . 'phpmailer/class.phpmailer.php';
        osc_sendMail($emailParams);
    }
    
    
    /* Database Handling */
    function myo_insert($data) {        
        $this->dao->insert($this->myo_table, $data);
    }
    
    function myo_update($values, $where) {
        $this->dao->from($this->myo_table);
        $this->dao->set($values);
        $this->dao->where($where);
        
        if (!$this->dao->update()) {
            return false;
        }        
        return true;
    }    
}
?>