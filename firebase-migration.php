<?php


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
    
    ?>

    <h2>Migrate Users to Firestore</h2>
    <form method='post'>
       <?php submit_button('Migrate Users', 'primary', 'migrate_users'); ?>
    </form>
<?php }


function migrate_users_to_firestore(){
    $currentUsersList = get_users();
    

    if(!empty($currentUsersList)){
        foreach($currentUsersList as $user){
            $newUserPass = wp_generate_password(12, true);
            echo "<div class='updated'><p>Name: $user->first_name</p></div>";
            echo "<div class='updated'><p>Email: $user->user_email</p></div>";
        };
    }
}
