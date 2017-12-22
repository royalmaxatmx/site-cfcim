<?php

/**
* Plugin Name: Add To Menus Lite
* Description: Add to Menus Lite provides a quick link in your post & pages to quickly add a menu item link for the article or page that you are viewing.
* Version: 0.1
* Author: Ravi Shakya
* Author URI: http://ravishakya.com.np
* License: GPL2
* Text Domain: atm
*/

class add_to_menus {
	
	function __construct() {
		
		// Admin functions
		add_action( 'admin_init', array( $this, 'admin_init_custom_menu' ) , 1 );

		// Add menu to the admin bar
		add_action( 'admin_bar_menu', array( $this , 'add_to_menu_link' ) , 999 );

		// Add custom css/html
		add_action( 'admin_notices', array( $this , 'add_custom_scripts' ) );

		// Include necessary css and scripts
		add_action( 'admin_enqueue_scripts', array( $this, 'atm_enqueue_scripts') , 9999 );
		add_action( 'wp_head', array( $this, 'atm_enqueue_scripts') , 9999 );

		// Ajax call for save the menu
		add_action( 'wp_ajax_atm_save_menu' , array( $this , 'atm_save_menu' ) );

		add_action( 'wp_ajax_atm_dismiss_notice' , array( $this , 'atm_dismiss_notice' ) );

		// exclude items via filter instead of via custom Walker
		if ( ! is_admin() ) {
			add_filter( 'wp_get_nav_menu_items', array( $this, 'exclude_menu_items' ) );
		}

		// add new fields via hook
		add_action( 'wp_nav_menu_item_custom_fields', array( $this, 'custom_fields' ), 10, 4 );

		// switch the admin walker
		add_filter( 'wp_edit_nav_menu_walker', array( $this, 'edit_nav_menu_walker' ) );

		// save the menu item meta
		add_action( 'wp_update_nav_menu_item', array( $this, 'nav_update'), 10, 2 );

		// Add link to the meta box
		add_action( 'add_meta_boxes', array( $this , 'atm_register_meta_boxes' ) );

		// Delete options on Deactivate and uninstall
		register_deactivation_hook( __FILE__ , array( $this , 'on_deactivation' ) );

	}

	function on_deactivation(){

		delete_option( 'hide_on_allowed_pages' ); 
		delete_option( 'hide_on_taxonomies' ); 
		delete_option( 'hide_on_not_allowed_pages' );   

	}

	/**
	* Allowed pages to display the button
	*/

	function allow_posts_categories(){

		global $typenow;
	
		$allow_pages = $this->allow_pages_to_display();

		// Post edit page
		$check_current_screen = $this->check_current_screen();

		if( $check_current_screen == true && in_array( $typenow , $allow_pages ) ){
			return true;
		}
		return false;

	}

	/**
	* Add meta box to posts/pages/custom post types
	*/

	function atm_register_meta_boxes(){

		$result = $this->allow_posts_categories();

		$allow_pages = $this->allow_pages_to_display();

		if( $result == true ){
			add_meta_box( 'atm-link', __( 'Add To Menus', 'atm' ), array( $this , 'atm_link_callback' ), $allow_pages , 'side' );
		}

	}

	/**
	* Meta Box To post edit pages
	*/

	function atm_link_callback(){ ?>

		<div class="atm_admin_bar atm_admin_bar_meta_box">
			<a href="javascript:void(0)" class="atm_admin_bar button atm_add_to_menus_meta_box">
				<?php _e( 'Add To Menus' , 'atm' ); ?>
			</a>

			<div class="atm_pro_version_wrap">
				<p>Want a Pro Version ??</p> 
				<p>Just for $10</p> 
				<a href="http://codecanyon.net/item/add-to-menus/15533312?ref=ravishakya" target="blank" class="button button-primary button-large">Download</a>
			</div>

		</div>

		<?php
	}

	/**
	* Added (atm_access_level) key on save menu
	* @param {int} $menu_id ( Menu ID )
	* @param {int} $menu_item_db_id ( Menu items ids )
	* @param {int} $access_level ( Access Level )
	*/

	function nav_update( $menu_id, $item_id ) {

		$access_level = !empty( $_POST['atm_access_level'] ) ? $_POST['atm_access_level'] : '';

		if( !empty( $access_level ) ){

			foreach( $access_level as $key => $value ){

				update_post_meta( $key , 'atm_access_level' , $value );

			}

		}

		if( !empty( $_POST['atm_menu_roles'] ) ){

			foreach( $_POST['atm_menu_roles'] as $keyMenuId => $rolesArray ){

				if( !empty( $rolesArray ) ){

					update_post_meta( $keyMenuId , 'atm_menu_roles' , $rolesArray );

				}

			}

		}

	}

	/**
	* Added custom walker function
	*/

	function edit_nav_menu_walker( $walker ) {
		return 'Walker_Nav_Menu_Edit_ATM';
	}

	/**
	* Included Walker class
	*/

	function admin_init_custom_menu(){

		include_once( plugin_dir_path( __FILE__ ) . 'class.Walker_Nav_Menu_Edit.php');

	}

	/**
	* Added access level custom field on the menu on the backend
	*/

	function custom_fields( $item_id, $item, $depth, $args ){ 

		$access_level = get_post_meta( $item_id , 'atm_access_level' , true ); 

		if( empty( $access_level ) || $access_level == 1 ){
			$checked_all = 'checked="checked"';
		} else {
			$checked_all = '';
		} ?>

		<div class="atm-custom-fields" style="display: table; margin: 10px 0;">
			
			<div class="menu_access_level_wrap">

				<p class="description"><?php _e( 'Access Level' , 'atm' ); ?></p>

				<label style="display: table;">
					<input type="radio" name="atm_access_level[<?php echo $item->ID ;?>]" value="1" <?php echo $checked_all; ?>> <?php _e( 'Everyone' , 'atm' ); ?>
				</label>

				<label style="display: table;">
					<input type="radio" name="atm_access_level[<?php echo $item->ID ;?>]" value="2" <?php checked( $access_level , 2 ); ?>> <?php _e( 'Logged In Users' , 'atm' ); ?>
				</label>

				<label style="display: table;">
					<input type="radio" name="atm_access_level[<?php echo $item->ID ;?>]" value="3" <?php checked( $access_level , 3 ); ?>> <?php _e( 'Non Logged In Users' , 'atm' ); ?>
				</label>

			</div>

			<?php $showHideRoles = ( $access_level != 2 ? 'style="display:none;"' : '' ); ?>

			<div class="restrict_roles_wrap" <?php echo $showHideRoles; ?>>

				<p class="description"><?php _e( 'Restrict menu item to a minimum role' , 'atm' ); ?></p>

				<div class="atm_notice" style="display:none;"><?php _e( 'Minimum 1 role should be selected' , 'atm' ); ?></div>

				<?php $this->get_roles_menu( 'atm_menu_roles[' . $item_id . ']' , $item_id ); ?>

			</div>

		</div>

		<?php

	}

	/**
	* Show the success message on the completion
	*/

	function success_added_menu(){ 

		if( !empty( $_GET['atm_result'] ) && $_GET['atm_result'] == 'success' ){ ?>

			<div class="notice notice-success is-dismissible">
		        <p><?php _e( 'Your item has been added to the menu(s).', 'atm' ); ?></p>
		    </div>

			<?php

		}

	}

	/**
	* Enqueue backend scripts and styles
	*/

	function atm_enqueue_scripts( $hook ){

		if( $this->is_edit_page( 'edit' ) == true ){

			wp_enqueue_style( 'atm-style', plugins_url( 'css/style.css', __FILE__ ) , array(), '1.0.0', false );
			wp_enqueue_script( 'atm-script' , plugins_url( 'js/custom.js', __FILE__ ) , array() , '1.0.0' , false );

			wp_enqueue_script( 'jquery-ui-dialog' );
			
			wp_enqueue_style( 'wp-jquery-ui-dialog' );
			
		}

		if( $hook == 'nav-menus.php' ){
			wp_enqueue_script( 'atm-menu-script' , plugins_url( 'js/menu.js', __FILE__ ) , array() , '1.0.0' , false );
		}

	}

	/**
	* Save menu by ajax
	*/

	function atm_save_menu(){

		if( $_POST['action'] != 'atm_save_menu' ){
			die;
		}
		
		$selected_menu = $_POST['parent_menu'];
		
		if( !empty( $selected_menu ) ){

			foreach( $selected_menu as $key1 => $value ){

				foreach( $value as $key => $parent_menu_id ){

					if( $key != 'menu_id' ){

						$selected_menu[$key1][$key];

						$this->atm_update_nav_menu_items( $selected_menu[$key1]['menu_id'] , $_POST , $parent_menu_id );

					}

					/**
					* Save menu as top menu
					*/

					if( $selected_menu[$key1]['menu_id'] == $parent_menu_id && is_numeric( $key ) ){

						$this->atm_update_nav_menu_items( $selected_menu[$key1]['menu_id'] , $_POST , 0 );

					}

				}

			}

		}

		echo 'success';

		die;

	}

	/**
	* Add menus to the database
	*/

	function atm_update_nav_menu_items( $menu_id , $array , $parent_menu_id ){

		$menu_title = sanitize_text_field( $array['title'] );
		$menu_class = sanitize_text_field( $array['class'] );
		$menu_title_attribute = sanitize_text_field( $array['title_attribute'] );
		$menu_description = sanitize_text_field( $array['description'] );
		$target = !empty( $array['target'] ) ? sanitize_text_field( $array['target'] ) : '';
		$object_type = sanitize_text_field( $array['object_type'] );
		$access_level = sanitize_text_field( $array['access_level'] );
		$atm_roles = !empty( $array['roles'] ) ? $array['roles'] : '';

		$open_target = ( $target == 1 ) ? '_blank' : '';

		// Check for post or category
		switch ( $object_type ) {

			case 'post':

				$post = get_post( $array['post_id'] );
				$object_id = $post->ID;
				$menu_item_object = $post->post_type;
				$item_type = 'post_type';
				break;

			case 'category':

				$object_id = $array['post_id'];
				$cat = get_term( $object_id , '' , ARRAY_A );
				$menu_item_object = $cat['taxonomy'] ;
				$item_type = 'taxonomy';
				break;
			
			default:
				# code...
				break;
		}

		// Save new menu
		$new_post_id = wp_update_nav_menu_item(
			$menu_id, 
			0, 
			array(
				'menu-item-title' => $menu_title,
				'menu-item-classes' => $menu_class,
				'menu-item-attr-title' => $menu_title_attribute,
				'menu-item-description' => $menu_description,

	           	'menu-item-object' => $menu_item_object,
	           	'menu-item-type' => $item_type,
	           	'menu-item-object-id' => $object_id,

	           	'menu-item-status' => 'publish',
	           	'menu-item-parent-id' => $parent_menu_id,
	           	'menu-item-position' => 0,
	           	'menu-item-target' => $open_target,
	        )
	    );

		// Added access level
	    update_post_meta( $new_post_id , 'atm_access_level', $access_level );

	    if( !empty( $atm_roles ) ){
	    	update_post_meta( $new_post_id , 'atm_menu_roles', $atm_roles );	
	    }

	}

	/**
	* Added icon on the admin bar
	*/

	function add_custom_scripts(){ 

		$this->show_pro_version_message();

		if( $this->check_current_screen() == true ){ 

			$this->success_added_menu();

			global $post;

			if( !empty( $post ) && is_object( $post ) ){

				$title = $post->post_title;
				$object_type = 'post';
				$object_id = $post->ID;

			} ?>

			<script>
				var atm_admin_ajax = "<?php echo admin_url( 'admin-ajax.php' ); ?>";
			</script>

			<style> 
		    	.atm_admin_bar .ab-item:before{
					font-family: "dashicons" !important;
					content:  "\f132" !important;
					top: 4px;
				} 
			</style>

			<div id="add_to_menu_dialog" title="<?php _e( 'Add To Menus' , 'atm' ); ?>" style="display:none">
		  		
		  		<form>
					<table>
						
						<tr valign="top">
							<th><?php _e( 'Menu Title <span class="required">*<span>' , 'atm' ); ?></th>
							<td>
								<input type="text" value="<?php echo $title; ?>" name="atm_menu_title" autocomplete="off">
								
								<label class="atm_open_new_tab">
									<input type="checkbox" name="atm_open_new_tab" autocomplete="off" value="1">
									<?php _e( 'Open in a new tab' , 'atm' ); ?>
							</label>
								
							</td>
						</tr>

						<tr valign="top">
							<th><?php _e( 'Menu Class' , 'atm' ); ?></th>
							<td>
								<input type="text" value="" name="atm_menu_class" autocomplete="off">
							</td>
						</tr>
						
						<tr valign="top">
							<th><?php _e( 'Title Attribute' , 'atm' ); ?></th>
							<td>
								<input type="text" value="" name="atm_title_attribute" autocomplete="off">
							</td>
						</tr>

						<tr valign="top">
							<th><?php _e( 'Description' , 'atm' ); ?></th>
							<td>
								<textarea name="atm_menu_description" autocomplete="off"></textarea>
								<p class="description"><?php _e( 'The description will be displayed in the menu if the current theme supports it.' , 'atm' ); ?></p>
							</td>
						</tr>
						
						<tr valign="top">
							<th><?php _e( 'Parent Menu <span class="required">*<span>' , 'atm' ); ?></th>
							<td>
								<?php $this->atm_get_registered_menus(); ?>
							</td>
						</tr>

						<tr valign="top" class="atm_edit_page_restrict_wrap" style="display: none;">
							
							<th><?php _e( 'Restrict Item To' , 'atm' ); ?></th>
							<td>
								<div class="atm_role_wrapper">
									<?php $this->get_roles_menu(); ?>
								</div>
							</td>

						</tr>

						<tr class="access_level" valign="top">
							<th>
								<?php _e( 'Access Level' , 'atm' ); ?>
							</th>
							<td>
								<select name="atm_access_level" autocomplete="off">
									<option value="1"><?php _e( 'For Everyone','wpad' ); ?></option>
									<option value="2"><?php _e( 'Logged In Users','wpad' ); ?></option>
									<option value="3"><?php _e( 'Non Logged In Users','wpad' ); ?></option>
								</select>
							</td>
						</tr>

						<tr class="break_buttons" valign="top">
							<th></th>
							<td>

								<input type="hidden" value="<?php echo $object_id; ?>" name="atm_post_id">
								<input type="hidden" value="<?php echo $object_type; ?>" name="atm_object_type">

								<button class="button button-primary button-large save_atm_menu" type="button"><?php _e( 'Save' , 'atm' ); ?></button>
								<img src="<?php echo admin_url( '/images/spinner.gif' ); ?>" style="display:none">
								<button class="button button-large atm_dialog_close" type="button"><?php _e( 'Cancel' , 'atm' ); ?></button>
							</td>
						</tr>

					</table>
				</form>

			</div>

			<?php

		} 

	}

	function get_roles_menu( $menu_name = null , $item_id = null ){  ?>

		<style>
			.atm-custom-fields .menu_roles_atm {
			    display: inline-block;
			    width: 32%;
			}
			.atm-custom-fields{
			    background: #f5f5f5 none repeat scroll 0 0;
			    border-left: 4px solid #BA55D3;
			    box-sizing: border-box;
			    font-size: 12px;
			    padding: 5px 15px 8px;
			    width: 97%;
			}

			.atm-custom-fields p.description {
			    background: #eee none repeat scroll 0 0;
			    margin: 10px 0;
			    padding: 2px 10px;
			}

			.atm_notice {
			    background: #fff none repeat scroll 0 0;
			    border-left: 3px solid red;
			    margin-bottom: 10px;
			    padding: 5px 0 4px 8px;
			}
		</style>

		<?php

		$menu_name = !empty( $menu_name ) ? $menu_name : 'atm_menu_roles';

		global $wp_roles;
     	$roles = $wp_roles->get_names(); 
     	$availableRoles = get_post_meta( $item_id, 'atm_menu_roles' , true );
     	$count = 0;

     	foreach( $roles as $key => $role ){

     		if( empty( $availableRoles ) /*&& $this->allow_posts_categories() == false*/ ){ // Show only on the menu manager page?>

     			<label class="menu_roles_atm">
					<input type="checkbox" name="<?php echo $menu_name; ?>" value="<?php echo $key; ?>" checked="checked">
					<?php echo $role; ?>
				</label>

     			<?php
     		} else {

	     		$defaultChecked = $this->role_checked_default( $item_id , $key ); ?>

				<label class="menu_roles_atm">
					<input type="checkbox" name="<?php echo $menu_name; ?>[<?php echo $count++ ;?>]" value="<?php echo $key; ?>" <?php echo $defaultChecked; ?>>
					<?php echo $role; ?>
				</label>

				<?php

			}

		}

	}

	function role_checked_default( $item_id , $role ){

		if( empty( $item_id ) ){
			return;
		}

		$availableRoles = get_post_meta( $item_id, 'atm_menu_roles' , true );

		$availableRoles = empty( $availableRoles ) ? array() : $availableRoles;

		if( in_array( $role , $availableRoles ) ){

			return 'checked="checked"';

		}

		return;

	}

	/**
	* Add to menu backend form
	*/

	function atm_get_registered_menus(){

		$menus_created = get_terms( 'nav_menu', array( 'hide_empty' => false ) );

		include 'custom_walker.php';

		echo '<select size="6" multiple name="atm_parent">';

		foreach ( $menus_created as $value ) {

			$flag = $this->get_current_language_menu( $value );

			if( $flag == true ){

				echo '<optgroup label="' . $value->name . '">';
				$this->get_custom_menus( $value );
				echo '</optgroup>';

			}

		}

		echo '</select>';

	}

	function get_current_language_menu( $value ){

		global $wpdb;

		$tablecheck = "SELECT * FROM $wpdb->prefix" . 'icl_translations';
		$check_table = $wpdb->get_row( $tablecheck );

		if( empty( $check_table ) ){
			return true;
		} elseif ( !class_exists('SitePress') ) {
			$language_code = 'en';
		} else {
			$language_code = ICL_LANGUAGE_CODE;
		}

		global $wpdb;
		$table = $wpdb->prefix . 'icl_translations';
		$results = $wpdb->get_results( "SELECT * FROM {$table}
			WHERE `language_code` = '" . $language_code . "'
			AND `element_type` = 'tax_nav_menu'
			AND `element_id` = {$value->term_id}" , ARRAY_A );
		
		return ( !empty( $results ) ? true : false );

	}

	/**
	* Get all registered menus
	*/

	function get_custom_menus( $object ){ 
		
		echo '<option class="atm_top_level_menu" value="' . $object->term_id . '">Top Level Menu</option>';
		
		wp_nav_menu( array(
		    'menu_class' => 'nav-menu',
		    'menu' => $object->term_id,
		    'walker' => new ATM_Custom_Nav_Walker(),
		) );

	}

	/**
	* Get menu object
	*/

	function get_menu_object( $menu_name ){

		$locations = get_nav_menu_locations();
		$menu_id = $locations[ $menu_name ] ;

		$term = get_term_by( 'id', $menu_id , 'nav_menu');
		return $term;

	}

	function show_pro_version_message(){

		//make sure we are on the backend
	    if (!is_admin()) return false;

	    global $pagenow;
	    global $post;

	    $allow_pages = array( 'post' , 'page' );
	    $hide_on_allowed_pages = get_option( 'hide_on_allowed_pages' );
	    $hide_on_not_allowed_pages = get_option( 'hide_on_not_allowed_pages' );
	    $hide_on_taxonomies = get_option( 'hide_on_taxonomies' );

	    // Show on Add new post/page
	    if( in_array( $pagenow, array( 'post-new.php' ) ) && in_array( $post->post_type , $allow_pages )){ 

	    	if( $hide_on_allowed_pages == 'off' ){
	    		return;
	    	} ?>

	    	<div class="warning notice notice-warning">
	    		<p>
	    			<span class="notice_wrapper">
	    				<strong>Add To Menus</strong> link will be visible after the post/page is saved.
	    			</span>
	    			<a class="atm-dismiss button" href="javascript:void(0);" value="1">
	    				<span>Hide this notice</span>
	    				<img src="<?php echo admin_url( 'images/spinner.gif' ); ?>" style="display:none;">
	    			</a>
	    		</p>
	    	</div>

	    	<?php

	    } elseif( in_array( $pagenow, array( 'post-new.php' ) ) && !in_array( $post->post_type , $allow_pages ) ){ 

	    	if( $hide_on_not_allowed_pages == 'off' ){
	    		return;
	    	} ?>

	    	<div class="warning notice notice-warning">
	    		<p>
	    			<span class="notice_wrapper">
	    				Custom Post Types doesn't support in <strong>Add To Menus Lite</strong> Version. Upgrade to 
	    			</span>
	    			<a class="button button-primary" href="http://codecanyon.net/item/add-to-menus/15533312?ref=ravishakya" target="blank">Pro Version</a>
	    			<a class="atm-dismiss button" href="javascript:void(0);" value="2">
	    				<span>Hide this notice</span>
	    				<img src="<?php echo admin_url( 'images/spinner.gif' ); ?>" style="display:none;">
	    			</a>
	    		</p>
	    		
	    	</div>

	    	<?php
	    
	    } elseif( in_array( $pagenow, array( 'edit-tags.php' ) ) ){ 

	    	if( $hide_on_taxonomies == 'off' ){
	    		return;
	    	}?>

	    	<div class="warning notice notice-warning">
	    		<p>
	    			<span class="notice_wrapper">
	    				Taxonomies doesn't support in <strong>Add To Menus Lite</strong> Version. Upgrade to 
	    			</span>
	    			<a class="button button-primary" href="http://codecanyon.net/item/add-to-menus/15533312?ref=ravishakya" target="blank">Pro Version</a>
	    			<a class="atm-dismiss button" href="javascript:void(0);" value="3">
	    				<span>Hide this notice</span>
	    				<img src="<?php echo admin_url( 'images/spinner.gif' ); ?>" style="display:none;">
	    			</a>
	    		</p>
	    	</div>

	    	<?php
	    } ?>

	    <style>
	    	.atm-dismiss {
			    padding: 0 10px !important;
			    position: absolute;
			    margin-left: 5px !important;
			}
			.atm-dismiss img {
			    margin-top: 3px;
			}
			.atm-dismiss span {
			    float: left;
			    margin-right: 5px;
			}
			.notice_wrapper {
			    float: left;
			    margin-right: 10px;
			    padding: 5px 0;
			}			
			.warning.notice p {
			    display: table;
			    position: relative;
			    width: 100%;
			}
	    </style>

	    <script>
	    	
		    jQuery( document ).on( 'click' , '.atm-dismiss' , function(){

		    	var selected = jQuery(this);

		    	jQuery.ajax({

		    		url : "<?php echo admin_url( 'admin-ajax.php' ); ?>",
		    		type : 'POST',
		    		dataType : 'json',
		    		data : {
		    			action : 'atm_dismiss_notice',
		    			value : selected.attr( 'value' )
		    		},
		    		beforeSend : function(){
		    			selected.find( 'img' ).show();
		    		},
		    		success : function( result ){

		    			selected.closest( '.warning' ).remove();

		    		}

	    		});

		    });
	    	

	    </script>

	    <?php
	}

	function atm_dismiss_notice(){

		if( $_POST['action'] == 'atm_dismiss_notice' ){

			$value = sanitize_text_field( $_POST['value'] );

			switch ( $value ) {

				case '1':
					update_option( 'hide_on_allowed_pages' , 'off' );
					break;

				case '2':
					update_option( 'hide_on_not_allowed_pages' , 'off' );
					break;

				case '3':
					update_option( 'hide_on_taxonomies' , 'off' );
					break;
				
				default:
					# code...
					break;
			}

			echo json_encode( array( 'result' => 'success' ) );
			die;

		}

		echo json_encode( array( 'result' => 'error' ) );
		die;

	}

	/**
	* Check if the page is edit page
	* @return boolen
	*/

	function is_edit_page($new_edit = null){

	    //make sure we are on the backend
	    if (!is_admin()) return false;

	    global $pagenow;

	    if($new_edit == "edit")
	        return in_array( $pagenow, array( 'post.php',  ) );
	    elseif($new_edit == "new") //check for new post page
	        return in_array( $pagenow, array( 'post-new.php' ) );
	    else //check for either new or edit
	        return in_array( $pagenow, array( 'post.php', 'post-new.php' ) );

	}

	/**
	* Check for the edit page
	* @return boolen 
	*/

	function check_current_screen(){

		if( $this->is_edit_page( 'edit' ) == true ){

			return true;

		}

		return false;

	}

	function allow_pages_to_display(){

		$allow_pages = array( 'page' , 'post' );

		return $allow_pages;

	}

	/**
	* Add menu to admin bar
	*/

	function add_to_menu_link( $wp_admin_bar ){

		global $typenow;
	
		$allow_pages = $this->allow_pages_to_display();

		// Post edit page
		$check_current_screen = $this->check_current_screen();

		// category edit page
		//$check_category_page = $this->check_category_page();

		if( $check_current_screen == true && in_array( $typenow , $allow_pages ) ){

			$args = array(
				'id'    => 'atm_admin_bar',
				'title' => 'Add To Menus',
				'href'  => '#',
				'meta'  => 	array( 
						'class' => 'atm_admin_bar',
						'title' => 'Add To Menus'
					)
			);

			$wp_admin_bar->add_node( $args );
			
		}
		
	}

	/**
	* Exclude menus on the frontend
	*/

	function exclude_menu_items( $items ) {

		$hide_children_of = array();

		// Iterate over the items to search and destroy
		foreach ( $items as $key => $item ) {

			$visible = true;

			// hide any item that is the child of a hidden item
			if( in_array( $item->menu_item_parent, $hide_children_of ) ){
				$visible = false;
				$hide_children_of[] = $item->ID; // for nested menus
			}

			$get_access_level = get_post_meta( $item->ID , 'atm_access_level' , true );
			$get_menu_roles = get_post_meta( $item->ID , 'atm_menu_roles' , true );

			// check any item that has NMR roles set
			if( $visible && !empty( $get_access_level ) ) {

				// check all logged in, all logged out, or role
				switch( $get_access_level ) {

					case '2' :

						if( is_user_logged_in() ){

							$visible = false;

							if( !empty( $get_menu_roles ) ){

								foreach( $get_menu_roles as $role ){

									if( current_user_can( $role ) ){
										$visible = true;
									}

								}

							}

						} else {
							$visible = false;
						}

						break;

					case '3' :
						$visible = ! is_user_logged_in() ? true : false;
						break;

					default:
						$visible = true;			
						break;

				}

			}

			// add filter to work with plugins that don't use traditional roles
			$visible = apply_filters( 'atm_nav_menu_roles_item_visibility', $visible, $item );

			// unset non-visible item
			if ( ! $visible ) {
				$hide_children_of[] = $item->ID; // store ID of item 
				unset( $items[$key] ) ;
			}

		}

		return $items;
	}


}

$add_to_menus = new add_to_menus();