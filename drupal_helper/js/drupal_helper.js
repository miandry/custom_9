/**
 * @file
 * drupal_helper.js
 *
 * Defines the behaviors needed for drupal_helper integration.
 */

(function ($) {
      //   $.fn.autosubmit = function() {
      //       this.submit(function(event) {
      //           event.preventDefault();
      //           var form = $(this);
      //           console.log("ajax form processing ...");
      //           $.ajax({
      //               type: form.attr('method'),
      //               url: form.attr('action'),
      //               data: form.serialize()
      //           }).done(function(data) {
      //                   window.location.reload();
      //           }).fail(function(data) {
      //               $.alert('Échec de la suppression');
      //           });
      //       });
      //       return this;
      //   }
      // $('form[ajax]').autosubmit();
      Window.drupal_delete = function (url) {
          jQuery.ajax({
              url: url,
              error: function () {
                  // message.html("<b style='color: red'>sku error</b>");
              },
              beforeSend: function () {
                  //  message.html("Loading ...");
              },
              success: function (response) {
                  var result = (response);
                  if (result['status'] == 1) {
                      window.location.reload();
                  }else{
                      $.alert('Échec de la suppression');
                  }
              },
              type: 'GET'
          });
      }

    //**event**//

    $('a[ajax-action="delete"]').click(function(event) {
        event.preventDefault();
        var url = $(this).attr('href');
        jQuery.confirm({
            title: 'Suppression',
            content: 'Êtes-vous sûr de vouloir supprimer ce contenu ?',
            buttons: {
                delete: {
                    text: 'confirmez',
                    action: function () {
                        Window.drupal_delete(url);
                    }
                },
                cancel: function () {
                }
            }
        });

    });
}(jQuery));
