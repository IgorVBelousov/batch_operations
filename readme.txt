=== Batch operations ===
Contributors: igor-v-belousov
Tags: batch, dev, development, prod, production
Requires at least: 3.5
Tested up to: 4.2

My version Drupal Batch API for WordPress.

== Description == 

Example code:

    $batch['title']='Title';
    $batch['operations'][]=array('test_batch_operation',array());
    
    batch_operations_start($batch);
    
    function test_batch_operation(&$context) {
      file_put_contents( ABSPATH . 'test.txt', "batch work" );
      $context['message'] = 'Yeap! Work!';
    }

