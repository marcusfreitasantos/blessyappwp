<?php

function customFormatDate($date){
  $formatDate = date_i18n("d \d\\e F, Y", strtotime($date));
  return $formatDate;
}

function getAllChurches(){
    $allChurches = get_users( array( 'role__in' => array( 'church') ) );
    $allChurchesWithCustomFields = [];

    if($allChurches){
      foreach($allChurches as $church){
          $churchAddress = get_user_meta($church->id, "church_address", true);
          $churchLogoID = get_user_meta($church->id, "church_logo", true);
          $churchCurrentFollowers = get_user_meta($church->id, "church_current_followers", true);
          $churchLogoUrl = wp_get_attachment_image_url( $churchLogoID, "large" );
  
          $allChurchesWithCustomFields[] = [
              "id" => $church->id,
              "name" => $church->first_name,
              "address" => $churchAddress,
              "logo" => $churchLogoUrl ? $churchLogoUrl : $churchLogoID,
              "totalFollowers" => $churchCurrentFollowers ? sizeof($churchCurrentFollowers) : 0
          ];  
      }
  
      return rest_ensure_response($allChurchesWithCustomFields);
    }else{
      return new WP_Error( 'not_found', 'No churches found.', array( 'status' => 404 ) );
    }
}





function getChurchById($reqData){
	$churchId = $reqData["id"];
    $currentChurch = get_user_by("id", $churchId);

	if($currentChurch){
		$churchDescription = get_user_meta($churchId, "church_description", true);
		$churchAddress = get_user_meta($churchId, "church_address", true);
    $churchCity = get_user_meta($churchId, "church_city", true);
    $churchState = get_user_meta($churchId, "church_state", true);
		$churchLogoID = get_user_meta($churchId, "church_logo", true);
    $churchHours= get_user_meta($churchId, "church_hours", true);
    $churchStaff= get_field("church_staff", "user_$churchId");
    $churchContacts= get_field("church_contacts", "user_$churchId");
    $churchSocialMedia= get_field("church_social_media", "user_$churchId");

    $churchStaffGroup = [];


    if($churchStaff){
      foreach($churchStaff as $staff){
        $churchStaffGroup[] = $staff;
      }       
    }


    $churchCurrentFollowers = get_user_meta($churchId, "church_current_followers", true);
		$churchLogoUrl = wp_get_attachment_image_url( $churchLogoID, "large" );
	
			
		$churchCoverImgID = get_user_meta($churchId, "church_cover_img", true);
		$churchCoverImg = wp_get_attachment_image_url( $churchCoverImgID, "large" );
	
		$churchData = [
			"id" => $churchId,
			"name" => $currentChurch->first_name,
			"address" => $churchAddress,
      "city" => $churchCity,
      "state" => $churchState,
			"description" => $churchDescription,
      "hours" => $churchHours,
      "contact" => $churchContacts,
      "staff" => $churchStaffGroup,
      "socialMedia" => $churchSocialMedia,
			"logo" => $churchLogoUrl ? $churchLogoUrl : $churchLogoID,
			"coverImg" => $churchCoverImg ? $churchCoverImg : $churchCoverImgID,
      "totalFollowers" => $churchCurrentFollowers ? sizeof($churchCurrentFollowers) : 0
		];
	
		return rest_ensure_response($churchData);
	}else{
    return new WP_Error( 'not_found', 'No church found with this ID.', array( 'status' => 404 ) );
	}
}





function getChurchContent($reqData){
  $postType = $reqData['content'];

  $posts = get_posts(array(
    'post_type' => $postType,
    'author' => $reqData['id'],
    'post_status' => 'publish',
    'numberposts'      => 50
  ));

  $formatedPosts = [];

  if($posts){
    foreach($posts as $post){
      if($postType === "event"){
        $formatedPosts[] = [
          "id" => $post->ID,
          "churchId" => $post->post_author,
          "postDate" => customFormatDate($post->post_date),
          "postTitle" => $post->post_title,
          "postExcerpt" => sanitize_text_field(get_field('event_excerpt', $post->ID)),
          "eventStartDate" => customFormatDate(get_field('event_start_date',$post->ID)),
          "eventEndDate" => customFormatDate(get_field('event_end_date',$post->ID)),
          "eventTime" => get_field('event_time',$post->ID),
          "eventAddress" => get_field('event_address',$post->ID),
          "eventEntranceType" => get_field('event_entrance_type',$post->ID),
          "eventEntranceValue" => get_field('event_entrance_value',$post->ID),
          "eventLink" => get_field('event_link',$post->ID)
        ];

      }else{
        $formatedPosts[] = [
          "id" => $post->ID,
          "churchId" => $post->post_author,
          "postDate" => customFormatDate($post->post_date),
          "postTitle" => $post->post_title,
          "postExcerpt" => sanitize_text_field(get_field('post_excerpt', $post->ID)),
        ];
      }
    }

    return rest_ensure_response($formatedPosts);
  }else{
    return new WP_Error( 'not_found', "No $postType found", array( 'status' => 404 ) );
  }
}






function getChurchSingleContent($reqData){
  $postTypeRequested = $reqData['content'];
  $currentPostType = get_post_type($reqData['post_id']);
  $post = get_post($reqData['post_id']);  

  if($post && ($currentPostType === $postTypeRequested)){
      $paragraphsGroup = get_field('paragraph_group', $post->ID);
      $formatedPost = [];
      $paragraphs = [];
      

      if($paragraphsGroup){
        foreach($paragraphsGroup as $paragraphGroup){
          $paragraphs[] = $paragraphGroup;
        }
      }

      if($postTypeRequested === 'event'){
        $formatedPost = [
          "id" => $post->ID,
          "churchId" => $post->post_author,
          "postDate" => customFormatDate($post->post_date),
          "postTitle" => $post->post_title,
          "postExcerpt" => sanitize_text_field(get_field('event_excerpt', $post->ID)),
          "postContent" => $paragraphs,
          "eventStartDate" => customFormatDate(get_field('event_start_date',$post->ID)),
          "eventEndDate" => customFormatDate(get_field('event_end_date',$post->ID)),
          "eventTime" => get_field('event_time',$post->ID),
          "eventAddress" => get_field('event_address',$post->ID),
          "eventEntranceType" => get_field('event_entrance_type',$post->ID),
          "eventEntranceValue" => get_field('event_entrance_value',$post->ID),
          "eventLink" => get_field('event_link',$post->ID)
        ];

      }else{
        $formatedPost = [
          "id" => $post->ID,
          "churchId" => $post->post_author,
          "postDate" => customFormatDate($post->post_date),
          "postTitle" => $post->post_title,
          "postExcerpt" => sanitize_text_field(get_field('post_excerpt', $post->ID)),
          "postContent" => $paragraphs,
        ];
      }


    return rest_ensure_response($formatedPost);
  }else{
    return new WP_Error( 'not_found', "No $postTypeRequested found", array( 'status' => 404 ) );
  }
}




function searchChurchByKeyword($req){
  $keyword = $req['keyword'];
  $churchesFound = get_users( array( 'search' => "$keyword*", 'role__in' => ['church'] ));
  $formatedChurchesFound = [];

  if($churchesFound){
    foreach($churchesFound as $church){
      $churchAddress = get_user_meta($church->id, "church_address", true);
      $churchLogoID = get_user_meta($church->id, "church_logo", true);
      $churchLogoUrl = wp_get_attachment_image_url( $churchLogoID, "large" );

      $formatedChurchesFound[] = [
          "id" => $church->id,
          "name" => $church->first_name,
          "address" => $churchAddress,
          "logo" => $churchLogoUrl ? $churchLogoUrl : $churchLogoID
      ];
    }


    return rest_ensure_response($formatedChurchesFound);
  }else{
    return new WP_Error( 'not_found', "No churches found", array( 'status' => 404 ) );
  }
}




function searchChurchByMetadata($req){
  $churchName = $req['church_name'];
  $churchState = $req['church_state'];
  $churchCity = $req['church_city'];
  $churchAddress = $req['church_address'];

  $metaQueryArgs = array(
      'relation' => 'AND',
  );

  if($churchState){
    $metaQueryArgs[] = [
        'key'     => 'church_state',
        'value'   => "$churchState",
        'compare' => '=', 
    ];
  }

  if($churchCity){
    $metaQueryArgs[] = [
        'key'     => 'church_city',
        'value'   => "$churchCity",
        'compare' => '=', 
    ];
  }

  if($churchAddress){
    $metaQueryArgs[] = [
        'key'     => 'church_address',
        'value'   => "$churchAddress",
        'compare' => '=', 
    ];
  }

  $churchesFound = get_users([
    'role__in' => ['church'],
    'search' => "$churchName*",
    'meta_query' => $metaQueryArgs
  ]);

  $formatedChurchesFound = [];

  if($churchesFound){
    foreach($churchesFound as $church){
      $churchAddress = get_user_meta($church->id, "church_address", true);
      $churchLogoID = get_user_meta($church->id, "church_logo", true);
      $churchLogoUrl = wp_get_attachment_image_url( $churchLogoID, "large" );

      $formatedChurchesFound[] = [
          "id" => $church->id,
          "name" => $church->first_name,
          "address" => $churchAddress,
          "logo" => $churchLogoUrl ? $churchLogoUrl : $churchLogoID
      ];
    }


    return rest_ensure_response($formatedChurchesFound);
  }else{
    return new WP_Error( 'not_found', "No churches found", array( 'status' => 404 ) );
  }
}



function saveChurchFollowers($userId, $churchId){
  $currentChurchFollowers = get_user_meta($churchId, 'church_current_followers', true);

  if($currentChurchFollowers && !in_array($userId, $currentChurchFollowers)){
    $currentChurchFollowers[] =  intval($userId);
    update_user_meta($churchId, 'church_current_followers', $currentChurchFollowers);
  }else{
    $newCurrentChurchFollowers = [intval($userId)];
    update_user_meta($churchId, 'church_current_followers', $newCurrentChurchFollowers);
  }
}

function removeChurchFollowers($userId, $churchId){
  $currentChurchFollowers = get_user_meta($churchId, 'church_current_followers', true);
  $newCurrentChurchFollowers = [];

  if($currentChurchFollowers && in_array($userId, $currentChurchFollowers)){
    foreach($currentChurchFollowers as $churchFollowerId){
      	if($churchFollowerId !== intval($userId)){
					$newCurrentChurchFollowers[] = $churchFollowerId;
				}
    }
    update_user_meta($churchId, 'church_current_followers', $newCurrentChurchFollowers);
  }
}


function getAllChurchFollowers($req){
  $currentChurch = get_user_by('id', $req['id']);
  $churchCurrentFollowersObj = [];

  if($currentChurch && in_array('church', $currentChurch->roles)){
    $churchCurrentFollowers = get_user_meta($req['id'], 'church_current_followers', true);

    $churchCurrentFollowersObj[] = [
      "followers" => $churchCurrentFollowers,
      "total" => sizeof($churchCurrentFollowers)
    ];
  
    return $churchCurrentFollowersObj;
   
  }else{
    return new WP_Error( 'not_found', "No church found with this ID.", array( 'status' => 404 ) );
  }
}



function getAllChurchAds(){
  $churchAds = get_posts(array(
    'post_type' => 'church_ad',
    'post_status' => 'publish',
    'numberposts'      => 5
  ));

  $formatedChurchesAds = [];

  if($churchAds){
    foreach($churchAds as $ad){      
      $formatedChurchesAds[] = [
          "bannerTitle" => $ad->post_title,
          "bannerLink" => get_field('church_ad_banner_link',$ad->ID),
          "bannerImg" => get_field('church_ad_banner_img', $ad->ID)
      ];
    };
 
    return rest_ensure_response($formatedChurchesAds);
  }else{
    return new WP_Error( 'not_found', "No ads found.", array( 'status' => 404 ) );
  }
}



