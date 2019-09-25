<?php
/**
* Tiziani Whitmyre Jazz HR Job Feed - display functions
* Functions to display jobs from the Jazz HR feed. The default setting displays data pulled directly from the job feed XML.  If that feed fails the function displays the jobs pulled from that job feed and stored as Posts in the WP database
**/

class TwJobFeedDisplay {

  public function __construct() {
    $this -> plugin_dir = $plugin_dir;
    // add shortcode
    
    add_shortcode('JobFeed', [$this, 'TwDisplayJobs']);
  }

  public function TwDisplayJobs() {
    // empty variables to pass the display data we will return in the shortcode
    $display;
    $jobs;
    // wpdb to get job feed url from db
    global $wpdb;
    $prefix = $wpdb->prefix;
    $table = $prefix.'tw_job_feed_urls';
    // retrieve jazz job feed url from db
    $sql = $wpdb->get_results("SELECT * FROM $table");

    $rows = count($sql);


      // if the database has a job feed url pull that value out and then load the contents of the XML feed using domDocument...
      $job_feed_slug = $sql[0]->job_feed_slug;
      $job_feed_url = $sql[0]->job_feed_url;
      $job_feed_post_type_slug = $sql[0]->job_feed_post_type_slug;

      $xml = file_get_contents($job_feed_url);
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

        foreach($jobs as $j) {
          $title = $j['title'];
          $description = $j['description'];
          $description = preg_replace('#(<[a-z ]*)(style=("|\')(.*?)("|\'))([a-z ]*>)#', '\\1\\6', $description);
          $jobID = $j['id'];
          $status = $j['status'];
          $department = $j['department'];
          $city = $j['city'];
          $state = $j['state'];
          $country = $j['country'];
          $zip = $j['postalcode'];
          $type = $j['type'];
          $experience = $j['experience'];
          $button = $j['buttons'];

          $display .=
          '<!-- this job feed was returned directly from the jazz hr job feed -->
            <div class="faq-pannel">
              <div class="faq-pannel-title">
                <h4>'.$title.'</h4>
              </div>
              <div class="faq-pannel-content" style="display: none;">
                <div class="job_details">
                  <div class="detail">
                    Type: <strong>'.$type.'</strong>
                  </div>
                  <div class="detail">
                    Experience: <strong>'.$experience.'</strong>
                  </div>
                  <div class="detail">
                    Status: <strong>'.$status.'</strong>
                  </div>
                  <div class="detail">
                    Department: <strong>'.$department.'</strong>
                  </div>
                  <div class="details">
                    Location: <strong>'.$city.'</strong> | <strong>'.$state.'</strong> | <strong>'.$country.'</strong>
                  </div>
                </div><!-- end .job_details -->
                <div class="job-desciption">'.
                  $description.
                '</div>
                <div class="apply-button">'
                  .$button.
                '</div>
              </div><!-- end .faq-pannel-content -->
              <div class="show-corner">
                <div class="shape-top-left"></div>
                <div class="shape-top-right"></div>
                <div class="shape-bottom-left"></div>
                <div class="shape-bottom-right"></div>
              </div>
            </div><!-- end .faq-pannel -->';
        }
      } else {
        // if loading the xml job feed fails get jobs that have been saved as a fallback...
        $args = array(
          'post_type' => 'open_jobs',
          'posts_per_page' => -1,
          'orderby' => 'date',
          'order' => 'ASC',
        );
        $jobs = new WP_Query( $args );

        if($jobs->have_posts()):
          while($jobs->have_posts()): $jobs->the_post();
          $title = get_the_title();
          $description = get_the_content();
          // strip inline css out of jazz hr job content
          $description = preg_replace('#(<[a-z ]*)(style=("|\')(.*?)("|\'))([a-z ]*>)#', '\\1\\6', $content);
          // get post_meta for each job
          $meta = get_post_meta($post->ID, 'job_info', false);
          $jobID = $meta[0]['jobid'];
          $status = $meta[0]['Status'];
          $department = $meta[0]['Department'];
          $city = $meta[0]['City'];
          $state = $meta[0]['State'];
          $country = $meta[0]['Country'];
          $zip = $meta[0]['Postal Code'];
          $type = $meta[0]['Type'];
          $experience = $meta[0]['Experience'];
          $button = $meta[0]['Button'];

          $display .=
          '<!-- this job feed was returned from jobs saved to the Open Jobs post type. If you are seeing this message then there was an issue parsing the XML feed and the Open Job posts are being displayed as a fallback... -->
            <div class="faq-pannel">
              <div class="faq-pannel-title">
                <h4>'.$title.'</h4>
              </div>
              <div class="faq-pannel-content" style="display: none;">
                <div class="job_details">
                  <div class="detail">
                    Type: <strong>'.$type.'</strong>
                  </div>
                  <div class="detail">
                    Experience: <strong>'.$experience.'</strong>
                  </div>
                  <div class="detail">
                    Status: <strong>'.$status.'</strong>
                  </div>
                  <div class="detail">
                    Department: <strong>'.$department.'</strong>
                  </div>
                  <div class="details">
                    Location: <strong>'.$city.'</strong> | <strong>'.$state.'</strong> | <strong>'.$country.'</strong>
                  </div>
                </div><!-- end .job_details -->
                <div class="job-desciption">'.
                  $description.
                '</div>
                <div class="apply-button">'
                  .$button.
                '</div>
              </div><!-- end .faq-pannel-content -->
              <div class="show-corner">
                <div class="shape-top-left"></div>
                <div class="shape-top-right"></div>
                <div class="shape-bottom-left"></div>
                <div class="shape-bottom-right"></div>
              </div>
            </div><!-- end .faq-pannel -->';

          endwhile;
        endif;
      }
      return $display;
  }


}
?>
