(function( $ ) {
	'use strict';

  $(document).ready(function() {
    //reset value
    if($('span#_wpcrm_contact-email-text').length){
      $('input#_wpcrm_contact-email-input').val('');
      $('input#_wpcrm_contact-email-input').show();
      $('span#_wpcrm_contact-email-text').hide();
    }
    $('input#_wpcrm_contact-email-input').addClass('highlight-error');
  });
})( jQuery );
