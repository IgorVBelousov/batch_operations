<?php
/**
 * Plugin Name: Batch operations
 * Description: My version Drupal Batch API for WordPress.
 * Version: 0.1.0
 * Author: Igor V Belousov
 * Author URI: http://belousovv.ru/
 */

// Create table on activate
register_activation_hook( ABSPATH . PLUGINDIR . '/batch_operations/batch.php', 'batch_operations_install');
// Delete table if deactivate
register_deactivation_hook( ABSPATH . PLUGINDIR . '/batch_operations/batch.php', 'batch_operations_deactivate' );

// Add backend page without menu item
add_action( 'admin_menu', 'batch_operations_add_page' );

// Add JSON query for run operation
add_action( 'wp_ajax_batch_operations', 'batch_operations_process' );

// Add translations
add_action( 'init', 'batch_operations_load_translation_file');

global $batch_operations_version;
$batch_operations_version = '0.1.0';


/**
 * Create table on activate
 */
function batch_operations_install () {
  if ( ! current_user_can( 'activate_plugins' ) )
    return;

  global $wpdb;

  $table_name = $wpdb->prefix . 'batch_operations';
  //WP>=3.5
  $charset_collate = $wpdb->get_charset_collate();

  $sql = "CREATE TABLE $table_name (
	  `id` bigint(20) NOT NULL AUTO_INCREMENT,
    `operations` longtext NOT NULL,
    PRIMARY KEY (`id`)
	) $charset_collate AUTO_INCREMENT=1;";

  require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
  dbDelta( $sql );

}

/**
 * Delete table if deactivate
 */
function batch_operations_deactivate(){
  if ( ! current_user_can( 'activate_plugins' ) )
    return;

  global $wpdb;

  $query = 'DROP TABLE `' . $wpdb->prefix . 'batch_operations' . '`';
  $wpdb->query( $query );
}

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
  global $wpdb;
  //WP>=3.3
  wp_enqueue_script( 'jquery' );
  wp_enqueue_script( 'batch_operations_script', plugin_dir_url('') . 'batch_operations/js/batch.min.js', array(), $batch_operations_version );
  wp_enqueue_style( 'batch_operations_script', plugin_dir_url('') . 'batch_operations/css/batch.css', array(), $batch_operations_version );

  $id = ( intval( $_REQUEST["id"] ) < 0 )? 0 : intval( $_REQUEST["id"] );

  $current_array = $wpdb->get_var( 'SELECT `operations` FROM `' . $wpdb->prefix . "batch_operations` WHERE `id` = $id;" );

  $title = __( 'Processing', 'batch-operations' );
  $init_message = '';
  if ( ! empty( $current_array ) ) {
    $current_array = unserialize( $current_array );
    $title = ( empty ( $current_array['title'] ) ) ? $title : $current_array['title'] ;
    $init_message = ( empty ( $current_array['init_message'] ) ) ? __( 'Initializing.', 'batch-operations' ) : $current_array['init_message'] ;
  }

  ?>
  <script type="text/javascript">
    var batch_id=<?php print $id; ?>,successful_page='<?php print get_admin_url(); ?>';
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

function batch_operations_process () {
  global $wpdb;
  $id = ( intval( $_REQUEST["id"] ) < 0 )? 0 : intval( $_REQUEST["id"] );

  if ( 1 > $id ){
    wp_send_json( array( 'do' => 'finish' ) );
  }

  $current_array = $wpdb->get_var( 'SELECT `operations` FROM `' . $wpdb->prefix . "batch_operations` WHERE `id` = $id;" );
  if ( empty( $current_array ) ) {
    wp_send_json( array( 'do' => 'finish' ) );
  }

  $result['do'] = '';
  $start = time() + 1;
  $flag = true;
  $current_array = unserialize( $current_array );

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
    $wpdb->update(
      $wpdb->prefix . 'batch_operations',
      array( 'operations' => serialize( $current_array ) ),
      array( 'id' => $id ),
      array( '%s' ),
      array( '%d' )
    );
  } else {
    $wpdb->query( 'DELETE FROM `' . $wpdb->prefix . 'batch_operations' . "` WHERE `id`=$id ;" );
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
 * <li> operations: (required) Array of operations to be performed, where each item is an array consisting of the name of an implementation of callback_batch_operation() and an array of parameter. Example:
 * <li> title: A safe, translated string to use as the title for the progress page. Defaults to __('Processing').
 * <li> init_message: Message displayed while the processing is initialized. Defaults to __('Initializing.').
 * <li> progress_message: Message displayed while processing the batch. Available placeholders are %current% and %total%.
 * <li> error_message: Message displayed if an error occurred while processing the batch. Defaults to __('An error has occurred.').
 * <li> finished: Name of an implementation of callback_batch_finished(). This is executed after the batch has completed. This should be used to perform any result massaging that may be needed, and possibly save data in $_SESSION for display after final page redirection.
 *
 * Sample callback_batch_operation():
 *
 * <pre>
 * function my_function_1($id, $text, &$context) {
 *   $context['results'][] = $text . $id;
 *   $context['message'] = 'Text + id ='.$text . $id;
 * }
 *
 * The $context array gathers batch context information about the execution (read),
 *  as well as 'return values' for the current operation (write)
 *  The following keys are provided :
 * 'results' (read / write): The array of results gathered so far by
 *   the batch processing, for the current operation to append its own.
 * 'message' (write): A text message displayed in the progress page.
 * The following keys allow for multi-step operations :
 * 'sandbox' (read / write): An array that can be freely used to
 *   store persistent data between iterations. It is recommended to
 *   use this instead of $_SESSION, which is unsafe if the user
 *   continues browsing in a separate window while the batch is processing.
 * 'finished' (write): A float number between 0 and 1 informing
 *   the processing engine of the completion level for the operation.
 *   1 (or no value explicitly set) means the operation is finished
 *   and the batch processing can continue to the next operation.
 * </pre>
 *
 * @param array $batch_arr array operations and more
 */
function batch_operations_start($batch_arr)
{
  global $wpdb;

  $batch_arr['context'] = array(
    'message'  => '',
    'sandbox'  => array(),
    'finished' => true,
    'results'  => array()
  );
  $batch_arr['count']   = count( $batch_arr['operations'] );
  $batch_arr['current'] = 0;

  if ( empty( $batch_arr['progress_message'] ) ) {
    $batch_arr['progress_message'] = __('Completed %current% of %total%.');
  }

  $wpdb->insert(
    $wpdb->prefix . 'batch_operations',
    array(
      'operations' => serialize( $batch_arr )
    ),
    array(
      '%s'
    )
  );

  $location = get_admin_url(null, 'tools.php') . "?page=batch-operations&id=" . $wpdb->insert_id;

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