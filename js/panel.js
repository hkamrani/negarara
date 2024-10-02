jQuery(document).ready(function ($) {
    $(document).on('change input', '.mwpl_range', function (e) {
        let el = $(this),
            valueWrapper = el.closest('.option_field').find('.range_value');
        valueWrapper.text(`( ${el.val()} )`);
    });
});

jQuery(document).ready(function($) {
    let isConverting = false;
    let totalImages = 0;
    let convertedImages = 0;
    let logMessages = [];

    function convertNextImage() {
        $.ajax({
            url: negararaAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'negarara_convert_single_image',
                security: negararaAjax.security,
                delete_original: $('input[name="negarara_delete_original"]:checked').length ? 1 : 0 // Get the value of the delete original checkbox
            },
            success: function(response) {
                if (response.success) {
                    convertedImages++;
                    logMessages = logMessages.concat(response.data.log);
                    updateProgress();
                    updateLog();
                    
                    if (response.data.more_images) {
                        convertNextImage();
                    } else {
                        finishConversion();
                    }
                } else {
                    handleError(response.data);
                }
            },
            error: function(xhr, status, error) {
                handleError(error);
            }
        });
    }

    function updateProgress() {
        let percentage = Math.round((convertedImages / totalImages) * 100);
        $('#negarara_conversion_progress').html(`<p>Converting: ${convertedImages} / ${totalImages} (${percentage}%)</p>`);
    }

    function updateLog() {
        let logHtml = logMessages.map(message => `<div class="${message.type}">${message.text}</div>`).join('');
        $('#negarara_conversion_log').html(logHtml);
        // Scroll to the bottom of the log
        let logContainer = $('#negarara_conversion_log');
        logContainer.scrollTop(logContainer[0].scrollHeight);
    }

    function finishConversion() {
        isConverting = false;
        $('#negarara_conversion_progress').html('<p style="color: green;">Conversion complete!</p>');
        $('#negarara_bulk_convert').prop('disabled', false);
    }

    function handleError(error) {
        console.error(error);
        logMessages.push({type: 'error', text: `Error: ${error}`});
        updateLog();
        isConverting = false;
        $('#negarara_bulk_convert').prop('disabled', false);
    }

    $('#negarara_bulk_convert').on('click', function(e) {
        e.preventDefault();
        if (isConverting) return;

        if (!confirm('Are you sure you want to convert all images? This process cannot be undone.')) {
            return;
        }

        isConverting = true;
        $(this).prop('disabled', true);
        $('#negarara_conversion_progress').html('<p>Preparing to convert images...</p>');
        $('#negarara_conversion_log').html('');
        logMessages = [];

        // First, get the total number of images to convert
        $.ajax({
            url: negararaAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'negarara_get_convertible_image_count',
                security: negararaAjax.security
            },
            success: function(response) {
                if (response.success) {
                    totalImages = response.data.count;
                    convertedImages = 0;
                    updateProgress();
                    convertNextImage();
                } else {
                    handleError(response.data);
                }
            },
            error: function(xhr, status, error) {
                handleError(error);
            }
        });
    });
});
