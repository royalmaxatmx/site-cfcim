jQuery(document).on( 'click' , '.menu_access_level_wrap input' , function(){

	if( jQuery(this).is(':checked') ){

		if( jQuery(this).val() == 2 ){

			jQuery(this).closest('.menu_access_level_wrap').next('.restrict_roles_wrap').slideDown();

		} else {
			jQuery(this).closest('.menu_access_level_wrap').next('.restrict_roles_wrap').slideUp();
		}

	}

});

jQuery(document).on( 'click' , '.restrict_roles_wrap input' , function(){

	var roles = 0;
	jQuery(this).closest('.restrict_roles_wrap').find('input').each(function(){

		if( jQuery(this).is(':checked') ){
    		++roles;
    	} 

	});

	if( roles == 0 ){
		jQuery(this).closest('.restrict_roles_wrap').find('.atm_notice').show();
	} else {
		jQuery(this).closest('.restrict_roles_wrap').find('.atm_notice').hide();
	}

});
