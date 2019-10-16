# Tiziani Whitmyre Job Feed Plugin

## Table of contents
---

1. [Admin](#admin)

2. [Display](#display)

3. [Cron Job](#cron)

4. [To Do](#to-do)
---

## Admin Area <a name="admin"></a>

When the TW Job Feed plugin is installed and activated it creates a database table called "*prefix*_tw_job_feed_urls" where *prefix* is the wp prefix being used for the WordPress database.  

This table has three columns:
- **job_feed_slug**
  - A slug that can be used to differentiate one job feed from another, assuming that two or more XML job feeds are being used.
- **job_feed_url**
  - The url of the Jazz HR XML feed for open jobs.
- **job_feed_post_type_slug**
  - The slug of the post type to publish the jobs in the XML feed to as posts.

The admin pages for the plugin can be found at this WordPress admin URL:
*WordPress site base URL*/wp-admin/admin.php?page=tw-job-feeds

If you're viewing this page for the first time you will see a form allowing you to input the **Jazz HR Job Feed URL**, the **Job Feed Slug**, and the **slug for the WordPress post type** to publish the jobs from the XML job feed to.  

The only required input field is the **Jazz HR Job Feed URL**.

If the plugin database table already has a value for the Jazz HR Job Feed URL the form will display the values from the database and the submit button will read "Update Account Data".

Once a Job Feed URL is provided the plugin will start publishing jobs from the XML feed to the post type provided in the admin form.  

To manually clear jobs out of the database that are old or no longer relevant click the tab at the top of the admin page that reads "Refresh Open Jobs".

At the top of the "Refresh..." section is an option to "Update All Jobs In Database". Clicking this button will delete any jobs published to the post type and then rescan the XML job feed to publish a fresh batch of jobs. You can also set a cron job to perform the same task. The cron script can be found in the ``` cron/ ``` directory.

Below that you can update the job data for each individual job saved to the selected post type.

##### *Notes*

The plugin looks for new jobs and pulls them in whenever the WordPress 'init' hook is fired so the plugin is scanning for jobs and adding them whenever a WordPress page on the site is loaded. The function that reads the job feed and adds jobs to the database is named ```tw_get_jazz_hr_job_feed()``` and can be found on line 184 of the file ```inc/admin/admin-form.php```.  

Currently this function does not check the jobs published to the WP database to see if that job is still in the job feed. It only looks for any jobs in the XML job feed that is not currently in the wp_posts database table.   

### Admin Function Definitions

**tw_job_feed_scripts**

Loads css and javascript used for the plugin admin pages.

**tw_add_setup_page**

Creates the admin page within the WordPress admin area.

**tw_admin_setup_page**

Outputs the HTML for the plugin admin page. Also handles checking if values are in the plugin database table and, if so, pre-populating the Jazz HR Feed Information form.

**tw_admin_ajax_url**

Outputs the url to the admin-ajax.php file when the hook admin_head is fired.


**tw_update_jazz_hr_information**

PHP function that updates the JAZZ HR info (URL, post type slug, job slug). This function is called by an AJAX request found in ```js/tw-job-feed-admin.js``` on line 26.

**tw_get_jazz_hr_job_feed**

This function reads the JAZZ HR job feed XML and adds new jobs not found in the WordPress database.  This is called whenever the WordPress init hook is fired.

**tw_refresh_jazz_hr_job_feed_ajax**

Generates the list of "Jobs Currently in WordPress Database" found under the "Refresh Open Jobs" tab.

**tw_update_jazz_hr_information**

Updates the job information for a job already saved to the WordPress database by checking the title and job description from the XML feed against the values in the WP database. If the job is in there but the values are different the WP post data will be overwritten with the new values from the XML feed.

**tw_update_all_jobs**

Function used to refresh jobs in database when the "Update All Jobs In Database" button is clicked. This is the PHP function that is called by an AJAX request. JS for the request can be found in ```js/tw-job-feed-admin.js``` on line 79.



## Display Functions <a name="#display"></a>
---

Currently the display of jobs pulled from the XML job feed is done with a bit of short code.

That short code looks like this:

```
[JobFeed][/JobFeed]
```

The output of the job feed is handled by the function ```TwDisplayJobs()``` found on line 16 of the file ```inc/display/job-feed-display.php```.

This function pulls data from the XML job feed directly into the page . If, for some reason, the script can't connect to or parse the XML feed the script will then revert to displaying jobs published to wp_posts database.  

You can verify which source is feeding the list of open jobs by checking the HTML of the web page for an html comment.  

If you are seeing jobs from the XML feed a comment will appear in the HTML that reads

```
<!-- this job feed was returned directly from the jazz hr job feed -->
```

If the XML feed has failed and you are seeing jobs published to and pulled from the WP database you will see

```
<!-- this job feed was returned from jobs saved to the Open Jobs post type. If you are seeing this message then there was an issue parsing the XML feed and the Open Job posts are being displayed as a fallback... -->
```

##### *Notes*

The short code does not currently take any attributes as arguments. If you wanted to extend the display functionality of the plugin you could certainly modify the **TwDisplayJobs** function to use attributes to modify the display or provide an option to display the data direct from the XML feed or from the WP database. You could also use an attribute to pull data from one or more job feeds using the **job_feed_slug** column as a short code attribute.   

### Display Function Definitions

**TwDisplayJobs**

Reads the JAZZ HR Job Feed XML and generates HTML to display Job data via short code.  If the XML feed is unavailable the script pulls instead from the jobs saved to the WordPress database.

---

## Cron Functions <a name="#cron"></a>

---

The cron directory holds a script that can be run as a scheduled task from a remote server or, with some modifications, from the WordPress site using wp_cron.

Currently the script just runs a check for jobs in the XML feed. If the wp_posts table has a job that *is not in the XML feed* then the job data is deleted from the WP database.  If the job feed has a job that is in the job feed but *not in the database* then that job is added. The script includes an email option that can send an email to an account with the status of the cron job when it is run.

Currently this script is not being run as a cron job for any site Tiz Inc. supports. This script exists merely as an option if a site owner wants an automated means of clearing out old jobs and adding new ones to the database.  If the site is using the display short code with the XML being directly pulled into the page then this cron script is largely superfluous.

## To Do <a name="#to-do"></a>
---

The following are some options that could be considered if you wanted to extend the functionality of this plugin...

- Modify JAZZ HR Feed Information form to allow adding multiple XML job feeds.

- Add "Delete This Job" button to the "Jobs Currently In Database section" / tie button click to an AJAX function that deletes the WP post and post meta for that job.

- Modify short code to accept attributes allowing for either display of data directly from the XML feed or jobs saved to the WordPress database.

- Modify short code to merge data from two or more job feeds together.  
