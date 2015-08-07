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

    case 9:
      $batch['operations'][]=array('test_batch_operation_params',array('c',3));
      batch_operations_start($batch,get_admin_url( null, 'tools.php' ) . "?page=batch-operations-test");
      break;

    case 10:
      $batch['operations'][]=array('test_batch_operation_messages',array());
      batch_operations_start($batch,get_admin_url( null, 'tools.php' ) . "?page=batch-operations-test");
      break;

    case 11:
      $batch = array(
        'title'            => "Test finished_callback without errors",
        'init_message'     => 'Custom Init Message',
        'progress_message' => 'Step %current% of %total%.',
        'operations'       => array(
          array('test_batch_operation_params',array('a',1)),
          array(array('TestBatch','Operations'),array()),
          array('test_batch_operation_context_finished',array()),
          array('test_batch_operation',array()),
        ),
        'finished'         => 'test_batch_operations_callback'
      );

      batch_operations_start($batch,get_admin_url( null, 'tools.php' ) . "?page=batch-operations-test");
      break;

    case 12:
      $batch = array(
        'title'            => "Test finished_callback with errors",
        'init_message'     => 'Custom Init Message',
        'progress_message' => 'Step %current% of %total%.',
        'operations'       => array(
          array('test_batch_operation_params',array('a')),
          array('test_batch_operation_params',array('Hello','World')),
          array(array('TestBatch','Operations'),array()),
          array('test_batch_operationh',array()),
        ),
        'finished'         => 'test_batch_operations_callback'
      );
      batch_operations_start($batch,get_admin_url( null, 'tools.php' ) . "?page=batch-operations-test");
      break;

    default:
      break;
  }

  ?>
  <div class="wrap">
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
      <li><a href="tools.php?page=batch-operations-test&test=9">Test redirect</a>
      <li><a href="tools.php?page=batch-operations-test&test=10">Test messages</a>
      <li><a href="tools.php?page=batch-operations-test&test=11">Test finished_callback without errors</a>
      <li><a href="tools.php?page=batch-operations-test&test=12">Test finished_callback with errors</a>
    </ol>
  </div>
  <?php
}

function test_batch_operation_messages( &$context ) {
  batch_operations_notice('default');
  batch_operations_notice('info','info');
  batch_operations_notice('success','success');
  batch_operations_notice('warning','warning');
  batch_operations_notice('error','error');
  $context['message'] = "Messages test";
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
  $context['results'][] = $a . ', ' . $b . '!';return true;
}

function test_batch_operations_callback( $success, $results, $errors ){
  if ( $success ) {
    batch_operations_notice('Test finished_callback without errors success.');
  } else {
    batch_operations_notice('Test finished_callback with errors success.');
    batch_operations_notice('<h3>results</h3><pre>' . print_r( $results, true ) . '</pre><h3>errors</h3><pre>' .
      print_r( $errors, true ) . '</pre>', 'error');
  }
}

class TestBatch {

  public function Operations(&$context){
    sleep(2);
    $context['message'] = 'class TestBatch';
  }

}