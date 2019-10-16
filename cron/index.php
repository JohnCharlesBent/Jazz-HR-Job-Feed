<?php
/**
* A script that can be run by a cron job to pull Jazz Job Feed XML into a WP custom post type
**/

$message; // variable to hold success or error messages that will be emailed to a support account when the cron job is run

// get path to wp-load.php file to load WP globals
$wp_load = substr( dirname(__FILE__), 0, strpos( (__FILE__), 'wp-content') ) . 'wp-load.php';

if( !empty($wp_load) && file_exists( $wp_load ) ) {
  require_once $wp_load;
  require_once ABSPATH . '/wp-admin/includes/post.php';

  $date = date('Y-m-d H:i:s');

  // get XML feed from plugin database table
  global $wpdb;
  $prefix = $wpdb->prefix;
  $account_table = $prefix.'tw_job_feed_urls';
  $sql = $wpdb->get_results("SELECT * FROM $account_table");

  $job_feed_url = $sql[0]->job_feed_url;
  $job_feed_post_type_slug = $sql[0]->job_feed_post_type_slug;

  $xml = file_get_contents($job_feed_url);
  $domDoc = new domDocument('1.0', 'utf-8');

  $jobs = array();
  $current_job_feed = array();

  if($domDoc->loadXML($xml)) {
    $items = $domDoc->getElementsByTagName('job');

    foreach($items as $item) {
      if($item->childNodes->length) {
        foreach($item->childNodes as $i) {
          $job[$i->nodeName] = $i->nodeValue; // push xml into array $job

          // push title and jobid into array for comparison with WP posts
          if($i->nodeName == 'id' || $i->nodeName == 'title') {
            array_push($current_job_feed, $i->nodeValue);
          }
        }
        $jobs[] = $job; // push $job arrays into $jobs
      }
    }
  }

  // compare job ids from XML feed to any jobs saved to the WP database
  foreach($current_job_feed as $current) {
    $job_id = $current[0];
    // get postmeta -- where the jobid and other details are stored when a job is saved as a wp post
    $get_meta = $wpdb->get_results($wpdb->prepare("SELECT meta_value, post_id from $wpdb->postmeta WHERE meta_key = 'job_info'"));

    foreach($get_meta as $meta) {
      $id = $meta->post_id;
      $data = unserialize($meta->meta_value);

      // compare $job_id to the $data['jobid'] value from the post meta. If the $job_id is not in the post meta then the job in the wp database is no longer current and should be deleted.
      if($job_id != $data['jobid']) {
        $delete_meta = delete_post_meta($id, 'job_info');
        $delete_post = $wpdb->query("DELETE FROM wp_posts WHERE ID = '$id'");
        if($delete_meta == true && $delete_post == true) { // if the result of deleting the postmeta and the post succeeds then add to the message variable
          $message .= "Job ID: " .$data['jobid']." has expired and was deleted from the WordPress database.<br><hr>";
        }
      }
    }

  }

  // loop through $jobs array, check to see if WP database already has job post, if not add job to open jobs CPT
  foreach($jobs as $job) {
    $id = $job['id'];
    $title = $job['title'];
    $title_slug = str_replace(' ', '-', $title);
    $title_slug = str_replace(',', '', $title);
    $content = $job['description'];

    // check to see if open jobs posts exists for this job...
    $check_open_jobs = post_exists($title, $content, '', 'open_jobs');


      if($check_open_jobs === 0) {
        // array of post content to publish job as open_jobs PT
        $new_job = array(
          'post_type' => $job_feed_post_type_slug,
          'post_name' => $title_slug,
          'post_title' => $title,
          'post_content' => $content,
          'post_status' => 'publish',
          'post_author' => 1,
          'post_parent' => 0,
          'post_date' => $date,
        );

        $insert = $wpdb->insert($wpdb->posts, $new_job);

        $new_post_id = $wpdb->get_var("SELECT ID FROM wp_posts WHERE post_type = '$job_feed_post_type_slug' AND post_title= '$title'");


        // push other job feed data into post meta
        $meta = array(
          'jobid' => $job['id'],
          'Status' => $job['status'],
          'Department' => $job['department'],
          'City'  => $job['city'],
          'State' => $job['state'],
          'Country' => $job['country'],
          'Postal Code' => $job['postalcode'],
          'Type'  => $job['type'],
          'Experience'  => $job['experience'],
          'Button'  => $job['buttons'],
        );

      $add_meta = add_post_meta($new_post_id, 'job_info', $meta);

      $message .= 'A job with the title '.$title.' has been added to the WordPress database. WordPress post id '. $new_post_id.'.<br><hr>';
    } else {
      $message .= 'No jobs were added to the WordPress database.';
    }
  }


} else {
  die('Could not load WordPress');
}

// email message with cron job status info...

$email = 'john.bent@tizinc.com'; // change this to the email account you want to receive the cron email, if any...
$subject = 'Open Jobs Cron Report';
$headers = "From: cronjobs@tizinc.com" . "\r\n";
mail($email, $subject, $message, $headers);

?>
