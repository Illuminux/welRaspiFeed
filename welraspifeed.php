<?php
/*
 * Plugin Name: wel!RaspiFeed
 * Plugin URI: http://www.welzels.de/
 * Description: Shows the RSS feeds of raspifeed.de on your web site
 * Version: 0.1
 * Author: Knut Welzel
 * Author URI: www.welzels.de
 * 
 * Copyright 2013  Knut Welzel  (email : admin@welzels.de)
 *
 * License:       GNU General Public License, v3
 * License URI:   http://www.gnu.org/licenses/quick-guide-gplv3
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.

 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 * 
 */

class welRaspiFed extends WP_Widget {

    function welRaspiFed() {
		
		$widget_ops = array(
			'classname'   => 'welRaspiFed',
			'description' => 'RaspiFeed RSS/Atom'
		);
		
        $this->WP_Widget(
			'welRaspiFed', 
			'wel!RaspiFed', 
			$widget_ops, 
			$control_ops
		);
    }
    
    function widget($args, $instance) {
		
		require_once (ABSPATH . WPINC . '/class-feed.php');
		
        extract($args);
		extract($instance);
		
		$title   = "RaspiFeed.de";
		$url     = "http://raspifeed.de/";
		$rss_url = "feed://raspifeed.de/feed";
		
		// Plugin Verzeichnis
		$plugin_dir = basename(dirname(__FILE__));
		
		// Plugin URL
		$plugin_url = WP_PLUGIN_URL . "/" . $plugin_dir;
		
		$feed = new SimplePie();
		$feed->set_feed_url($rss_url);
		$feed->init();
		$feed->handle_content_type();
		$feed->set_cache_class('WP_Feed_Cache');
        $feed->set_file_class('WP_SimplePie_File');
        $feed->set_cache_duration(apply_filters('wp_feed_cache_transient_lifetime', $cachetime));


        if ( $feed->error() )
            return new WP_Error('simplepie-error', $feed->error());
		
		
		$feedHTML = "<ul>\n";
		
		if(is_wp_error($feed)) {
			
			$feedHTML .= "<li>Error reading feed</li>";
		}
		else {
		
			foreach ($feed->get_items(0, $max_items) as $item) {
	
				$item_url = parse_url($item->get_permalink());
				
				$feedTimestamp   = strtotime($item->get_date());
				$feedDate        = date_i18n($date_format, $feedTimestamp);
				$feedFavicon     = "http://g.etfv.co/http://" . $item_url['host'];
				$feedLink        = $item->get_permalink();
				$feedDescription = str_replace(array("\n", "\r"), ' ', esc_attr(strip_tags(@html_entity_decode($item->get_description(), ENT_QUOTES, get_option('blog_charset')))));;
				$feedDescription = wp_html_excerpt($feedDescription, 200) . '&hellip; ';
				$feedDescription = esc_html($feedDescription);
				$feedTitle       = $item->get_title();
				
				$feedAuthorInfo  = $item->get_author();
				// wird als Email und nicht als Name intepretiert
				$feedAuthor      = $feedAuthorInfo->email;
				if(strlen($feedAuthor) > 25)
					$feedAuthor  = wp_html_excerpt($feedAuthor, 22) . '&hellip; ';
			
				$feedHTML .= "<li  class=\"welRaspiFeed-feeditem\" ";
				$feedHTML .= $show_icon ? "style=\"list-style-type: none;\"><img src=\"$feedFavicon\" width=\"16\" height=\"16\" class=\"welRaspiFeed-feedicon\" />" : ">";
				$feedHTML .= "<span class=\"welRaspiFeed-feedname\">";
				$feedHTML .= "<a href=\"$feedLink\" title=\"$feedDescription\" class=\"welRaspiFeed-feed\" target=\"_blank\">$feedTitle</a></span><br />";
				$feedHTML .= "<div class=\"welRaspiFeed-author\">";
				$feedHTML .= "<cite>$feedAuthor</cite> - <span>$feedDate</span>";
				$feedHTML .= "</div>";
				$feedHTML .= "</li>\n";
			}
		}
		
		$feedHTML .= "<li class=\"welRaspiFeed-feeditem\" style=\"list-style-type: none;\">";
     	$feedHTML .= "<a href=\"$url\" target=\"_blank\">...mehr auf <img src=\"$plugin_url/images/raspifeed-$logo_color.png\" height=\"14\" width=\"99\" style=\"float:none; margin-bottom:-2px;\" /></a>";		
		$feedHTML .= "</li>\n";
		$feedHTML .= "</ul>\n";
		$feedHTML .= "<div style=\"text-align: right; line-height:10px; margin-top: -10px;\">";
		$feedHTML .= "<a href=\"http://welzels.de/\" target=\"_blank\"><img src=\"http://g.etfv.co/http://welzels.de\" width=\"8\" height=\"8\" border=\"0\"></a> ";
		$feedHTML .= "<a href=\"https://github.com/Illuminux/welRaspiFeed\" title=\"Get this Plugin on GitHub\" target=\"_blank\"><img src=\"$plugin_url/images/GitHub-$logo_color.png\" width=\"8\" height=\"8\" border=\"0\"></a>";
		$feedHTML .= "</div>\n";

        
        // output
        echo $before_widget;
        if($title)
            echo $before_title.$title.$after_title;
        echo $feedHTML;
        echo $after_widget;
    
    }


	function update($new_instance, $old_instance) {
		
		$instance = $old_instance;
		
		$instance['max_items']   = intval($new_instance['max_items']);
		$instance['cachetime']   = intval($new_instance['cachetime']);
		$instance['show_icon']   = intval($new_instance['show_icon']);
		$instance['date_format'] = $new_instance['date_format'];
		$instance['logo_color']  = $new_instance['logo_color'];
		
		return $instance;
    }
	
  
	function form($instance) {
		
		// Plugin Verzeichnis
		$plugin_dir = basename(dirname(__FILE__));
		
		// Plugin URL
		$plugin_url = WP_PLUGIN_URL . "/" . $plugin_dir;
		
		$instance = wp_parse_args((array)$instance, array(
			'max_items'   => 5, 
			'cachetime'   => 6000, 
			'show_icon'   => 1,
			'date_format' => "j. M Y",
			'logo_color'  => "black"
		));
        
		$max_items   = intval($instance['max_items']);
		$cachetime   = intval($instance['cachetime']);
		$show_icon   = intval($instance['show_icon']);
		$date_format = $instance['date_format'];
		$logo_color  = $instance['logo_color'];
  
        echo '
			<p>
			  <label for="'.$this->get_field_name('max_items').'">Maximum Items: </label>
			  <input type="text" id="'.$this->get_field_id('max_items').'" name="'.$this->get_field_name('max_items').'" value="'.$max_items.'" style="width:50px" />
			</p>
            <p>
                <label for="'.$this->get_field_name('cachetime').'">Cache Period (sec): </label>
                <input type="text" id="'.$this->get_field_id('cachetime').'" name="'.$this->get_field_name('cachetime').'" value="'.$cachetime.'" style="width:50px" />
            </p>
            <p>
              <label for="'.$this->get_field_name('date_format').'">Date Format: </label>
              <input type="text" id="'.$this->get_field_id('date_format').'" name="'.$this->get_field_name('date_format').'" value="'.$date_format.'" style="width:80px" />
            </p>
            <p>
              <input type="checkbox" id="'.$this->get_field_id('show_icon').'" name="'.$this->get_field_name('show_icon').'"  value="1" '.(($show_icon)?'checked="checked"': '').'/>
              <label for="'.$this->get_field_name('show_icon').'">Display favicon </label>
            </p>
			<p>
			  <input type="radio" id="'.$this->get_field_id('logo_color').'_black" name="'.$this->get_field_name('logo_color').'" value="black" '.(($logo_color=='black')?'checked="checked"': '').'/>
			  <img src="'.$plugin_url.'/images/raspifeed-black.png" height="14" width="99" style="vertical-align:middle;" />
			  <br />
			  <input type="radio" id="'.$this->get_field_id('logo_color').'_white" name="'.$this->get_field_name('logo_color').'" value="white" '.(($logo_color=='white')?'checked="checked"': '').'/>
			  <img src="'.$plugin_url.'/images/raspifeed-white.png" height="14" width="99" style="vertical-align:middle;" />
			<p>
		';
    }
}

function welRaspiFed_init() {
	
	register_widget('welRaspiFed');
}


add_action('widgets_init', 'welRaspiFed_init');
add_action('wp_head', 'welRaspiFed_style', 8);

	
function welRaspiFed_style(){
?>
<style type="text/css">
	/* welRaspiFed Formtierung */

	.welRaspiFeed-feeditem {
		margin-bottom: 0;
		background: none !important;
		padding: 0px 0px 0.5em 0px !important;
		clear: left;
	}

	img.welRaspiFeed-feedicon {
		float: left;
		height: 16px;
		width: 16px;
		margin-top: 0px;
		margin-left: -24px;
		padding: 2px;
	}

	.welRaspiFeed-feeditem span img {
		float: none;
		height: 8px;
		width: 8px;
		margin-top: 0px;
		padding: 2px;
	}
	
	.welRaspiFeed-feedname a {
	   text-decoration: none;
		font-weight: bold;
		margin: 0px;
	}

	.welRaspiFeed-author {
		text-align: left;
		font-size: 10px;
		padding: 0px;
	}

</style>
<?php
}
?>
