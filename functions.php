<?php
/**
 * OceanWP Child Theme Functions
 *
 * When running a child theme (see http://codex.wordpress.org/Theme_Development
 * and http://codex.wordpress.org/Child_Themes), you can override certain
 * functions (those wrapped in a function_exists() call) by defining them first
 * in your child theme's functions.php file. The child theme's functions.php
 * file is included before the parent theme's file, so the child theme
 * functions will be used.
 *
 * Text Domain: oceanwp
 * @link http://codex.wordpress.org/Plugin_API
 *
 */

/**
 * Load the parent style.css file
 *
 * @link http://codex.wordpress.org/Child_Themes
 */
function oceanwp_child_enqueue_parent_style() {

	$theme   = wp_get_theme( 'OceanWP' );
	$version = $theme->get( 'Version' );

	wp_enqueue_style( 'child-style', get_stylesheet_directory_uri() . '/style.css', array( 'oceanwp-style' ), $version );
	
}

add_action( 'wp_enqueue_scripts', 'oceanwp_child_enqueue_parent_style' );


add_action( 'admin_enqueue_scripts', function(){
	wp_enqueue_style( 'admin-custom', get_stylesheet_directory_uri() . '/admin-style.css' );
} );


require_once("api/users.php");
require_once("api/churches.php");



function addAuthorUserRoleOnUserRegistration($userId, $feed, $entry, $form){
	$newUser = get_user_by('id', $userId);

	if($newUser && in_array('church', $newUser->roles)){
		$newUser->add_role( 'author' );
	}
}

add_action('fluentform/user_registration_completed', 'addAuthorUserRoleOnUserRegistration', 10, 4);


function changeUserRegisterUrl( $url ) {
    if( is_admin() ) {
    	return $url;
    }
    return '/cadastro-igreja/';
}
add_filter( 'register_url', 'changeUserRegisterUrl' );


function userRestrictMediaLibrary(  $query ) {
    $currentUserId = get_current_user_id();
	if(!current_user_can('administrator')){
		$query['author'] = $currentUserId ;
	}
    return $query;
}
add_filter( 'ajax_query_attachments_args', "userRestrictMediaLibrary" );



function showUserOwnPosts($query) {
    global $pagenow;
    if (is_admin() && !current_user_can('administrator') && 'edit.php' === $pagenow) {
        $currentUserId = get_current_user_id();
        $query->set('author', $currentUserId);
    }
}
add_action('pre_get_posts', 'showUserOwnPosts');


function hideMenuItemsForUsers(){
	if(is_user_logged_in() && !current_user_can('administrator')){
		
		global $menu;
	
		$menuItemstoHide = [
			"index.php",
			"edit.php",
			"edit-comments.php",
			"tools.php",
			"link-manager.php"
		];
	
		foreach($menu as $menuItem){
			if(in_array($menuItem[2], $menuItemstoHide)){
				remove_menu_page($menuItem[2]);
			}
		}
	}
}
add_action( 'admin_menu', 'hideMenuItemsForUsers' );


