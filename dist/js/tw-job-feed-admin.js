var $ = jQuery;

var modal = $('.admin-modal');

function getJobFeeds() {
  $.ajax({
    type: 'POST',
    url: ajaxUrl,
    data: {
      action: 'refresh_job_feeds',
    },
    success: function(response) {
      modal.removeClass('hidden');
      $('.refresh_job_feeds .jobs .wp_open_jobs_list').empty();
      $('.refresh_job_feeds .jobs .wp_open_jobs_list').append(response);
      setTimeout(function() {
        modal.addClass('hidden');
      }, 2000);
      $('.refresh_job_feeds').removeClass('hidden');

    }
  })
}

// submit of update Jazz HR Job feed data to database
$('input#job-feed-submit').on('click', function(el){
  el.preventDefault();
  var form = $('#tw-jazz-hr-admin');
  var data = form.serializeArray();

  $.ajax({
    type: 'POST',
    url: ajaxUrl,
    data: {
      action: 'update_information',
      data: data,
    },
    success: function(response) {
      //console.log(response);
      modal.removeClass('hidden');

      setTimeout(function() {
        modal.addClass('hidden');
      }, 2000);

      var message = response.replace('"', '');

      $('.form-admin-messages').prepend('<h3 class="message">'+response+'</h3>');

      setTimeout(function() {
        window.location.reload();
      }, 4000);
    }
  })
});

$('.admin-tabs span.tab').on('click', function() {
  var tabs = $('.admin-tabs span.tab');
  var data_id = $(this).attr('data-id');

  tabs.removeClass('active');
  $(this).addClass('active');

  switch(data_id)  {
    case 'refresh-current-feeds':
      $('.form_wrapper').slideUp('fast');
      getJobFeeds();
    break;
    case 'admin-form':
      $('.refresh_job_feeds').addClass('hidden');
      $('.form_wrapper').slideDown('fast');
    break;
    default:

  }

});

$('input#resync_all_jobs').on('click', function(el) {
  el.preventDefault();
  var form = $('form#resync_job_feed');
  var formData = form.serializeArray();

  $.ajax({
    type: 'POST',
    url: ajaxUrl,
    data: {
      action: 'update_all',
      data: formData,
    },
    success: function(response) {
      $('.resync_button_wrapper .messages').append(response);
      $('.resync_button_wrapper .messages').slideDown("slow");
      setTimeout(function() {
        $('.resync_button_wrapper .messages').slideUp("slow").empty();
        location.reload();
      }, 5000)
    },
  });
});

$('body').on('click', 'input.sync_job_submit', function(el){
  el.preventDefault();
  var formData = $(this).parent('form').serializeArray();
  $.ajax({
    type: 'POST',
    url: ajaxUrl,
    data: {
      action: 'update_job',
      data: formData,
    },
    success: function(response) {
      $('.wp_open_jobs .messages').append(response);
      $('.wp_open_jobs .messages').slideDown("slow");
      setTimeout(function(){
        $('.wp_open_jobs .messages').slideUp("slow").empty();
        location.reload();
      }, 5000);
    }
  })

});
