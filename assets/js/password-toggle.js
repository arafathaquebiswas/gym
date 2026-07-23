// Auto-wraps every password field on the page with a show/hide eye-icon toggle.
// Pure DOM manipulation — no per-view markup changes needed anywhere.
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('input[type="password"]').forEach(function (input) {
    if (input.closest('.password-toggle-wrap')) {
      return;
    }

    var wrap = document.createElement('div');
    wrap.className = 'password-toggle-wrap';
    input.parentNode.insertBefore(wrap, input);
    wrap.appendChild(input);
    input.classList.add('password-toggle-input');

    var btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'password-toggle-btn';
    btn.setAttribute('aria-label', 'Show password');
    btn.innerHTML = '<i class="bi bi-eye"></i>';
    wrap.appendChild(btn);

    btn.addEventListener('click', function () {
      var showing = input.type === 'text';
      input.type = showing ? 'password' : 'text';
      btn.innerHTML = showing ? '<i class="bi bi-eye"></i>' : '<i class="bi bi-eye-slash"></i>';
      btn.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
    });
  });
});
