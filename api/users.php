<?php
//REGISTER USER
function registerUsersFromApp($req){
	$userFullName = $req['userFullName'];
	$username = generateUniqueUsername($req['username']);
	$userPassword = $req['userPass'];
	$userEmail = $req['userEmail'];

	if($username){;
		$newUserId = wp_create_user($username, $userPassword, $userEmail);
	
		if(is_wp_error( $newUserId )){
			$errorMsg = $newUserId->get_error_message();
			return "Error: $errorMsg";
		
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

//GET CHURCHES LIST
function getAllChurches(){
    $allChurches = get_users( array( 'role__in' => array( 'church') ) );
    $allChurchesWithCustomFields = [];

    foreach($allChurches as $church){
        $churchAddress = get_user_meta($church->id, "church_address", true);
        $churchLogoID = get_user_meta($church->id, "church_logo", true);
        $churchLogoUrl = wp_get_attachment_image_url( $churchLogoID, "large" );

        $allChurchesWithCustomFields[] = [
            "id" => $church->id,
            "name" => $church->first_name,
            "address" => $churchAddress,
            "logo" => $churchLogoUrl
        ];

    }

    return $allChurchesWithCustomFields;
}

add_action( 'rest_api_init', function () {
  register_rest_route( 'blessyapp/v2', '/church', array(
    'methods' => 'GET',
    'callback' => 'getAllChurches',
  ) );
} );


//GET CHURCH BY ID
function getChurchById($reqData){
	$churchId = $reqData["id"];
    $currentChurch = get_user_by("id", $churchId);

	if($currentChurch){
		$churchDescription = get_user_meta($churchId, "church_description", true);
		$churchAddress = get_user_meta($churchId, "church_address", true);
		
		$churchLogoID = get_user_meta($churchId, "church_logo", true);
		$churchLogoUrl = wp_get_attachment_image_url( $churchLogoID, "large" );
	
			
		$churchCoverImgID = get_user_meta($churchId, "church_cover_img", true);
		$churchCoverImg = wp_get_attachment_image_url( $churchCoverImgID, "large" );
	
		$churchData = [
			"id" => $churchId,
			"name" => $currentChurch->first_name,
			"address" => $churchAddress,
			"description" => $churchDescription,
			"logo" => $churchLogoUrl,
			"cover_img" => $churchCoverImg
		];
	
		return $churchData;
	}else{
		return "User not found";
	}
}

add_action( 'rest_api_init', function () {
  register_rest_route( 'blessyapp/v2', '/church/(?P<id>\d+)', array(
    'methods' => 'GET',
    'callback' => 'getChurchById',
  ) );
} );