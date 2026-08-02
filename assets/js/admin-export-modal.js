(function () {
  'use strict';

  function getExportModal() {
    var modalEl = document.getElementById('adminExportModal');
    if (!modalEl) return null;
    return bootstrap.Modal.getOrCreateInstance(modalEl);
  }

  function getSelectedRowIds() {
    var checkboxes = document.querySelectorAll('.admin-bulk-row:checked, .row-check:checked, input[name="ids[]"]:checked');
    var ids = [];
    checkboxes.forEach(function (cb) {
      var val = parseInt(cb.value, 10);
      if (!isNaN(val) && val > 0) {
        ids.push(val);
      } else {
        var row = cb.closest('tr');
        if (row) {
          var idAttr = row.getAttribute('data-id');
          if (idAttr) ids.push(parseInt(idAttr, 10));
        }
      }
    });
    return ids;
  }

  function formatModuleName(moduleKey) {
    var names = {
      'members': 'Members',
      'trainers': 'Trainers',
      'packages': 'Packages',
      'coupons': 'Coupons',
      'products': 'Products',
      'categories': 'Categories',
      'attributes': 'Product Attributes',
      'brands': 'Brands',
      'suppliers': 'Suppliers',
      'purchases': 'Purchases',
      'orders': 'Orders',
      'sales': 'Store Sales',
      'pos': 'POS Sales',
      'attendance': 'Attendance',
      'reports': 'Reports',
      'reviews': 'Product Reviews',
      'messages': 'Contact Messages',
      'audit-logs': 'Audit Logs',
      'staff': 'Staff',
      'delivery-staff': 'Delivery Staff'
    };
    if (names[moduleKey]) return names[moduleKey];
    return moduleKey.replace(/-/g, ' ').replace(/\b\w/g, function (l) { return l.toUpperCase(); });
  }

  function generateDefaultFilename(moduleName, format) {
    var date = new Date();
    var yyyy = date.getFullYear();
    var mm = String(date.getMonth() + 1).padStart(2, '0');
    var dd = String(date.getDate()).padStart(2, '0');
    var hh = String(date.getHours()).padStart(2, '0');
    var min = String(date.getMinutes()).padStart(2, '0');

    var cleanName = moduleName.replace(/[^A-Za-z0-9]/g, '_');
    return cleanName + '_' + yyyy + '-' + mm + '-' + dd + '_' + hh + '-' + min + '.' + format;
  }

  function updateFilenameExtension(newFormat) {
    var input = document.getElementById('exportFilenameInput');
    if (!input || !input.value) return;

    var val = input.value;
    var lastDot = val.lastIndexOf('.');
    if (lastDot !== -1) {
      input.value = val.substring(0, lastDot) + '.' + newFormat;
    } else {
      input.value = val + '.' + newFormat;
    }
  }

  function setupExportTriggers() {
    document.addEventListener('click', function (e) {
      var btn = e.target.closest('[data-export-module]');
      if (!btn) return;
      e.preventDefault();

      var moduleKey = btn.getAttribute('data-export-module');
      if (!moduleKey) return;

      var modalEl = document.getElementById('adminExportModal');
      if (!modalEl) return;

      var moduleName = formatModuleName(moduleKey);

      // Set Target Module Name
      var titleEl = document.getElementById('exportModalModuleName');
      if (titleEl) {
        titleEl.textContent = moduleName;
      }

      // Set Hidden Module
      var moduleInput = document.getElementById('exportModuleField');
      if (moduleInput) {
        moduleInput.value = moduleKey;
      }

      // Format Default Choice
      var activeFmt = document.querySelector('input[name="export_format"]:checked');
      var format = activeFmt ? activeFmt.value : 'xlsx';

      // Auto-generate Default Filename
      var filenameInput = document.getElementById('exportFilenameInput');
      if (filenameInput) {
        filenameInput.value = generateDefaultFilename(moduleName, format);
      }

      // Selected rows check
      var selectedIds = getSelectedRowIds();
      var selectedRadio = document.getElementById('exportScopeSelected');
      var badge = document.getElementById('exportSelectedCountBadge');

      if (selectedIds.length > 0) {
        if (selectedRadio) {
          selectedRadio.disabled = false;
          selectedRadio.checked = true;
        }
        if (badge) {
          badge.textContent = selectedIds.length + ' selected';
          badge.style.display = 'inline-block';
        }
      } else {
        if (selectedRadio) {
          selectedRadio.disabled = true;
          selectedRadio.checked = false;
        }
        if (badge) {
          badge.style.display = 'none';
        }
        var allRadio = document.getElementById('exportScopeAll');
        if (allRadio) allRadio.checked = true;
      }

      var bsModal = getExportModal();
      if (bsModal) bsModal.show();
    });

    // Format Change Listener to update filename extension dynamically
    document.querySelectorAll('input[name="export_format"]').forEach(function (radio) {
      radio.addEventListener('change', function () {
        updateFilenameExtension(this.value);
      });
    });
  }

  function setupSubmitHandler() {
    var submitBtn = document.getElementById('exportSubmitBtn');
    if (!submitBtn) return;

    submitBtn.addEventListener('click', function () {
      var moduleInput = document.getElementById('exportModuleField');
      var moduleKey = moduleInput ? moduleInput.value : '';
      if (!moduleKey) return;

      var formatInput = document.querySelector('input[name="export_format"]:checked');
      var format = formatInput ? formatInput.value : 'xlsx';

      var scopeInput = document.querySelector('input[name="export_scope"]:checked');
      var scope = scopeInput ? scopeInput.value : 'all';

      var customFilename = document.getElementById('exportFilenameInput') ? document.getElementById('exportFilenameInput').value.trim() : '';

      var includeHeaders = document.getElementById('optIncludeHeaders') ? (document.getElementById('optIncludeHeaders').checked ? '1' : '0') : '1';
      var includeFilters = document.getElementById('optIncludeFilters') ? (document.getElementById('optIncludeFilters').checked ? '1' : '0') : '1';
      var includeLogo = document.getElementById('optIncludeLogo') ? (document.getElementById('optIncludeLogo').checked ? '1' : '0') : '1';
      var includeDate = document.getElementById('optIncludeDate') ? (document.getElementById('optIncludeDate').checked ? '1' : '0') : '1';
      var includeUser = document.getElementById('optIncludeUser') ? (document.getElementById('optIncludeUser').checked ? '1' : '0') : '1';

      var downloadUrl;
      if (moduleKey === 'reports' && window.location.pathname.indexOf('/admin/reports/') !== -1) {
        var params = new URLSearchParams(window.location.search);
        params.set('export', format);
        if (customFilename) params.set('filename', customFilename);
        params.set('include_headers', includeHeaders);
        params.set('include_filters', includeFilters);
        params.set('include_logo', includeLogo);
        params.set('include_date', includeDate);
        params.set('include_user', includeUser);
        downloadUrl = window.location.pathname + '?' + params.toString();
      } else {
        var adminIdx = window.location.pathname.indexOf('/admin');
        var appSubdir = adminIdx !== -1 ? window.location.pathname.substring(0, adminIdx) : '';
        var baseUrl = window.location.origin + appSubdir + '/admin/export/' + encodeURIComponent(moduleKey);
        var params = new URLSearchParams(window.location.search);

        params.set('format', format);
        params.set('scope', scope);
        if (customFilename) params.set('filename', customFilename);
        params.set('include_headers', includeHeaders);
        params.set('include_filters', includeFilters);
        params.set('include_logo', includeLogo);
        params.set('include_date', includeDate);
        params.set('include_user', includeUser);

        if (scope === 'selected') {
          var selectedIds = getSelectedRowIds();
          if (selectedIds.length > 0) {
            params.set('ids', selectedIds.join(','));
          }
        }
        downloadUrl = baseUrl + '?' + params.toString();
      }

      // Trigger download
      window.location.href = downloadUrl;

      // Close modal
      var bsModal = getExportModal();
      if (bsModal) bsModal.hide();
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    setupExportTriggers();
    setupSubmitHandler();
  });
})();
