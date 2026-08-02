(function () {
  'use strict';

  function directRows(table) {
    return Array.from(table.tBodies).flatMap(function (body) {
      return Array.from(body.rows).filter(function (row) {
        return row.querySelector('form[action*="/delete"]');
      });
    });
  }

  function showNotice(message, type) {
    var notice = document.createElement('div');
    notice.className = 'alert alert-' + (type || 'success') + ' alert-dismissible fade show admin-bulk-notice';
    notice.setAttribute('role', 'alert');
    notice.textContent = message;
    var close = document.createElement('button');
    close.type = 'button';
    close.className = 'btn-close';
    close.setAttribute('data-bs-dismiss', 'alert');
    close.setAttribute('aria-label', 'Close');
    notice.appendChild(close);
    document.querySelector('.admin-content').prepend(notice);
  }

  function ensureModal() {
    var modal = document.getElementById('adminBulkDeleteModal');
    if (modal) return modal;

    modal = document.createElement('div');
    modal.className = 'modal fade';
    modal.id = 'adminBulkDeleteModal';
    modal.tabIndex = -1;
    modal.innerHTML = '<div class="modal-dialog modal-dialog-centered"><div class="modal-content bg-dark">'
      + '<div class="modal-header"><h5 class="modal-title">Delete selected records</h5>'
      + '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>'
      + '<div class="modal-body"><p class="mb-0" data-bulk-message></p></div>'
      + '<div class="modal-footer"><button type="button" class="btn btn-ps-outline btn-sm" data-bs-dismiss="modal">Cancel</button>'
      + '<button type="button" class="btn btn-danger btn-sm" data-bulk-confirm>Delete</button></div>'
      + '</div></div>';
    document.body.appendChild(modal);
    return modal;
  }

  function setupTable(table, index) {
    if (table.closest('[data-bulk-managed]') || table.closest('.admin-card') && table.closest('.admin-card').querySelector('#bulkToolbar')) return;
    var rows = directRows(table);
    if (!rows.length) return;

    var headerRow = table.tHead && table.tHead.rows[0];
    if (!headerRow) return;
    var id = 'bulkSelectAll' + index;
    var headerCell = document.createElement('th');
    headerCell.innerHTML = '<input class="form-check-input" type="checkbox" id="' + id + '" aria-label="Select all visible rows">';
    headerRow.insertBefore(headerCell, headerRow.firstChild);

    rows.forEach(function (row) {
      var cell = row.insertCell(0);
      cell.className = 'admin-bulk-select-cell';
      cell.innerHTML = '<input class="form-check-input admin-bulk-row" type="checkbox" aria-label="Select this row">';
    });
    Array.from(table.tBodies).forEach(function (body) {
      Array.from(body.rows).forEach(function (row) {
        if (rows.indexOf(row) !== -1) return;
        row.querySelectorAll('td[colspan]').forEach(function (cell) {
          cell.colSpan += 1;
        });
      });
    });

    var toolbar = document.createElement('div');
    toolbar.className = 'admin-bulk-toolbar';
    toolbar.setAttribute('data-bulk-managed', '');
    toolbar.innerHTML = '<div class="admin-bulk-status">Selected: <strong>0</strong></div>'
      + '<div class="admin-bulk-controls"><select class="form-select form-select-sm" aria-label="Bulk action">'
      + '<option value="delete">Delete Selected</option></select>'
      + '<button type="button" class="btn btn-ps-outline btn-sm" disabled>Apply</button></div>';
    var tableContainer = table.closest('.table-responsive') || table;
    tableContainer.parentNode.insertBefore(toolbar, tableContainer);

    var selectAll = headerCell.querySelector('input');
    var checks = rows.map(function (row) { return row.querySelector('.admin-bulk-row'); });
    var count = toolbar.querySelector('strong');
    var apply = toolbar.querySelector('button');

    function selectedRows() {
      return rows.filter(function (row) { return row.querySelector('.admin-bulk-row').checked; });
    }
    function update() {
      var selected = selectedRows();
      count.textContent = selected.length;
      apply.disabled = selected.length === 0;
      selectAll.checked = selected.length === rows.length;
      selectAll.indeterminate = selected.length > 0 && selected.length < rows.length;
    }
    selectAll.addEventListener('change', function () {
      checks.forEach(function (check) { check.checked = selectAll.checked; });
      update();
    });
    checks.forEach(function (check) { check.addEventListener('change', update); });

    apply.addEventListener('click', function () {
      var selected = selectedRows();
      if (!selected.length) return;
      var modal = ensureModal();
      modal.querySelector('[data-bulk-message]').textContent = 'Delete ' + selected.length + ' selected record' + (selected.length === 1 ? '' : 's') + '?';
      var confirm = modal.querySelector('[data-bulk-confirm]');
      var instance = bootstrap.Modal.getOrCreateInstance(modal);
      confirm.onclick = async function () {
        confirm.disabled = true;
        try {
          for (var i = 0; i < selected.length; i += 1) {
            var form = selected[i].querySelector('form[action*="/delete"]');
            var response = await fetch(form.action, {
              method: form.method || 'POST',
              body: new FormData(form),
              credentials: 'same-origin'
            });
            if (!response.ok) throw new Error('Delete request failed');
          }
          instance.hide();
          showNotice(selected.length + ' selected record' + (selected.length === 1 ? ' was' : 's were') + ' deleted.');
          selected.forEach(function (row) { row.remove(); });
          rows = rows.filter(function (row) { return row.isConnected; });
          update();
        } catch (error) {
          showNotice('Some selected records could not be deleted. Please refresh and review the list.', 'danger');
        } finally {
          confirm.disabled = false;
        }
      };
      instance.show();
    });
  }

  function setupExistingToolbar() {
    var toolbar = document.getElementById('bulkToolbar');
    var apply = document.getElementById('bulkApplyBtn');
    var count = document.getElementById('bulkCount');
    if (!toolbar || !apply || !count) return;

    function update() {
      var selected = document.querySelectorAll('.row-check:checked').length;
      count.textContent = selected;
      apply.disabled = selected === 0;
      toolbar.classList.remove('d-none');
    }
    document.querySelectorAll('.row-check').forEach(function (check) {
      check.addEventListener('change', update);
    });
    update();

    // Capture delete before the legacy handlers invoke a browser confirm,
    // while still submitting the same module-specific bulk endpoint.
    apply.addEventListener('click', function (event) {
      var action = document.getElementById('bulkActionSelect');
      var form = document.getElementById('bulkForm');
      var selected = Array.from(document.querySelectorAll('.row-check:checked'));
      if (!action || action.value !== 'delete' || !form || !selected.length) return;
      event.preventDefault();
      event.stopImmediatePropagation();

      var modal = ensureModal();
      modal.querySelector('[data-bulk-message]').textContent = 'Delete ' + selected.length + ' selected record' + (selected.length === 1 ? '' : 's') + '?';
      modal.querySelector('[data-bulk-confirm]').onclick = function () {
        form.querySelectorAll('input[name="ids[]"]').forEach(function (input) { input.remove(); });
        selected.forEach(function (check) {
          var input = document.createElement('input');
          input.type = 'hidden';
          input.name = 'ids[]';
          input.value = check.value;
          form.appendChild(input);
        });
        document.getElementById('bulkActionField').value = 'delete';
        form.submit();
      };
      bootstrap.Modal.getOrCreateInstance(modal).show();
    }, true);
  }

  document.addEventListener('DOMContentLoaded', function () {
    setupExistingToolbar();
    document.querySelectorAll('table.admin-table').forEach(setupTable);
  });
})();
