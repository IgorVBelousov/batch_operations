jQuery( document ).ready( function($) {
  var batch_progress = function ( data ) {
    $( '.batch-progress-message' ).html( data['progress_message'] );
    var percent = 0;
    if ( 'undefined' != typeof data['percent'] ) {
      percent = data['percent'];
    }
    $( '.batch-percent' ).html( percent + "%" );
    $( '.batch-progress > span' ).animate({ width: percent + "%" }, 500);
  };

  function batch_process() {
    $.post(
      ajaxurl + '?action=batch_operations&id=' + batch_id,
      function( data ) {
        if ( 'finish' == data['do'] )
        {
          batch_progress( data );
          $( '.batch-message' ).html( data['message'] ).delay( 1500 ).queue( function () {
              $( location ).attr( 'href', successful_page );
              $( this ).dequeue();
            }
          );
        }
        else
        {
          $( '.batch-message' ).html( data['message'] );
          batch_progress( data );
          batch_process()
        }
      }
    );
  }

  batch_process();

});