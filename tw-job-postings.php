<?php
/**
* Plugin Name: Tiziani Whitmyre Jazz HR Job Feed
* Plugin URI: https://github.com/JohnCharlesBent/Jazz-HR-Job-Feed
* Description: A plugin that pulls job listings from a Jazz HR job feed and adds each listing as a post in a WordPress custom post type.
* Version: 1.0.0
* Author: John Bent for Tiziani Whitmyre
* License: GPL v2 or later
* License URI: https://www.gnu.org/licenses/gpl-2.0.html
**/

$plugin_dir = plugin_dir_path(__FILE__);

function tw_job_posting_install() {
  global $wpdb, $wnm_db_version;

  require_once( ABSPATH . 'wp-admin/includes/upgrade.php');

  $charset_collate = $wpdb->get_charset_collate();
  $prefix = $wpdb->prefix;

  // create a db table to hold the url for the Jazz HR Job Feed
  $job_feeds = $prefix.'tw_job_feed_urls';

  $sql = "CREATE TABLE IF NOT EXISTS $job_feeds (
    id int(9) NOT NULL AUTO_INCREMENT,
    job_feed_slug varchar(250) NOT NULL,
    job_feed_url varchar(250) NOT NULL,
    job_feed_post_type_slug varchar(250) NOT NULL,
    PRIMARY KEY (id)
  ) $charset_collate;";

  dbDelta($sql);

}

register_activation_hook(__FILE__, 'tw_job_posting_install');

/**
* load required files for admin functions
**/
include('inc/admin/admin-form.php');

/**
* Instantiate plugin admin classes
**/
$admin = new TwJobFeedAdmin;
//echo $admin->tw_get_jazz_hr_job_feed();

/**
* load required files for display functions
**/
include('inc/display/job-feed-display.php');

/**
* Instantiate plugin display classes
**/

$display = new TwJobFeedDisplay;
