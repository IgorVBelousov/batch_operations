<?php
/**
 * Plugin Name: Batch operations
 * Description: My version Drupal Batch API for WordPress.
 * Version: 0.1.0
 * Author: Igor V Belousov
 * Author URI: http://belousovv.ru/
 */

// Add backend page without menu item
add_action( 'admin_menu', 'batch_operations_add_page' );

// Add JSON query for run operation
add_action( 'wp_ajax_batch_operations', 'batch_operations_process' );

// Add translations
add_action( 'init', 'batch_operations_load_translation_file' );

// Add huck for view messages
add_action( 'admin_notices', 'batch_operations_notice_view' );

global $batch_operations_version;
$batch_operations_version = '0.1.0';

/**
 * Load translation file
 */
function batch_operations_load_translation_file() {
  load_plugin_textdomain( 'batch-operations', false, '/batch_operations/languages' );
}

/**
 * Add backend page without menu item
 */
function batch_operations_add_page() {
  add_submenu_page( null, 'Batch operations', 'Batch operations', 'edit_posts', 'batch-operations', 'batch_operations_page_view' );
}

/**
 * View batch operations page
 */
function batch_operations_page_view() {
  global $batch_operations_version;

  wp_enqueue_script( 'jquery' );
  wp_enqueue_script( 'batch_operations_script', plugin_dir_url('') . 'batch_operations/js/batch.min.js', array(), $batch_operations_version );
  wp_enqueue_style( 'batch_operations_script', plugin_dir_url('') . 'batch_operations/css/batch.css', array(), $batch_operations_version );

  $id = ( empty( $_REQUEST["id"] ) )? 0 : $_REQUEST["id"];
  if ( ! preg_match( '/^[\d,A-F]*$/', $id ) || ( strlen( $id ) != 39 ) ) {
    $id = 0;
  }

  if ( false === ( $current_array = get_transient( 'batch_' . $id ) ) ) {
    $id = 0;
  }

  $title = __( 'Processing', 'batch-operations' );
  $init_message = '';
  if ( ! empty( $current_array ) ) {
    $title = ( empty ( $current_array['title'] ) ) ? $title : $current_array['title'] ;
    $init_message = ( empty ( $current_array['init_message'] ) ) ? __( 'Initializing.', 'batch-operations' ) : $current_array['init_message'] ;
  }

  ?>
  <script type="text/javascript">
    var batch_id='<?php print $id; ?>',successful_page='<?php echo $current_array['successful_page']; ?>';
  </script>
  <div class="wrap">
    <h2><?php echo $title ?></h2>
    <div class="batch-progress">
      <span style="width:0%;"></span>
    </div>
    <div class="batch-progress-message"><?php echo $init_message; ?></div><div class="batch-percent"></div>
    <div class="batch-message"></div>

  </div>
  <?php
}

/**
 * Execution operations from $batch['operations']
 */
function batch_operations_process () {
  $id = ( empty( $_REQUEST["id"] ) )? 0 : $_REQUEST["id"];
  if ( ! preg_match( '/^[\d,A-F]*$/', $id ) || ( strlen( $id ) != 39 ) ) {
    wp_send_json( array( 'do' => 'finish' ) );
  }

  if ( false === ( $current_array = get_transient( 'batch_' . $id ) ) ) {
    wp_send_json( array( 'do' => 'finish' ) );
  }

  $result['do'] = '';
  $start = time() + 1;
  $flag = true;

  while ($flag) {
    //make array of parameters for function
    $parameters_array = array();
    if ( isset( $current_array['operations'][0][1] ) ) {
      $parameters_array = $current_array['operations'][0][1];
    }
    $parameters_array[] = &$current_array['context'];
    //run function
    call_user_func_array( $current_array['operations'][0][0], $parameters_array );

    if ( true == $current_array['context']['finished'] ) {
      $current_array['context']['sandbox'] = array();
      array_splice( $current_array['operations'], 0, 1 );
      $current_array['current']++;
    }

    if ( time() > $start || 0 == count( $current_array['operations'] ) ) {
      $flag=false;
    }
  }

  if ( 0 == count( $current_array['operations'] ) ) {
    $result['do']='finish';
  }

  $result['percent'] = round( $current_array['current'] / ($current_array['count'] / 100 ) );
  $result['progress_message'] = str_replace(
    array(
      '%current%',
      '%total%'
      ),
    array(
      $current_array['current'],
      $current_array['count']
    ),
    __( $current_array['progress_message'], 'batch-operations')
  );
  $result['message'] = $current_array['context']['message'];

  if ( '' == $result['do'] ) {
    set_transient( 'batch_' . $id, $current_array , WEEK_IN_SECONDS );
  } else {
    delete_transient( 'batch_' . $id );
  }

  wp_send_json( $result );
}


/**
 * Start batch operations
 *
 * <pre>
 * $batch = array(
 *   'title' => t('Exporting'),
 *   'operations' => array(
 *     array('my_function_1', array(123, 'qwe')),
 *     array('my_function_2', array()),
 *   ),
 *   'finished' => 'my_finished_callback',
 * );
 *
 * batch_operations_start($batch);
 * </pre>
 *
 * <ul>
 * <li> operations: (required) Array of operations to be performed, where each item is an array consisting of the name
 *      of an implementation of callback_batch_operation() and an array of parameter. Example:
 * <li> title: A safe, translated string to use as the title for the progress page. Defaults to __('Processing').
 * <li> init_message: Message displayed while the processing is initialized. Defaults to __('Initializing.').
 * <li> progress_message: Message displayed while processing the batch. Available placeholders are %current% and %total%.
 * <li> error_message: Message displayed if an error occurred while processing the batch. Defaults to __('An error has occurred.').
 * <li> finished: Name of an implementation of callback_batch_finished(). This is executed after the batch has completed. This should be used to perform any result massaging that may be needed, and possibly save data in $_SESSION for display after final page redirection.
 * </ul>
 *
 * Sample callback_batch_operation():
 *
 * <pre>
 * function my_function_1($id, $text, &$context) {
 *   $context['results'][] = $text . $id;
 *   $context['message'] = 'Text + id ='.$text . $id;
 * }
 * </pre>
 *
 * The $context array gathers batch context information about the execution (read),
 *  as well as 'return values' for the current operation (write)
 *  The following keys are provided :
 *
 * 'results' (read / write): The array of results gathered so far by
 *   the batch processing, for the current operation to append its own.
 *
 * 'message' (write): A text message displayed in the progress page.
 * The following keys allow for multi-step operations :
 *
 * 'sandbox' (read / write): An array that can be freely used to
 *   store persistent data between iterations. It is recommended to
 *   use this instead of $_SESSION, which is unsafe if the user
 *   continues browsing in a separate window while the batch is processing.
 *
 * 'finished' (write): A float number between 0 and 1 informing
 *   the processing engine of the completion level for the operation.
 *   1 (or no value explicitly set) means the operation is finished
 *   and the batch processing can continue to the next operation.
 *
 * @param array $batch_arr array operations and more
 * @param string $redirect Url to redirect to when the batch has finished processing
 */
function batch_operations_start( $batch_arr, $redirect = NULL )
{
  $id = rand( 100, 999 ) . strtoupper( md5( date( 'YMDBs' ) ) ) . rand( 1000, 9999 );

  $batch_arr['context'] = array(
    'message'  => '',
    'sandbox'  => array(),
    'finished' => true,
    'results'  => array()
  );
  $batch_arr['count']   = count( $batch_arr['operations'] );
  $batch_arr['current'] = 0;

  if ( empty( $redirect ) ) {
    $batch_arr['successful_page'] =  get_admin_url();
  } else {
    $batch_arr['successful_page'] = $redirect;
  }

  if ( empty( $batch_arr['progress_message'] ) ) {
    $batch_arr['progress_message'] = __( 'Completed %current% of %total%.' );
  }

  set_transient( 'batch_' . $id, $batch_arr , WEEK_IN_SECONDS );
  $location = get_admin_url( null, 'tools.php' ) . "?page=batch-operations&id=" . $id;

  if ( ! headers_sent() ) {
    wp_redirect( $location );
  } else {
    // if header is set then runs this hack
    echo '<script type="text/javascript">';
    echo 'document.location.href="' .  $location .'";';
    echo '</script>';
    echo '<noscript>';
    echo '<meta http-equiv="refresh" content="0;url=' . $location . '" />';
    echo '</noscript>';
  }
  exit(0);
}

/**
 * Set message for next view for current user
 *
 * @param string $message message text
 * @param string $type type of message: info, success, warning, error. default - info
 */
function batch_operations_notice( $message, $type = 'info' ) {

  if ( false === ( $messages = get_transient( 'batch_operations_notice' ) ) ) {
    $messages = array();
  }

  $messages[ get_current_user_id() ][] = array(
                                          'message' => $message,
                                          'type'    => $type
                                         );

  set_transient( 'batch_operations_notice', $messages );

}

/**
 * View message
 */
function batch_operations_notice_view() {
  if ( $messages = get_transient( 'batch_operations_notice' )  ) {
    $current_uid = get_current_user_id();
    if ( ! empty( $messages[ $current_uid ] ) ) {
      // if version < 4.1.1 then add css
      global $wp_version;

      $version = explode( '.', $wp_version );

      if ( 4 > $version[0] || ( ( 4 == $version[0] && 1 >= $version[1] ) && 2 == count( $version ) ) ) {
        global $batch_operations_version;
        wp_enqueue_style( 'batch_operations_script', plugin_dir_url('') . 'batch_operations/css/notice.css', array(), $batch_operations_version );
      }

      // print message
      foreach ( $messages[ $current_uid ] as $key => $value ) {
        echo '<div class="notice notice-' . $value['type'] . '"><p>' . $value['message'] . '</p></div>';
      }

      //delete messages
      unset( $messages[ $current_uid ] );

      if ( empty( $messages ) ) {
        delete_transient( 'batch_operations_notice' );
      } else {
        set_transient( 'batch_operations_notice', $messages );
      }
    }
  }
}