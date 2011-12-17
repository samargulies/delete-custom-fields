<?php
/*
Plugin Name: Delete Custom Fields
Description: Ever have one erroneously entered custom field name confuse all of your users and you just can't figure out how to get rid of it? Delete Custom Fields will let you delete every instance of a custom field from your site. 
Version: 0.2
License: GPL version 2 or any later version
Author: Sam Margulies
Author URI: http://belabor.org/

Copyright 2011  Sam Margulies  (email : freebuffalo@gmail.com)

*/

class Delete_Custom_Fields {
	static $instance;

	function __construct() {
		self::$instance =& $this;
		// add page to tools menu 
		add_action('admin_menu', array( &$this, 'add_menu') );
		add_filter('plugin_action_links', array( &$this, 'plugin_action_links'), 10, 2);


	}
	function add_menu() {
		add_management_page( 'Delete Custom Fields', 'Delete Custom Fields', 'manage_options', 'delete-custom-fields', array( &$this, 'admin_page' ) );
	}
	function get_all_meta_keys( $include_hidden = false ) {
		global $wpdb;		
		$limit = 100;
		$include_hidden = ($include_hidden) ? "" : "HAVING meta_key NOT LIKE '\_%'";
		$keys = $wpdb->get_col( "
				SELECT meta_key
				FROM $wpdb->postmeta
				GROUP BY meta_key
				$include_hidden
				ORDER BY meta_key
				LIMIT $limit" );
		return $keys;
	}
	
	function get_all_posts_for_meta_key( $key ) {
		$custom_value_query = new WP_Query( array(
			'post_type' => 'any',
			'nopaging' => true,
			'ignore_sticky_posts' => true,
			'post_status' => array('publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit', 'trash'),
			'meta_query' => array(
				array(
					'key' => $key
				)
			)
		) );
		
		if( ! $custom_value_query->have_posts() ) { return false; }
		
		$output = array();
		
		while ( $custom_value_query->have_posts() ) : $custom_value_query->the_post();
		
			$output[] = get_the_ID();
			
		endwhile;
		
		return $output;
	}
	
	function remove_all_meta_with_key( $key ) {
	
		$posts = $this->get_all_posts_for_meta_key( $key );
		
		if( ! $posts ) { return false; }
		
		foreach( $posts as $post ) {
			delete_post_meta($post, $key);
		}
		
		return true;
	}
	
	function admin_page( ) {
		if( ! current_user_can('manage_options') )  {
			wp_die( __('You do not have sufficient permissions to access this page.') );
		}
		
		// Now display the settings editing screen

	    echo '<div class="wrap">';
	
	    // header
	
	    echo "<h2>" . __( 'Delete Custom Fields', 'delete-custom-fields' ) . "</h2>";
		// see if we have some fields to delete
				
		if ( !empty( $_POST ) && 
			 check_admin_referer( 'delete_custom_fields','delete_custom_fields_nonce' ) && 
			 isset( $_POST['custom-field-to-delete'] ) ) {
			 
			$custom_field = esc_attr( $_POST['custom-field-to-delete'] );
 			
			$deleted = Delete_Custom_Fields::remove_all_meta_with_key( $custom_field );
			
			if( $deleted ) {
				?>
			
				<div class="updated"><p><?php printf(__("The custom field <strong>%s</strong> has been deleted.", 'delete-custom-fields'), $custom_field); ?></p></div>
			
				<?php
			}

 		}	
		
	    // settings form
	    
	    ?>
	    <div class="narrow">
		<p>
		<?php _e("This form will <strong>permanently delete</strong> custom fields you select along with any content associated with them. Before using this form, please <strong>make sure that you are not deleting a custom field used by your theme or plugins</strong>; just because you did not explicitly enter or create a custom field does not mean that it does not hold information used by your theme or plugins." ); ?> To show hidden custom fields <a href="<?php echo admin_url('tools.php?page=delete-custom-fields&show-hidden=true'); ?>">click here</a>.
		</p>
				
		<form name="delete-custom-fields" method="post">
		
		<?php wp_nonce_field( "delete_custom_fields", "delete_custom_fields_nonce" ) ?>
		
		<label for="custom-field-to-delete"><?php _e("Custom Fields to Delete", "delete-custom-fields" ); ?> 
		
		<select name="custom-field-to-delete">
			<option disabled="disabled"><?php _e("Select a Field", "delete-custom-fields"); ?></option>
			
			<?php
			
			$show_hidden = ($_GET['show-hidden']) ? true : false;
			
			$custom_fields = Delete_Custom_Fields::get_all_meta_keys( $show_hidden );
			
			foreach($custom_fields as $field) {
				echo "<option value='$field'>$field</option>";
			}
			
			?>
				
		</select>
		
		</label>
		
		<p class="submit">
		<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Delete Permanently') ?>" />
		</p>
		
		</form>
		
		</div> <!-- .narrow -->
		
		</div> <!-- .wrap -->
		
		<?php
	}
	function plugin_action_links($links, $file) {

	    if( $file == plugin_basename(__FILE__) ) {
	    
			$settings_link = '<a href="' . admin_url('tools.php?page=delete-custom-fields') . '">Manage</a>';
	        $links = array_merge( array( $settings_link ), $links );
	    }
	
	    return $links;
	}

}

// Bootstrap
new Delete_Custom_Fields;


?>