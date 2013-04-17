# Embedded Profile Form

## A WordPress plugin

---

*  Provides a template tag, `get_embedded_profile_form()`, that embeds a
profile settings form into a theme template. The form markup overridden
by copying profile-form.php into your theme folder and modifying it as you see fit.

*  Allows users to upload their own custom avatar image rather than using the 
default Gravatar. This can be disabled by removing the field from profile-form.php.

*  Custom profile fields can be added easily.

### Example usage

    <?php
        get_header();
        $current_user = wp_get_current_user();
    ?>

    <?php echo get_avatar( $current_user->ID, 250 ); ?>

    <h2><?php echo $current_user->display_name; ?></h2>

    <?php get_embedded_profile_form(); ?>


    <?php get_footer(); ?>