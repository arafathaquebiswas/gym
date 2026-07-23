/* PowerSurge Admin — rotates the chevron on any expandable-detail-row toggle button (admin list tables). */
$(function () {
  'use strict';

  document.querySelectorAll('.details-chevron').forEach(function (chevron) {
    var btn = chevron.closest('[data-bs-toggle="collapse"]');
    if (!btn) return;
    var target = document.querySelector(btn.getAttribute('data-bs-target'));
    if (!target) return;
    target.addEventListener('show.bs.collapse', function () { chevron.classList.add('rotated'); });
    target.addEventListener('hide.bs.collapse', function () { chevron.classList.remove('rotated'); });
  });
});
