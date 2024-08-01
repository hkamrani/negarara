<?php
function negarara_settings_page() {
    ?>
    <div class="wrap" style="background-color: #3bceac; padding: 20px; border-radius: 10px;">
        <h1 style="color: #540d6e;"><?php esc_html_e('WebP Converter Settings', 'negarara'); ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('negarara_settings_group');
            do_settings_sections('negarara_settings');
            submit_button(null, 'primary', null, true, array('style' => 'background-color: #540d6e; border-color: #540d6e;'));
            ?>
        </form>
    </div>
    <?php
}

function negarara_settings_init() {
    register_setting('negarara_settings_group', 'negarara_quality');

    add_settings_section(
        'negarara_settings_section',
        esc_html__('Settings', 'negarara'),
        'negarara_settings_section_callback',
        'negarara_settings'
    );

    add_settings_field(
        'negarara_quality',
        esc_html__('WebP Image Quality', 'negarara'),
        'negarara_quality_callback',
        'negarara_settings',
        'negarara_settings_section'
    );
}
add_action('admin_init', 'negarara_settings_init');

function negarara_settings_section_callback() {
    echo '<p style="color: #540d6e;">' . esc_html__('Set the quality for the exported WebP images.', 'negarara') . '</p>';
}

function negarara_quality_callback() {
    $quality = get_option('negarara_quality', 80);
    echo '<input type="range" id="negarara_quality" name="negarara_quality" min="10" max="100" value="' . esc_attr($quality) . '" style="width: 100%; margin-top: 10px;">';
}
?>
