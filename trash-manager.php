<?php
/*
Plugin Name: Trash Manager
Plugin URI: http://www.poradnik-webmastera.com/projekty/trash_manager/
Description: This plugin allows you to delete Posts, Pages and Comments without moving them to Trash first. Additionally it restores all 'Are you sure?' questions when you try to delete, trash or restore something.
Author: Daniel Frużyński
Version: 1.2
Author URI: http://www.poradnik-webmastera.com/
Text Domain: trash-manager
*/

/*  Copyright 2009-2010  Daniel Frużyński  (email : daniel [A-T] poradnik-webmastera.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

if ( !class_exists( 'TrashManager' ) ) {

define( 'TRASH_MGR_JS_VER', '1.1' );

class TrashManager {
	// Constructor
	function TrashManager() {
		// Initialise plugin
		add_action( 'init', array( &$this, 'init' ) );
		
		if ( is_admin() ) {
			// Initialise plugin - admin part
			add_action( 'admin_init', array( &$this, 'admin_init' ) );
			
			// Add items to admin menu
			add_action( 'admin_menu', array( &$this, 'admin_menu' ) );
			
			// Add actions to each post on Post List
			add_filter( 'post_row_actions', array( &$this, 'post_row_actions' ), 10, 2 );
			// Add actions to each page on Page List
			add_filter( 'page_row_actions', array( &$this, 'page_row_actions' ), 10, 2 );
			// Add actions to each comment on Comment List
			add_filter( 'comment_row_actions', array( &$this, 'comment_row_actions' ), 10, 2 );
			
			// HACK: WP does not delete posts/pages/comments directly, it moves them to trash first.
			// Therefore we need to delete them (finally!) after they are moved to trash
			add_action( 'trashed_post', array( &$this, 'trashed_post' ) );
			add_action( 'trashed_comment', array( &$this, 'trashed_comment' ) );
		}
	}
	
	// Initialise plugin
	function init() {
		load_plugin_textdomain( 'trash-manager', false, dirname( plugin_basename( __FILE__ ) ).'/lang' );
	}
	
	// Initialise plugin - admin part
	function admin_init() {
		// Load additional JS script
		wp_enqueue_script( 'trash-manager', 
			WP_PLUGIN_URL.'/'.dirname( plugin_basename( __FILE__ ) ).'/trash-manager.js',
			array( 'common' ), TRASH_MGR_JS_VER );
		wp_localize_script( 'trash-manager', 'trashMgrL10n', array(
			'bulkDelete' => get_option( 'trashmgr_bulk_ays_delete' ) ? __("You are about to delete the selected items.\n  'Cancel' to stop, 'OK' to delete.", 'trash-manager') : '',
			'bulkTrash' => get_option( 'trashmgr_bulk_ays_trash' ) ? __("You are about to trash the selected items.\n  'Cancel' to stop, 'OK' to trash.", 'trash-manager') : '',
			'bulkUntrash' => get_option( 'trashmgr_bulk_ays_untrash' ) ? __("You are about to restore the selected items.\n  'Cancel' to stop, 'OK' to restore.", 'trash-manager') : '',
			'commentDelete' => get_option( 'trashmgr_comment_ays_delete' ) ? __("You are about to delete this comment\n 'Cancel' to stop, 'OK' to delete.", 'trash-manager') : '',
			'commentTrash' => get_option( 'trashmgr_comment_ays_trash' ) ? __("You are about to trash this comment\n 'Cancel' to stop, 'OK' to trash.", 'trash-manager') : '',
			'commentUntrash' => get_option( 'trashmgr_comment_ays_untrash' ) ? __("You are about to restore this comment\n 'Cancel' to stop, 'OK' to restore.", 'trash-manager') : '',
		) );
		
		register_setting( 'trash-manager', 'trashmgr_post_add_delete', array( &$this, 'sanitise_bool' ) );
		register_setting( 'trash-manager', 'trashmgr_post_ays_delete', array( &$this, 'sanitise_bool' ) );
		register_setting( 'trash-manager', 'trashmgr_post_ays_trash', array( &$this, 'sanitise_bool' ) );
		register_setting( 'trash-manager', 'trashmgr_post_ays_untrash', array( &$this, 'sanitise_bool' ) );
		
		register_setting( 'trash-manager', 'trashmgr_page_add_delete', array( &$this, 'sanitise_bool' ) );
		register_setting( 'trash-manager', 'trashmgr_page_ays_delete', array( &$this, 'sanitise_bool' ) );
		register_setting( 'trash-manager', 'trashmgr_page_ays_trash', array( &$this, 'sanitise_bool' ) );
		register_setting( 'trash-manager', 'trashmgr_page_ays_untrash', array( &$this, 'sanitise_bool' ) );
		
		register_setting( 'trash-manager', 'trashmgr_comment_add_delete', array( &$this, 'sanitise_bool' ) );
		register_setting( 'trash-manager', 'trashmgr_comment_ays_delete', array( &$this, 'sanitise_bool' ) );
		register_setting( 'trash-manager', 'trashmgr_comment_ays_trash', array( &$this, 'sanitise_bool' ) );
		register_setting( 'trash-manager', 'trashmgr_comment_ays_untrash', array( &$this, 'sanitise_bool' ) );
		
		//register_setting( 'trash-manager', 'trashmgr_bulk_add_delete', array( &$this, 'sanitise_bool' ) );
		register_setting( 'trash-manager', 'trashmgr_bulk_ays_delete', array( &$this, 'sanitise_bool' ) );
		register_setting( 'trash-manager', 'trashmgr_bulk_ays_trash', array( &$this, 'sanitise_bool' ) );
		register_setting( 'trash-manager', 'trashmgr_bulk_ays_untrash', array( &$this, 'sanitise_bool' ) );
	}
	
	// Add Admin menu option
	function admin_menu() {
		add_submenu_page( 'options-general.php', 'Trash Manager', 
			'Trash Manager', 'manage_options', __FILE__, array( &$this, 'options_panel' ) );
	}
	
	// Add actions to each post on Post List
	function post_row_actions( $actions, $post ) {
		// Add 'Delete permanently' to action list
		if ( get_option( 'trashmgr_post_add_delete' ) && current_user_can('delete_post', $post->ID) 
			&& isset( $actions['trash'] ) && !isset( $actions['delete'] ) ) {
			$temp_actions = $actions;
			$actions = array();
			foreach ( $temp_actions as $name => $html ) {
				$actions[$name] = $html;
				if ( $name == 'trash' ) {
					$html = '<a class="submitdelete" title="' . 
						esc_attr(__('Delete this post permanently', 'trash-manager')) . '" href="' . 
						wp_nonce_url('post.php?action=delete&amp;post='.$post->ID, 'delete-post_'.$post->ID) .
						'">' . __('Delete Permanently', 'trash-manager') . '</a>';
					$actions['delete'] = $html;
				}
			}
		}
		
		// Add AYS question to Delete Permanently link
		if ( get_option( 'trashmgr_post_ays_delete' ) && isset( $actions['delete'] ) ) {
			$msg = sprintf( ('draft' == $post->post_status) ?
				__("You are about to delete this draft '%s'\n 'Cancel' to stop, 'OK' to delete.", 'trash-manager') : 
				__("You are about to delete this post '%s'\n 'Cancel' to stop, 'OK' to delete.", 'trash-manager'),
				$post->post_title );
			$actions['delete'] = $this->add_AYS_code( $actions['delete'], $msg );
		}
		
		// Add AYS question to Trash link
		if ( get_option( 'trashmgr_post_ays_trash' ) && isset( $actions['trash'] ) ) {
			$msg = sprintf( 
				__("You are about to trash this post '%s'\n 'Cancel' to stop, 'OK' to trash.", 'trash-manager'),
				$post->post_title );
			$actions['trash'] = $this->add_AYS_code( $actions['trash'], $msg );
		}
		
		// Add AYS question to Restore link
		if ( get_option( 'trashmgr_post_ays_untrash' ) && isset( $actions['untrash'] ) ) {
			$msg = sprintf( 
				__("You are about to restore this post '%s'\n 'Cancel' to stop, 'OK' to restore.", 'trash-manager'),
				$post->post_title );
			$actions['untrash'] = $this->add_AYS_code( $actions['untrash'], $msg );
		}
		
		return $actions;
	}
	
	// Add actions to each page on Page List
	function page_row_actions( $actions, $page ) {
		// Add 'Delete permanently' to action list
		if ( get_option( 'trashmgr_page_add_delete' ) && current_user_can('delete_page', $page->ID) 
			&& isset( $actions['trash'] ) && !isset( $actions['delete'] ) ) {
			$temp_actions = $actions;
			$actions = array();
			foreach ( $temp_actions as $name => $html ) {
				$actions[$name] = $html;
				if ( $name == 'trash' ) {
					$html = '<a class="submitdelete" title="' . 
						esc_attr(__('Delete this page permanently', 'trash-manager')) . '" href="' . 
						wp_nonce_url('page.php?action=delete&amp;post='.$page->ID, 'delete-page_'.$page->ID) .
						'">' . __('Delete Permanently', 'trash-manager') . '</a>';
					$actions['delete'] = $html;
				}
			}
		}
		
		// Add AYS question to Delete Permanently link
		if ( get_option( 'trashmgr_page_ays_delete' ) && isset( $actions['delete'] ) ) {
			$msg = sprintf( ('draft' == $page->post_status) ?
				__("You are about to delete this draft '%s'\n 'Cancel' to stop, 'OK' to delete.", 'trash-manager') : 
				__("You are about to delete this page '%s'\n 'Cancel' to stop, 'OK' to delete.", 'trash-manager'),
				$page->post_title );
			$actions['delete'] = $this->add_AYS_code( $actions['delete'], $msg );
		}
		
		// Add AYS question to Trash link
		if ( get_option( 'trashmgr_page_ays_trash' ) && isset( $actions['trash'] ) ) {
			$msg = sprintf( 
				__("You are about to trash this page '%s'\n 'Cancel' to stop, 'OK' to trash.", 'trash-manager'),
				$page->post_title );
			$actions['trash'] = $this->add_AYS_code( $actions['trash'], $msg );
		}
		
		// Add AYS question to Restore link
		if ( get_option( 'trashmgr_page_ays_untrash' ) && isset( $actions['untrash'] ) ) {
			$msg = sprintf( 
				__("You are about to restore this page '%s'\n 'Cancel' to stop, 'OK' to restore.", 'trash-manager'),
				$page->post_title );
			$actions['untrash'] = $this->add_AYS_code( $actions['untrash'], $msg );
		}
		
		return $actions;
	}
	
	// Add actions to each comment on Comment List
	function comment_row_actions( $actions, $comment ) {
		// Add 'Delete permanently' to action list
		$post = get_post($comment->comment_post_ID);
		if ( get_option( 'trashmgr_comment_add_delete' ) && current_user_can('edit_post', $post->ID) 
			&& isset( $actions['trash'] ) && !isset( $actions['delete'] ) ) {
			$temp_actions = $actions;
			$actions = array();
			foreach ( $temp_actions as $name => $html ) {
				$actions[$name] = $html;
				if ( $name == 'trash' ) {
					$del_nonce = esc_html( '_wpnonce='.wp_create_nonce( 'delete-comment_'.$comment->comment_ID ) );
					$delete_url = esc_url( 'comment.php?action=deletecomment&p='.$post->ID
						.'&c='.$comment->comment_ID.'&'.$del_nonce );
					$html = '<a href="'.$delete_url.'" class="delete:the-comment-list:comment-' .
						$comment->comment_ID.'::delete=1 delete vim-d vim-destructive">' . 
						__('Delete Permanently', 'trash-manager') . '</a>';
					$actions['delete'] = $html;
				}
			}
		}
		
		return $actions;
	}
	
	// Add 'Are You Sure?' JS code to HTML link
	function add_AYS_code( $html, $message ) {
		return str_replace( '<a ', '<a onclick="if(confirm(\'' . esc_js( $message ) . 
			'\')){return true;};return false;" ', $html );
	}
	
	// Delete post/page after it gets trashed instead of deleted
	function trashed_post( $post_ID ) {
		global $action;
		if ( isset( $action ) && ( $action == 'delete' ) ) {
			wp_delete_post( $post_ID, true );
		}
	}
	
	// Delete comment after it gets trashed instead of deleted
	function trashed_comment( $comment_ID ) {
		global $action;
		if ( isset( $action ) ) {
			if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
				if ( ( $action == 'delete-comment' ) && isset( $_POST['delete'] ) && ($_POST['delete'] == 1 ) ) {
					// Workaround: WP does not clear temporary variable after changing comment status
					if ( isset( $GLOBALS['comment'] ) && ( $GLOBALS['comment']->comment_ID == $comment_id ) ) {
						unset( $GLOBALS['comment'] );
					}
					wp_delete_comment( $comment_ID, true );
				}
			} else {
				if ( $action == 'deletecomment' ) {
					// Workaround: WP does not clear temporary variable after changing comment status
					if ( isset( $GLOBALS['comment'] ) && ( $GLOBALS['comment']->comment_ID == $comment_id ) ) {
						unset( $GLOBALS['comment'] );
					}
					wp_delete_comment( $comment_ID, true );
				}
			}
		}
	}
	
	// Handle options panel
	function options_panel() {
?>
<div class="wrap">
<?php screen_icon(); ?>
<h2><?php _e('Trash Manager - Options', 'trash-manager'); ?></h2>

<form name="dofollow" action="options.php" method="post">
<?php settings_fields( 'trash-manager' ); ?>
<table class="form-table">

<!-- Posts -->
<tr><th colspan="2"><h3><?php _e('Posts:', 'trash-manager'); ?></h3></th></tr>

<tr>
<th scope="row" style="text-align:right; vertical-align:top;">
<label for="trashmgr_post_add_delete"><?php _e('Add \'Delete Permanently\' action', 'trash-manager'); ?>: </label>
</th>
<td>
<input type="checkbox" id="trashmgr_post_add_delete" name="trashmgr_post_add_delete" value="yes" <?php checked( true, get_option( 'trashmgr_post_add_delete' ) ); ?> />
</td>
</tr>

<tr>
<th scope="row" style="text-align:right; vertical-align:top;">
<label for="trashmgr_post_ays_delete"><?php _e('Show \'Are you sure?\' question for \'Delete Permanently\' action', 'trash-manager'); ?>: </label>
</th>
<td>
<input type="checkbox" id="trashmgr_post_ays_delete" name="trashmgr_post_ays_delete" value="yes" <?php checked( true, get_option( 'trashmgr_post_ays_delete' ) ); ?> />
</td>
</tr>

<tr>
<th scope="row" style="text-align:right; vertical-align:top;">
<label for="trashmgr_post_ays_trash"><?php _e('Show \'Are you sure?\' question for \'Trash\' action', 'trash-manager'); ?>: </label>
</th>
<td>
<input type="checkbox" id="trashmgr_post_ays_trash" name="trashmgr_post_ays_trash" value="yes" <?php checked( true, get_option( 'trashmgr_post_ays_trash' ) ); ?> />
</td>
</tr>

<tr>
<th scope="row" style="text-align:right; vertical-align:top;">
<label for="trashmgr_post_ays_untrash"><?php _e('Show \'Are you sure?\' question for \'Restore\' action', 'trash-manager'); ?>: </label>
</th>
<td>
<input type="checkbox" id="trashmgr_post_ays_untrash" name="trashmgr_post_ays_untrash" value="yes" <?php checked( true, get_option( 'trashmgr_post_ays_untrash' ) ); ?> />
</td>
</tr>

<!-- Pages -->
<tr><th colspan="2"><h3><?php _e('Pages:', 'trash-manager'); ?></h3></th></tr>

<tr>
<th scope="row" style="text-align:right; vertical-align:top;">
<label for="trashmgr_page_add_delete"><?php _e('Add \'Delete Permanently\' action', 'trash-manager'); ?>: </label>
</th>
<td>
<input type="checkbox" id="trashmgr_page_add_delete" name="trashmgr_page_add_delete" value="yes" <?php checked( true, get_option( 'trashmgr_page_add_delete' ) ); ?> />
</td>
</tr>

<tr>
<th scope="row" style="text-align:right; vertical-align:top;">
<label for="trashmgr_page_ays_delete"><?php _e('Show \'Are you sure?\' question for \'Delete Permanently\' action', 'trash-manager'); ?>: </label>
</th>
<td>
<input type="checkbox" id="trashmgr_page_ays_delete" name="trashmgr_page_ays_delete" value="yes" <?php checked( true, get_option( 'trashmgr_page_ays_delete' ) ); ?> />
</td>
</tr>

<tr>
<th scope="row" style="text-align:right; vertical-align:top;">
<label for="trashmgr_page_ays_trash"><?php _e('Show \'Are you sure?\' question for \'Trash\' action', 'trash-manager'); ?>: </label>
</th>
<td>
<input type="checkbox" id="trashmgr_page_ays_trash" name="trashmgr_page_ays_trash" value="yes" <?php checked( true, get_option( 'trashmgr_page_ays_trash' ) ); ?> />
</td>
</tr>

<tr>
<th scope="row" style="text-align:right; vertical-align:top;">
<label for="trashmgr_page_ays_untrash"><?php _e('Show \'Are you sure?\' question for \'Restore\' action', 'trash-manager'); ?>: </label>
</th>
<td>
<input type="checkbox" id="trashmgr_page_ays_untrash" name="trashmgr_page_ays_untrash" value="yes" <?php checked( true, get_option( 'trashmgr_page_ays_untrash' ) ); ?> />
</td>
</tr>

<!-- Comments -->
<tr><th colspan="2"><h3><?php _e('Comments:', 'trash-manager'); ?></h3></th></tr>

<tr>
<th scope="row" style="text-align:right; vertical-align:top;">
<label for="trashmgr_comment_add_delete"><?php _e('Add \'Delete Permanently\' action', 'trash-manager'); ?>: </label>
</th>
<td>
<input type="checkbox" id="trashmgr_comment_add_delete" name="trashmgr_comment_add_delete" value="yes" <?php checked( true, get_option( 'trashmgr_comment_add_delete' ) ); ?> />
</td>
</tr>

<tr>
<th scope="row" style="text-align:right; vertical-align:top;">
<label for="trashmgr_comment_ays_delete"><?php _e('Show \'Are you sure?\' question for \'Delete Permanently\' action', 'trash-manager'); ?>: </label>
</th>
<td>
<input type="checkbox" id="trashmgr_comment_ays_delete" name="trashmgr_comment_ays_delete" value="yes" <?php checked( true, get_option( 'trashmgr_comment_ays_delete' ) ); ?> />
</td>
</tr>

<tr>
<th scope="row" style="text-align:right; vertical-align:top;">
<label for="trashmgr_comment_ays_trash"><?php _e('Show \'Are you sure?\' question for \'Trash\' action', 'trash-manager'); ?>: </label>
</th>
<td>
<input type="checkbox" id="trashmgr_comment_ays_trash" name="trashmgr_comment_ays_trash" value="yes" <?php checked( true, get_option( 'trashmgr_comment_ays_trash' ) ); ?> />
</td>
</tr>

<tr>
<th scope="row" style="text-align:right; vertical-align:top;">
<label for="trashmgr_comment_ays_untrash"><?php _e('Show \'Are you sure?\' question for \'Restore\' action', 'trash-manager'); ?>: </label>
</th>
<td>
<input type="checkbox" id="trashmgr_comment_ays_untrash" name="trashmgr_comment_ays_untrash" value="yes" <?php checked( true, get_option( 'trashmgr_comment_ays_untrash' ) ); ?> />
</td>
</tr>

<!-- Bulk Actions -->
<tr><th colspan="2"><h3><?php _e('Bulk Actions:', 'trash-manager'); ?></h3></th></tr>

<tr><th colspan="2"><?php _e('Bulk actions allows to perform single action for all checked items. They are shared between posts, pages, etc., so there are no separate options for each of these items.', 'trash-manager'); ?></th></tr>

<?php /*
<tr>
<th scope="row" style="text-align:right; vertical-align:top;">
<label for="trashmgr_bulk_add_delete"><?php _e('Add \'Delete Permanently\' action', 'trash-manager'); ?>: </label>
</th>
<td>
<input type="checkbox" id="trashmgr_bulk_add_delete" name="trashmgr_bulk_add_delete" value="yes" <?php checked( true, get_option( 'trashmgr_bulk_add_delete' ) ); ?> />
</td>
</tr>
*/ ?>

<tr>
<th scope="row" style="text-align:right; vertical-align:top;">
<label for="trashmgr_bulk_ays_delete"><?php _e('Show \'Are you sure?\' question for \'Delete Permanently\' action', 'trash-manager'); ?>: </label>
</th>
<td>
<input type="checkbox" id="trashmgr_bulk_ays_delete" name="trashmgr_bulk_ays_delete" value="yes" <?php checked( true, get_option( 'trashmgr_bulk_ays_delete' ) ); ?> />
</td>
</tr>

<tr>
<th scope="row" style="text-align:right; vertical-align:top;">
<label for="trashmgr_bulk_ays_trash"><?php _e('Show \'Are you sure?\' question for \'Trash\' action', 'trash-manager'); ?>: </label>
</th>
<td>
<input type="checkbox" id="trashmgr_bulk_ays_trash" name="trashmgr_bulk_ays_trash" value="yes" <?php checked( true, get_option( 'trashmgr_bulk_ays_trash' ) ); ?> />
</td>
</tr>

<tr>
<th scope="row" style="text-align:right; vertical-align:top;">
<label for="trashmgr_bulk_ays_untrash"><?php _e('Show \'Are you sure?\' question for \'Restore\' action', 'trash-manager'); ?>: </label>
</th>
<td>
<input type="checkbox" id="trashmgr_bulk_ays_untrash" name="trashmgr_bulk_ays_untrash" value="yes" <?php checked( true, get_option( 'trashmgr_bulk_ays_untrash' ) ); ?> />
</td>
</tr>

</table>

<p class="submit">
<input type="submit" name="Submit" value="<?php _e('Save settings', 'trash-manager'); ?>" /> 
</p>

</form>
</div>
<?php
	}
	
	// Sanitise function for boolean options
	function sanitise_bool( $value ) {
		return isset( $value ) && ( $value == 'yes' );
	}
}

add_option( 'trashmgr_post_add_delete', true ); // Add 'Delete Permanently' action for Posts
add_option( 'trashmgr_post_ays_delete', true ); // Show 'Are you sure?' question for 'Delete Permanently' action for Posts
add_option( 'trashmgr_post_ays_trash', true ); // Show 'Are you sure?' question for 'Trash' action for Posts
add_option( 'trashmgr_post_ays_untrash', true ); // Show 'Are you sure?' question for 'Restore' action for Posts

add_option( 'trashmgr_page_add_delete', true ); // Add 'Delete Permanently' action for Pages
add_option( 'trashmgr_page_ays_delete', true ); // Show 'Are you sure?' question for 'Delete Permanently' action for Pages
add_option( 'trashmgr_page_ays_trash', true ); // Show 'Are you sure?' question for 'Trash' action for Pages
add_option( 'trashmgr_page_ays_untrash', true ); // Show 'Are you sure?' question for 'Restore' action for Pages

add_option( 'trashmgr_comment_add_delete', true ); // Add 'Delete Permanently' action for Comments
add_option( 'trashmgr_comment_ays_delete', true ); // Show 'Are you sure?' question for 'Delete Permanently' action for Comments
add_option( 'trashmgr_comment_ays_trash', true ); // Show 'Are you sure?' question for 'Trash' action for Comments
add_option( 'trashmgr_comment_ays_untrash', true ); // Show 'Are you sure?' question for 'Restore' action for Comments

//add_option( 'trashmgr_bulk_add_delete', true ); // Add 'Delete Permanently' action to bulk action list
add_option( 'trashmgr_bulk_ays_delete', true ); // Show 'Are you sure?' question for 'Delete Permanently' action on bulk action list
add_option( 'trashmgr_bulk_ays_trash', true ); // Show 'Are you sure?' question for 'Trash' action on bulk action list
add_option( 'trashmgr_bulk_ays_untrash', true ); // Show 'Are you sure?' question for 'Restore' action on bulk action list

$wp_trash_manager = new TrashManager();

} /* END */

?>