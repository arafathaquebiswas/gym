/* PowerSurge Gym — AJAX contact form submission */
$(function () {
  'use strict';

  $('#contactForm').on('submit', function (e) {
    e.preventDefault();

    const $form = $(this);
    const $btn = $form.find('button[type="submit"]');
    const $alert = $('#contactAlert');

    $btn.prop('disabled', true).text('Sending...');
    $alert.addClass('d-none');

    $.ajax({
      url: $form.attr('action'),
      method: 'POST',
      data: $form.serialize(),
      dataType: 'json',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
    }).done(function (res) {
      $alert.removeClass('d-none alert-danger').addClass('alert-success').text(res.message);
      if (res.success) {
        $form[0].reset();
        $form.removeClass('was-validated');
      }
    }).fail(function (xhr) {
      const msg = (xhr.responseJSON && xhr.responseJSON.message) || 'Something went wrong. Please try again.';
      $alert.removeClass('d-none alert-success').addClass('alert-danger').text(msg);
    }).always(function () {
      $btn.prop('disabled', false).text('Send Message');
    });
  });
});
