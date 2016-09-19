(function( $ ) {
	//'use strict';

  jQuery('document').ready(function() {

          jQuery('#submit-cuar-pp').on('click', function(e) {
                  // call registerChange like any add
                  wpNavMenu.registerChange();

                  // call our custom function
                  cuarNavAddWidgettoMenu();
          });

          function cuarNavAddWidgettoMenu( ) {

                  // initialise object
                  menuItems = { };

                  // the usual method for ading menu Item
                  processMethod = wpNavMenu.addMenuItemToBottom;

                  var t = jQuery('.cuar-nav-div');

                  // Show the ajax spinner
                  t.find('.spinner').show();

                  // regex to get the index
                  re = /menu-item\[([^\]]*)/;

                  m = t.find('.menu-item-db-id');
                  // match and get the index
                  listItemDBIDMatch = re.exec(m.attr('name')),
                          listItemDBID = 'undefined' == typeof listItemDBIDMatch[1] ? 0 : parseInt(listItemDBIDMatch[1], 10);

                  // assign data
                  menuItems[listItemDBID] = t.getItemData('add-menu-item', listItemDBID);
                  //menuItems[listItemDBID]['menu-item-description'] = description;

                  if(menuItems[listItemDBID]['menu-item-title'] === ''){
                          menuItems[listItemDBID]['menu-item-title'] = '(Untitled)';
                  }

                  // get our custom nonce
                  nonce = jQuery('#cuar-private-pages-nonce').val();

                  // set up params for our ajax hack
                  params = {
                          'action': 'ajax_cuar_nav',
                          'cuar-nav-nonce': nonce,
                          'menu-item': menuItems[listItemDBID]
                  };
                  // call it
                  jQuery.post(ajaxurl, params, function(objectId) {

                          // returns the incremented object id, add to ui
                          jQuery('#cuar-nav-div .menu-item-object-id').val(objectId);

                          // now call the ususl addItemToMenu
                          wpNavMenu.addItemToMenu(menuItems, processMethod, function() {
                                  // Deselect the items and hide the ajax spinner
                                  t.find('.spinner').hide();
                                  // Set form back to defaults
                                  //jQuery('#gs-sim-title').val('').blur();
                                  //jQuery('#gs-sim-html').val('');

                          });

                  });


          }

  });


})( jQuery );
