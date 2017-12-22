/*
 * function to run animation once
 * */
jQuery.fn.extend({
    animateCss: function (animationName) {
        var animationEnd = 'webkitAnimationEnd mozAnimationEnd MSAnimationEnd oanimationend animationend';
        this.addClass('animated ' + animationName).one(animationEnd, function () {
            jQuery(this).removeClass('animated ' + animationName);
        });
    },

    insertLoading: function (loadingText) {
        var loader = '<div class="buwd-loader">' +
            '<div class="rect1"></div>' +
            '<div class="rect2"></div>' +
            '<div class="rect3"></div>' +
            '<div class="rect4"></div>' +
            '<div class="rect5"></div>' +
            '</div>';
        if (loadingText) {
            loader += '<span class="buwd-loader-text">' + loadingText + '</span>'
        }

        this.html(loader);
    },

    insertLargeLoading: function (loadingText) {
        var loader = '<div class="buwd-loader-container"><div class="buwd-loader loader-big">' +
            '<div class="rect1"></div>' +
            '<div class="rect2"></div>' +
            '<div class="rect3"></div>' +
            '<div class="rect4"></div>' +
            '<div class="rect5"></div>' +
            '</div>';
        if (loadingText) {
            loader += '<div class="buwd-loader-text">' + loadingText + '</div>'
        }
        loader += '</div>';
        this.html(loader);
    },

    removeLoading: function () {
        this.find('.buwd-loader').remove();
    }
});


/**
 * toggle tab
 */
jQuery(document).on('change', '.toggle-tab', function () {
    jQuery('#tab-' + jQuery(this).attr('id')).toggle();
    jQuery('#tab-' + jQuery(this).attr('id')).animateCss('shake');
});


/** mail options **/
jQuery(document).on('change', '#sending_method', function () {
    jQuery('.buwd-mailtype').closest('tr').addClass('buwd-hide');
    jQuery('.buwd-' + jQuery(this).val()).closest('tr').removeClass('buwd-hide');

});

jQuery(document).on('click', '#run_job', function () {
    var data = {};
    data['action'] = 'buwd-run_job';
    buwd_do_test_ajax();
    jQuery.post(buwd.ajaxurl, data, function (response) {
        console.log(response);
    });
});

function buwd_do_test_ajax() {
    jQuery.post(buwd.ajaxurl, {}, function (response) {
        setTimeout(function () {
            buwd_do_test_ajax();
        }, 2000);
    });
}

//login
function buwdLogin() {
    var data = {};
    data.action = 'buwd_login';
    data.username = jQuery("#username").val();
    data.password = jQuery("#password").val();
    data.type = 'login_user';
    if (data.username == '' || data.password == '') {
        jQuery("#invalid_password").hide();
        jQuery("#required_fields").show();
        return false;
    }

    jQuery("#button_login span.spinner").css({"visibility": "visible", "display": "inline-block"});
    jQuery("#button_login").addClass("disable");
    jQuery.ajax({
        type: "POST",
        //  dataType: 'json',
        url: buwd.ajaxurl,
        data: data,
        success: function (response) {
            console.log(response);
            if (response.hasOwnProperty('error')) {
                jQuery("#invalid_password").show();
                jQuery("#button_login").removeClass("disable");
                jQuery("#button_login span.spinner").css({"visibility": "hidden", "display": "none"});
            } else {
                jQuery("#invalid_password").hide();
            }
            jQuery("#required_fields").hide();

            location.reload();
        },
        failure: function (errorMsg) {
            console.log('Failure' + errorMsg);
        },
        error: function (error) {
            console.log(error);
        }
    });
};

function buwd_run_action(url) {
    jQuery('.buwd_overlay').addClass('animated fadeIn').insertLargeLoading();
    jQuery.get(url).success(function () {
        location.reload();
    });
}

function buwd_stop_job(url) {
    jQuery('.buwd_overlay').addClass('animated fadeIn').insertLargeLoading('Stopping job, please be patient');
    jQuery.get(url);
    jQuery('.buwd-progress-message').addClass('buwd-hide');
    jQuery('.buwd-progress-log').addClass('buwd-hide');
    jQuery('.buwd-progress-stop-message').html('&nbsp;');
}

function buwd_bulk_action(page) {
    var data = {};
    data['page'] = 'buwd_' + page;
    data['action'] = jQuery('#bulk_action').val();
    data['_ajax_nonce'] = buwd.ajaxnonce;

    var elems = [];
    jQuery('.'+page+'-cb').each(function () {
        if (this.checked) {
            elems.push(jQuery(this).val());
        }
    });

    data[page] = elems;
    jQuery('.buwd_overlay').addClass('animated fadeIn').insertLargeLoading();
    jQuery.post(buwd.ajaxurl, data, function (response) {
        location.reload();
    });
}





