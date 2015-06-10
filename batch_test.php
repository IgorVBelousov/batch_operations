<?php
/**
 * Plugin Name: Batch operations TEST
 * Description: DEV TEST
 * Version: 0.1.0
 * Author: Igor V Belousov
 * Author URI: http://belousovv.ru/
 */

add_action( 'admin_menu', 'batch_operations_test_add_page' );

function batch_operations_test_add_page() {
  add_management_page( 'Batch operations TEST', 'Batch TEST', 'edit_posts', 'batch-operations-test', 'batch_operations_test_page_view' );
}

function batch_operations_test_page_view() {

  $test = ( empty ($_REQUEST["test"] ) )? 0 : $_REQUEST["test"];

  switch ($test) {
    case 2:
      $batch['operations'][]=array('test_batch_operation',array());
      batch_operations_start($batch);
      break;

    case 3:
      $batch['title']="Custom Title";
      $batch['init_message']="Custom Init Message";
      $batch['operations'][]=array('test_batch_operation',array());
      batch_operations_start($batch);
      break;

    case 4:
      $batch['operations'][]=array('test_batch_operation',array());
      $batch['operations'][]=array('test_batch_operation',array());
      $batch['operations'][]=array('test_batch_operation',array());
      $batch['operations'][]=array('test_batch_operation',array());
      $batch['operations'][]=array('test_batch_operation',array());
      batch_operations_start($batch);
      break;

    case 5:
      $batch['progress_message']='Current=%current%. Total=%total%.';
      $batch['operations'][]=array('test_batch_operation',array());
      $batch['operations'][]=array('test_batch_operation',array());
      $batch['operations'][]=array('test_batch_operation',array());
      $batch['operations'][]=array('test_batch_operation',array());
      $batch['operations'][]=array('test_batch_operation',array());
      batch_operations_start($batch);
      break;

    case 6:
      $batch['operations'][]=array('test_batch_operation_context_finished',array());
      $batch['operations'][]=array('test_batch_operation',array());
      $batch['operations'][]=array('test_batch_operation',array());
      batch_operations_start($batch);
      break;

    case 7:
      $batch['operations'][]=array('test_batch_operation_params',array('a',1));
      $batch['operations'][]=array('test_batch_operation_params',array('b',2));
      $batch['operations'][]=array('test_batch_operation_params',array('c',3));
      batch_operations_start($batch);
      break;

    case 8:
      $batch['operations'][]=array(array('TestBatch','Operations'),array());
      $batch['operations'][]=array(array('TestBatch','Operations'),array());
      batch_operations_start($batch);
      break;

    default:
      break;
  }

  ?>
  <div class="wrap">
    <?php screen_icon(); ?>
    <h2><?php echo get_admin_page_title() ?></h2>
    <ol>
      <li><a href="tools.php?page=batch-operations&id=0">$current_array is empty</a>
      <li><a href="tools.php?page=batch-operations-test&test=2">Default <strong>title</strong> & <strong>init_message</strong></a>
      <li><a href="tools.php?page=batch-operations-test&test=3">Set <strong>custom title</strong> & <strong>custom init_message</strong></a>
      <li><a href="tools.php?page=batch-operations-test&test=4">5 operations default <strong>progress_message</strong></a>
      <li><a href="tools.php?page=batch-operations-test&test=5">5 operations custom <strong>progress_message</strong></a>
      <li><a href="tools.php?page=batch-operations-test&test=6">Test <strong>$context['finished']</strong></a>
      <li><a href="tools.php?page=batch-operations-test&test=7">Test params</a>
      <li><a href="tools.php?page=batch-operations-test&test=8">Test operations in class</a>
    </ol>
  </div>
  <?php
}

function test_batch_operation( &$context ) {
  sleep(1);
  $context['message'] = 'Yeap! Work!';
}

function test_batch_operation_context_finished( &$context ) {
  if ( empty( $context['sandbox'] ) ) {
    // First run
    $context['sandbox']['current_id'] = 0;
    $context['sandbox']['max'] = 5 ;
  }
  sleep(2);
  $context['message'] = 'Test $context[\'finished\']. current_id = ' . $context['sandbox']['current_id'];
  $context['sandbox']['current_id']++;
  if ( $context['sandbox']['current_id'] < $context['sandbox']['max'] ) {
    $context['finished'] = false;
  } else {
    $context['finished'] = true;
  }
}

function test_batch_operation_params( $a, $b, &$context ) {
  sleep(2);
  $context['message'] = '$a=' . $a . ' $b=' . $b ;
}

class TestBatch {

  public function Operations(&$context){
    sleep(2);
    $context['message'] = 'class TestBatch';
  }

}