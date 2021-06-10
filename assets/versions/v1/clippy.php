<?php
/*
Plugin name: Clippy
Plugin URI: 
Description: [clipppy] shortcode that adds a textbox allowing registered users to submit a note to the associated post. AJAX powered 
Author: 
Author URI: 
Version: 0.1
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
function get_clippy(){
$id=get_the_id();

//Clippy is only avaliable to logged in uers 
$user_id = get_current_user_id();
if($user_id>0){
//This is our parent container where the posted clippys will be display and stored after being subnmitted via ajax
  $str ="<div id='clippyresponse'>";
  //parameters to obtain only clippy post type from related post id only
	$args = array('post_type'=> 'clippy','post_parent'=>$id,'order'=>'ASC');
	//WP Query - Go get my data wordpress!!
$loop = new WP_Query($args);
//Loop thru and display all clippys NOTE you can add css class to div for style
//Check if theres any data
if($loop->have_posts()){
while ( $loop->have_posts() ) {
    $loop->the_post();
    $content=get_the_content();
  $str .='<div>'.$content.'</div>';
}  
//Closing clippyresponse container
}else{
	
	//No Clippys yet  @TODO you can return message inbetween div dont forget to use ' or escape " 
	$str .="<div></div>";
}
//closing div#clippyresponse container
$str .="</div>";
//reset for ghood measure
wp_reset_postdata();
//REcast ID cause loop above was causing issues
$id=get_the_id();
//output our clippy textbox and submission form 
   $str .= "<form action='' method='POST' name='submit_clippy_post' id='clippyform' enctype='multipart/form-data'>".wp_nonce_field('wp-clippy')."<textarea name='clippy' class='textbox' id='clippy' required ></textarea><input type='hidden' id='post_id' name='post_id' value='{$id}' /><input type='button' value='submit' id='addbtn' name='addbtn' /> </form>";
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
	wp_enqueue_script( 'clippy_submit',$wp_clippy_plugin_url. '/clippy_submit.js', array( 'jquery' ) );
	wp_localize_script( 'clippy_submit', 'PT_Ajax', array(
			'ajaxurl'   => admin_url( 'admin-ajax.php' ),
			'nextNonce' => wp_create_nonce( 'clippy-nonce' )
		)
	);

}

//This function handles the data recieved from the client side, after the user sends the form data
//Its Verifys security wordpress nonce and stores clippy post data to database and resturn JSON response for AJAX 
function myajax_clippySubmit_func() {
	// check nonce
	$nonce = $_POST['nextNonce'];
	if ( ! wp_verify_nonce( $nonce, 'clippy-nonce' ) ) {
		die ( 'Busted!' );
	}
	//Check user is registered 
$user_id = get_current_user_id();
//Send error user is not regiatered
if($user_id===0){
	//RETURN ERROR trigger JS alert
  $ret=array("status"=>'error',"content"=>"You must be logged in");
}else{
	//User is legit, lets store the data
$my_post = array(
  'post_content'  => wp_strip_all_tags($_POST['clippy']),  //Stripping potentially dangerous tags from data 
  'post_status'   => 'publish',
    'post_type'   => 'clippy',
  'post_author'   => $user_id,
  'post_parent' => $_POST['post_id']  //The parent post associated with this clippy
);
// Insert the post into the database
$id=wp_insert_post( $my_post );
//RETURN SUCCESS and content of clippy
$ret=array("status"=>'success',"content"=>wp_strip_all_tags($_POST['clippy']));
}
	//  JSON output for response
	header( "Content-Type: application/json" );
	echo json_encode($ret);
	// IMPORTANT: don't forget to "exit"
	exit;
}

?>