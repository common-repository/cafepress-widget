<?php
/*
Plugin Name: Cafepress Widget
Plugin URI: 
Description: Display a list of cafepress products in a sidebar widget.
Version: 1.0.5
Author: widget
Author URI: 

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St - 5th Floor, Boston, MA  02110-1301, USA.

*/

define('CAFEPRESS_PLUGIN_DIR',      plugin_basename(dirname(__FILE__)));
define('CAFEPRESS_PLUGIN_URLPATH',  plugins_url() . '/' . CAFEPRESS_PLUGIN_DIR);
define('CAFEPRESS_API_URLPATH',     'http://widgets.cafepress.com/api/wordpress');

// Class CafePress_Widget is extending WP_Widget class
class CafePress_Widget extends WP_Widget {

    public static $Paths = array();

    function CafePress_Widget() {
        $widgetSettings     = array (
                                    'classname'     => 'CafePress_Widget',
                                    'description'   => 'Display a list of cafepress products in a sidebar widget.'
                                    );

        $controlSettings    = array (
                                    'width'         => 400,
                                    'height'        => 400,
                                    'id_base'       => 'cafepress_widget'
                                    );

        $this->WP_Widget('cafepress_widget', 'CafePress Widget', $widgetSettings, $controlSettings);

        
        self::$Paths['PLUGIN_DIR']      = CAFEPRESS_PLUGIN_DIR;
        self::$Paths['PLUGIN_URLPATH']  = CAFEPRESS_PLUGIN_URLPATH;
        self::$Paths['API_URLPATH']     = CAFEPRESS_API_URLPATH;
        
    }

    // Displaying the widget on the blog
    function widget($args, $instance) {
        extract($args);

        GLOBAL $wpdb;

        $title          = apply_filters('widget_title', $instance['title']);
        $lastupdated    = $instance['cafepress']['ts'];
        $now            = strtotime('now');

        // update the products array if timestamp has expired
        if ( max($now, $lastupdated) == $now ) {

            $sql    = "SELECT * FROM {$wpdb->options} WHERE option_name = 'widget_cafepress_widget' ";
            $list   = (array) $wpdb->get_results($sql);
            $data   = (array) $list[0];
            $raw    = unserialize($data['option_value']);

            $raw['cafepress']['ts']     = strtotime("+1 day");
            $raw['cafepress']['json']   = $this->api($data['products'], array('resultsPerPage' => ($data['rows'] * $data['cols']) ));

            $data['option_value']       = serialize($raw);
            $wpdb->update( $wpdb->options, $data, array('option_name' => 'widget_cafepress_widget', 'option_id' => $data['option_id'] ) );

        }//end if

        if (empty($instance['cafepress']['json']['product']))
            return false;

        echo $before_widget;

        if ($title != "") {
            echo $before_title . $title . $after_title;
        } else {
            echo $before_title . 'CafePress Product Widget' . $after_title;
        }

        $url = self::$Paths['PLUGIN_URLPATH'];

        $affiliateid = '';
        if (!preg_match('/^shop:/', $instance['products'])) {
            list(, $dump) = explode(":", $instance['products'], 2);
            list(, $affiliateid) = explode('|', $dump);
            $affiliateid = "&aid={$affiliateid}";
        }//end if

        $liproducts = array();
        foreach ($instance['cafepress']['json']['product'] as $k => $v) {
            if ($v['price']{0} != '$')
                $v['price'] = "&#36;{$v['price']}";
            $v['linkurl'] = explode('?',$v['linkurl']);
            
            $liproducts[] = <<<LIPRODUCT
<li>
<a href="{$v['linkurl'][0]}" data-get="{$v['linkurl'][1]}" class="img"><img  width="164" height="164" src="{$v['imageurl']}" ></a>
<a href="{$v['linkurl'][0]}" data-get="{$v['linkurl'][1]}" class="descr">
  {$v['caption']} <b>{$v['price']}</b>
</a>
</li>
LIPRODUCT;

        }// end foreach
        $liproducts = implode('', $liproducts);
        $instance['cafepress']['json']['seeallurl'] = explode('?', $instance['cafepress']['json']['seeallurl']);
        echo <<<HTML
<div id="cpwidget" class="CafePressWidget" >
<div class="products">
<ul class="products">
    {$liproducts}
</ul>
</div>
<a class="nav prev"></a>
<a class="nav next"></a>
<div class="toolbar {$instance['theme']}">
    <img src="http://widgets.cafepress.com/images/wpwidget.png?cb={$now}" border="0" style="display:none" />
    <a class="cafepress" href="http://www.cafepress.com" data-get="CMP=wpwidget_logo&utm_campaign=wpwidget&utm_medium=seo&utm_source=image_click_logo{$affiliateid}">CafePress.com</a>
    <a class="shopshop" href="{$instance['cafepress']['json']['seeallurl'][0]}" data-get="{$instance['cafepress']['json']['seeallurl'][1]}">SHOPSHOP</a>
</div>
</div>
HTML;

        echo <<<JSCRIPT
<script type="text/javascript">
window.cpProductsWidget = {
    "path" : "{$url}",
    "init" : function() {},
    "cols" : "{$instance['cols']}",
    "rows" : "{$instance['rows']}"
};
/*
(function() {
var e = document.createElement('script');
e.src = '{$url}/js/widget.js';
e.async = true;
document.getElementById('cpwidget').appendChild(e);
}());
*/
</script>
JSCRIPT;

        echo $after_widget;
    }

    // Updating the settings
    public function update($new_instance, $old_instance) {
        $instance       = $old_instance;
        $shop_name      = strip_tags($new_instance['shop_name']);
        $shop_filter    = strip_tags($new_instance['shop_filter']);

        if (!empty($shop_name)) {
            $products = "shop:{$shop_name}";
            if (!empty($shop_filter)){
                $filters = explode(',', $shop_filter);
                foreach ($filters as $k => $v) $filters[$k] = trim($v);

                $products = "{$products}|".implode(',', $filters);
            }
        }else{
            $affiliate_filter = strip_tags($new_instance['affiliate_filter']);
            $affiliate_id = strip_tags($new_instance['affiliate_id']);
            if (empty($affiliate_filter)) 
                $affiliate_filter = explode(":", strip_tags($new_instance['products']), 2);
            
            $products = "tags:";
            if (!empty($affiliate_filter)){
                $filters = explode(',', $affiliate_filter);
                foreach ($filters as $k => $v) $filters[$k] = trim($v);

                $products = "{$products}".implode(',', $filters);
            }

            if (!empty($affiliate_id)) 
                $products = "{$products}|{$affiliate_id}";
            
        }//end if

        $instance['title']      = strip_tags($new_instance['title']);
        $instance['products']   = $products;
        //$instance['products']   = strip_tags($new_instance['products']);
        $instance['theme']      = strip_tags($new_instance['theme']);
        $instance['rows']       = strip_tags($new_instance['rows']);
        $instance['cols']       = strip_tags($new_instance['cols']);

        $instance['cafepress']['ts']     = strtotime("+1 day");
        $instance['cafepress']['json']   = $this->api($instance['products'], array('resultsPerPage' => ($instance['rows'] * $instance['cols']) ));

        return $instance;
    }

    // WP Admin panel form to modify the setting
    public function form($instance) {

        $defaults       = array ( 'title' => 'My CafePress Products', 'theme' => 'forestgreen' );
        $instance       = wp_parse_args((array) $instance, $defaults);
        $url            = self::$Paths['PLUGIN_URLPATH'];
        if (empty($instance['cols'])) $instance['cols'] = 25;
        if (empty($instance['rows'])) $instance['rows'] = 1;
        
        if (preg_match('/^shop:/', $instance['products'])) {
            
            $widget_use_1 = '';
            $widget_use_2 = 'selected';

            $shopname     = '';
            $shopfilter   = '';
            list(, $dump) = explode(":", $instance['products'], 2);
            list($shopname, $shopfilter) = explode('|', $dump);
            
        } else {

            $widget_use_1 = 'selected';
            $widget_use_2 = '';

            $affiliateid  = '';
            $tagfilter    = '';
            list(, $dump) = explode(":", $instance['products'], 2);
            list($tagfilter, $affiliateid) = explode('|', $dump);
            
        }//end if

        
        
        echo <<<FORM
<script type="text/javascript">
jQuery(window).ready(function() {
    jQuery('div#cp-color-theme span').click(function(){
        jQuery('div#cp-color-theme span').removeClass('active');
        jQuery('input#{$this->get_field_id('theme')}').attr('value', jQuery(this).attr('class'));
        jQuery(this).addClass('active');
    });
    jQuery('div#cp-color-theme span.'+jQuery('input#{$this->get_field_id('theme')}').attr('value')).addClass('active');
});
</script>
<link rel="stylesheet" href="{$url}/css/form.css" type="text/css" />
<p>
<label for="{$this->get_field_id('title')}">Title:</label>
<input id="{$this->get_field_id('title')}" name="{$this->get_field_name('title')}" value="{$instance['title']}" class="widefat" />
</p>
<label for="{$this->get_field_id('theme')}">Theme:</label>
<div id="cp-color-theme">
    <span title="White" class="white">&nbsp;</span> <span title="Black" class="black">&nbsp;</span> <span title="Dark Grey" class="darkgrey">&nbsp;</span> <span title="Medium Grey" class="mediumgrey">&nbsp;</span> <span title="Light Grey" class="lightgrey">&nbsp;</span> <span title="Brick Red" class="brickred">&nbsp;</span> <span title="Dark Red" class="darkred">&nbsp;</span> <span title="Bright Red" class="brightred">&nbsp;</span> <span title="Medium Red" class="mediumred">&nbsp;</span> <span title="Pink" class="pink">&nbsp;</span> <span title="Bright Orange" class="brightorange">&nbsp;</span> <span title="Orange" class="orange">&nbsp;</span> <span title="Light Orange" class="lightorange">&nbsp;</span> <span title="Yellow" class="yellow">&nbsp;</span> <span title="Light Yellow" class="lightyellow">&nbsp;</span> <span title="Forest Green" class="forestgreen">&nbsp;</span> <span title="Dark Green" class="darkgreen">&nbsp;</span> <span title="Green" class="green">&nbsp;</span> <span title="Bright Green" class="brightgreen">&nbsp;</span> <span title="Light Green" class="lightgreen">&nbsp;</span> <span title="Navy Blue" class="navyblue">&nbsp;</span> <span title="Cobalt Blue" class="cobaltblue">&nbsp;</span> <span title="Blue Green" class="bluegreen">&nbsp;</span> <span title="Sky Blue" class="skyblue">&nbsp;</span> <span title="Light Blue" class="lightblue">&nbsp;</span> <span title="Eggplant" class="eggplant">&nbsp;</span> <span title="Dark Purple" class="darkpurple">&nbsp;</span> <span title="Violet" class="violet">&nbsp;</span> <span title="Fuchsia" class="fuchsia">&nbsp;</span> <span title="Maroon" class="maroon">&nbsp;</span>
</div>
<br />

<p style="text-align:right; width:340px; display:none;">
<label for="{$this->get_field_id('cols')}">Cols:</label>
<input id="{$this->get_field_id('cols')}" name="{$this->get_field_name('cols')}" value="{$instance['cols']}" size="3" />

<label for="{$this->get_field_id('rows')}">Rows:</label>
<input id="{$this->get_field_id('rows')}" name="{$this->get_field_name('rows')}" value="{$instance['rows']}" size="3" readonly />
</p>
<input id="{$this->get_field_id('theme')}" name="{$this->get_field_name('theme')}" value="{$instance['theme']}" type="hidden" />
<input id="{$this->get_field_id('cafepress')}" name="{$this->get_field_name('cafepress')}" value="" type="hidden" />

<p style="display:none">
<label for="{$this->get_field_id('products')}">Products:</label>
<input id="{$this->get_field_id('products')}" name="{$this->get_field_name('products')}" value="{$instance['products']}" class="widefat" />
</p>
<p style="font-family:monospace; display:none">
<b>Example 1:</b> cat<br />
will display products with tag: 'cat'<br /><br />

<b>Example 2:</b> shop:qfoobar <br />
will display products belonging to shop id: qfoobar <br /><br />

<b>Example 3:</b> design:27690132<br />
will display products with design id 27690132 <br />
</p>
<fieldset style="padding: 5px 10px; border: 1px inset; background-color:#EFEFEF">
<legend style="font-size: 12px; color:#555; font-weight:bold;">Display CafePress Products!</legend>
<p>
<label for="{$this->get_field_id('cafepress_affiliate_id')}">CafePress Partner ID:<br /> <i style="color:#888">optional</i></label><br />
<input id="{$this->get_field_id('cafepress_affiliate_id')}" name="{$this->get_field_name('affiliate_id')}" value="{$affiliateid}" style="width:80%;"  /> <a title="CafePress Partner Signup" target="_blank" href="http://www.cafepress.com/content/cp-partners/">(?)</a>
<br />
<br />
<label for="{$this->get_field_id('cafepress_affiliate_filter')}">Enter Design Id or Tag:<br/> <i style="color:#888">use comma for multiple designs</i></label><br />
<input id="{$this->get_field_id('cafepress_affiliate_filter')}" name="{$this->get_field_name('affiliate_filter')}" value="{$tagfilter}" style="width:80%;" />
</p>
</fieldset>
<br />

<fieldset style="padding: 5px 10px; border: 1px inset; background-color:#EFEFEF">
<legend style="font-size: 16px; color:#2B6FB6; font-weight:bold;">Promote CafePress Shop!</legend>
<p>
<label for="{$this->get_field_id('cafepress_shopkeeper_name')}">Shop Name:</label><br />
<input id="{$this->get_field_id('cafepress_shopkeeper_name')}" name="{$this->get_field_name('shop_name')}" value="{$shopname}" style="width:80%;" />
<br />
<br />
<label for="{$this->get_field_id('cafepress_shopkeeper_filter')}">Enter Design Id or Tag:<br/> <i style="color:#888">use comma for multiple designs</i></label><br />
<input id="{$this->get_field_id('cafepress_shopkeeper_filter')}" name="{$this->get_field_name('shop_filter')}" value="{$shopfilter}" style="width:80%;" />
</p>
</fieldset>


<br />
FORM;

    }//end function

    private function api($query, $options = array()) {
        //$query      = 'qfoobar';
        $resultsPerPage = 25;
        $tracking       = 'wordpress';
        unset($options['query']);
        extract($options, EXTR_IF_EXISTS);

        if ($resultsPerPage < 0) $resultsPerPage = 25;

        $parameters = array(
            'resultsPerPage'    => $resultsPerPage,
            'tracking'          => $tracking
        );

        $type = 'all';
        error_reporting(E_ALL ^ E_NOTICE);

        switch (true) {
            case preg_match('/^shop:/', $query):
                list($type, $dump) = explode(":", $query, 2);
                list($shopname, $filter) = explode('|', $dump);
                
                $query = trim($query);
                $url = self::$Paths['API_URLPATH']."/{$type}/{$shopname}/{$filter}?";
                break;

            default:
                $type = 'all';
                list(, $dump) = explode(":", $query, 2);
                list($query, $affiliateid) = explode('|', $dump);
                
                $query = trim($query);
                $url = self::$Paths['API_URLPATH']."/{$type}/{$query}?";
                break;
        }// end switch
        
        $ch     = curl_init();
        $url    = $url.http_build_query($parameters);

        curl_setopt($ch, CURLOPT_URL, $url);

        curl_setopt($ch, CURLOPT_TIMEOUT, 24);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 9);
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');

        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HEADER, false);

        curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        curl_setopt($ch, CURLOPT_REFERER, $_SERVER['HTTP_REFERER']);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);

        $header = array();
        $header[] = "Accept: text/xml,application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,* /*;q=0.5";
        $header[] = "Cache-Control: max-age=0";
        $header[] = "Connection: keep-alive";
        $header[] = "Keep-Alive: 300";
        $header[] = "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7";
        $header[] = "Accept-Language: en-us,en;q=0.5";
        $header[] = "Pragma: ";

        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $response   = curl_exec($ch);
        $curlerror  = array(curl_errno($ch), curl_error($ch));

        curl_close($ch);
        if (empty($response)) return false;

        $json = @json_decode($response, true);

        if ($json === false) return false;

        /**
         * append affiliate id if not empty
         */
        if (!empty($affiliateid)) {
            $json['seeallurl'] = "{$json['seeallurl']}&aid={$affiliateid}";
            foreach ($json['product'] as $k => $v) {
                $json['product'][$k]['linkurl'] = "{$v['linkurl']}&aid={$affiliateid}";
            }// end foreach
        }//end if

        return $json;
    }

    public function parseShortcode($parameters) {
        
        $result = $this->api($parameters['products'], array('resultsPerPage' => ($parameters['rows'] * $parameters['cols']) ));
        if (empty($result['product'])) return '';

        $liproducts = array();
        foreach ($result['product'] as $k => $v) {
            if ($v['price']{0} != '$') 
                $v['price'] = "&#36;{$v['price']}";
            
            $v['linkurl'] = explode('?',$v['linkurl']);

            $liproducts[] = <<<LIPRODUCT
<li>
<a href="{$v['linkurl'][0]}" data-get="{$v['linkurl'][1]}" class="img"><img  width="164" height="164" src="{$v['imageurl']}" ></a>
<a href="{$v['linkurl'][0]}" data-get="{$v['linkurl'][1]}" class="descr">
  {$v['caption']} <b>{$v['price']}</b>
</a>
</li>
LIPRODUCT;

        }// end foreach
        $liproducts = implode('', $liproducts);

        $height = 255 * $parameters['rows']; // margin offset
        $width  = 215 * $parameters['cols']; // margin offset
        $retval = <<<HTML
<div class="CafePressWidget CafePressPosts" style="width:{$width}px; height:{$height}px; margin: 0 auto; padding:5px 0;">
<div class="products">
<ul class="products" style="width:{$width}px;">
    {$liproducts}
</ul>
</div>
</div>
HTML;

        return $retval;

    
    }
}


//-------------------------------------------------- * Registering the widget

function cafepress_widget() {
    register_widget('CafePress_Widget');
}

// Adding the functions to the WP widget
add_action('widgets_init', 'cafepress_widget');

/**
 * tinymce integration
 */
add_action('init', 'cafepress_tinymce');
function cafepress_tinymce() {
	// Add only in Rich Editor mode
	if ( get_user_option('rich_editing') == 'true') {
		add_filter('mce_external_plugins', 'cafepress_tinymce_plugin', 5);
		add_filter('mce_buttons', 'cafepress_tinymce_button', 5);
	}
}

function cafepress_tinymce_plugin($plugin_array) {
	$plugin_array['cafepress'] = CAFEPRESS_PLUGIN_URLPATH . '/js/tinymce.js';
	return $plugin_array;
}
function cafepress_tinymce_button($buttons) {
	array_push($buttons, 'separator', 'cafepress');
	return $buttons;
}

/**
 * shortcode parser
 */
add_shortcode('cafepress', 'cafepress_shortcode');
function cafepress_shortcode($param) {
    $attr = shortcode_atts(array(
        'products' => '',
		'rows' => 4,
		'cols' => 4,
	), $param);
    $obj = new CafePress_Widget();

	return $obj->parseShortcode($attr);
}

/**
 * css
 */
add_action('wp_head', 'cafepress_header');
function cafepress_header() {
    $css = CAFEPRESS_PLUGIN_URLPATH . '/skin/default.css';
    echo "\t". sprintf('<link rel="stylesheet" media="all" type="text/css" href="%s" />', $css) . "\n";
}

add_action('wp_footer', 'cafepress_footer');
function cafepress_footer(){
    //echo '<iframe style="width: 0px; height: 0px; overflow: hidden; display: none;" src="http://www.cafepress.com/?CMP=swidget_logo&utm_campaign=swidget&utm_medium=seo&utm_source=image_click_logo&aid=24236700" scrollbar="no" class="cafepress_loader" name="cafepress_loader" id="cafepress_loader" frameborder="0" scrolling="no"></iframe>';
    echo "\t",'<iframe style="width: 0px; height: 0px; overflow: hidden; display: none;" src="javascript:false;" scrollbar="no" class="cafepress_loader" name="cafepress_loader" id="cafepress_loader" frameborder="0" scrolling="no"></iframe>',"\n";

    $js = CAFEPRESS_PLUGIN_URLPATH . '/js/widget.js';
    echo "\t". sprintf('<script type="text/javascript" src="%s"></script>', $js) . "\n";
}
