<?php
/**
 * The markup for the embedded user profile settings form. This file can be
 * overidden by copying it into your theme folder and modifying it as you see fit.
 */
 
 
$current_user = wp_get_current_user();

// Check for empty fields and provide some default form values.
$display_name = $first_name = $last_name = $email = $occupation = '';
if( ! empty( $current_user->display_name ) )
    $display_name = $current_user->display_name;
if( ! empty( $current_user->user_firstname ) )
    $first_name = $current_user->user_firstname;
if( ! empty( $current_user->user_lastname ) )
    $last_name = $current_user->user_lastname;
if( ! empty( $current_user->user_email ) )
    $email = $current_user->user_email;
if( ! empty( $current_user->user_url ) )
    $user_url = $current_user->user_url;

/*
Custom fields can be added by creating variables here and
using the commented-out form fields further on down the page.

if( ! empty( $meta['occupation'] ) )
    $occupation = $meta['occupation'];
*/

?>

<form class="profile-form" method="post" action="" enctype="multipart/form-data">
    <fieldset>
    
        <label for="first_name">Display Name</label>
        <input type="text" name="USER[display_name]" value="<?php echo $display_name; ?>">

        <label for="first_name">First Name</label>
        <input type="text" name="USER[first_name]" value="<?php echo $first_name; ?>">

        <label for="last_name">Last Name</label>
        <input type="text" name="USER[last_name]" value="<?php echo $last_name; ?>">

        <label for="user_email">Email</label>
        <input type="text" name="USER[user_email]" value="<?php echo $email; ?>">

        <label for="user_pass">New Password</label>
        <input type="password" name="USER[user_pass]" value="">
        <label for="user_pass">Reenter Password </label>
        <input type="password" name="USER[user_pass_2]" value="">

        <label for="user_url">Website</label>
        <input type="text" name="USER[user_url]" value="<?php echo $user_url; ?>">

        <?php
        /*
        Add additional custom inputs like so:
        
        <label for="occupation">Occupation</label>
        <input type="text" name="META[occupation]" value="<?php echo $occupation ?>">
        */
        ?>
                    
        <label for="file">Profile Image (500kb max)</label>
        <input type="file" name="upload" title="Choose a file..." style="line-height: normal;">
    
        <label for="save_button"></label>
        <input type="submit" value="Save Changes" name="submit">

    </fieldset>
</form>