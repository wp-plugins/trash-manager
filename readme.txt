=== Plugin Name ===
Contributors: sirzooro
Tags: trash, delete, restore, admin, post, posts, page, pages, comment, comments
Requires at least: 2.9
Tested up to: 2.9.9
Stable tag: 1.0

This plugin allows you to delete Posts, Pages and Comments without moving them to Trash first. Additionally it restores all Are you sure? questions.

== Description ==

WordPress 2.9 introduced compete new functionality - Trash. By default it is not possible to directly delete posts, pages and comments - you have to move them to Trash first, and then delete them. This is of course helpful for novice users, but for advanced ones this is only an obstacle.

It is possible to disable Trash completely by defining `EMPTY_TRASH_DAYS` to 0 in your `wp-config.php` file. This works, but is not perfect - in WordPress 2.9 all 'Are you sure?' dialogs are removed. Therefore you have to be more careful than before when you click somewhere.

In order to resolve above issues, I wrote Trash Manager plugin. It adds 'Delete Permanently' link to post, page and comments list, so you can delete them directly without moving them to Trash first. It also restores 'Are you sure?' dialogs, so you do not have to take extra care when you click.

This version does not provide configuration options - I plan to add them in next version.

[Changelog](http://wordpress.org/extend/plugins/trash-manager/changelog/)

== Installation ==

1. Upload `trash-manager` directory to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Configure and enjoy :)

== Changelog ==

= 1.0 =
* Initial version
