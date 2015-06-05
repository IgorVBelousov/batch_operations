jQuery(document).ready(function($) {
  var batch_progress = function (percent){
    return $(".batch-progress > span").animate({ width: percent + "%" }, 600);
  };

  function batch_process(){
    $.post(
      ajaxurl+'?action=batch_operations&id='+batch_id,
      function(data){
        if (data['do']=='finish')
        {

          batch_progress(data['percent']);
          $('.batch-message').html(data['message']).delay(1500).queue(function () {
              $(location).attr('href',successful_page);
              $(this).dequeue();
            }
          );
        }
        else
        {
          $('.batch-message').html(data['message']);
          batch_progress(data['percent']);
          batch_process()
        }
      }
    );
  }

  batch_process();

});