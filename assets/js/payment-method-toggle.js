// Auto-wires every ".payment-method-select" on the page: shows + requires the paired
// ".reference-no-wrap"/".reference-no-input" whenever a method other than cash/card/cod
// (i.e. one that needs a transaction ID) is chosen.
document.addEventListener('DOMContentLoaded', function () {
  var NO_REFERENCE_METHODS = ['cash', 'card', 'cod'];

  document.querySelectorAll('.payment-method-select').forEach(function (select) {
    var form = select.closest('form') || document;
    var wrap = form.querySelector('.reference-no-wrap');
    var input = form.querySelector('.reference-no-input');
    if (!wrap || !input) {
      return;
    }

    function update() {
      var needsReference = select.value !== '' && NO_REFERENCE_METHODS.indexOf(select.value) === -1;
      wrap.classList.toggle('d-none', !needsReference);
      input.required = needsReference;
    }

    select.addEventListener('change', update);
    update();
  });
});
