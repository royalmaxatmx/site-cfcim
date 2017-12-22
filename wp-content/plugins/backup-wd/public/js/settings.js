jQuery(document).on('change', '#auth_method', function () {
    jQuery('.settings_auth').closest('tr').addClass('buwd-hide');
    if (jQuery(this).val() != 'none')
        jQuery('.' + jQuery(this).val() + '_auth').closest('tr').removeClass('buwd-hide');
});

function checkHashKey(elem) {
    if (jQuery(elem).val().length < 6) {
        jQuery(elem).addClass('buwd-input-error');
        return false;
    } else if (!(/^[a-zA-Z0-9_.-]*$/.test(jQuery(elem).val()))) {
        jQuery(elem).addClass('buwd-input-error');
        return false;
    } else {
        jQuery(elem).removeClass('buwd-input-error');
    }
    return true;
}

jQuery(document).on('keyup', '#hash_key', function () {
    checkHashKey(jQuery(this))
});

function is_valid() {
    if (!checkHashKey(jQuery('#hash_key'))) {
        jQuery('#tab-general.buwd-nav-tab').click();
        return false;
    }

    return true;
}

jQuery(document).on('click', '.buwd-api-keys .buwd-nav-tab, .buwd-settings .buwd-nav-tab', function () {
    jQuery('.buwd-tabs').find('.buwd-nav-tab').removeClass('buwd-active');
    jQuery(this).addClass('buwd-active');
    var content_tab_id = jQuery(this).attr('id').replace('tab-', 'option-');
    jQuery('.buwd-tab-option').addClass('buwd-hide');
    jQuery('#' + content_tab_id).removeClass('buwd-hide');

    /*user guide*/
    jQuery('.update-nag.buwd-active').removeClass('buwd-active').addClass('buwd-hide');
    jQuery('.' + jQuery(this).attr('id') + '-guide').removeClass('buwd-hide').addClass('buwd-active');

    return false;
});

function save_settings(form_id) {
    var tab = jQuery('.buwd-nav-tab.buwd-active').attr('id').replace('tab-', '');
    jQuery('#current_tab').val(tab);
    jQuery('#' + form_id).submit();
}


