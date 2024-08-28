<?php
/*
Plugin Name: Negarara
Plugin URI: https://ertano.com/negarara
Description: Convert uploaded images to WebP format with customizable quality settings.
Version: 1.1
Author: Ertano
Author URI: https://ertano.com
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: negarara
Domain Path: /languages
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Load plugin text domain for translations
function negarara_load_textdomain() {
    load_plugin_textdomain( 'negarara', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' ); 
}
add_action( 'plugins_loaded', 'negarara_load_textdomain' );

// Include admin pages
include_once plugin_dir_path( __FILE__ ) . 'admin/settings-page.php';

if(!defined('NEAGARARA_DIR_URL')) {
    define('NEAGARARA_DIR_URL', plugin_dir_url(__FILE__));
}

// Enqueue scripts and styles
function negarara_enqueue_scripts($hook) {
    // Escape URLs for security
    wp_enqueue_script('negarara-settings-script', esc_url(plugins_url('/js/panel.js', __FILE__)), array('jquery'), '1.0', true);
    wp_enqueue_style('negarara-upload-style', esc_url(plugins_url('/css/style.css', __FILE__)), array(), '1.0');
}
add_action('admin_enqueue_scripts', 'negarara_enqueue_scripts');

// Add menu items
function negarara_add_admin_menu() {
    add_options_page(__('Negarara Settings', 'negarara'), __('Negarara', 'negarara'), 'manage_options', 'negarara', 'negarara_settings_page');
}
add_action('admin_menu', 'negarara_add_admin_menu');

// Hook into image size generation process
function negarara_convert_image_sizes_to_webp($metadata, $attachment_id) {
    // Sanitize the attachment ID to ensure it's an integer
    $attachment_id = absint($attachment_id);

    // Allowed formats to be converted to WebP
    $formats = ['jpg','jpeg', 'png', 'gif'];
    $upload_dir = wp_upload_dir();
    $original_file_path = get_attached_file($attachment_id);
    $file_info = pathinfo($original_file_path);

    // Validate the file extension
    $extension = strtolower($file_info['extension']);
    if (!in_array($extension, $formats)) {
        return $metadata; // Early return if the format is not allowed
    }

    // Convert the full-size image if it's in the selected formats
    if (in_array($extension, $formats)) {
        $converted_file = negarara_process_webp_conversion($original_file_path);
        if ($converted_file) {
            // Update metadata to point to the WebP file
            $metadata['file'] = str_replace($extension, 'webp', $metadata['file']);
            update_attached_file($attachment_id, $converted_file);
        }
    }

    // Convert each generated size
    if (isset($metadata['sizes']) && is_array($metadata['sizes'])) {
        foreach ($metadata['sizes'] as $size => $size_info) {
            $size_file_path = $upload_dir['basedir'] . '/' . dirname($metadata['file']) . '/' . $size_info['file'];
            $converted_file = negarara_process_webp_conversion($size_file_path);
            if ($converted_file) {
                // Update metadata to point to the WebP file
                $metadata['sizes'][$size]['file'] = str_replace($extension, 'webp', $size_info['file']);
            }
        }
    }

    // Update the post's mime type to WebP
    wp_update_post(array('ID' => $attachment_id, 'post_mime_type' => 'image/webp'));

    return $metadata;
}

// Helper function to convert an image to WebP and delete the original file
function negarara_process_webp_conversion($file_path) {
    $file_info = pathinfo($file_path);

    // Convert the image to WebP
    $image = wp_get_image_editor($file_path);
    if (!is_wp_error($image)) {
        // Ensure quality is within valid range
        $quality = absint(80); // Default quality for WebP
        if ($quality < 10 || $quality > 100) {
            $quality = 80; // Reset to default if out of range
        }
        $webp_file = $file_info['dirname'] . '/' . $file_info['filename'] . '.webp';
        $result = $image->save($webp_file, 'image/webp', ['quality' => $quality]);

        if (!is_wp_error($result)) {
            // Delete the original file securely
            if (file_exists($file_path)) {
                wp_delete_file($file_path);
            }

            // Return the path to the WebP file
            return $webp_file;
        }
    }

    return false;
}

// Hook into the metadata generation process to convert each image size to WebP
add_filter('wp_generate_attachment_metadata', 'negarara_convert_image_sizes_to_webp', 10, 2);

