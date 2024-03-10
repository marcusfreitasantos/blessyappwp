<?php
function registerUsersFromApp($req){
	$userFullName = $req['userFullName'];
	$username = generateUniqueUsername($req['username']);
	$userPassword = $req['userPass'];
	$userEmail = $req['userEmail'];

	if($username){;
		$newUserId = wp_create_user($username, $userPassword, $userEmail);
	
		if(is_wp_error( $newUserId )){
			$errorMsg = $newUserId->get_error_message();
			return new WP_Error( 'not_found', $errorMsg, array( 'status' => 401 ) );
		
		}else{
			wp_update_user( array( 'ID' => $newUserId, 'first_name' => $userFullName ) );
			return "Success: User registered";
		}
	}

}



add_action( 'rest_api_init', function () {
  register_rest_route( 'blessyapp/v2', '/users', array(
    'methods' => 'POST',
    'callback' => 'registerUsersFromApp',
  ) );
} );



function generateUniqueUsername($username) {

	$username = sanitize_title( $username );

	static $i;
	if ( null === $i ) {
		$i = 1;
	} else {
		$i ++;
	}
	if ( ! username_exists( $username ) ) {
		return $username;
	}
	$new_username = sprintf( '%s-%s', $username, $i );
	if ( ! username_exists( $new_username ) ) {
		return $new_username;
	} else {
		return call_user_func( __FUNCTION__, $username );
	}
}


function sendUserDataAfterJWTAuth($jwt, $user){
    $userAvatarUrl = wp_get_attachment_image_url( get_user_meta($user->ID, "avatar", true), "medium" );
	if(!in_array('church', $user->roles)){
		$data = array(
			'token' => $jwt['token'],
			'userID' => $user->ID,
			'email' => $user->user_email,
			'firstName' => $user->first_name,
			'lastName' => $user->last_name,
			'avatar' => $userAvatarUrl
		);
		return $data;
	}else{
		return new WP_Error( 'forbidden', "User not allowed.", array( 'status' => 403 ) );
	}
}
add_filter('jwt_auth_token_before_dispatch', 'sendUserDataAfterJWTAuth', 10, 2);


function updateUserById($req){
	$reqBody = json_decode(file_get_contents('php://input'));
	$userData = [
		'ID' => $req['id'],
		'user_email' => $reqBody->email,
		'first_name' => $reqBody->firstName,
		'last_name' => $reqBody->lastName,
		'user_pass' => $reqBody->userPass,
	];

	if ( is_wp_error( wp_update_user($userData) ) ) {
    	return new WP_Error( 'not_found', "User couldn't be updated.", array( 'status' => 404 ) );
	} else {
		$userNewData = [
			'userID' => $req['id'],
			'email' => $reqBody->email,
			'firstName' => $reqBody->firstName,
			'lastName' => $reqBody->lastName,
		];
		return rest_ensure_response($userNewData);
	}
}

add_action( 'rest_api_init', function () {
  register_rest_route( 'blessyapp/v2', '/users/(?P<id>\d+)', array(
    'methods' => 'POST',
    'callback' => 'updateUserById',
  ) );
} );


function getUserBookmarks($req){
	$userId = $req['id'];
	$userBookmarkedChurches = get_user_meta($userId, 'user_bookmarked_churches', true);
	
	if($userBookmarkedChurches){
		return rest_ensure_response($userBookmarkedChurches);
	}else{
		return new WP_Error( 'not_found', "No bookmarks found.", array( 'status' => 404 ) );
	}
}


add_action( 'rest_api_init', function () {
  register_rest_route( 'blessyapp/v2', '/users/(?P<id>\d+)/bookmark', array(
    'methods' => 'GET',
    'callback' => 'getUserBookmarks',
  ) );
} );


function saveUserBookmarks($req){
	$userId = $req['id'];
	$reqBody = json_decode(file_get_contents('php://input'));
	
	if(get_user_by('id', $userId)){
		$userBookmarkedChurches = get_user_meta($userId, 'user_bookmarked_churches', true);
		if($userBookmarkedChurches){
			foreach($userBookmarkedChurches as $churchId){
				if(!in_array($reqBody->churchId, $userBookmarkedChurches)){
					$userBookmarkedChurches[] =  $reqBody->churchId;
				}
			}		
		}else{
			$userBookmarkedChurches = [$reqBody->churchId];
		}

		update_user_meta($userId, 'user_bookmarked_churches', $userBookmarkedChurches);
		rest_ensure_response($userBookmarkedChurches);
	}else{
		return new WP_Error( 'not_found', "User not found.", array( 'status' => 404 ) );
	}
}


add_action( 'rest_api_init', function () {
  register_rest_route( 'blessyapp/v2', '/users/(?P<id>\d+)/bookmark', array(
    'methods' => 'POST',
    'callback' => 'saveUserBookmarks',
  ) );
} );


function removeUserChurchBookmarks($req){
	$userId = $req['id'];
	$reqBody = json_decode(file_get_contents('php://input'));
	
	if(get_user_by('id', $userId)){
		$userBookmarkedChurches = get_user_meta($userId, 'user_bookmarked_churches', true);
		$newUserBookmarkedChurches = [];

		if($userBookmarkedChurches){
			foreach ($userBookmarkedChurches as $churchId) {
				if($churchId !== $reqBody->churchId){
					$newUserBookmarkedChurches[] = $churchId;
				}
			}
			
			update_user_meta($userId, 'user_bookmarked_churches', $newUserBookmarkedChurches);
			return rest_ensure_response($newUserBookmarkedChurches);
		}
	}else{
		return new WP_Error( 'not_found', "User not found.", array( 'status' => 404 ) );
	}
}


add_action( 'rest_api_init', function () {
  register_rest_route( 'blessyapp/v2', '/users/(?P<id>\d+)/bookmark', array(
    'methods' => 'PUT',
    'callback' => 'removeUserChurchBookmarks',
  ) );
} );