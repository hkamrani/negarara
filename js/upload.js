jQuery(document).ready(function($) {
    var dropArea = $('#negarara-drop-area');
    var fileInput = $('#negarara-file-upload');
    var uploadProgress = $('#negarara-upload-progress');
    var uploadForm = $('#negarara-upload-form');

    dropArea.on('dragover', function(e) {
        e.preventDefault();
        e.stopPropagation();
        dropArea.addClass('drag-over');
    });

    dropArea.on('dragleave', function(e) {
        e.preventDefault();
        e.stopPropagation();
        dropArea.removeClass('drag-over');
    });

    dropArea.on('drop', function(e) {
        e.preventDefault();
        e.stopPropagation();
        dropArea.removeClass('drag-over');
        var files = e.originalEvent.dataTransfer.files;
        fileInput[0].files = files;
        uploadFiles(files);
    });

    fileInput.on('change', function(e) {
        var files = e.target.files;
        uploadFiles(files);
    });

    $('.upload-instructions .button').on('click', function() {
        fileInput.click();
    });

    function uploadFiles(files) {
        var formData = new FormData();
        $.each(files, function(i, file) {
            formData.append('files[]', file);
        });
        formData.append('action', 'negarara_handle_upload');

        $.ajax({
            url: negarara_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function() {
                uploadProgress.html('<div class="progress"><div class="progress-bar"><span class="progress-text">Please wait...</span></div></div>');
            },
            xhr: function() {
                var xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener('progress', function(e) {
                    if (e.lengthComputable) {
                        var percentComplete = (e.loaded / e.total) * 50;
                        uploadProgress.find('.progress-bar').css('width', percentComplete + '%');
                        uploadProgress.find('.progress-text').text('Please wait... ' + Math.round(percentComplete) + '%');
                    }
                }, false);
                return xhr;
            },
            success: function(response) {
                uploadProgress.html('<div class="success-message">Images have been uploaded and converted to WebP!</div>');
            },
            error: function(xhr, status, error) {
                var errorMessage = '<div class="error-message">An error occurred: ' + error + '<br>Debug info: ' + xhr.responseText + '</div>';
                uploadProgress.html(errorMessage);
            }
        });
    }
});
