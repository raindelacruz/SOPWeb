document.addEventListener('DOMContentLoaded', function () {
    function createPdfPickerModal() {
        var overlay = document.createElement('div');
        overlay.className = 'pdf-picker-overlay d-none';
        overlay.innerHTML = [
            '<div class="pdf-picker-dialog" role="dialog" aria-modal="true" aria-labelledby="pdf-picker-title">',
            '  <div class="pdf-picker-header">',
            '    <div>',
            '      <h5 id="pdf-picker-title" class="mb-1">Locate PDF File</h5>',
            '      <p class="mb-0 text-muted small">Choose a file from the configured server folders.</p>',
            '    </div>',
            '    <button type="button" class="btn btn-link text-muted p-0" data-pdf-picker-close aria-label="Close">Close</button>',
            '  </div>',
            '  <div class="pdf-picker-body">',
            '    <input type="search" class="form-control mb-3" placeholder="Search by file name or folder" data-pdf-picker-search>',
            '    <div class="small text-muted mb-2" data-pdf-picker-roots></div>',
            '    <div class="list-group pdf-picker-results" data-pdf-picker-results></div>',
            '  </div>',
            '  <div class="pdf-picker-footer">',
            '    <button type="button" class="btn btn-outline-secondary" data-pdf-picker-close>Cancel</button>',
            '  </div>',
            '</div>'
        ].join('');

        document.body.appendChild(overlay);
        return overlay;
    }

    function renderPdfPickerResults(modal, files, activeField) {
        var container = modal.querySelector('[data-pdf-picker-results]');
        container.innerHTML = '';

        if (!files.length) {
            container.innerHTML = '<div class="list-group-item text-muted">No PDF files matched your search.</div>';
            return;
        }

        files.forEach(function (file) {
            var button = document.createElement('button');
            button.type = 'button';
            button.className = 'list-group-item list-group-item-action';
            button.innerHTML = '<strong>' + file.name + '</strong><div class="small text-muted">' + file.directory + '</div>';
            button.addEventListener('click', function () {
                if (activeField) {
                    activeField.value = file.path;
                    activeField.dispatchEvent(new Event('change', { bubbles: true }));
                }

                modal.classList.add('d-none');
            });
            container.appendChild(button);
        });
    }

    function loadPdfPickerFiles(modal, url, activeField, search) {
        var rootsLabel = modal.querySelector('[data-pdf-picker-roots]');
        var results = modal.querySelector('[data-pdf-picker-results]');
        results.innerHTML = '<div class="list-group-item text-muted">Loading PDF files...</div>';

        fetch(url + '?search=' + encodeURIComponent(search || ''))
            .then(function (response) {
                return response.json();
            })
            .then(function (payload) {
                var roots = payload.roots || [];
                rootsLabel.textContent = roots.length ? 'Available folders: ' + roots.join(' | ') : 'No PDF folders are configured.';
                renderPdfPickerResults(modal, payload.files || [], activeField);
            })
            .catch(function () {
                rootsLabel.textContent = '';
                results.innerHTML = '<div class="list-group-item text-danger">Unable to load PDF files right now.</div>';
            });
    }

    function parseAuthoringRules(form) {
        var raw = form.getAttribute('data-authoring-rules');

        if (!raw) {
            return {
                change_type_modes: {},
                relationship_type_modes: {},
                helper_messages: {}
            };
        }

        try {
            return JSON.parse(raw);
        } catch (error) {
            return {
                change_type_modes: {},
                relationship_type_modes: {},
                helper_messages: {}
            };
        }
    }

    function resolveMode(rules, changeValue, relationshipValue) {
        var relationshipModes = rules.relationship_type_modes || {};
        var changeModes = rules.change_type_modes || {};

        if (relationshipValue && relationshipModes[relationshipValue]) {
            return relationshipModes[relationshipValue];
        }

        if (changeValue && changeModes[changeValue]) {
            return changeModes[changeValue];
        }

        return 'neutral';
    }

    var pdmsForms = document.querySelectorAll('[data-pdms-authoring-form]');
    var pdfPickerFields = document.querySelectorAll('[data-pdf-picker]');
    var pdfPickerModal = pdfPickerFields.length ? createPdfPickerModal() : null;
    var activePdfField = null;
    var activePdfUrl = '';
    var searchTimer = null;

    pdmsForms.forEach(function (form) {
        var rules = parseAuthoringRules(form);
        var changeType = form.querySelector('[name="change_type"]');
        var relationshipType = form.querySelector('[name="relationship_type"]');
        var targetVersion = form.querySelector('[name="target_version_id"]');
        var affectedSections = form.querySelector('[name="affected_sections"]');
        var helper = form.querySelector('[data-pdms-authoring-helper]');

        if (!changeType || !relationshipType || !targetVersion || !affectedSections || !helper) {
            return;
        }

        function pdmsMode() {
            var changeValue = (changeType.value || '').toUpperCase();
            var relationshipValue = (relationshipType.value || '').toUpperCase();
            return resolveMode(rules, changeValue, relationshipValue);
        }

        function renderPdmsHelper(mode) {
            var helperMessages = rules.helper_messages || {};
            helper.textContent = helperMessages[mode] || helperMessages.neutral || '';
        }

        function syncPdmsAuthoringState() {
            var mode = pdmsMode();

            if (mode !== 'amend') {
                affectedSections.setAttribute('disabled', 'disabled');
                affectedSections.value = '';
            } else {
                affectedSections.removeAttribute('disabled');
            }

            renderPdmsHelper(mode);
        }

        changeType.addEventListener('change', syncPdmsAuthoringState);
        relationshipType.addEventListener('change', syncPdmsAuthoringState);
        syncPdmsAuthoringState();
    });

    if (pdfPickerModal) {
        pdfPickerFields.forEach(function (field) {
            var openButton = field.querySelector('[data-pdf-picker-open]');
            var input = field.querySelector('[data-pdf-path-input]');
            var url = field.getAttribute('data-picker-url');

            if (!openButton || !input || !url) {
                return;
            }

            openButton.addEventListener('click', function () {
                activePdfField = input;
                activePdfUrl = url;
                pdfPickerModal.classList.remove('d-none');
                pdfPickerModal.querySelector('[data-pdf-picker-search]').value = '';
                loadPdfPickerFiles(pdfPickerModal, activePdfUrl, activePdfField, '');
            });
        });

        pdfPickerModal.querySelectorAll('[data-pdf-picker-close]').forEach(function (button) {
            button.addEventListener('click', function () {
                pdfPickerModal.classList.add('d-none');
            });
        });

        pdfPickerModal.addEventListener('click', function (event) {
            if (event.target === pdfPickerModal) {
                pdfPickerModal.classList.add('d-none');
            }
        });

        pdfPickerModal.querySelector('[data-pdf-picker-search]').addEventListener('input', function (event) {
            if (!activePdfUrl) {
                return;
            }

            clearTimeout(searchTimer);
            searchTimer = setTimeout(function () {
                loadPdfPickerFiles(pdfPickerModal, activePdfUrl, activePdfField, event.target.value);
            }, 200);
        });
    }
});
