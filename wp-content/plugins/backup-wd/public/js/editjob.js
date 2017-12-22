/**
 * tab onclick submit
 */
jQuery(document).on('click', '#buwd-job .buwd-nav-tab', function () {
    var redirect_tab = jQuery(this).attr('id').replace('tab-', '');
    if (redirect_tab != jQuery('#current_tab').val()) {
        jQuery('#tab').val(redirect_tab);
        jQuery('#buwd-form').submit();
        return false;
    }
});


/**
 * job type db table selection
 */
jQuery(document).on('click', '.db-table-check', function () {
    if (jQuery(this).attr('id') == 'dball') {
        jQuery('.type-db-tables').prop('checked', true);
    } else {
        jQuery('.type-db-tables').prop('checked', false);
        if (jQuery(this).attr('id') == 'dbprefix') {
            jQuery('input[value^="' + buwd.db_prefix + '"].type-db-tables').prop('checked', true);
        }
    }
});

/**
 * job format name
 */
jQuery(document).on('focus', '.buwd-format-name', function () {
    jQuery(this).closest('.buwd-value').find('.buwd-filename-replacement').removeClass('buwd-hide');
    if (!jQuery(this).hasClass('already_triggered')) {
        jQuery(this).addClass('already_triggered');
    }
});

jQuery(document).click(function () {
    jQuery('.buwd-filename-replacement').addClass('buwd-hide');
});

jQuery(document).on('click', '.buwd-format-name', function () {
    return false;
});

jQuery(document).on('click', '.buwd-filename-replacement a', function () {
    var addText = jQuery(this).data('value');
    var text = jQuery('#archive_name').val();
    var cursorPos = jQuery('#archive_name').prop('selectionStart')
    var beforeText = text.substring(0, cursorPos);
    var afterText = text.substring(cursorPos, text.length);
    jQuery('#archive_name').val(beforeText + addText + afterText);

    var archive_name = jQuery('#archive_name').val();

    jQuery('.buwd-filename-preview-text').text(gen_file_name(archive_name));
});


jQuery(document).on('change', '#archive_name', function () {
    var archive_name = jQuery('#archive_name').val();
    jQuery('.buwd-filename-preview-text').text(gen_file_name(archive_name));

});

// day functions
jQuery(document).on('click', '#buwd_days .buwd-day-span', function () {
    if (jQuery(this).hasClass("buwd-day-span-active")) {
        jQuery(this).removeClass("buwd-day-span-active");
    }
    else {
        jQuery(this).addClass("buwd-day-span-active");
    }
    updateScheduleDay();
});

function updateScheduleDay() {
    var days = [];
    jQuery(".buwd-day-span-active").each(function () {
        days.push(jQuery(this).attr("data-value"));
    });
    jQuery("[name=scheduleday]").val(days);
}

/**
 * Gen File Name
 */

function gen_file_name(name) {
    var name = name.replace('{hash_key}', buwd.hash_key);
    var types = ['%d', '%j', '%m', '%n', '%y', '%Y', '%a', '%A', '%g', '%G', '%h', '%H', '%i', '%s'];
    var values = [];
    var date = new Date();

    for (var i in types) {
        values[i] = date.dateFormat(types[i].replace('%', '')) + '';
    }

    for (var i in types) {
        newName = replaceAll(name, types[i], values[i]);
        name = newName;
    }

    return newName;

}

function escapeRegExp(str) {
    return str.replace(/([.*+?^=!:${}()|\[\]\/\\])/g, "\\$1");
}


function replaceAll(str, find, replace) {
    return str.replace(new RegExp(escapeRegExp(find), 'g'), replace);
}

/**
 * job schedule
 */
jQuery(document).on('change', '.buwd-schedule', function () {
    if (jQuery(this).val() == 'wpcron' || jQuery(this).val() == 'easycron' || jQuery(this).val() == 'lotus') {
        var type = jQuery('.buwd-schedule-type:checked').val();
        jQuery('.tr-schedulelink').addClass('buwd-hide');
        jQuery('.tr-scheduletype').removeClass('buwd-hide');

        if (type == 'basic') {
            jQuery('.scheduleadvanced').closest('tr').addClass('buwd-hide');
            jQuery('.tr-cron_expression').removeClass('buwd-hide');

        }
        else {
            jQuery('.tr-cron_expression').closest('tr').addClass('buwd-hide');
            jQuery('.scheduleadvanced').closest('tr').removeClass('buwd-hide');
        }
    } else if (jQuery(this).val() == 'link') {
        jQuery(this).closest('tr').nextAll().addClass('buwd-hide');
        jQuery('.tr-schedulelink').removeClass('buwd-hide');
    } else {
        jQuery(this).closest('tr').nextAll().addClass('buwd-hide');
    }
});

jQuery(document).on('change', '.buwd-schedule-type', function () {

    var type = jQuery(this).val();

    if (type == 'basic') {
        jQuery(this).closest('tr').nextAll().addClass('buwd-hide');
        jQuery('.tr-cron_expression').removeClass('buwd-hide');

    }
    else {
        jQuery(this).closest('tr').nextAll().addClass('buwd-hide');
        jQuery('.scheduleadvanced').closest('tr').removeClass('buwd-hide');
    }

});


/**
 * files backup
 */
jQuery(document).on('change', '.bup_root', function () {
    jQuery('.bup_root_choices').closest('tr').addClass('buwd-hide');
    if (jQuery(this).prop('checked')) {
        jQuery('.bup_root_choices').closest('tr').removeClass('buwd-hide');
    }
});

jQuery(document).on('change', '.bup_content', function () {
    jQuery('.bup_content_choices').closest('tr').addClass('buwd-hide');
    if (jQuery(this).prop('checked')) {
        jQuery('.bup_content_choices').closest('tr').removeClass('buwd-hide');
    }
});

jQuery(document).on('change', '.bup_themes', function () {
    jQuery('.bup_themes_choices').closest('tr').addClass('buwd-hide');
    if (jQuery(this).prop('checked')) {
        jQuery('.bup_themes_choices').closest('tr').removeClass('buwd-hide');
    }
});

jQuery(document).on('change', '.bup_plugins', function () {
    jQuery('.bup_plugins_choices').closest('tr').addClass('buwd-hide');
    if (jQuery(this).prop('checked')) {
        jQuery('.bup_plugins_choices').closest('tr').removeClass('buwd-hide');
    }
});

jQuery(document).on('change', '.bup_uploads', function () {
    jQuery('.bup_uploads_choices').closest('tr').addClass('buwd-hide');
    if (jQuery(this).prop('checked')) {
        jQuery('.bup_uploads_choices').closest('tr').removeClass('buwd-hide');
    }
});

jQuery(document).on('click', '.bup_choices', function () {
    //alert(jQuery(this).attr('class'));
    var folder_size = jQuery(this).attr('data-size');

    if (folder_size) {
        var class_name = jQuery(this).attr('class').replace('_choices bup_choices', '');
        var folders_size = jQuery('.' + class_name).attr('data-size');
        var diff_sum = this.checked ? parseInt(folders_size) - parseInt(folder_size) : parseInt(folders_size) + parseInt(folder_size);
        jQuery('.' + class_name).attr('data-size', diff_sum);
        jQuery('label[for="' + class_name + '-1"]' + ' > span').html('( ' + formatBytes(diff_sum) + ' )');
    }
});

function formatBytes(bytes) {
    if (bytes == 0) return '0 Bytes';
    var k = 1024,
        dm = 2,
        sizes = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'],
        i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
}
/**
 *end for files backup
 **/


jQuery(document).on('click', '.buwd-type', function () {
    if (jQuery(this).val() == 'archive') {
        jQuery('.tr-archive_name ,.tr-archive_format').removeClass('buwd-hide');
        jQuery('#destination-email, #destination-ftp, #destination-sugarsync, #destination-amazon-glacier, #destination-gdrive').parent().parent().removeClass('buwd-hide');
    } else {
        jQuery('.tr-archive_name ,.tr-archive_format').addClass('buwd-hide');
        jQuery('#destination-email, #destination-ftp, #destination-sugarsync, #destination-amazon-glacier, #destination-gdrive').parent().parent().addClass('buwd-hide');
        jQuery('#destination-email, #destination-ftp, #destination-sugarsync, #destination-amazon-glacier, #destination-gdrive').attr('checked', false);
        jQuery('#tab-destination-email, #tab-destination-ftp, #tab-destination-sugarsync, #tab-destination-amazon-glacier, #tab-destination-gdrive').css('display', 'none');
    }
});


/**
 * google drive authentication
 **/

/*
 jQuery(document).on('click', '.gdrive-auth', function () {
 var data = {
 'action': 'buwd-gdrive',
 };

 jQuery.post( buwd.ajaxurl, data, function( response ) {
 });
 return false;
 });*/

jQuery(document).on('change', '#db_name,.wp_connection,#db_host,#db_user,#db_password', function () {
    var data = {};

    data['action'] = 'buwd-type-db';
    data['db_name'] = jQuery('#db_name').val();
    data['db_host'] = jQuery('#db_host').val();
    data['db_password'] = jQuery('#db_password').val();
    data['use_wp_connection'] = 0;
    data['job_id'] = jQuery('#job_id').val();
    if (jQuery('.wp_connection').prop('checked'))
        data['use_wp_connection'] = 1;


    if (!jQuery('.wp_connection').prop('checked')) {
        jQuery('.tr-db_host,.tr-db_user,.tr-db_password').removeClass('buwd-hide')
    }
    else {
        jQuery('.tr-db_host,.tr-db_user,.tr-db_password').addClass('buwd-hide')
    }

    jQuery('.tr-db_name').addClass('buwd-hide');
    jQuery('.tr-dbtables').addClass('buwd-hide');
    jQuery('.tr-dbtables_all .buwd-value').insertLoading('Connecting To Database');

    jQuery.post(buwd.ajaxurl, data, function (response) {
        jQuery('.tr-dbtables .buwd-value').removeLoading();
        if (jQuery(response).find('tr.tr-dbtables').length) {
            jQuery('.tr-dbtables').html(jQuery(response).find('tr.tr-dbtables').html()).removeClass('buwd-hide');
            console.log(jQuery(response).find('tr.tr-dbtables').prop('class'));
            jQuery('.tr-dbtables_all').html(jQuery(response).find('tr.tr-dbtables_all').html()).removeClass('buwd-hide');
            jQuery('.buwd-tables-all').attr('checked', false);
            if (!jQuery('.wp_connection').prop('checked'))
                jQuery('.tr-db_name').removeClass('buwd-hide');
        }
        else {
            jQuery('.tr-dbtables_all').html('<td class="buwd-value" colspan="2"><p class="buwd-error">Cannot establish a connection</p></td>');
            jQuery('.tr-dbtables').addClass('buwd-hide');
        }
    });
    return false;
});

jQuery(document).ready(function () {

    if (!jQuery('.wp_connection').prop('checked'))
        jQuery('.buwd_db_settings').closest('tr').removeClass('buwd-hide');
    else
        jQuery('.buwd_db_settings').closest('tr').addClass('buwd-hide');


});


jQuery(document).on('change', '#s3accesskey,#s3privatekey,#s3service', function () {
    var data = {};

    data['action'] = 'buwd-amazon-s3';
    data['buwd_ajax'] = '1';
    data['job_id'] = jQuery('#job_id').val();
    data['region'] = jQuery('#s3service').val();
    data['key'] = jQuery('#s3accesskey').val();
    data['secret'] = jQuery('#s3privatekey').val();
    data['endpoint'] = jQuery('#s3endpoint').val();
    jQuery('.tr-s3bucket .buwd-value').insertLoading('Fetching buckets');
    jQuery.post(buwd.ajaxurl, data, function (response) {
        jQuery('.tr-s3bucket').removeLoading();
        jQuery('.tr-s3bucket').html(jQuery(response).find('tr.tr-s3bucket').html());
    });
});

jQuery(document).on('change', '#glacier_accesskey,#glacier_privatekey,#glacier_region', function () {
    var data = {};

    data['action'] = 'buwd-amazon-glacier';
    data['buwd_ajax'] = '1';
    data['region'] = jQuery('#glacier_region').val();
    data['job_id'] = jQuery('#job_id').val();

    data['key'] = jQuery('#glacier_accesskey').val();
    data['secret'] = jQuery('#glacier_privatekey').val();
    jQuery('.tr-glacier_vault .buwd-value').insertLoading('Fetching vaults');
    jQuery.post(buwd.ajaxurl, data, function (response) {
        jQuery('.tr-glacier_vault').removeLoading();
        jQuery('.tr-glacier_vault').html(jQuery(response).find('tr.tr-glacier_vault').html());
    });
});

jQuery(document).on('change', '#azurename,#azurekey', function () {
    var data = {};

    data['action'] = 'buwd-azure';
    data['buwd_ajax'] = '1';
    data['job_id'] = jQuery('#job_id').val();

    data['acc_name'] = jQuery('#azurename').val();
    data['access_key'] = jQuery('#azurekey').val();
    jQuery('.tr-azurecontainer .buwd-value').insertLoading('Fetching containers');
    jQuery.post(buwd.ajaxurl, data, function (response) {
        jQuery('.tr-azurecontainer').removeLoading();
        jQuery('.tr-azurecontainer').html(jQuery(response).find('tr.tr-azurecontainer').html());
    });
});

jQuery(document).on('change', '#rscuser,#rsckey,#rscregion', function () {
    var data = {};

    data['action'] = 'buwd-rsc';
    data['buwd_ajax'] = '1';
    data['job_id'] = jQuery('#job_id').val();

    data['username'] = jQuery('#rscuser').val();
    data['api_key'] = jQuery('#rsckey').val();
    data['rsc_region'] = jQuery('#rscregion').val();
    jQuery('.tr-rsccontainer .buwd-value').insertLoading('Fetching containers');
    jQuery.post(buwd.ajaxurl, data, function (response) {
        jQuery('.tr-rsccontainer').removeLoading();
        jQuery('.tr-rsccontainer').html(jQuery(response).find('tr.tr-rsccontainer').html());
    });
});

jQuery(document).on('click', '#buwd_sugarsync_auth', function () {
    var data = {};

    data['action'] = 'buwd-sugarsync';
    data['buwd_ajax'] = '1';
    data['sugar_auth'] = '1';
    data['sugar_email'] = jQuery('#sugar_email').val();
    data['sugar_pass'] = jQuery('#sugar_pass').val();
    data['job_id'] = jQuery('#job_id').val();

    jQuery('.tr-sugar_error').addClass('buwd-hide');
    jQuery('.buwd-sugar-loading').removeClass('buwd-hide').insertLoading('Authenticating');
    jQuery.post(buwd.ajaxurl, data, function (response) {
        jQuery('.buwd-sugar-loading').html('').addClass('buwd-hide').removeLoading();

        if (jQuery(response).find('#sugartoken').val() != '') {
            jQuery('.tr-sugar_email')[0].outerHTML = jQuery(response).find('tr.tr-sugar_email')[0].outerHTML;
            jQuery('.tr-sugar_pass')[0].outerHTML = jQuery(response).find('tr.tr-sugar_pass')[0].outerHTML;
            jQuery('.tr-sugarsyncfolder').prev()[0].outerHTML = jQuery(response).find('tr.tr-sugarsyncfolder').prev()[0].outerHTML;
            jQuery('.tr-sugarsyncfolder')[0].outerHTML = jQuery(response).find('tr.tr-sugarsyncfolder')[0].outerHTML;
            jQuery('.tr-sugartoken').html(jQuery(response).find('tr.tr-sugartoken').html());
        }
        jQuery('.tr-sugar_error')[0].outerHTML = jQuery(response).find('tr.tr-sugar_error')[0].outerHTML;

    });
});

jQuery(document).on('click', '#schedulemonth-any, #scheduleweek-any', function () {
    var this_class = jQuery(this).attr('id').replace('-any', '');
    if (jQuery(this).prop('checked')) {
        jQuery('.' + this_class).attr('checked', 'checked');
    }
    else {
        jQuery('.' + this_class).removeAttr('checked');
    }
});


jQuery(document).on('change', '#scheduleweek', function () {
    if (jQuery('#scheduleweek option')[0].selected) {
        jQuery('#scheduleweek option').prop('selected', true);
    }
});

jQuery(document).on('change', '#scheduleday', function () {
    if (jQuery('#scheduleday option')[0].selected) {
        jQuery('#scheduleday option').prop('selected', true);
    }
});

jQuery(window).load(function () {

    if (typeof(jQuery('#cron_expression').val()) != 'undefined') {
        if (jQuery('#cron_expression').val() == '' || jQuery('#scheduletype-advanced').prop('checked')) {
            cron_value = "0 0 1 * *"
        }
        else {
            cron_value = jQuery('#cron_expression').val();
        }

        jQuery('#buwd_cron_expression_select').cron({
            initial: cron_value,
            onChange: function () {
                jQuery('#cron_expression').val(jQuery(this).cron("value"));
            }
        });
    }

});

jQuery(".job-type").on('change', function () {
    buwd_toggle_checkbox()
});

jQuery(window).on('load', function () {
    jQuery.validator.addMethod("check_db_connection", function (value, element) {
        
        if (jQuery('.tr-dbtables_all .buwd-value').find('p.buwd-error').length) {
            return false;
        }
        return true;

    }, 'Please enter correct mysql credentials');

    buwd_toggle_checkbox();

    jQuery('#buwd-form').validate({
        rules: {
            name: {
                required: true
            },
            archive_name: {
                required: true
            },
            folderdelete: {
                required: true
            },
            gdrivefiledelete: {
                required: true
            },
            dboxfiledelete: {
                required: true
            },
            azurefiledelete: {
                required: true
            },
            rscfiledelete: {
                required: true
            },
            ftpfiledelete: {
                required: true
            },
            ftptimeout: {
                required: true
            },
            glacier_filedelete: {
                required: true
            },
            s3filedelete: {
                required: true
            },
            db_host: {
                check_db_connection: true
            },
            db_user: {
                check_db_connection: true
            },
            db_password: {
                check_db_connection: true
            }
        }
    });

    jQuery('.bup_choices').each(function () {
        var folder_size = jQuery(this).attr('data-size');

        if (folder_size) {
            console.log(folder_size);
            var class_name = jQuery(this).attr('class').replace('_choices bup_choices', '');
            var folders_size = jQuery('.' + class_name).attr('data-size');
            var diff_sum = this.checked ? parseInt(folders_size) - parseInt(folder_size) : parseInt(folders_size) /*: parseInt(folders_size) + parseInt(folder_size)*/;
            jQuery('.' + class_name).attr('data-size', diff_sum);
            jQuery('label[for="' + class_name + '-1"]' + ' > span').html('( ' + formatBytes(diff_sum) + ' )');
        }
    })


});

function buwd_toggle_checkbox() {
    if (jQuery(".job-type:checked").length == 1) {
        jQuery(".job-type:checked").addClass('buwd-disabled')
    }
    else {
        jQuery(".job-type").removeClass('buwd-disabled')
    }
}

function get_auth_url(type) {
    var data = {};
    data['type'] = type;
    data['page'] = 'buwd_editjob';
    data['action'] = 'buwd-dropbox';

    jQuery.post(buwd.ajaxurl, data, function (response) {
        if (response) {
            window.open(response);
        }
    });
}

jQuery(document).on('change', '.buwd-tables-all', function () {
    if (jQuery(this).prop('checked')) {
        jQuery('.tr-dbtables').addClass('buwd-hide');
    }
    else {
        jQuery('.tr-dbtables').removeClass('buwd-hide');

    }

});