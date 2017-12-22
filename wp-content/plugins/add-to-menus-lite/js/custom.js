jQuery(document).ready(function(){

	hideAllRows();

});

function hideAllRows(){

	jQuery( '.atm_uber_settings' ).each( function(){
		jQuery(this).find('tr').not(':first').hide();
	});

}

function dialog_box( selector , height ){
	
	height = ( height != null ? height : 110 );
	
	jQuery( selector ).dialog({

		modal: true,
		resizable: false,
		dialogClass: "atm-dialog-class",
		width : 700,
		height : height,
		draggable: true,
		open: function(event, ui) {
		  	jQuery("body").css({ overflow: 'hidden' }) // Disable scrolling on open dialog
		},
		close: function(event, ui) {
		  	jQuery("body").css({ overflow: 'inherit' }) // Enable scrolling on close dialog
		}

	});

}

jQuery( document ).on( 'click' , '.atm_admin_bar' , function(){

	dialog_box( '#add_to_menu_dialog' , 550 );

});

jQuery( document ).on( 'click' , '.atm_dialog_close' , function () {
    jQuery('#add_to_menu_dialog').dialog('close');
    return false;
});

jQuery( document ).on( 'click' , '.save_atm_menu' , function(){

	var title = jQuery('[name="atm_menu_title"]').val();
	var parent_menu = jQuery('[name="atm_parent"]').val();
	parent_menu = ( parent_menu == null ? 0 : parent_menu.length );

	if( title == '' || parent_menu < 1 ){
		alert( 'Menu Title & Parent Menu are Mandatory Fields.' );
		return;
	}
	
	var parent = [];

	jQuery( '[name="atm_parent"] optgroup' ).each(function( i ){
        
		var menu_id = jQuery(this).find('.atm_top_level_menu').val(); 

		parent[i] = {};
		parent[i]['menu_id'] = menu_id;
		jQuery(this).find('option').each(function( j ) {
		    
		    if( jQuery(this).is(':selected') ){
		    	parent[i][j] = {};
		    	parent[i][j]['']
		    	parent[i][j] = jQuery(this).val();
		    }
			
		});

    });

	var roles = [];
	var x = 0;
    jQuery( '.atm_role_wrapper label input' ).each(function( i ){

    	var roleName = jQuery(this).val();

    	if( jQuery(this).is(':checked') ){
    		roles[x++] = roleName;
    	}


    });

	jQuery.ajax({
		url : atm_admin_ajax,
		data : {
			action : 'atm_save_menu',
			title : jQuery('[name="atm_menu_title"]').val(),
			class : jQuery('[name="atm_menu_class"]').val(),
			title_attribute : jQuery('[name="atm_title_attribute"]').val(),
			description : jQuery('[name="atm_menu_description"]').val(),
			parent_menu : parent,
			access_level : jQuery('[name="atm_access_level"]').val(),
			post_id : jQuery('[name="atm_post_id"]').val(),
			target : jQuery('[name="atm_open_new_tab"]:checked').val(),
			object_type : jQuery('[name="atm_object_type"]').val(),
			roles : roles
		},
		type : 'POST',
		beforeSend : function(){
			jQuery('.save_atm_menu').text( 'Saving ...' );
			jQuery('.break_buttons img').show();
			jQuery('.save_atm_menu').attr( 'disabled' , true );
		},
		success : function( result ){

			if( result.trim() == 'success' ){

				var location = window.location.href + '&atm_result=success';
				var withoutHash = location.replace( /#/, "" );
				window.location.replace( withoutHash );

			}

		}

	});

});

jQuery( document ).on( 'change' , '#add_to_menu_dialog select[name="atm_access_level"]' , function(){
	
	if( jQuery(this).val() == 2 ){

		jQuery( '.atm_edit_page_restrict_wrap' ).show(); // If logged in user selected show the roles 

	} else {

		jQuery( '.atm_edit_page_restrict_wrap' ).hide(); // If not hide the roles

	}

});

jQuery( document ).on( 'click' , '.atm_set_icon' , function(){

	jQuery('.atm_ubermenu_icons_fa_icons').slideToggle( 'fast' ); // After icon clicked hide the icons wrapper

});

jQuery(document).on( 'click' , '.atm_ubermenu_icons_fa_icons a' , function(){

	var font = jQuery(this).find('span').attr('class'); // get font awesome class
	jQuery('button.atm_set_icon i').attr( 'class' , font ); // append font awesome class to the button
	jQuery('.atm_ubermenu_icons_fa_icons').hide(); // Hide icon wrapper

});

jQuery(document).on( 'click' , '.uber_remove_icon' , function(){

	jQuery('button.atm_set_icon i').removeClass(); // remove fontawesome class
	jQuery('.atm_ubermenu_icons_fa_icons').hide(); // Hide icon wrapper

});

jQuery( document ).on( 'click' , '.atm_uber_settings .uber_label' , function(){

	hideAllRows();
	jQuery(this).closest( '.atm_uber_settings' ).find('tr').show();

});