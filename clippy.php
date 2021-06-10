<?php
/*
Plugin name: Clippy
Plugin URI:
Description: [clippy] is a shortcode that can be placed on a page and will create a text field for the logged in user to save data. Only one per page unless you also pass a key value: [clippy key=unique_value]. Other options include: label="Your Field Label", id=1(to match to a post id), or type=input or type=textarea to provide the different fields.
Author: Chris Upton
Author URI: twitter.com/chrisupton
Version: 0.23
*/
/**
 * Register the "clippy" custom post type
 */
function clippy_setup_post_type() {
    register_post_type( 'clippy', ['public' => true ] );
}
add_action( 'init', 'clippy_setup_post_type' );


/**
 * Activate the plugin.
 */
function clippy_activate() {
    // Trigger our function that registers clippy.
    clippy_setup_post_type();
    // Clear the permalinks after the post type has been registered.
    flush_rewrite_rules();
}

register_activation_hook( __FILE__, 'clippy_activate' );

//Short code that handles clippy shortcode
function get_clippy($atts) {

    extract(shortcode_atts([
        'type' => 'textarea', //textarea, input //checkbox?
        'label' => '',
        'id'    => 0,
        'key'   => '',
        'slug'   => '',
    ], $atts));

    $p_id=get_the_id();
    //Clippy is only avaliable to logged in uers
    $user_id = get_current_user_id();

    if( $user_id > 0 ) {
        //This is our parent container where the posted clippys will be display and stored after being subnmitted via ajax

        //parameters to obtain only clippy post type from related post id only
    	$args = [
            'post_type' => 'clippy',
            'post_title' => $id,
            'author' => $user_id,
            'post_parent' => $p_id,
            'order'=>'DESC',
            'numberposts' => 1,
            'posts_per_page' => 1,
            'p' => $id,
        ];

        //If id is passed ALSO confirm that its the right user
        if ($id) {
            $args = [
                'p' => $id,
                'post_type' => 'clippy',
                'author' => $user_id,
            ];
        }

        //if key is passed,
        if ($key || $slug) {
            $args = [
                'post_type' => 'clippy',
                's' => $key,
                'author' => $user_id,
            ];
        }

        $loop = new WP_Query($args);

        $str ='<div class="clippy-container">';

        // Loop thru and display all clippys NOTE you can add css class to div for style
        // Check if theres any data
        $content = '';
        if ( $loop->have_posts() ) {
            while ( $loop->have_posts() ) {
                $loop->the_post();
                $id = get_the_ID();
                $content = get_the_content();
                $str .='<div class="clippy-display" style="display:none;">'.$content.'</div>';
            }
        //Closing clippyresponse container
        } else {
        	//No Clippys yet  @TODO you can return message inbetween div dont forget to use ' or escape "
        	$str .= '<div class="clippy-display" style="display:none;"></div>';
        }

        //reset for ghood measure
        wp_reset_postdata();
        //REcast ID cause loop above was causing issues
        $p_id = get_the_id();
        //output our clippy textbox and submission form

        if ( !empty($label) ) {
            $str .= '<label for="clippy">'.$label.'</label>';
        }

        if ($type == 'textarea') {
            $str .= '<textarea name="clippy" class="textbox clippy-field" required>'.$content.'</textarea>';
        } elseif ($type == 'input') {
            $str .= '<input name="clippy" type="text" class="textbox clippy-field" value="'.$content.'" required />';

        }


        $str .= '<div class="clippy-feedback" style="font-size:70%;text-align:right;"></div>
        <input type="hidden" class="original_id" name="original_id" value="'.$id.'" />
        <input type="hidden" class="post_id" name="post_id" value="'.$p_id.'" />
        <input type="hidden" class="key_name" name="key_name" value="'.$key.'" />';
        $str .= '</div>'; //closing div#clippyresponse container
        return $str;
    }
}


//Register our short code
function register_shortcodes(){
    add_shortcode('clippy', 'get_clippy');
}
add_action( 'init', 'register_shortcodes');


//NOT IN USE----MAY BE REMOVCED leaving for fall back in case ajax fails and standard post is made
function custom_update_post() {
$user_id = get_current_user_id();
$my_post = array(
  'post_content'  => wp_strip_all_tags($_POST['clippy']),
  'post_status'   => 'publish',
  'post_type'   => 'clippy',
  'post_author'   => $user_id,
  'post_parent' => $_POST['post_id']
);
// Insert the post into the database
wp_insert_post( $my_post );
}


//Register the ajax post action with wordpress
add_action( 'wp_ajax_custom_update_post', 'custom_update_post' );
//Enques the ajax post action with wordpress
add_action( 'wp_enqueue_scripts', 'clippy_submit_scripts' );
//Handles privilaged ajax request
add_action( 'wp_ajax_ajax-clippySubmit', 'myajax_clippySubmit_func' );
//Handles non-privaleged request
add_action( 'wp_ajax_nopriv_ajax-clippySubmit', 'myajax_clippySubmit_func' );


//Create wordpress nonce security token,  Emque client side code to support ajax handlin
function clippy_submit_scripts() {
$wp_clippy_plugin_url="/wp-content/plugins/clippy";
	wp_enqueue_script( 'clippy_submit', $wp_clippy_plugin_url . '/assets/js/clippy_submit.js', array( 'jquery' ) );
	wp_localize_script( 'clippy_submit', 'PT_Ajax', array(
			'ajaxurl'   => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'ajax-nonce' )
		)
	);

}

//This function handles the data recieved from the client side, after the user sends the form data
//Its Verifys security wordpress nonce and stores clippy post data to database and resturn JSON response for AJAX
function myajax_clippySubmit_func() {

	// check nonce
	if ( ! wp_verify_nonce( $_POST['nonce'], 'ajax-nonce' ) ) {
		die ( 'Busted!' );
	}
	//Check user is registered
    $user_id = get_current_user_id();
    $original_id=$_POST['original_id'];
    $key_name = $_POST['key_name'];
    //Send error user is not regiatered
    if ( $user_id===0 ){

    	//RETURN ERROR trigger JS alert
        $ret=array("status"=>'error',"content"=>"You must be logged in");
    } else {
        //add key_name logic
        if ( !empty($original_id) ) {
            // Update the post based on the id
            $p_id = wp_update_post( [
                'ID'             => $original_id,
                'post_content'  => wp_strip_all_tags($_POST['clippy']),  //Stripping potentially dangerous tags from data
            ] );
            //RETURN SUCCESS and content of clippy
            $ret=array("status"=>'success',"content"=>wp_strip_all_tags($_POST['clippy']),'original_id'=>$original_id, 'resp' => $p_id);

        } elseif ( !empty($key_name) ) {
            // Insert the post into the database
            $p_id=wp_insert_post( [
                'post_content'  => wp_strip_all_tags($_POST['clippy']),  //Stripping potentially dangerous tags from data
                'post_status'   => 'publish',
                'post_type'   => 'clippy',
                'post_author'   => $user_id,
                'post_title' => $key_name,  //The parent post associated with this clippy
            ] );
            //RETURN SUCCESS and content of clippy
            $ret=array("status"=>'success',"content"=>wp_strip_all_tags($_POST['clippy']),'original_id'=>$p_id);
        } else {
            // Insert the post into the database
            $p_id=wp_insert_post( [
                'post_content'  => wp_strip_all_tags($_POST['clippy']),  //Stripping potentially dangerous tags from data
                'post_status'   => 'publish',
                'post_type'   => 'clippy',
                'post_author'   => $user_id,
                'post_parent' => $_POST['post_id'],  //The parent post associated with this clippy
                // 'post_title' => $key_name,  //The parent post associated with this clippy
            ] );
            //RETURN SUCCESS and content of clippy
            $ret=array("status"=>'success',"content"=>wp_strip_all_tags($_POST['clippy']),'original_id'=>$p_id);
        }
    	//  JSON output for response
    	header( "Content-Type: application/json" );
    	echo json_encode($ret);
    	// IMPORTANT: don't forget to "exit"
    	exit;
    }
}
?>
