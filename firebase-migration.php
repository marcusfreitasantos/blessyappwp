<?php
require __DIR__ . '/vendor/autoload.php';
use Kreait\Firebase\Factory;
use Kreait\Firebase\Contract\Auth;
use Google\Cloud\Firestore\FirestoreClient;

global $credentials, $factory, $auth, $firestore;
$credentials = __DIR__ . "/google-credentials/blessy-app-firebase.json";
$factory = (new Factory)->withServiceAccount($credentials);
$auth = $factory->createAuth();

putenv("GOOGLE_APPLICATION_CREDENTIALS=$credentials");

if (!defined('FIREBASE_PROJECT_ID') || empty(FIREBASE_PROJECT_ID)) {
    die("Error: FIREBASE_PROJECT_ID is not set or is empty.");
}

$firestore = new FirestoreClient([
    'projectId' => FIREBASE_PROJECT_ID,
    'database'  => '(default)'
]);


function firebase_migration() {
    add_menu_page(
        'Firebase migration',
        'Firebase migration',
        'manage_options',
        'firebase-migration',
        'firebase_migration_page',
        'dashicons-cloud-saved'
    );
}
add_action('admin_menu', 'firebase_migration');


function firebase_migration_page(){ 
    if(isset($_POST['migrate_users'])){
        migrate_users_to_firestore();
    };
    
    if(isset($_POST['migrate_news'])){
        migrate_news_to_firestore();
    };
    ?>

    <form method='post'>
        <?php submit_button('Migrate Users', 'primary', 'migrate_users'); ?>
    </form>

    <form method='post'>
        <?php submit_button('Migrate News', 'primary', 'migrate_news'); ?>
    </form>
<?php }


function insert_user_meta_data_in_firestore($user, $userUID){
    global $firestore;

    try{
        $usersCollection = $firestore->collection('Users');

        $usersCollection->add([
            "address" => get_user_meta($user->id, "church_address", true),
            "state" => get_user_meta($user->id, "church_logo", true),
            "city" => get_user_meta($user->id, "church_logo", true),
            "email" => $user->user_email,
            "firstName" => $user->first_name,
            "lastName" => $user->last_name,
            "role" => $user->roles[0],
            "userId" => $userUID,
        ]);
 
        echo "<div class='updated'><p>Created user: $user->first_name | $user->user_email | $userUID</p></div>";


    }catch (ApiException $e) {
        $request = $e->getRequest();
        $response = $e->getResponse();
    
        echo $request->getUri().PHP_EOL;
        echo $request->getBody().PHP_EOL;
    
        if ($response) {
            echo $response->getBody();
        }
    }

}


function migrate_users_to_firestore(){
    global $auth;
    $currentUsersList = get_users();
    
    if(!empty($currentUsersList)){
        foreach($currentUsersList as $user){
            $userAvatarUrl = wp_get_attachment_image_url( get_user_meta($user->ID, "avatar", true), "medium" );
            $newUserPass = wp_generate_password(12, true);

            $userProperties = [
                'email' => $user->user_email,
                'emailVerified' => false,
                'password' => $newUserPass,
                'displayName' => $user->first_name,
                'disabled' => false,
            ];
            
            try{
                $createdUser = $auth->createUser($userProperties);
                insert_user_meta_data_in_firestore($user, $createdUser->uid);
            }catch(\Kreait\Firebase\Exception\Auth\EmailExists $e){
                $errorMessage = $e->getMessage();
                echo "<div class='error'><p>Error: $errorMessage</p></div>";
            }

        };
    }
}


function get_user_from_firestore($userEmail){
    global $auth;

    try{
        $user = $auth->getUserByEmail($userEmail);
        return $user->uid;

    }catch (ApiException $e) {
        $request = $e->getRequest();
        $response = $e->getResponse();
    
        echo $request->getUri().PHP_EOL;
        echo $request->getBody().PHP_EOL;
    
        if ($response) {
            echo $response->getBody();
        }
    }
}

function insert_news_in_firestore($newsData){
    global $firestore;

    try{
        $usersCollection = $firestore->collection('news');
        $usersCollection->add($newsData);
 
        echo "<br><br>";
        print_r($usersCollection);
        echo "<br>";
        echo "<div class='updated'><p>News created!</p></div>";
        echo "<br><br>";



    }catch (ApiException $e) {
        $request = $e->getRequest();
        $response = $e->getResponse();
    
        echo $request->getUri().PHP_EOL;
        echo $request->getBody().PHP_EOL;
    
        if ($response) {
            echo $response->getBody();
        }
    }

}
  

function migrate_news_to_firestore(){
    $news = get_posts(array(
        'post_type' => 'news',
        'post_status' => 'publish',
        'numberposts'      => -1,
    )); 

    if(!empty($news)){
        foreach($news as $newsPost){
            $authorID = get_post_field( 'post_author', $newsPost->ID );
            $authorData = get_user_by( 'id', $authorID);
            $firebaseUserId = get_user_from_firestore($authorData->user_email);

            $newsData = [
                'postTitle' => $newsPost->post_title,
                'postContent' => $newsPost->post_content,
                'postExerpt' => $newsPost->post_excerpt,
                'postDate' => $newsPost->post_date,
                'postStatus' => 'publish',
                'authorID' => $firebaseUserId,
            ];

            insert_news_in_firestore($newsData);
        };
    }

}


