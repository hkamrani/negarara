<?php
/*
Plugin Name: Negarara
Plugin URI: https://ertano.com/negarara
Description: Convert uploaded images to WebP format with customizable quality settings.
Version: 1.0
Author: Ertano
Author URI: https://ertano.com
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: NegarAra
Domain Path: /languages
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Include admin pages
include_once plugin_dir_path( __FILE__ ) . 'admin/settings-page.php';
include_once plugin_dir_path( __FILE__ ) . 'admin/upload-page.php';

// Enqueue scripts and styles
function negarara_enqueue_scripts($hook) {
    if ($hook != 'toplevel_page_negarara_upload' && $hook != 'settings_page_negarara_settings') {
        return;
    }
    wp_enqueue_script('negarara-upload-script', plugins_url('/js/upload.js', __FILE__), array('jquery'), '1.0', true);
    wp_localize_script('negarara-upload-script', 'negarara_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('negarara_upload_nonce')));
    wp_enqueue_style('negarara-upload-style', plugins_url('/css/style.css', __FILE__), array(), '1.0');
}
add_action('admin_enqueue_scripts', 'negarara_enqueue_scripts');

// Add menu items
function negarara_add_admin_menu() {
    add_menu_page('Negarara', 'Negarara', 'manage_options', 'negarara_upload', 'negarara_upload_page', 'dashicons-format-image', 11);
    add_submenu_page('negarara_upload', 'Settings', 'Settings', 'manage_options', 'negarara_settings', 'negarara_settings_page');
}
add_action('admin_menu', 'negarara_add_admin_menu');

// Handle file upload via AJAX
function negarara_handle_upload() {
    // Verify nonce
    if (!isset($_POST['negarara_upload_nonce']) || !wp_verify_nonce($_POST['negarara_upload_nonce'], 'negarara_upload_nonce')) {
        wp_die('Nonce verification failed.');
    }

    if (!empty($_FILES['files']['name'][0])) {
        $quality = get_option('negarara_quality', 80); // Default quality 80
        $upload_dir = wp_upload_dir();

        foreach ($_FILES['files']['tmp_name'] as $key => $tmp_name) {
            $file_name = sanitize_file_name($_FILES['files']['name'][$key]);
            $file_path = $upload_dir['path'] . '/' . $file_name;

            // Use WordPress function to move the uploaded file
            $movefile = wp_handle_upload($_FILES['files'], array('test_form' => false));
            if ($movefile && !isset($movefile['error'])) {
                $file_path = $movefile['file'];

                // Convert to WebP
                $response = wp_remote_get($file_path);
                if (is_wp_error($response)) {
                    wp_die(esc_html($response->get_error_message()));
                }
                $image = imagecreatefromstring(wp_remote_retrieve_body($response));
                $webp_path = $upload_dir['path'] . '/' . pathinfo($file_name, PATHINFO_FILENAME) . '.webp';
                imagewebp($image, $webp_path, $quality);

                // Insert into media library
                $attachment = array(
                    'post_mime_type' => 'image/webp',
                    'post_title' => pathinfo($webp_path, PATHINFO_FILENAME),
                    'post_content' => '',
                    'post_status' => 'inherit'
                );
                $attach_id = wp_insert_attachment($attachment, $webp_path);
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                $attach_data = wp_generate_attachment_metadata($attach_id, $webp_path);
                wp_update_attachment_metadata($attach_id, $attach_data);

                // Cleanup
                imagedestroy($image);
            } else {
                // Handle upload error
                wp_die(esc_html($movefile['error']));
            }
        }
    }
    wp_redirect(admin_url('admin.php?page=negarara_upload'));
    exit;
}
add_action('wp_ajax_negarara_handle_upload', 'negarara_handle_upload');
?>
