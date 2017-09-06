(function( $ ) {
  $(document).ready(function(){
    //set width of extra tab is we have one
    var width = $('#tabs .extra-project-tab').outerWidth(true) + 6 ;
    $('#tabs #ul-tabs').css('width','calc(100% - '+width+'px)');
    $('#ul-tabs').scrollTabs({
      click_callback: function(e){
        var id = $(this).data('panel');
        if(id){
          $( '#panels div.display-none' ).hide();
          $('#panels div'+id).show().find('#tasks-accordion' ).accordion({
            collapsible: true
          });
        }
      }
    });

    $('#tabs div.extra-project-tab a').click(function(){
      var id = $(this).parent().data('panel');
      if(id){
        $( '#panels div.display-none' ).hide();
        $('#panels div'+id).show();
      }
    });
  });

})( jQuery );
