jQuery(document).ready(function($) {
  var batch_progress = function ( data ) {
    $( '.batch-progress-message' ).html( data['progress_message'] );
    $( '.batch-percent' ).html( data['percent'] + "%" );
    $( '.batch-progress > span' ).animate({ width: data['percent'] + "%" }, 500);
  };

  function batch_process() {
    $.post(
      ajaxurl+'?action=batch_operations&id='+batch_id,
      function(data){
        if (data['do']=='finish')
        {
          batch_progress( data );
          $('.batch-message').html(data['message']).delay(1500).queue(function () {
              $(location).attr('href',successful_page);
              $(this).dequeue();
            }
          );
        }
        else
        {
          $('.batch-message').html(data['message']);
          batch_progress( data );
          batch_process()
        }
      }
    );
  }

  batch_process();

});