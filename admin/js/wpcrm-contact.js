(function( $ ) {
	'use strict';
  //document ready
	$( function() {
    $('#_wpcrm_contact-address1').parent().hide();
    $('#_wpcrm_contact-address2').parent().hide();
    $('#_wpcrm_contact-city').parent().hide();
    $('#_wpcrm_contact-state').parent().hide();
    $('#_wpcrm_contact-postal').parent().hide();
    $('#_wpcrm_contact-country').parent().hide();
    $('#_wpcrm_contact-attach-to-organization-new').parent().hide();
    $('#_wpcrm_contact-website').parent().hide();
    //let's put the phone in the same column as the email and fax
    var phone = $('#_wpcrm_contact-phone-edit').parent();
    var mobile = $('#_wpcrm_contact-mobile-phone-edit').parent();
    $('#_wpcrm_contact-email-edit').closest('div.wp-crm-one-half').append(phone).append(mobile);
    //$('#').parent().hide();


  });

})( jQuery );
