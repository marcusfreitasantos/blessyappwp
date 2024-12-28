<?php

function createNews(WP_REST_Request $request){
    $reqData = json_decode($request->get_body(), true);

    $postData = [
        'post_title' => $reqData['title'],
        'post_content' => $reqData['content'],
        'post_status' => 'publish',
        'post_author' => $reqData['userId'],
        'post_type' => 'news'   
    ];
    
    $newPost = wp_insert_post($postData);

    if(is_wp_error($newPost)){
        return new WP_Error('error', 'Error', ['status' => 400]);
    }else{
        return rest_ensure_response("News created successfully");
    }
}


function updateNews(WP_REST_Request $request){
    $reqData = json_decode($request->get_body(), true);
    $postId = $reqData['postId'];
    $authorId = $reqData['userId'];
    $post = get_post($postId);
    
    if(!$post){
        return new WP_Error('error', 'Post not found', ['status' => 400]);
    }
    
    $postAuthor = $post->post_author;

    if($postAuthor != $authorId){
        return new WP_Error('error', 'You are not allowed to delete this post', ['status' => 400]);
    }

    $postData = [
        'ID' => $reqData['postId'],
        'post_title' => $reqData['title'],
        'post_content' => $reqData['content'],
    ];
    
    $updatedPost = wp_update_post($postData);

    if(is_wp_error($updatedPost)){
        return new WP_Error('error', 'Error', ['status' => 400]);
    }else{
        return rest_ensure_response("News updated successfully");
    }
}


function deleteNews(WP_REST_Request $request){
    $reqData = json_decode($request->get_body(), true);
    $postId = $reqData['postId'];
    $authorId = $reqData['userId'];
    $post = get_post($postId);
    
    if(!$post){
        return new WP_Error('error', 'Post not found', ['status' => 400]);
    }
    
    $postAuthor = $post->post_author;

    if($postAuthor != $authorId){
        return new WP_Error('error', 'You are not allowed to delete this post', ['status' => 400]);
    }

    $deletedPost = wp_delete_post($postId);

    if($deletedPost){
        return rest_ensure_response("The post was successfully deleted");
    }else{
        return new WP_Error('error', 'Error', ['status' => 400]);
    }
}