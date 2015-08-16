=== Batch operations ===
Contributors: igor-v-belousov
Tags: batch, dev, development, prod, production
Requires at least: 3.5
Tested up to: 4.2
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

My version Drupal Batch API for WordPress.

== Description ==

Batch API need for running long-term operation without using the function
`set_time_limit` or CLI.

Long-term operation is divided into short-term sub-operations which are executed
in the order queue. Sub-operation can send a message below the progress bar.

Example code:

    $batch['title']='Title';
    $batch['operations'][]=array('test_batch_operation',array());
    
    batch_operations_start($batch);
    
    function test_batch_operation(&$context) {
      file_put_contents( ABSPATH . 'test.txt', "batch work" );
      $context['message'] = 'Yeap! Work!';
    }

== Installation ==

1. Upload latest stable version of code <https://github.com/IgorVBelousov/batch_operations/archive/master.zip>.
2. Unpack this archive to the `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Use batch operations in your code.

