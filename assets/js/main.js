/* PowerSurge Gym — shared front-end behaviour */
$(function () {
  'use strict';

  // Shrink navbar on scroll
  $(window).on('scroll', function () {
    $('.navbar-ps').toggleClass('shadow-lg', $(window).scrollTop() > 10);
  });

  // Bootstrap client-side validation styling
  document.querySelectorAll('form.needs-validation').forEach(function (form) {
    form.addEventListener('submit', function (event) {
      if (!form.checkValidity()) {
        event.preventDefault();
        event.stopPropagation();
      }
      form.classList.add('was-validated');
    }, false);
  });
});
