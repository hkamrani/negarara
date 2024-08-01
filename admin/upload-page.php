<?php
function negarara_upload_page() {
    ?>
    <div class="wrap">
        <h1 style="color: #540d6e;"><?php esc_html_e('Upload Images for WebP Conversion', 'negarara'); ?></h1>
        <div id="negarara-drop-area">
            <form id="negarara-upload-form" method="post" action="" enctype="multipart/form-data">
                <input type="file" id="negarara-file-upload" name="files[]" multiple accept="image/*">
                <div class="upload-instructions">
                    <p><?php esc_html_e('Drag & Drop your files here or click to upload', 'negarara'); ?></p>
                    <button type="button" class="button"><?php esc_html_e('Select Files', 'negarara'); ?></button>
                </div>
            </form>
        </div>
        <div id="negarara-upload-progress"></div>
    </div>
    <?php
}

add_action('wp_ajax_negarara_handle_upload', 'negarara_handle_upload');
?>
