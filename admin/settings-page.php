<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

function negarara_settings_page()
{
?>
    <div class="mwtc_admin_settings_wrapper <?php echo esc_attr(is_rtl() ? 'mwtc-rtl' : 'mwtc-ltr'); ?>">
        <div class="mwtc_sidebar_wrapper">
            <div class="sidebar">
                <div class="mw_logo_section">
                    <div class="mw_logo">
                        <img src="<?php echo esc_url(NEAGARARA_DIR_URL . 'img/logo.svg'); ?>" width="100" height="100" alt="<?php esc_attr_e('Logo', 'negarara'); ?>">
                    </div>
                    <div class="mw_hello">
                        <span><?php printf('%s %s', esc_html__('Welcome to NegarAra', 'negarara'), '👋'); ?></span>
                    </div>
                </div>
                <div class="mw_menu_section">
                    <ul>
                        <li class="active">
                            <a href="#">
                                <span class="menu-icon" tab="note"></span>
                                <span class="menu-name"><?php esc_html_e('General', 'negarara'); ?></span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="content_wrapper">
            <div class="content">
                <form method="post" action="options.php" class="mp_options_panel" id="negarara-option-panel-form">
                    <?php
                    settings_fields('negarara_settings_group');
                    do_settings_sections('negarara_settings');
                    ?>
                    <div class="option_section">
                        <h3 class="option_section_title"><?php esc_html_e('Settings', 'negarara'); ?></h3>
                        <p class="option_section_description"><?php esc_html_e('Set the quality for the exported WebP images.', 'negarara'); ?></p>
                        <div class="option_field option_row_field">
                            <?php $quality = get_option('negarara_quality', 80); ?>
                            <label>
                                <span><?php esc_html_e('WebP Image Quality', 'negarara'); ?></span>
                                <span class="range_value"><?php echo sprintf('( %s )', esc_html($quality)); ?></span>
                            </label>
                            <input type="range" id="negarara_quality" class="mwpl_range" name="negarara_quality" min="10" max="100" value="<?php echo esc_attr($quality); ?>" style="width: 100%; margin-top: 10px;">
                        </div>
                    </div>

                    <div class="option_section">
                        <h3 class="option_section_title"><?php esc_html_e('Advanced Settings', 'negarara'); ?></h3>
                        <div class="option_field">
                            <label>
                                <span><?php esc_html_e('Select File Formats to Convert', 'negarara'); ?></span>
                            </label>
                            <div class="option_formats">
                                <label><input type="checkbox" name="negarara_formats[]" value="jpeg" <?php checked(in_array('jpeg', get_option('negarara_formats', ['jpeg', 'png', 'gif', 'jpg']))); ?> /> <?php esc_html_e('JPEG', 'negarara'); ?></label><br/>
                                <label><input type="checkbox" name="negarara_formats[]" value="jpg" <?php checked(in_array('jpg', get_option('negarara_formats', ['jpeg', 'png', 'gif', 'jpg']))); ?> /> <?php esc_html_e('JPG', 'negarara'); ?></label><br/>
                                <label><input type="checkbox" name="negarara_formats[]" value="png" <?php checked(in_array('png', get_option('negarara_formats', ['jpeg', 'png', 'gif', 'jpg']))); ?> /> <?php esc_html_e('PNG', 'negarara'); ?></label><br/>
                                <label><input type="checkbox" name="negarara_formats[]" value="gif" <?php checked(in_array('gif', get_option('negarara_formats', ['jpeg', 'png', 'gif', 'jpg']))); ?> /> <?php esc_html_e('GIF', 'negarara'); ?></label>
                            </div>
                        </div>
                    </div>

                    <div class="option_section">
                        <h3 class="option_section_title"><?php esc_html_e('Bulk Conversion', 'negarara'); ?></h3>
                        <p class="option_section_description"><?php esc_html_e('Convert all existing images to WebP format and delete the original files.', 'negarara'); ?></p>
                        <div class="option_field">
                            <label>
                                <input type="checkbox" name="negarara_delete_original" value="1" <?php checked(get_option('negarara_delete_original', 1)); ?> />
                                <?php esc_html_e('Delete original files after conversion', 'negarara'); ?>
                            </label>
                            <p class="option_section_description"><?php esc_html_e('Check this box to delete the original images after converting to WebP format during bulk conversion.', 'negarara'); ?></p>
                        </div>
                        <button id="negarara_bulk_convert" class="button button-primary"><?php esc_html_e('Convert All Images', 'negarara'); ?></button>
                        <div id="negarara_conversion_progress"></div>
                        <div id="negarara_conversion_log" style="max-height: 300px; overflow-y: auto; border: 1px solid #ccc; padding: 10px; margin-top: 10px;"></div>
                    </div>
                    <div class="option_section">
                    <input type="submit" class="button button-primary" value="<?php esc_attr_e('Save Changes', 'negarara'); ?>" />
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php
}

function negarara_settings_init() {
    register_setting('negarara_settings_group', 'negarara_quality', 'negarara_validate_quality');
    register_setting('negarara_settings_group', 'negarara_delete_original', 'absint');  // Ensure it's a boolean (1 or 0)
    register_setting('negarara_settings_group', 'negarara_formats', 'negarara_sanitize_formats');  // Custom sanitization function for formats array

    // Set default values if not already set
    if (get_option('negarara_delete_original') === false) {
        update_option('negarara_delete_original', 0); // Default to false
    }

    if (get_option('negarara_formats') === false) {
        update_option('negarara_formats', ['jpeg', 'png', 'gif', 'jpg']);
    }
}

function negarara_validate_quality($input) {
    return absint($input);
}

function negarara_sanitize_formats($input) {
    return array_map('sanitize_text_field', (array)$input);
}

register_setting('negarara_settings_group', 'negarara_quality', 'negarara_validate_quality');

add_action('admin_init', 'negarara_settings_init');