<?php
/*
Plugin Name: Negarara
Plugin URI: https://ertano.com/negarara
Description: Convert uploaded images to WebP format with customizable quality settings.
Version: 1.2
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
    wp_localize_script('negarara-settings-script', 'negararaAjax', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'security' => wp_create_nonce('negarara_bulk_convert_nonce'),
    ));
    wp_enqueue_style('negarara-upload-style', esc_url(plugins_url('/css/style.css', __FILE__)), array(), '1.0');
}
add_action('admin_enqueue_scripts', 'negarara_enqueue_scripts');


// Add menu items
function negarara_add_admin_menu() {
    add_options_page(__('Negarara Settings', 'negarara'), __('Negarara', 'negarara'), 'manage_options', 'negarara', 'negarara_settings_page');
}
add_action('admin_menu', 'negarara_add_admin_menu');

// Hook into image size generation process
function negarara_convert_image_sizes_to_webp($metadata, $attachment_id,$delete_file) {
    $log = [];
    $attachment_id = absint($attachment_id);
    $formats = get_option('negarara_formats', ['jpeg', 'png', 'gif', 'jpg']);
    $upload_dir = wp_upload_dir();
    $original_file_path = get_attached_file($attachment_id);
    $file_info = pathinfo($original_file_path);
    $delete_file = $delete_file;
    $extension = strtolower($file_info['extension']);
    // If the format is not selected for conversion, just return the original metadata
    if (!in_array($extension, $formats)) {
        return $metadata;
    }


    // Convert the full-size image
    // Translators: %s is the image file name.
    $log[] = ['type' => 'info', 'text' => sprintf(__('Converting full-size image: %s', 'negarara'), basename($original_file_path))];
    $converted_file = negarara_process_webp_conversion($original_file_path,$delete_file);
    if ($converted_file) {
        $metadata['file'] = str_replace($extension, 'webp', $metadata['file']);
        update_attached_file($attachment_id, $converted_file);
        $log[] = ['type' => 'success', 'text' => __('Full-size image converted successfully', 'negarara')];
    } else {
        $log[] = ['type' => 'error', 'text' => __('Failed to convert full-size image', 'negarara')];
    }

    // Convert each generated size
    if (isset($metadata['sizes']) && is_array($metadata['sizes'])) {
        foreach ($metadata['sizes'] as $size => $size_info) {
            $size_file_path = $upload_dir['basedir'] . '/' . dirname($metadata['file']) . '/' . $size_info['file'];
            $size_file_info = pathinfo($size_file_path);
            $size_extension = strtolower($size_file_info['extension']);
            if (in_array($size_extension, $formats)) {
                // Translators: %s is the image size, %s is the file name.
                $log[] = ['type' => 'info', 'text' => sprintf(__('Converting %1$s size: %2$s', 'negarara'), $size, basename($size_file_path))];
                $converted_size_file = negarara_process_webp_conversion($size_file_path,$delete_file);
                if ($converted_size_file) {
                    $metadata['sizes'][$size]['file'] = str_replace($size_extension, 'webp', $size_info['file']);
                    // Translators: %s is the size of the image.
                    $log[] = ['type' => 'success', 'text' => sprintf(__('%s size converted successfully', 'negarara'), $size)];
                } else {
                    // Translators: %s is the image size.
                    $log[] = ['type' => 'error', 'text' => sprintf(__('Failed to convert %s size', 'negarara'), $size)];
                }
            }
        }
    }

    // Update the post's mime type to WebP
    wp_update_post(array('ID' => $attachment_id, 'post_mime_type' => 'image/webp'));

    return ['metadata' => $metadata, 'log' => $log];
}


// Helper function to convert an image to WebP
function negarara_process_webp_conversion($file_path,$delete_file) {
    $file_info = pathinfo($file_path);
    $delete_file = $delete_file;
    // Convert the image to WebP
    $image = wp_get_image_editor($file_path);
    if (!is_wp_error($image)) {
        // Set default quality and allow user to customize it via settings
        $quality = get_option('negarara_quality', 80); // Assuming you added this option
        $quality = absint($quality);
        if ($quality < 10 || $quality > 100) {
            $quality = 80; // Reset to default if out of range
        }
        $webp_file = $file_info['dirname'] . '/' . $file_info['filename'] . '.webp';
        $result = $image->save($webp_file, 'image/webp', ['quality' => $quality]);

        if (!is_wp_error($result)) {
            // Delete the original file securely
            if (file_exists($file_path) AND $delete_file == true) {
                wp_delete_file($file_path);
            }

            // Return the path to the WebP file
            return $webp_file;
        }
    }

    return false;
}

// Hook into the metadata generation process to convert each image size to WebP
add_filter('wp_generate_attachment_metadata', 'negarara_convert_image_sizes_to_webp', 10, 3);


function negarara_get_convertible_image_count() {
    check_ajax_referer('negarara_bulk_convert_nonce', 'security');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Insufficient permissions');
    }

    $selected_formats = get_option('negarara_formats', ['jpeg', 'png', 'gif', 'jpg']);
    $mime_types = [];
    foreach ($selected_formats as $format) {
        switch ($format) {
            case 'jpeg':
            case 'jpg':
                $mime_types[] = 'image/jpeg';
                break;
            case 'png':
                $mime_types[] = 'image/png';
                break;
            case 'gif':
                $mime_types[] = 'image/gif';
                break;
        }
    }

    $args = array(
        'post_type'      => 'attachment',
        'post_mime_type' => $mime_types,
        'post_status'    => 'inherit',
        'posts_per_page' => -1,
        'fields'         => 'ids',
    );

    $images = get_posts($args);
    $count = count($images);

    wp_send_json_success(['count' => $count]);
}
add_action('wp_ajax_negarara_get_convertible_image_count', 'negarara_get_convertible_image_count');

function negarara_convert_single_image() {
    check_ajax_referer('negarara_bulk_convert_nonce', 'security');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Insufficient permissions');
    }

    $selected_formats = get_option('negarara_formats', ['jpeg', 'png', 'gif', 'jpg']);
    $mime_types = [];
    foreach ($selected_formats as $format) {
        switch ($format) {
            case 'jpeg':
            case 'jpg':
                $mime_types[] = 'image/jpeg';
                break;
            case 'png':
                $mime_types[] = 'image/png';
                break;
            case 'gif':
                $mime_types[] = 'image/gif';
                break;
        }
    }

    $args = array(
        'post_type'      => 'attachment',
        'post_mime_type' => $mime_types,
        'post_status'    => 'inherit',
        'posts_per_page' => 1,
        'orderby'        => 'date',
        'order'          => 'ASC'
    );

    $images = get_posts($args);

    if (empty($images)) {
        wp_send_json_success(['more_images' => false, 'log' => []]);
    }

    $image = $images[0];
    $attachment_id = $image->ID;
    $log = [];

    $metadata = wp_get_attachment_metadata($attachment_id);
    if (!$metadata) {
        update_post_meta($attachment_id, '_negarara_converted', true);
        // Translators: %d is the image attachment ID
        $log[] = ['type' => 'error', 'text' => sprintf(__('Error: No metadata found for image %d', 'negarara'), $attachment_id)];
        wp_send_json_success(['more_images' => true, 'log' => $log]);
    }

    $delete_file = get_option('negarara_delete_original');

    // Convert the image
    $result = negarara_convert_image_sizes_to_webp($metadata, $attachment_id,$delete_file);
    
    if (is_wp_error($result)) {
        // Translators: %d is the image attachment ID, %s is the error message.
        $log[] = ['type' => 'error', 'text' => sprintf(__('Error converting image %1$d: %2$s', 'negarara'), $attachment_id, $result->get_error_message())];
    } else {
        $metadata = $result['metadata'];
        $log = array_merge($log, $result['log']);

        // Update the attachment metadata
        wp_update_attachment_metadata($attachment_id, $metadata);

        // Mark this image as converted
        update_post_meta($attachment_id, '_negarara_converted', true);
    }

    wp_send_json_success(['more_images' => true, 'log' => $log]);
}
add_action('wp_ajax_negarara_convert_single_image', 'negarara_convert_single_image');