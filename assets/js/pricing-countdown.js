/* PowerSurge Gym — live countdown for active membership offers, with auto-expire */
$(function () {
  'use strict';

  const $timers = $('[data-offer-countdown]');
  if ($timers.length === 0) {
    return;
  }

  function tick() {
    $timers.each(function () {
      const $timer = $(this);
      const endTime = new Date($timer.data('offer-countdown')).getTime();
      const now = Date.now();
      const remaining = endTime - now;
      const $card = $timer.closest('[data-pkg-card]');

      if (remaining <= 0) {
        // Offer just expired while the page was open — fall back to regular pricing live.
        $card.find('.offer-only').addClass('d-none');
        $card.find('.offer-expired-fallback').removeClass('d-none');
        return;
      }

      const days = Math.floor(remaining / (1000 * 60 * 60 * 24));
      const hours = Math.floor((remaining / (1000 * 60 * 60)) % 24);
      const minutes = Math.floor((remaining / (1000 * 60)) % 60);

      $timer.find('.js-days').text(String(days).padStart(2, '0'));
      $timer.find('.js-hours').text(String(hours).padStart(2, '0'));
      $timer.find('.js-minutes').text(String(minutes).padStart(2, '0'));
    });
  }

  tick();
  setInterval(tick, 1000 * 30);
});
