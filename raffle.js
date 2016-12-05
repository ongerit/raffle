$().ready(function(e) {
  var progress_html = $('.progress').html();

  $('#allrsvps').click(function(e) {
    setTimeout(function(e) {
      var shown_num = $('#all_rsvps .rsvp').show().length;

      if (shown_num > 0) {
        $('#controls').show();
      } else {
        $('#controls').hide();
      }
    }, 0);
  });
  $('#allrsvps').click();

  $('#random').click(function(e) {
    var all = $('#all_rsvps .rsvp'),
      picked_index,
      picked,
      fake,
      tries = 30;

    var animator = function(number) {
      $('#random').attr('disabled', 'disabled').removeClass('btn-primary');

      if (picked) {
        picked.removeClass('picking');
      }

      if (fake) {
        fake.remove();
      }

      picked_index = Math.round(Math.random() * (all.length - 1));
      picked = $(all[picked_index]);
      fake = picked.clone(true);
      fake.appendTo($('#winners'));

      picked.addClass('picking');

      var progress = (tries - number) * 100 / tries;

      $('.progress').addClass('progress-striped');
      $('.progress-bar').width(progress + '%');

      if (number > 0) {
        window.setTimeout(function() {
          animator(number - 1);
        }, 700 / number);
      } else {
        window.setTimeout(function() {
          if (picked) {
            picked.removeClass('picking');
          }

          if (fake) {
            fake.remove();
          }

          // actually picking
          picked.remove().appendTo($('#winners'));

          $('#random').removeAttr('disabled').addClass('btn-primary');

          setTimeout(function() {
            $('.progress').removeClass('progress-striped');
            $('.progress-bar').width('100%').addClass('progress-bar-success');
          }, 100);
        }, 1);
      }
    }

    $('.progress').html(progress_html);

    $('#winner_section').show();

    // random animation
    if (all.length > 1) {
      animator(tries);
    } else {
      $('#controls').hide();
      animator(0);
    }
  });
});
