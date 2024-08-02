jQuery(document).ready(function ($) {
    $(document).on('change', '.mwpl_range', function (e) {
        let el = $(this),
            valueWrapper = el.closest('option_field').find('range_value')

        valueWrapper.text(el.val())
    })
    $(document).on('input', '.mwpl_range', function (e) {
        let mwpl_this = $(this),
            valueWrapper = mwpl_this.parent().find('.range_value')
        valueWrapper.text(`( ${mwpl_this.val()} )`)
    });
})