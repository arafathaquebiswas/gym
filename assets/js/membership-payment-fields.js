// Shows/hides + (un)requires the payment-method-specific fields (bKash/Nagad/Rocket number,
// card details, bank details, transaction/reference number) on any form with a
// ".payment-method-select" — used by the Add Member and Renew Membership forms, where each
// method has its own mandatory proof-of-payment fields. Runs alongside (not instead of)
// payment-method-toggle.js, which still owns the generic .reference-no-wrap where present.
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.payment-method-select').forEach(function (select) {
    var form = select.closest('form') || document;
    var groups = form.querySelectorAll('[data-payment-fields]');
    if (!groups.length) {
      return;
    }

    function update() {
      groups.forEach(function (group) {
        var methods = group.getAttribute('data-payment-fields').split(',');
        var show = methods.indexOf(select.value) !== -1;
        group.classList.toggle('d-none', !show);
        group.querySelectorAll('[data-payment-required]').forEach(function (input) {
          input.required = show;
        });
      });
    }

    select.addEventListener('change', update);
    update();
  });
});
