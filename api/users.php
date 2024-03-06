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
	$data = array(
		'token' => $jwt['token'],
		'userID' => $user->ID,
		'email' => $user->user_email,
		'firstName' => $user->first_name,
		'lastName' => $user->last_name,
	);
	return $data;
}
add_filter('jwt_auth_token_before_dispatch', 'sendUserDataAfterJWTAuth', 10, 2);


function updateUserById(){
	$reqBody = json_decode(file_get_contents('php://input'));
	$userData = wp_update_user([
		'ID' => $reqBody->id,
		'user_email' => $reqBody->email,
		'first_name' => $reqBody->firstName,
		'last_name' => $reqBody->lastName,
		'user_pass' => $reqBody->password,
	]);

	if ( is_wp_error( $userData ) ) {
    	return new WP_Error( 'not_found', "User couldn't be updated.", array( 'status' => 404 ) );
	} else {
		return rest_ensure_response('User profile updated.');
	}
}

add_action( 'rest_api_init', function () {
  register_rest_route( 'blessyapp/v2', '/users/(?P<id>\d+)', array(
    'methods' => 'POST',
    'callback' => 'updateUserById',
  ) );
} );