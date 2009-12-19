<?php
/*
Plugin Name: Trash Manager
Plugin URI: http://www.poradnik-webmastera.com/projekty/trash_manager/
Description: This plugin allows you to delete Posts, Pages and Comments without moving them to Trash first. Additionally it restores all 'Are you sure?' questions when you try to delete, trash or restore something.
Author: Daniel Frużyński
Version: 1.0
Author URI: http://www.poradnik-webmastera.com/
Text Domain: trash-manager
*/

/*  Copyright 2009  Daniel Frużyński  (email : daniel [A-T] poradnik-webmastera.com)

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

define( 'TRASH_MGR_VER', '1.0' );

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
			
			// Load additional JS script
			wp_enqueue_script( 'trash-manager', 
				WP_PLUGIN_URL.'/'.dirname( plugin_basename( __FILE__ ) ).'/trash-manager.js',
				array( 'common' ), TRASH_MGR_VER );
			wp_localize_script( 'trash-manager', 'trashMgrL10n', array(
				'bulkDelete' => __("You are about to delete the selected items.\n  'Cancel' to stop, 'OK' to delete.", 'trash-manager'),
				'bulkTrash' => __("You are about to trash the selected items.\n  'Cancel' to stop, 'OK' to trash.", 'trash-manager'),
				'bulkUntrash' => __("You are about to restore the selected items.\n  'Cancel' to stop, 'OK' to restore.", 'trash-manager'),
				'commentDelete' => __("You are about to delete this comment\n 'Cancel' to stop, 'OK' to delete.", 'trash-manager'),
				'commentTrash' => __("You are about to trash this comment\n 'Cancel' to stop, 'OK' to trash.", 'trash-manager'),
				'commentUntrash' => __("You are about to restore this comment\n 'Cancel' to stop, 'OK' to restore.", 'trash-manager'),
			) );
		}
	}
	
	// Initialise plugin
	function init() {
		load_plugin_textdomain( 'trash-manager', WP_PLUGIN_DIR.'/'.dirname( plugin_basename( __FILE__ ) ) );
	}
	
	// Initialise plugin - admin part
	function admin_init() {
	}
	
	// Add Admin menu option
	function admin_menu() {
		add_submenu_page( 'options-general.php', 'Trash Manager', 
			'Trash Manager', 10, __FILE__, array( $this, 'options_panel' ) );
	}
	
	// Add actions to each post on Post List
	function post_row_actions( $actions, $post ) {
		// Add 'Delete permanently' to action list
		if ( true && current_user_can('delete_post', $post->ID) 
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
		if ( true && isset( $actions['delete'] ) ) {
			$msg = sprintf( ('draft' == $post->post_status) ?
				__("You are about to delete this draft '%s'\n 'Cancel' to stop, 'OK' to delete.", 'trash-manager') : 
				__("You are about to delete this post '%s'\n 'Cancel' to stop, 'OK' to delete.", 'trash-manager'),
				$post->post_title );
			$actions['delete'] = $this->add_AYS_code( $actions['delete'], $msg );
		}
		
		// Add AYS question to Trash link
		if ( true && isset( $actions['trash'] ) ) {
			$msg = sprintf( 
				__("You are about to trash this post '%s'\n 'Cancel' to stop, 'OK' to trash.", 'trash-manager'),
				$post->post_title );
			$actions['trash'] = $this->add_AYS_code( $actions['trash'], $msg );
		}
		
		// Add AYS question to Restore link
		if ( true && isset( $actions['untrash'] ) ) {
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
		if ( true && current_user_can('delete_page', $page->ID) 
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
		if ( true && isset( $actions['delete'] ) ) {
			$msg = sprintf( ('draft' == $page->post_status) ?
				__("You are about to delete this draft '%s'\n 'Cancel' to stop, 'OK' to delete.", 'trash-manager') : 
				__("You are about to delete this page '%s'\n 'Cancel' to stop, 'OK' to delete.", 'trash-manager'),
				$page->post_title );
			$actions['delete'] = $this->add_AYS_code( $actions['delete'], $msg );
		}
		
		// Add AYS question to Trash link
		if ( true && isset( $actions['trash'] ) ) {
			$msg = sprintf( 
				__("You are about to trash this page '%s'\n 'Cancel' to stop, 'OK' to trash.", 'trash-manager'),
				$page->post_title );
			$actions['trash'] = $this->add_AYS_code( $actions['trash'], $msg );
		}
		
		// Add AYS question to Restore link
		if ( true && isset( $actions['untrash'] ) ) {
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
		if ( true && current_user_can('edit_post', $post->ID) 
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
	
	// Handle options panel
	function options_panel() {
?>
<div id="dropmessage" class="updated" style="display:none;"></div>
<div class="wrap">
<h2>Trash Manager</h2>

<p>No configuration yet - I plan to add it in next version.</p>

</div>
<?php
	}
}

$wp_trash_manager = new TrashManager();

} /* END */

?>