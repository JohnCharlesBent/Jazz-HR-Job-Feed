# Tiziani Whitmyre Job Feed Plugin

## Table of contents
---

1. [Admin Functions](#admin)
  - Admin area
  -
2. [Display Functions](#display)
3. [Cron Job Functions](#cron)
---

### Admin Functions <a name="admin"></a>

When the TW Job Feed plugin is installed and activated it creates a database table called "*prefix*_tw_job_feed_urls" where *prefix* is the wp prefix being used in the database.  

This table has three columns:
- job_feed_slug
  - A slug that can be used to differentiate one job feed from another, assuming that two or more XML job feeds are being used.
- job_feed_url
  - The url of the Jazz HR XML feed for open jobs.
- job_feed_post_type_slug
  - The slug of the post type to publish the jobs in the XML feed to as posts.

The admin pages for the plugin can be found at this WordPress admin URL:
*WordPress site base URL*/wp-admin/admin.php?page=tw-job-feeds

If you're viewing this page for the first time you will see a form allowing you to input the **Jazz HR Job Feed URL**, the **Job Feed Slug**, and the **slug for the WordPress post type** to publish the jobs from the XML job feed to.  

The only required input field is the **Jazz HR Job Feed URL**.

If the plugin database table already has a value for the Jazz HR Job Feed URL the form will display the values from the database and the submit button will read "Update Account Data".

Once a Job Feed URL is provided the plugin will start publishing jobs from the XML feed to the post type provided in the admin form.  

To manually clear jobs out of the post type that are old or no longer relevant click the tab at the top of the admin page that reads "Refresh Open Jobs".

At the top of the "Refresh..." section is an option to "Update All Jobs In Database". Clicking this button will delete any jobs published to the post type and then rescan the XML job feed to publish a fresh batch of jobs.

Below that you can update the job data for each individual job saved to the selected post type.

#### *Notes*
---
The plugin looks for new jobs and pulls them in whenever the WordPress 'init' hook is fired. The init hook runs after WordPress has finished loading but before headers are sent, so the plugin is scanning for jobs and adding them whenever a WordPress page on the site is loaded. The function that reads the job feed is named ```tw_get_jazz_hr_job_feed()``` and can be found on line 184 of the file ```inc/admin/admin-form.php```.  

Currently this function does not check the jobs published to the WP post type to see if that job is still in the job feed. It only looks for any jobs in the XML job feed that is not currently in the wp_posts database table.   

---
### Display Functions <a href="#display"></a>
---

Currently the display of jobs pulled from the XML job feed is done with a bit of shortcode.  
That shortcode, and the output of the job feed, is handled by the function ```TwDisplayJobs()``` found on line 16 of the file ```inc/display/job-feed-display.php```.

This function displays the data from the XML job feed directly. If, for some reason, the script can't connect to or parse the XML feed the script will then revert to displaying jobs published to wp_posts database.  

You can verify which source is feeding the list of open jobs by checking the HTML of the web page for an html comment.  

If you are seeing jobs from the XML feed a comment will appear in the HTML that reads

```
<!-- this job feed was returned directly from the jazz hr job feed -->
```

If the XML feed has failed and you are seeing jobs published to and pulled from the WP database you will see

```
<!-- this job feed was returned from jobs saved to the Open Jobs post type. If you are seeing this message then there was an issue parsing the XML feed and the Open Job posts are being displayed as a fallback... -->
```
