<?php
/*
Plugin Name: Embedded Profile Form
Description: Provides a template tag to embed a 'profile settings' form into a theme template. Also enables user-uploaded avatars.
Author: Paul Fernandez
Version: 0.1.0
Author URI: https://github.com/pfernandez
License: MIT
*/


/**
 * TODO: Add the Javascript for avatar image cropping. Currently auto-crops images square.
 *//*
function embedded_profile_form_scripts() {

    wp_register_script(
		'profile-form',
		plugins_url(  'profile-form.js', __FILE__ ),
		array( 'jquery' ),
		null,
		true
	);
	wp_enqueue_script( 'profile-form' );
}
add_action( 'wp_enqueue_scripts', 'embedded_profile_form_scripts' );
*/


/**
 * This is the template tag to display our embedded profile form, profile-form.php.
 * The form markup can be overridden with a file of the same name in the user's theme directory.
 */
function get_embedded_profile_form() {

    // If the submit button was just pressed, validate and update the data.
    if( isset($_SERVER['REQUEST_METHOD']) && strtolower($_SERVER['REQUEST_METHOD']) == 'post' ) {
    
        // Update the user's info, store the resulting message in a session variable,
        // and refresh the page.
        $_SESSION['message'] = embedded_profile_form_update( wp_get_current_user()->ID );
        global $post;
        wp_redirect( home_url() . '/' . get_post( $post )->post_name );
    }

    // Display the form to a logged-in user with the current values populated.
    elseif( is_user_logged_in() ) {

        // Get the current user's information.
        $meta = get_user_meta( wp_get_current_user()->ID, 'profile' );
        if( sizeof( $meta ) > 0 )
            $meta = $meta[0];

        // Include the upload form template.
        if( locate_template( array( 'profile-form.php' ) ) )
            get_template_part( 'profile-form' );
        else
            include_once( plugin_dir_path( __FILE__ ) . 'profile-form.php' );
    }
    else {

        echo "<div class='modal-body'>"
            . "<h2>Oops! You aren't logged in.</h2>"
            . "<h3><a href='" . wp_login_url( get_permalink() ) . "' title='Login'>Log in</a></h3>"
            . "</div";
    } 
}


/**
 * Validate the submitted form data, update the database and return a message.
 *
 * @param $user_id: The user's id number.
 */
function embedded_profile_form_update( $user_id ) {

    // If a file that was uploaded exceeds its post_max_size, it will not return any form data.
    if( ! isset( $_POST['submit'] ) ) {
        return "Sorry, there was a problem with your submission. The file you tried to upload "
            . "may have been too large or it may have been an invalid type. Only images under "
            . "300kb are allowed.";
    }

    require_once( ABSPATH . 'wp-admin/includes/admin.php' );
    
    $USER = $_POST['USER'];
    $USER['ID'] = $user_id;

    // Provide an empty $META array in case no fields were present.
    $META = array();
    if( ! empty( $_POST['META'] ) )
        $META = $_POST['META'];
    
    // Make sure the email address entered is valid.
    if( ! is_email( $USER['user_email'] ) )
        return 'Sorry, ' . $USER['user_email'] . ' is not a valid email address.';

    // Check for mismatched password fields.
    if( $USER['user_pass'] != $USER['user_pass_2'] )
        return "Oops! The passwords you entered didn't match.";

	unset( $USER['user_pass_2'] );

	// Ensure that the password does not contain any spaces.
	if ( preg_match( '/\s/', $user_pass ) )
	    return 'Your password may not contain any spaces.';
	
    // Profile image upload.
    if( ! empty( $_FILES['upload']['name'] ) ) {

        if( ! empty( $_FILES['upload']['error'] ) )
            return "Sorry, there was problem uploading your file."; 
            
        // Make sure the file is an image.
        if( ! eregi( 'image/', $_FILES['upload']['type'] ) )
            return "The uploaded file is not an image. Please upload a valid file.";        

        // Images must not exceed 350kb in size.
        if( $_FILES['upload']['size'] > 500000 )
            return "Sorry, your image was too large. Please keep it under 500kb.";
        
        if ( ! function_exists( 'wp_handle_upload' ) )
            require_once( ABSPATH . 'wp-admin/includes/file.php' );
        
        // Upload the file.
        $uploaded_file = $_FILES['upload'];
        $upload_overrides = array( 'test_form' => false );
        $file_info = wp_handle_upload( $uploaded_file, $upload_overrides );
        $file_info = exif( $file_info );  // fix image orientation issues
        
        // Resize the image and crop it square.
        $image = wp_get_image_editor( $file_info['file'] );
        if ( ! is_wp_error( $image ) ) {
            $image->resize( 300, 300, true );
            $image->save( $file_info['file'] );
        }
        else {
            return "Error resizing avatar image.";
        }
        
        if ( $file_info ) {
        
            // Store the old path so we can delete the image.
            $old_avatar_info = get_user_meta( $user_id, 'custom_avatar', true );
        
            // Record the file info array to the user's metadata.
            $meta_updated = update_user_meta( $user_id, 'custom_avatar', $file_info );
            if( ! $meta_updated )
                return "Unable to update avatar.";
            
            // Delete the old image.
            if( ! empty( $old_avatar_info['path'] ) )
                unlink( $old_avatar_info['path'] );
        }
        else {
            return "Error uploading avatar.";
        }
    }
	
	try {
	
	    // Set the new password if it is not empty and is at least 6 characters long.
	    if( ! empty( $user_pass ) ) {
	    
	        if( strlen( $user_pass ) < 6 )
                return 'Your password must be at least six characters long.';
            
            // Since wp_set_password() logs the user out, we'll store their credentials temporarily
            // in a session variable, allowing us to log them back in after refreshing the page.
            $user_info = get_userdata( $user_id );
            $creds['user_login'] = $user_info->user_login;
            $creds['user_password'] = $USER['user_pass'];
            $creds['remember'] = true;
            
	        // Update the USER data in the wp_user table.
            $user = wp_update_user( $USER );
	        $user = wp_signon( $creds, false );
        }
        else {
            // Unset the password field so it doesn't get recorded as an empty string.
            unset( $USER['user_pass'] );
            
            // Update the USER data in the wp_user table.
            $user = wp_update_user( $USER );
        }
        
        if ( is_wp_error($user) )
            return $user->get_error_message();
            
        // Update the META data if applicable.
        if( ! empty( $META ) ) 
            update_user_meta( $user_id, 'profile', $META, false );

        return 'Your profile has been updated successfully.';
	}
	catch ( Exception $e ) {
	    // Catch any errors and display them.
		return $e->getMessage();
	}
}


/**
 * Corrects loss of image orientation data when using wp_handle_uploads().
 * Thanks to https://rtcamp.com/tutorials/fixing-image-orientation-wordpress-uploads/
 * for this function.
 *
 * @param $file: An array returned from wp_handle_upload().
 */
function exif($file) {
    //This line reads the EXIF data and passes it into an array
    $exif = read_exif_data($file['file']);

    //We're only interested in the orientation
    $exif_orient = isset($exif['Orientation'])?$exif['Orientation']:0;
    $rotateImage = 0;

    //We convert the exif rotation to degrees for further use
    if (6 == $exif_orient) {
        $rotateImage = 90;
        $imageOrientation = 1;
    } elseif (3 == $exif_orient) {
        $rotateImage = 180;
        $imageOrientation = 1;
    } elseif (8 == $exif_orient) {
        $rotateImage = 270;
        $imageOrientation = 1;
    }

    //if the image is rotated
    if ($rotateImage) {

        //WordPress 3.5+ have started using Imagick, if it is available since there is a noticeable
        //difference in quality. Why spoil beautiful images by rotating them with GD, if the user
        //has Imagick?

        if (class_exists('Imagick')) {
            $imagick = new Imagick();
            $imagick->readImage($file['file']);
            $imagick->rotateImage(new ImagickPixel(), $rotateImage);
            $imagick->setImageOrientation($imageOrientation);
            $imagick->writeImage($file['file']);
            $imagick->clear();
            $imagick->destroy();
        } else {

            //if no Imagick, fallback to GD
            //GD needs negative degrees
            $rotateImage = -$rotateImage;

            switch ($file['type']) {
                case 'image/jpeg':
                    $source = imagecreatefromjpeg($file['file']);
                    $rotate = imagerotate($source, $rotateImage, 0);
                    imagejpeg($rotate, $file['file']);
                    break;
                case 'image/png':
                    $source = imagecreatefrompng($file['file']);
                    $rotate = imagerotate($source, $rotateImage, 0);
                    imagepng($rotate, $file['file']);
                    break;
                case 'image/gif':
                    $source = imagecreatefromgif($file['file']);
                    $rotate = imagerotate($source, $rotateImage, 0);
                    imagegif($rotate, $file['file']);
                    break;
                default:
                    break;
            }
        }
    }
    // The image orientation is fixed, pass it back for further processing
    return $file;
}


/**
 * Modifies the output of get_avatar() to use the avatar found in the user's
 * custom_avatar meta field, if it exists.
 */
function embedded_profile_form_get_avatar( $avatar, $id_or_email, $size ) {

    // Request must by made using a user id.
    if ( is_numeric( $id_or_email ) ) {
        $user_id = (int) $id_or_email;
        $user = get_userdata( $user_id );
        
        // If the user exists and has a "custom_avatar" meta field, use that instead
        // of whatever WordPress was going to use.
        if ( $user ) {
            $custom_avatar = get_user_meta( $user_id, 'custom_avatar', true );
            if( ! empty( $custom_avatar['url'] ) ) {
                $avatar = '<img src="' . $custom_avatar['url'] . '" alt="' . $user->display_name
                    . '" class="avatar avatar-' . $size . 'photo avatar-custom" '
                    . 'height="' . $size . '" width="' . $size . '" />';
            }
        }
    }

    return $avatar;
}
add_filter( 'get_avatar', 'embedded_profile_form_get_avatar', 99, 3 );


/**
 * Create a PHP $_SESSION array that we can use to store user data for
 * as long as the stay logged in.
 */
function embedded_profile_form_start_session() {
    if( ! session_id() )
        session_start();
}
function embedded_profile_form_end_session() {
    session_destroy();
}
add_action('init', 'embedded_profile_form_start_session', 1);
add_action('wp_logout', 'embedded_profile_form_end_session');
add_action('wp_login', 'embedded_profile_form_end_session');


/**
 * Turn on PHP output buffering. Required for automatic login after a user changes their password.
 * This is because the refresh that is required is sent after page headers are already written,
 * which would otherwise result in a PHP error.
 */
function buffer_php_output() {
        ob_start();
}
add_action( 'init', 'buffer_php_output' );

?>