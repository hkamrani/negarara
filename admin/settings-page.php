<?php
function negarara_settings_page()
{
?>
    <div class="mwtc_admin_settings_wrapper <?php echo is_rtl() ? 'mwtc-rtl' : 'mwtc-ltr'; ?>">
        <div class="mwtc_sidebar_wrapper">
            <div class="sidebar">
                <div class="mw_logo_section">
                    <div class="mw_logo">
                        <img src="<?php echo NEAGARARA_DIR_URL . 'img/logo.png'; ?>" width="100" height="100" alt="Mihan Panel Logo">
                    </div>
                    <div class="mw_hello">
                        <span><?php printf('%s %s', __('Welcome to NegarAra', 'negarara'), '👋') ?></span>
                    </div>
                </div>
                <div class="mw_menu_section">
                    <ul>
                        <li class="active">
                            <a href="#">
                                <span class="menu-icon" tab="note"></span>
                                <span class="menu-name"><?php _e('General', 'negarara')?></span>
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
                        <h3 class="option_section_title"><?php _e('Settings', 'negarara') ?></h3>
                        <p class="option_section_description"><?php _e('Set the quality for the exported WebP images.', 'negarara') ?></p>
                        <div class="option_field option_row_field">
                            <?php $quality = get_option('negarara_quality', 80); ?>
                            <label>
                                <span><?php _e('WebP Image Quality', 'negarara') ?></span>
                                <span class="range_value"><?php echo sprintf('( %s )', esc_html($quality)) ?></span>
                            </label>

                            <input type="range" id="negarara_quality" class="mwpl_range" name="negarara_quality" min="10" max="100" value="<?php echo esc_attr($quality) ?>" style="width: 100%; margin-top: 10px;">
                        </div>
                    </div>
                </form>
            </div>
            <div class="submit-row">
                <?php submit_button(null, 'primary', null, true, ['form' => 'negarara-option-panel-form']); ?>
            </div>
        </div>

    </div>
<?php
}

function negarara_settings_init()
{
    register_setting('negarara_settings_group', 'negarara_quality');

    // add_settings_section(
    //     'negarara_settings_section',
    //     esc_html__('Settings', 'negarara'),
    //     'negarara_settings_section_callback',
    //     'negarara_settings',
    //     [
    //         'before_section' => '<div class="option_section">',
    //         'after_section' => "</div>",
    //     ]
    // );

    // add_settings_field(
    //     'negarara_quality',
    //     esc_html__('WebP Image Quality', 'negarara'),
    //     'negarara_quality_callback',
    //     'negarara_settings',
    //     'negarara_settings_section'
    // );
}
add_action('admin_init', 'negarara_settings_init');

function negarara_settings_section_callback()
{
    echo '<p style="color: #540d6e;">' . esc_html__('Set the quality for the exported WebP images.', 'negarara') . '</p>';
}

function negarara_quality_callback()
{
    $quality = get_option('negarara_quality', 80);
    echo '<input type="range" id="negarara_quality" name="negarara_quality" min="10" max="100" value="' . esc_attr($quality) . '" style="width: 100%; margin-top: 10px;">';
}
?>