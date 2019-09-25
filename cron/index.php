<?php
/**
* A script that can be run by a cron job to pull Jazz Job Feed XML into a WP custom post type
**/

// get path to wp-load.php file to load WP globals
$wp_load = substr( dirname(__FILE__), 0, strpos( (__FILE__), 'wp-content') ) . 'wp-load.php';

if( !empty($wp_load) && file_exists( $wp_load ) ) {
  require_once $wp_load;

  $current_job_feed = array();

  // get jobs from jazz hr feed ...
  global $wpdb;
  $prefix = $wpdb->prefix;


  $account_table = $prefix.'tw_job_feed_urls';
  $sql = $wpdb->get_results("SELECT * FROM $account_table");
  $job_feed_slug = $sql[0]->job_feed_slug;
  $job_feed_url = $sql[0]->job_feed_url;
  $job_feed_post_type_slug = $sql[0]->job_feed_post_type_slug;

  $xml = file_get_contents($job_feed_url);
  $domDoc = new domDocument('1.0', 'utf-8');

  if($domDoc->loadXML($xml)) {
    $items = $domDoc->getElementsByTagName('job');

    foreach($items as $item) {
      if($item->childNodes->length) {
        foreach($item->childNodes as $i) {
          if( $i->nodeName == 'id' || $i->nodeName == 'title') {
            array_push($current_job_feed, $i->nodeValue);
          }
        }
      }
    }
  }
  //var_dump($current_job_feed);

  // get Open Jobs posts from WordPress DB
  $get_posts = $wpdb->get_results("SELECT id, post_title FROM wp_posts WHERE post_type = '$job_feed_post_type_slug'");

  //var_dump($get_posts);

  foreach($get_posts as $post) {
    $id = $post->id;
    $meta = get_post_meta($id, 'job_info',false);
    $job_id = $meta[0]['jobid'];
    $title = $post->post_title;
    //check current_job_feed array for both job title and job id pulled from WP post and postmeta. If not present delete WP post and postmeta...
    if(!in_array($title, $current_job_feed) && !in_array($job_id, $current_job_feed)) {

      $delete_post_meta = $wpdb->query("DELETE from wp_postmeta WHERE post_id = '$id' AND meta_key = 'job_info'");

      $delete_job_posts = $wpdb->query("DELETE from wp_posts WHERE ID = '$id' AND post_type = '$job_feed_post_type_slug'");

      echo '<pre>'$title . ' has been deleted from the database</pre>';
    }
  }

} else {
  die('Could no load WordPress');
}



?>
