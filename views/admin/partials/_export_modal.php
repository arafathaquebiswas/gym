<!-- Professional Enterprise Export Modal -->
<div class="modal fade" id="adminExportModal" tabindex="-1" aria-labelledby="adminExportModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content bg-dark border-secondary text-white shadow-lg">
      <!-- Modal Header -->
      <div class="modal-header border-secondary bg-dark-subtle px-4 py-3">
        <div>
          <div class="text-orange text-uppercase fw-bold small tracking-wider"><i class="bi bi-shield-check me-1"></i> PowerSurge Gym</div>
          <h5 class="modal-title fw-bold text-white mb-0" id="adminExportModalLabel">Professional Data Export</h5>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <!-- Modal Body -->
      <div class="modal-body p-4">
        <form id="adminExportForm">
          <input type="hidden" id="exportModuleField" value="">

          <!-- Module Badge Banner -->
          <div class="d-flex align-items-center justify-content-between p-3 mb-4 rounded bg-dark border border-secondary">
            <div>
              <span class="text-white-50 small text-uppercase font-monospace d-block">Target Module</span>
              <h5 class="mb-0 fw-bold text-orange" id="exportModalModuleName">Members</h5>
            </div>
            <div class="text-end">
              <span class="badge text-bg-dark border border-secondary px-3 py-2 fs-6" id="exportRecordCountBadge">
                <i class="bi bi-database me-1"></i><span id="exportRecordCountText">0</span> records
              </span>
            </div>
          </div>

          <div class="row g-4">
            <!-- Column 1: Format & Scope -->
            <div class="col-md-6">
              <!-- Export Format -->
              <div class="mb-4">
                <label class="form-label text-white-50 fw-semibold small text-uppercase mb-2">Export Format</label>
                <div class="d-flex flex-column gap-2">
                  <label class="form-check p-3 rounded bg-dark border border-secondary cursor-pointer hover-border-orange">
                    <input class="form-check-input me-2" type="radio" name="export_format" id="exportFmtXlsx" value="xlsx" checked>
                    <span class="form-check-label">
                      <i class="bi bi-file-earmark-spreadsheet text-success fs-5 me-2 align-middle"></i>
                      <strong class="text-white">Excel</strong> <span class="text-white-50 small">(.xlsx)</span>
                    </span>
                  </label>
                  <label class="form-check p-3 rounded bg-dark border border-secondary cursor-pointer hover-border-orange">
                    <input class="form-check-input me-2" type="radio" name="export_format" id="exportFmtCsv" value="csv">
                    <span class="form-check-label">
                      <i class="bi bi-filetype-csv text-info fs-5 me-2 align-middle"></i>
                      <strong class="text-white">CSV</strong> <span class="text-white-50 small">(.csv)</span>
                    </span>
                  </label>
                  <label class="form-check p-3 rounded bg-dark border border-secondary cursor-pointer hover-border-orange">
                    <input class="form-check-input me-2" type="radio" name="export_format" id="exportFmtPdf" value="pdf">
                    <span class="form-check-label">
                      <i class="bi bi-filetype-pdf text-danger fs-5 me-2 align-middle"></i>
                      <strong class="text-white">PDF Document</strong> <span class="text-white-50 small">(.pdf)</span>
                    </span>
                  </label>
                </div>
              </div>

              <!-- Export Scope -->
              <div>
                <label class="form-label text-white-50 fw-semibold small text-uppercase mb-2">Export Scope</label>
                <div class="d-flex flex-column gap-2">
                  <label class="form-check p-2 px-3 rounded bg-dark border border-secondary" id="exportScopeSelectedWrap">
                    <input class="form-check-input me-2" type="radio" name="export_scope" id="exportScopeSelected" value="selected">
                    <span class="form-check-label text-white small">
                      <i class="bi bi-check2-square text-warning me-1"></i> Selected Records <span id="exportSelectedCountBadge" class="badge text-bg-warning ms-1" style="display:none">0</span>
                    </span>
                  </label>
                  <label class="form-check p-2 px-3 rounded bg-dark border border-secondary">
                    <input class="form-check-input me-2" type="radio" name="export_scope" id="exportScopeCurrent" value="current">
                    <span class="form-check-label text-white small">
                      <i class="bi bi-file-earmark-text text-info me-1"></i> Current Page
                    </span>
                  </label>
                  <label class="form-check p-2 px-3 rounded bg-dark border border-secondary">
                    <input class="form-check-input me-2" type="radio" name="export_scope" id="exportScopeFiltered" value="filtered">
                    <span class="form-check-label text-white small">
                      <i class="bi bi-funnel text-primary me-1"></i> Filtered Results
                    </span>
                  </label>
                  <label class="form-check p-2 px-3 rounded bg-dark border border-secondary">
                    <input class="form-check-input me-2" type="radio" name="export_scope" id="exportScopeAll" value="all" checked>
                    <span class="form-check-label text-white small">
                      <i class="bi bi-database text-success me-1"></i> All Records
                    </span>
                  </label>
                </div>
              </div>
            </div>

            <!-- Column 2: File Name & Options -->
            <div class="col-md-6">
              <!-- File Name -->
              <div class="mb-4">
                <label for="exportFilenameInput" class="form-label text-white-50 fw-semibold small text-uppercase">File Name</label>
                <div class="input-group">
                  <span class="input-group-text bg-dark border-secondary text-white-50"><i class="bi bi-file-earmark-code"></i></span>
                  <input type="text" class="form-control bg-dark text-white border-secondary" id="exportFilenameInput" value="" placeholder="e.g. Members_2026-08-02_14-30.xlsx">
                </div>
                <div class="form-text text-white-50 small">Auto-generated. You may customize the name before exporting.</div>
              </div>

              <!-- Checkbox Options -->
              <div>
                <label class="form-label text-white-50 fw-semibold small text-uppercase mb-2">Export Options</label>
                <div class="d-flex flex-column gap-2 bg-dark p-3 rounded border border-secondary">
                  <div class="form-check">
                    <input class="form-check-input" type="radio" name="opt_headers" id="optHeaderYes" value="1" checked style="display:none">
                    <input class="form-check-input" type="checkbox" id="optIncludeHeaders" checked>
                    <label class="form-check-label text-white small" for="optIncludeHeaders">
                      <i class="bi bi-layout-three-columns me-1 text-white-50"></i> Include column headers
                    </label>
                  </div>
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="optIncludeFilters" checked>
                    <label class="form-check-label text-white small" for="optIncludeFilters">
                      <i class="bi bi-filter me-1 text-white-50"></i> Include applied filters
                    </label>
                  </div>
                  <div class="form-check" id="optIncludeLogoWrap">
                    <input class="form-check-input" type="checkbox" id="optIncludeLogo" checked>
                    <label class="form-check-label text-white small" for="optIncludeLogo">
                      <i class="bi bi-image me-1 text-white-50"></i> Include company logo (PDF only)
                    </label>
                  </div>
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="optIncludeDate" checked>
                    <label class="form-check-label text-white small" for="optIncludeDate">
                      <i class="bi bi-calendar3 me-1 text-white-50"></i> Include export date
                    </label>
                  </div>
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="optIncludeUser" checked>
                    <label class="form-check-label text-white small" for="optIncludeUser">
                      <i class="bi bi-person-badge me-1 text-white-50"></i> Include exported by
                    </label>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </form>
      </div>

      <!-- Modal Footer -->
      <div class="modal-footer border-secondary bg-dark-subtle px-4 py-3">
        <button type="button" class="btn btn-ps-outline btn-sm px-4" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-ps btn-sm px-4" id="exportSubmitBtn"><i class="bi bi-download me-1"></i> Export Data</button>
      </div>
    </div>
  </div>
</div>
