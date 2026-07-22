/* PowerSurge Gym — trainer booking widget (AJAX slot lookup + booking submit) */
$(function () {
  'use strict';

  const $widget = $('#bookingWidget');
  if ($widget.length === 0) {
    return;
  }

  const trainerId = $widget.data('trainer-id');
  const slotsUrl = $widget.data('slots-url');
  const bookUrl = $widget.data('book-url');
  const csrf = $widget.data('csrf');
  const $date = $('#bookingDate');
  const $slots = $('#bookingSlots');
  const $message = $('#bookingMessage');

  function loadSlots() {
    const date = $date.val();
    $slots.html('<p class="text-white-50 small mb-0"><i class="bi bi-hourglass-split"></i> Loading slots...</p>');
    $message.empty();

    $.ajax({
      url: slotsUrl,
      method: 'GET',
      data: { trainer_id: trainerId, date: date },
      dataType: 'json',
    }).done(function (res) {
      if (!res.success) {
        $slots.html('<p class="text-danger small mb-0">' + res.message + '</p>');
        return;
      }
      if (res.slots.length === 0) {
        $slots.html('<p class="text-white-50 small mb-0"><i class="bi bi-calendar-x"></i> No open slots for this date.</p>');
        return;
      }
      $slots.empty();
      res.slots.forEach(function (slot) {
        const $btn = $('<button type="button" class="slot-btn"></button>').text(slot.label);
        $btn.on('click', function () {
          bookSlot(slot.start, slot.label);
        });
        $slots.append($btn);
      });
    }).fail(function () {
      $slots.html('<p class="text-danger small mb-0">Could not load slots. Please try again.</p>');
    });
  }

  function bookSlot(startTime, label) {
    if (!confirm('Book this session: ' + $date.val() + ' at ' + label + '?')) {
      return;
    }

    const $form = $('<form method="post"></form>').attr('action', bookUrl);
    $form.append($('<input type="hidden" name="_csrf">').val(csrf));
    $form.append($('<input type="hidden" name="date">').val($date.val()));
    $form.append($('<input type="hidden" name="start_time">').val(startTime));
    $('body').append($form);
    $form.trigger('submit');
  }

  $date.on('change', loadSlots);
  loadSlots();
});
