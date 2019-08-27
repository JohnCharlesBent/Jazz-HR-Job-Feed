<?php
/**
* Tiziani Whitmyre Jazz HR Job Feed - admin forms
* Forms that handle submitting and processing admin data such as the Jazz HR Job Feed URL, post type slug, creating a post type if needed, and submitting/deleting from database
**/

class TwJobFeedAdmin {


  public function __construct() {
    $this -> plugin_dir = $plugin_dir;
    add_action('admin_enqueue_scripts', [$this, 'tw_job_feed_scripts']);
    add_action('admin_menu', [$this, 'tw_add_setup_page']);
    // make admin-ajax.php file
    add_action('admin_head', [$this, 'tw_admin_ajax_url']);
    // update account information
    add_action('wp_ajax_update_information', [$this, 'tw_update_jazz_hr_information']);
    // adding an ajax call back for getting and publishing the job feed just in case we want to add a button that allows the client to update the feed manually...
    add_action('wp_ajax_refresh_job_feeds', [$this,'tw_refresh_jazz_hr_job_feed_ajax']);
    // call the get job feed function during init actions...
    add_action('init', [$this, 'tw_get_jazz_hr_job_feed']);
    // ajax function to update job data for one job in database
    add_action('wp_ajax_update_job', [$this, 'tw_update_job_information']);
    // ajax function to delete delete all open jobs posts then add all jobs from JAZZ HR feed
    add_action('wp_ajax_update_all', [$this, 'tw_update_all_jobs']);
  }
  // enqueue scripts and styles for the admin pages
  public function tw_job_feed_scripts( $hook ) {
    if($hook != 'toplevel_page_tw-job-feeds') {
      return;
    } else {
      wp_register_style('tw-job-feed-admin', plugin_dir_url(dirname(dirname(__FILE__))).'dist/css/tw-job-feed-admin.css', false, '1.0.0');

      wp_enqueue_style('tw-job-feed-admin');

      wp_enqueue_script('tw-job-feed-js', plugin_dir_url(dirname(dirname(__FILE__))).'dist/js/tw-job-feed-admin.js', array('jquery'), '1.0.0', true);
    }

  }

  //add admin pages
  public function tw_add_setup_page() {
    add_menu_page('Job Feeds', 'Job Feeds', 'manage_options', 'tw-job-feeds', [$this, 'tw_admin_setup_page'], 'dashicons-megaphone', 2);
  }

  // form and content for main admin page
  public function tw_admin_setup_page() {
    global $wpdb;
    $form;
    $submit_value = 'Submit';
    $prefix = $wpdb->prefix;
    $table = $prefix.'tw_job_feed_urls';
    // check database to see if the job feed table already has a feed
    $sql = $wpdb->get_results("SELECT * FROM $table");

    $rows= count($sql);

    if($rows != 0) {
      $job_feed_slug = $sql[0]->job_feed_slug;
      $job_feed_url = $sql[0]->job_feed_url;
      $job_feed_post_type_slug = $sql[0]->job_feed_post_type_slug;
      $submit_value = 'Update Account Data';
    }

    ?>
    <div class="tw-job-feed-admin__wrapper">
      <h1 class="tw-title">Jazz HR Job Feed</h1>
      <hr>


      <div class="form-admin-messages">
      </div>

    <?php if($rows != 0) { ?>
      <div class="admin-tabs">
        <span class="tab active" data-id="admin-form">Jazz HR Feed Information</span>

        <span class="tab" data-id="refresh-current-feeds">Refresh Open Jobs</span>
      </div>
    <?php } ?>

    <div class="form_wrapper">
      <p>
        Pull job postings from your Jazz HR account into your WordPress site. Provide the URL for the Jazz HR XML job feed.
      </p>
    <form id="tw-jazz-hr-admin">
      <fieldset>
        <label>
          Jazz HR Job Feed URL <span class="req">*</span>
          <input type="text" name="job_feed_url" value="<?php echo $job_feed_url; ?>" placeholder="Enter the Job Feed URL" required="required"/>
        </label>
        <label>
          Job Feed Slug
          <span class="instructions">Provide a <strong>slug</strong> to help identify this job feed. <em>Ex: 'job-feed-slug'</em></span>
          <input type="text" name="job_feed_slug" value="<?php echo $job_feed_slug; ?>" placeholder="Enter a Job Feed Slug"/>
        </label>
        <label>
          Publish new Jobs to this Post Type
          <span class="instructions">Provide the <strong>slug</strong> for the WordPress post type you want new jobs published to. <em>Ex: 'post-type-slug'</em></span>
          <input type="text" name="job_feed_post_type_slug" value="<?php echo $job_feed_post_type_slug; ?>" placeholder="Enter a Post Type Slug" required="required"/>
          </label>
      </fieldset>
      <fieldset>
        <input type="submit" value="<?php echo $submit_value; ?>" id="job-feed-submit"/>
      </fieldset>
    </form>
    </div><!-- end .form_wrapper -->
  </div><!-- end.tw-job-feed-admin__wrapper -->

  <div class="refresh_job_feeds hidden">
    <div class="jobs">
      <div class="resync_button_wrapper">
        <h3>Update All Jobs in WordPress Database</h3>
        <div class="messages" style="display: none;"></div>
        <p class="warning">
          This will delete all jobs currently in your WordPress database and replace them with all jobs currently in your Jazz HR Job Feed.
        </p>
        <form id="resync_job_feed">
          <input type="hidden" name="job_feed_url" value="<?php echo $job_feed_url; ?>" />
          <input type="hidden" name="job_feed_post_type_slug" value="<?php echo $job_feed_post_type_slug; ?>" />
          <input type="submit" id="resync_all_jobs" value="Update All Jobs In Database" />
        </form>
      </div>
      <hr>
      <div class="wp_open_jobs">
        <h3>Jobs Currently in WordPress Database</h3>
        <div class="messages" style="display: none;"></div>
        <p>Update Open Jobs currently in your WordPress database with current data from your Jazz HR Job Feed.</p>
        <ul class="wp_open_jobs_list">
        </ul>
      </div>

    </div>
  </div>

  <div class="admin-modal hidden">
    <div class="modal-content">
      <div class="modal-spinner">
        <div class="double-bounce1"></div>
        <div class="double-bounce2"></div>
      </div>
    </div>
  </div>
  <?php
  }

  public function tw_admin_ajax_url() {
    echo '<script type="text/javascript">'.
            'var ajaxUrl = "' . admin_url('admin-ajax.php').'";'.
          '</script>';

  }

  public function tw_update_jazz_hr_information() {
    $data = $_POST['data'];
    // prepare data to insert into database
    $values = array(
      'job_feed_slug' => $data[1]['value'],
      'job_feed_url' => $data[0]['value'],
      'job_feed_post_type_slug' => $data[2]['value'],
    );
    $format = array('%s', '%s', '%s');

    // check database table for rows
    global $wpdb;
    $prefix = $wpdb->prefix;
    $table = $prefix.'tw_job_feed_urls';

    $sql = $wpdb->get_results("SELECT * FROM $table");
    $rows = count($sql);

    // if table has now rows insert values into database
    if($rows === 0) {
      $wpdb->insert($table, $values, $format);
      $insert_id = $wpdb->insert_id;
      echo json_encode('Your Jazz HR information has been added to the database.');
    } else {
      $update = $wpdb->update($table, $values, array('id' => 1));
      echo json_encode('Your Jazz HR information has been updated.');
    }
    exit;
  }

  public function tw_get_jazz_hr_job_feed() {
    global $wpdb;
    $prefix = $wpdb->prefix;
    $table = $prefix.'tw_job_feed_urls';

    $sql = $wpdb->get_results("SELECT * FROM $table");
    $rows = count($sql);

    if($sql === 0) {
      echo json_encode("You have not entered any Jazz HR Job Feeds. Please add one now.");
    } else {
      // if results are found in the database then get the feed url to pull the job feed and display it
      $feed_url = $sql[0]->job_feed_url;
      $jobs_post_type = $sql[0]->job_feed_post_type_slug;

      // parse XML and move into array $jobs
      $xml = file_get_contents($feed_url); // get job feed xml
      $domDoc = new domDocument('1.0', 'utf-8');
      $jobs = array();

      if($domDoc->loadXML($xml)) {
        $items = $domDoc->getElementsByTagName('job');

        foreach($items as $item) {
          if($item->childNodes->length) {
            foreach($item->childNodes as $i) {
              $job[$i->nodeName] = $i->nodeValue;
            }
            $jobs[] = $job;
          }
        }
      }
      // get the current date to pass as the post publishing date

      $date = date('Y-m-d H:i:s');
      // get job titles and check open jobs posts to make sure the jobs aren't already in the database...
      foreach($jobs as $j) {
        //var_dump($j);
        $title = $j['title'];
        $title_slug = str_replace(' ', '-', $title);
        $title_slug = str_replace(',', '', $title);
        $title_slug = strtolower($title_slug);
        $content = $j['description'];

        $id = $wpdb->get_var("SELECT ID FROM wp_posts WHERE post_type = '$jobs_post_type' AND post_title = '$title'");

        if(empty($id)) { // if no id is retrieved from the database then publish the job data as an open_jobs post.
           $new_job = array(
              'post_type' => $jobs_post_type,
              'post_name' => $title_slug,
              'post_title' => $title,
              'post_content' => $content,
              'post_status' => 'publish',
              'post_author' => 1,
              'post_parent' => 0,
              'post_date' => $date,
            );
            $post_id = $wpdb->insert($wpdb->posts, $new_job);
            // add remaining job posting info as post meta so we can grab it and display it.

            // get newly created post id
            $new_post_id = $wpdb->get_var("SELECT ID FROM wp_posts WHERE post_type = '$jobs_post_type' AND post_title = '$title'");

            $post_meta = array(
              'jobid' => $j['id'],
              'Status' => $j['status'],
              'Department' => $j['department'],
              'City'  => $j['city'],
              'State' => $j['state'],
              'Country' => $j['country'],
              'Postal Code' => $j['postalcode'],
              'Type'  => $j['type'],
              'Experience'  => $j['experience'],
              'Button'  => $j['buttons'],
            );

            add_post_meta($new_post_id, 'job_info', $post_meta);


        } else {
          continue;
        }
      }
    }

  }

  public function tw_refresh_jazz_hr_job_feed_ajax() {
    global $wpdb;
    $prefix = $wpdb->prefix;
    $table = $prefix.'tw_job_feed_urls';

    $jobs = array();
    $job_ids = array();
    $current_open_jobs_posts = '';

    $sql = $wpdb->get_results("SELECT * FROM $table");
    $rows = count($sql);

    if($sql === 0) {
      echo json_encode("You have not entered any Jazz HR Job Feeds. Please add one now.");
    } else {
      $feed_url = $sql[0]->job_feed_url;
      $jobs_post_type = $sql[0]->job_feed_post_type_slug;

      $xml = file_get_contents($feed_url); // get job feed xml
      $domDoc = new domDocument('1.0', 'utf-8');


      if($domDoc->loadXML($xml)) {
        $items = $domDoc->getElementsByTagName('job');

        foreach($items as $item) {
          if($item->childNodes->length) {
            foreach($item->childNodes as $i) {
              $job[$i->nodeName] = $i->nodeValue;
            }
            $jobs[] = $job;
          }
        }
      }
    }

    // get job_ids from post_meta table for post_meta key `job_info`
    $post_meta = $wpdb->get_results($wpdb->prepare("SELECT meta_value FROM $wpdb->postmeta where meta_key = %s", 'job_info'));

    // move jobid numbers from post_meta array into sep. array to check against job_ids from jazz hr feed
    foreach($post_meta as $meta) {
      $meta = unserialize($meta->meta_value);
      array_push($job_ids, $meta['jobid']);
    }

    foreach($jobs as $j) {
      $title = $j['title'];
      $id = $j['id'];
      if(in_array($id, $job_ids)) {
        $get_wp_open_jobs = $wpdb->get_results("SELECT ID, post_title FROM wp_posts WHERE post_type = '$jobs_post_type' AND post_title = '$title'");

        $wp_job_title = $get_wp_open_jobs[0]->post_title;
        $wp_post_id = $get_wp_open_jobs[0]->ID;
        $post_edit_link = get_edit_post_link($wp_post_id);

        $current_open_jobs_posts .= '<li class="open_job_post">'.
                                      '<span class="title">Title: <strong>'.$wp_job_title.'</strong></span> <span class="jazz_job_id">Jazz HR Job Id: <strong>'.$id.'</strong></span>'.
                                      '<form class="sync_job_data">'.
                                        '<input type="hidden" name="post_id" value="'.$wp_post_id.'" />'.
                                        '<input type="hidden" name="post_title" value="'.$wp_job_title.'" />'.
                                        '<input type="hidden" name="jazz_job_feed" value="'.$feed_url.'" />'.
                                        '<input type="hidden" name="jobs_post_type" value="'.$jobs_post_type.'" />'.
                                        '<input type="hidden" name="job_id" value="'.$id.'" />'.
                                        '<input class="sync_job_submit" type="submit" value="Update This Job" />'.
                                        '<a class="edit_post_link" href="'.$post_edit_link.'">View WordPress Job Post</a>'.
                                      '</form>'.
                                    '</li>';
      }
    }
    echo $current_open_jobs_posts;
    exit;
  }

  public function tw_update_job_information() {
    global $wpdb;
    $prefix = $wpdb->prefix;

    $data = $_POST;

    $post_id = $data['data'][0]['value'];
    $jazz_job_feed = $data['data'][2]['value'];
    $jobs_post_type_slug = $data[3]['value'];
    $job_id = $data['data'][4]['value'];

    $xml = file_get_contents($jazz_job_feed);
    $domDoc = new domDocument('1.0', 'utf-8');
    $job_data = array();

    if($domDoc->loadXML($xml)) {
      $items = $domDoc->getElementsByTagName('job');
      foreach($items as $item) {
        if($item->childNodes->length) {
          $job_id_number = $item->getElementsByTagName('id');
          foreach($job_id_number as $j_id) {
              if($j_id->nodeValue == $job_id) {
                foreach($item->childNodes as $i) {
                  $job_info[$i->nodeName] = $i->nodeValue;
                }
              }
            }
          }
        }
        $job_data[] = $job_info;
      }
      $data = date('Y-m-d H:i:s');

      foreach($job_data as $j) {
        $title = $j['title'];
        $title_slug = str_replace(' ', '-', $title);
        $title_slug = str_replace(',', '', $title);
        $title_slug = strtolower($title_slug);
        $content = $j['description'];

        $new_post_data = array(
          'ID' => $post_id,
          'post_title' => $title,
          'post_name' => $title_slug,
          'post_content' => $content,
        );
        $update_post = wp_update_post($new_post_data);

        $post_meta = array(
          'jobid' => $j['id'],
          'Status' => $j['status'],
          'Department' => $j['department'],
          'City'  => $j['city'],
          'State' => $j['state'],
          'Country' => $j['country'],
          'Postal Code' => $j['postalcode'],
          'Type'  => 'whatever',
          'Experience'  => $j['experience'],
          'Button'  => $j['buttons'],
        );

        $update_post_meta = update_post_meta($post_id, 'job_info', $post_meta);

        if($update_post != 0) {
          echo '<h4><i>'.$job_id.' | '.$title.'</i> has been updated with new data from Jazz HR.</h4>';
          exit;
        } else {
          echo '<h4><i>'.$job_id.' | '.$title.'</i> was not updated.</h4><p>Either there was no new data and the post was not modified or an error occurred. Please contact your site administrator for more information.</p>';
          exit;
        }
      }

    }

  // delete all jobs in WordPress database
  public function tw_update_all_jobs() {
    global $wpdb;
    $prefix = $wpdb->prefix;

    $data = $_POST;

    $job_feed_url = $data['data'][0]['value'];
    $job_feed_post_type_slug = $data['data'][1]['value'];
    $post_meta_key = 'job_info';

    $delete_post_meta = $wpdb->query("DELETE from wp_postmeta WHERE meta_key = '$post_meta_key'");

    $delete_jop_posts = $wpdb->query("DELETE from wp_posts WHERE post_type = '$job_feed_post_type_slug'");

    echo "<h4>All jobs in the database have been updated with current data from Jazz HR.</h4><p>Any older job posts in the WordPress database that are not in the Jazz HR Job Feed have been deleted.</p>";
    exit;
  }

}
 ?>
