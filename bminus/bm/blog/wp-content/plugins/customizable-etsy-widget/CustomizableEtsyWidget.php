<?php
/*
Plugin Name: Customizable Etsy Widget
Plugin URI: http://sneddo.net/wp-plugins-widget-etsy
Description: This widget combines the options of "Custom Etsy Widget" and "Etsy Sidebar Widget" to allow users to display Etsy items in a sidebar widget, either from their favourites or their store. It does not rely on flash or an iframe, which allows a much more integrated view in your theme. The term "Etsy" is a trademark of Etsy, Inc.  This application uses the Etsy API but is not endorsed or certified by Etsy, Inc.
Version: 1.3.5
Author: John Sneddon
Author URI: http://www.sneddo.net
License: GPL2
*/

/*
Future releases
 - (perhaps) allow favourites that are no longer active
 - (perhaps) Optional CSS
 - 1.4.0 Improve settings panel
 */

define('ETSY_API_KEY', '2jv9d97u8yyujvyzh4zeacrs');		//If you modify this source in any way please obtain your own etsy api key from: developer.etsy.com
define('ETSY_CACHE_LIFE',  6); 							// hours to cache results - default 6
define('ETSY_BASE_URL', 'http://openapi.etsy.com/v2');  // add sandbox to test. No trainling slash
define('ETSY_MAXITEMSRETURNED', 100);					// Etsy API returns max 100 items
define('ETSY_DEV', false); 								// used for testing only

class CustomizableEtsyWidget extends WP_Widget {

	/** constructor */
	function CustomizableEtsyWidget() {
		parent::WP_Widget(false, $name = 'Customizable Etsy Widget', array('description' => 'Widget that displays Etsy items from store or favourites', 'class' => 'customizableetsywidget'));	
	}

	/* Display the widget */
	function widget($args, $instance) {		
		extract( $args );
		$title = apply_filters('widget_title', $instance['title'] );

		// fix old variables
		if (!isset($instance['storelinkloc']))
				$instance['storelinkloc'] = "Bottom";		

		echo $before_widget;	  
		if ( $title ) {
			echo $before_title;
			if ( $instance['storelinkloc'] == "Title" )
				echo '<a href="http://www.etsy.com/shop/' . $instance['etsyid'] . '" >' . $title . '</a>';
			else
				echo $title;
			echo $after_title;
		}
		if ( $instance['storelinkloc'] == "Top" )
			echo '<a href="http://www.etsy.com/shop/' . $instance['etsyid'] . '" >' . $instance['storelinktext'] . '</a>';

		if (isset($instance['etsyid']) && $instance['etsyid'] != "") {
			$url = CustomizableEtsyWidget::getEtsyURL($instance['itemsource'], $instance['etsyid'], $instance['showsection']);
			
			$etsyitems = CustomizableEtsyWidget::getEtsyItems( $url, $instance );

			if (!$etsyitems) {
				_e("No items to display");
				echo "<br />";
			}
			else
			{
				CustomizableEtsyWidget::showEtsyItems($etsyitems, $instance);
			}
		}
		else
		{
			_e("No items to display");
		}

		if ( $instance['storelinkloc'] == "Bottom" )
			echo '<a href="http://www.etsy.com/shop/' . $instance['etsyid'] . '" >' . $instance['storelinktext'] . '</a>';
		echo $after_widget; 
	}

	/** @see WP_Widget::update */
	function update($new_instance, $old_instance) {
		$instance = $old_instance;
		$instance['title'] = $new_instance['title'];
		$instance['etsyid'] = $new_instance['etsyid'];

		// Store link info		
		$instance['storelinktext'] = $new_instance['storelinktext'];
		$instance['storelinkloc'] = $new_instance['storelinkloc'];


		$instance['columns'] = $new_instance['columns'];
		$instance['rows'] = $new_instance['rows'];

		$instance['imgsize'] = $new_instance['imgsize'];

		$instance['itemsource'] = $new_instance['itemsource'];
		$instance['showsection'] = $new_instance['showsection'];
		
		$instance['storesections'] = $new_instance['storesections'];
		$instance['nwlinks'] = $new_instance['nwlinks'];
		$instance['order'] = $new_instance['order'];
		
		$instance['updatefreq'] = $new_instance['updatefreq'];

		$instance['showadv'] = $new_instance['showadv'];
		
		// test cache location
		if(!file_exists($new_instance['cacheloc'])) {
			if (!mkdir($new_instance['cacheloc'], 0777, true))
				$instance['cacheloc'] = sys_get_temp_dir();	
			else
				$instance['cacheloc'] = $new_instance['cacheloc'];
		}
		elseif (is_writable($new_instance['cacheloc']))  {
			$instance['cacheloc'] = $new_instance['cacheloc'];		
		}
		else {
			$instance['cacheloc'] = sys_get_temp_dir();	
		}
		
		// clear the cache
		if (($old_instance['itemsource'] != $new_instance['itemsource']) || 
			($old_instance['showsection'] != $new_instance['showsection']) ||
			($old_instance['etsyid'] != $new_instance['etsyid']) ||
			($old_instance['order'] != $new_instance['order']) ||
			(($old_instance['rows']*$old_instance['columns']) < ($new_instance['rows']*$new_instance['columns'])))
		{
			$parsed = parse_url(get_bloginfo('home'));
			$cache_file = $instance['cacheloc'].'/'.$parsed['host'].'_customizableetsywidget_'.$instance['etsyid'].'_'.$instance['itemsource'].'_cache_.json';

			if (file_exists($cache_file)){
				unlink($cache_file);
			}
			
			// refresh cache
			$url = CustomizableEtsyWidget::getEtsyURL($instance['itemsource'], $instance['etsyid'], $instance['showsection']);
			CustomizableEtsyWidget::getEtsyItems($url, $instance);
		}
		// remove old variables
		if (isset($instance['displaystore']))
			unset($instance['displaystore']);
		
		return $instance;
	}

	/** @see WP_Widget::form */
	function form($instance) {	
		// set defaults
		if (!$instance['imgsize'])
			$instance['imgsize'] = '75x75';
		if (!$instance['itemsource'])
			$instance['itemsource'] = 'store';
		if (!$instance['order'])
			$instance['order'] = 'newest';	
		if (!isset($instance['storelinktext']))
			$instance['storelinktext'] = "Visit my Store";
		if (!isset($instance['storelinkloc']))
			$instance['storelinkloc'] = "None";
		if (!isset($instance['updatefreq']))
			$instance['updatefreq'] = ETSY_CACHE_LIFE;
		if (!$instance['cacheloc'])
			$instance['cacheloc'] = sys_get_temp_dir();		
		
		if (!isset($instance['showadv']))
			$instance['showadv'] = false;
		if (!isset($instance['nwlinks']))
			$instance['nwlinks'] = false;	

		/* legacy variables */
		/* Removed in 1.2 */		
		if (!isset($instance['displaystore'])) {
			unset($instance['displaystore']);
		}

		////[alx359] check curl installed or plugin doesn't work
		if (!function_exists('curl_init')) { // check curl
			echo __('<div class="dashboard-widget-error">curl() is required and not available. Please enable it</div>','CustomizableEtsyWidget');
		}
		else
		{
			?>
			<p>
				<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e('Widget Title:'); ?></label>
				<input type="text" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>" style="width:90%" />
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'etsyid' ); ?>"><?php _e('Etsy Username:'); ?></label>
				<input type="text" id="<?php echo $this->get_field_id( 'etsyid' ); ?>" name="<?php echo $this->get_field_name( 'etsyid' ); ?>" value="<?php echo $instance['etsyid']; ?>" style="width:90%" />
			</p>			
			<p>
				<label for="<?php echo $this->get_field_id( 'columns' ); ?>"><?php _e('Columns:'); ?></label>
				<select id="<?php echo $this->get_field_id( 'columns' ); ?>" name="<?php echo $this->get_field_name( 'columns' ); ?>">
					<?php $this->customisableetsywidget_createOptions( array_keys(array_fill(1, 5, 0)), $instance['columns']); ?>
				</select>
				<label for="<?php echo $this->get_field_id( 'rows' ); ?>"><?php _e('Rows:'); ?></label>
				<select id="<?php echo $this->get_field_id( 'rows' ); ?>" name="<?php echo $this->get_field_name( 'rows' ); ?>">
					<?php $this->customisableetsywidget_createOptions( array_keys(array_fill(1, 5, 0)), $instance['rows']); ?>
				</select>
			</p>
			<p>
				Image Size:<br />
				<input type="radio" id="<?php echo $this->get_field_id( 'imgsize' ); ?>-75x75" name="<?php echo $this->get_field_name( 'imgsize' ); ?>" value="75x75" <?php if ($instance['imgsize'] == "75x75"){echo 'checked="checked"';} ?> /><label for="<?php echo $this->get_field_id( 'imgsize' ); ?>-75x75"><?php _e('75x75'); ?></label>
				<input type="radio" id="<?php echo $this->get_field_id( 'imgsize' ); ?>-170x135" name="<?php echo $this->get_field_name( 'imgsize' ); ?>" value="170x135" <?php if ($instance['imgsize'] == "170x135"){echo 'checked="checked"';} ?> /><label for="<?php echo $this->get_field_id( 'imgsize' ); ?>-170x135"><?php _e('170x135'); ?></label>				
			</p>
			<p>
				Show items from...<br />
				<input type="radio" id="<?php echo $this->get_field_id( 'itemsource' ); ?>-store" name="<?php echo $this->get_field_name( 'itemsource' ); ?>" value="store" <?php if ($instance['itemsource'] == "store"){echo 'checked="checked"';} ?>  onchange='sourceChanged(this, "<?php echo $this->get_field_id( 'etsyid' ); ?>", "<?php echo $this->get_field_id( 'showsection' ); ?>")' /><label for="<?php echo $this->get_field_id( 'itemsource' ); ?>-store"><?php _e('Store'); ?></label>
				<input type="radio" id="<?php echo $this->get_field_id( 'itemsource' ); ?>-favourites" name="<?php echo $this->get_field_name( 'itemsource' ); ?>" value="favourites" <?php if ($instance['itemsource'] == "favourites"){echo 'checked="checked"';} ?>  onchange='sourceChanged(this)' /><label for="<?php echo $this->get_field_id( 'itemsource' ); ?>-favourites"><?php _e('Favourites'); ?></label>
				<div id='etsysourcestore' <?php if ($instance['itemsource'] != "store"){echo "style='display: none'";} ?> >
					<div id='loadingsections' style='display: none'>
						Loading Store Sections...
					</div>
					<div id='etsystoresectionerror' style='background: #FFDDDD; border: 1px solid red; display: none'>
					
					</div>
					<div id='etsystoresections'>
						Show items from Section: 
						<select id="<?php echo $this->get_field_id( 'showsection' ); ?>" name="<?php echo $this->get_field_name( 'showsection' ); ?>">
							<?php
							$sections = explode("|", $instance['storesections']);
							
							foreach ($sections as $section) {
								$sec = explode(":", $section);
								echo "<option value='".$sec[0]."'".(($instance['showsection'] == $sec[0])?'selected="selected"':'').">".$sec[1]."</option>";
							}
							?>
						</select>
						<img src="<?php echo WP_PLUGIN_URL . '/' . basename(dirname(__FILE__)) . '/images/refresh.png' ?>" width='15' height='15' title='Reload Sections' alt='reload' onclick='sourceChanged(jQuery("#<?php echo $this->get_field_id( 'itemsource' ); ?>-store"), "<?php echo $this->get_field_id( 'etsyid' ); ?>", "<?php echo $this->get_field_id( 'showsection' ); ?>")' />
						<input type='hidden' name="<?php echo $this->get_field_name( 'storesections' ); ?>" id="<?php echo $this->get_field_id( 'storesections' ); ?>" value="<?php echo $instance['storesections']; ?>" />
					</div>
				</div>
			</p>
			<p>
				<input type='checkbox' name="<?php echo $this->get_field_name( 'nwlinks' ); ?>" id="<?php echo $this->get_field_id( 'nwlinks' ); ?>" <?php if ($instance['nwlinks']) { echo 'checked="checked"'; } ?> /><label for="<?php echo $this->get_field_id( 'nwlinks' ); ?>">Open links in new window</label>
			</p>
			<p>
				Order:<br />
				<input type="radio" id="<?php echo $this->get_field_id( 'order' ); ?>-newest" name="<?php echo $this->get_field_name( 'order' ); ?>" value="newest" <?php if ($instance['order'] == "newest"){echo 'checked="checked"';} ?> /><label for="<?php echo $this->get_field_id( 'order' ); ?>-newest"><?php _e('Newest'); ?></label>
				<input type="radio" id="<?php echo $this->get_field_id( 'order' ); ?>-random" name="<?php echo $this->get_field_name( 'order' ); ?>" value="random" <?php if ($instance['order'] == "random"){echo 'checked="checked"';} ?> /><label for="<?php echo $this->get_field_id( 'order' ); ?>-random"><?php _e('Random'); ?></label>				
			</p>
			
			<div id='etsywidget_storelink'>
				<b>Store Link</b><br />
				<label for="<?php echo $this->get_field_id( 'storelinkloc' ); ?>"><?php _e('Link Location:'); ?></label>
				<select id="<?php echo $this->get_field_id( 'storelinkloc' ); ?>" name="<?php echo $this->get_field_name( 'storelinkloc' ); ?>">
					<?php $this->customisableetsywidget_createOptions( array_values(array("None", "Title", "Bottom", "Top")), $instance['storelinkloc']); ?>
				</select> <br />
				<label for="<?php echo $this->get_field_id( 'storelinktext' ); ?>"><?php _e('Link Text:'); ?></label>
				<input type="text" id="<?php echo $this->get_field_id( 'storelinktext' ); ?>" name="<?php echo $this->get_field_name( 'storelinktext' ); ?>" value="<?php echo $instance['storelinktext']; ?>" style="width:90%" />
			</div>
			
			<!-- Advanced Options -->
			<p><input type='checkbox' name="<?php echo $this->get_field_name( 'showadv' ); ?>" id="<?php echo $this->get_field_id( 'showadv' ); ?>" onchange='showadv(this)' <?php if ($instance['showadv']) { echo 'checked="checked"'; } ?> /><label for="<?php echo $this->get_field_id( 'showadv' ); ?>">Show Advanced options</label></p>
			<div id='etsywidget_adv' <?php if (!$instance['showadv']) { echo 'style="display: none"'; } ?> >
				<b>Advanced Options</b><br />			
				<p>Update every:
				<select id="<?php echo $this->get_field_id( 'updatefreq' ); ?>" name="<?php echo $this->get_field_name( 'updatefreq' ); ?>">
					<?php $this->customisableetsywidget_createOptions( array_values(array(0, 6, 12, 24)), $instance['updatefreq']); ?>
				</select> 
				Hours.<br /><span style='font-size: x-small'>Set this to 0 to manually update by clicking Save</span></p>	
				<p>
					<label for="<?php echo $this->get_field_id( 'cacheloc' ); ?>"><?php _e('Cache Location:'); ?></label>
					<input type="text" id="<?php echo $this->get_field_id( 'cacheloc' ); ?>" name="<?php echo $this->get_field_name( 'cacheloc' ); ?>" value="<?php echo $instance['cacheloc']; ?>" style="width:90%" />
				</p>
			</div>
			<?php
		} //else - if curl is available
	}

	// helper function for creating option lists for select inputs. Takes an array of options and a selected item.
	function customisableetsywidget_createOptions( &$options, $so ){
		$r = '';
		foreach ($options as $o){
			$r .= '<option value="'.$o.'"' . (($o == $so)?' selected="selected"':'') . '>&nbsp;&nbsp;'.$o.'&nbsp;</option>';
		}
		echo $r;
	}
	
	function getEtsyURL($source, $etsyid, $showsection) {
		if ($source == "store") {		
			if ($showsection > 0) {
				return ETSY_BASE_URL . "/sections/" . $showsection . "?includes=Listings(title,url):active/Images(url_75x75,url_170x135):1:0&api_key=" . ETSY_API_KEY;
			}
			return (ETSY_BASE_URL . '/shops/' . $etsyid . '/listings/active?includes=Images(url_75x75,url_170x135):1:0&fields=title,url&api_key=' . ETSY_API_KEY);
		}
		elseif ($source == "favourites") 
			return (ETSY_BASE_URL . '/users/' . $etsyid . '/favorites/listings?includes=Listing(title,url)/Images(url_75x75,url_170x135)&fields=state&api_key=' . ETSY_API_KEY);
	}
	
	function queryEtsy( $url, $offset, $limit ) {
		$url = $url . "&offset=$offset&limit=$limit";
		if (ETSY_DEV)
			echo "<!-- $url -->";

		////[alx359] check curl installed or plugin doesn't work
		if (!function_exists('curl_init')) { // check curl
			echo __('<b>curl() is needed! Enable in php.ini</b>','CustomizableEtsyWidget');
			return;
		}
		else
		{
			$ch = curl_init($url);
	
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$response_body = curl_exec($ch);
			$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			if (intval($status) != 200) return false;
	
			return $response_body;
		}
	}

	function getEtsyItems( $url, $instance ){
		$parsed = parse_url(get_bloginfo('home'));
		$offset = 0;
		// To aid performance- if we are returning new items, we don't need to return all items. 
		if ($instance['order'] == 'newest')
			$limit = $instance['columns']*$instance['rows'];
		else
			$limit = ETSY_MAXITEMSRETURNED;
			
		$items = Array();
	
		// remove this in future release - required for upgrade from 1.0
		if(empty($instance['cacheloc'])) $instance['cacheloc'] = sys_get_temp_dir();
		
		$cache_file = $instance['cacheloc'].'/'.$parsed['host'].'_customizableetsywidget_'.$instance['etsyid'].'_'.$instance['itemsource'].'_cache_.json';

		if (!file_exists($cache_file) or (($instance['updatefreq'] > 0) && (time() - filemtime($cache_file) >= ($instance['updatefreq']*3600))) or ETSY_DEV){
			
			$data = CustomizableEtsyWidget::queryEtsy( $url, 0, $limit );
			
			if ($data)
			{
				$data_decode = json_decode($data);
					
				$items = array_merge($items, $data_decode->results); 

				$numResults = $data_decode->count;
			
				while (false && $numResults > $offset+ETSY_MAXITEMSRETURNED) {
					$offset=$offset+ETSY_MAXITEMSRETURNED;
					$data = CustomizableEtsyWidget::queryEtsy( $url, $offset, ETSY_MAXITEMSRETURNED );
					$data_decode = json_decode($data);	
					$items = array_merge($items, $data_decode->results); 
				}	
				file_put_contents($cache_file, json_encode($items));
			}
		}else{
			$data = file_get_contents($cache_file);
			$items = json_decode($data);	
		}

		// handle no results
		if (!$data)
			return false;
			
		return $items;
	}

	function showEtsyItems( $items, $instance) {
		$numitems = count($items);
		$validItems = Array();
		
		if ($instance['order'] == 'random' && $instance['itemsource'] == 'favourites') {
			foreach ($items as $item)
			{
				if ($item->state == 'active')
					array_push($validItems, $item);
			}
			$items = $validItems;
		}
		
		if ($instance['order'] == 'random') {
			shuffle($items);	
		}
	
		echo '<table class="etsyItemTable">';
		for ($i = 0; $i < $instance['rows']; $i++) {
			echo '<tr>';
			for ($j = 0; $j < $instance['columns']; $j++) {
				$k = ($i*$instance['columns']+$j);

				if ($instance['itemsource'] == 'store' && $instance['showsection'] > 0 ) {
					$item = $items[0]->Listings[$k];
					$numitems = count($items[0]->Listings); // fix number of items
				}
				elseif ($instance['itemsource'] == 'store' )	
					$item = $items[$k];
				elseif ($instance['itemsource'] == 'favourites')
					$item = $items[$k]->Listing;
				
				echo '<td>';
				if ($k < $numitems) {
					echo '<a href="' . htmlspecialchars($item->url) . '" title="' . $item->title . '"' . (($instance['nwlinks'])?'target="_blank"':"") . '>';
					if ($instance['imgsize'] == '75x75')
						echo '<img src="' . htmlspecialchars($item->Images[0]->url_75x75) . '" alt="' . $item->title . '" />';
					if ($instance['imgsize'] == '170x135')
						echo '<img src="' . htmlspecialchars($item->Images[0]->url_170x135) . '" alt="' . $item->title . '" />';
					echo '</a>';
				}
				echo "</td>";
			}
			echo '</tr>';
		}
		echo '</table>';
	}
} // class CustomizableEtsyWidget


// Some basic CSS to style the etsyItemTable element
function customizableetsywidget_head_css() {
	?>
	<style type='text/css'>
		.widget_customizableetsywidget { text-align: center }
		.etsyItemTable { margin: 0 auto;  }
		.etsyItemTable td { padding: 3px; }
		.etsyItemTable td a img { border: 1px #000; border-style:solid;  }
	</style>
	<?php
}

// Enqueue jquery and widget setting Javascript
function etsy_settings_js() {
	$s = WP_PLUGIN_URL . '/' . basename(dirname(__FILE__)) . '/settings.js';
	wp_register_script('s',$s);
	wp_enqueue_script('s');
	wp_enqueue_script('jquery');
}

add_action('widgets_init', create_function('', 'return register_widget("CustomizableEtsyWidget");'));
add_action('wp_head', 'customizableetsywidget_head_css');
add_action('admin_print_scripts-widgets.php', 'etsy_settings_js');
?>