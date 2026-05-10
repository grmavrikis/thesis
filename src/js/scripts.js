/**
 * Performs an asynchronous AJAX call using XMLHttpRequest and returns a Promise.
 * @param {string} url - The URL to call.
 * @param {string} method - The HTTP method ('GET', 'POST').
 * @param {Object | null} data - The data (Query Params or Request Body).
 * @returns {Promise<Object>} - Promise that resolves to an Object.
 */
function aAjaxCall(url, method, data = null) {
    const httpMethod = method.toUpperCase();
    let finalUrl = url;
    let payload = null;

    return new Promise((resolve, reject) => {
        const xhr = new XMLHttpRequest();

        // GET Parameters Handling
        if (httpMethod === 'GET' && data) {
            try {
                const params = new URLSearchParams(data).toString();
                finalUrl = `${url}?${params}`;
            } catch (e) {
                return reject(new Error(js_texts.error_invalid_get_data));
            }
        }

        xhr.open(httpMethod, finalUrl, true);

        // Response content type
        xhr.setRequestHeader('Accept', 'application/json, text/plain, */*');

        // POST/PUT Data Handling
        if (httpMethod === 'POST' || httpMethod === 'PUT') {
            if (data) {
                xhr.setRequestHeader('Content-Type', 'application/json');
                payload = JSON.stringify(data);
            }
        }

        // Response Handling
        xhr.onload = function () {
            if (xhr.status >= 200 && xhr.status < 300) {
                let response;
                try {
                    response = JSON.parse(xhr.responseText);
                } catch (e) {
                    // If response is not valid JSON, return raw text
                    response = {
                        success: true,
                        data: xhr.responseText,
                        is_raw_string: true
                    };
                }

                resolve(response);
            } else {
                // ReJect for HTTP Status Errors (4xx, 5xx)
                let errorPayload;
                let errorResponseText = xhr.responseText;

                try {
                    errorPayload = JSON.parse(errorResponseText);
                } catch (e) {
                    errorPayload = {
                        success: false,
                        message: js_texts.error_network_or_server,
                        error_details: errorResponseText,
                        status: xhr.status
                    };
                }

                const customError = new Error(errorPayload.message || `HTTP Error: ${xhr.status}`);
                customError.status = xhr.status;
                customError.serverResponse = errorPayload;

                reject(customError);
            }
        };

        // Network Error Handling
        xhr.onerror = function () {
            reject(new Error(js_texts.error_network_failure + `${finalUrl}.`));
        };

        xhr.send(payload);
    });
}


/**
 * Handles admin/dietitian login (Unified Logic).
 * @param {Event} event - The form submission event.
 */
async function handleAdminLogin(event) {
    // Stop the default form submit
    event.preventDefault();

    const form = event.target;
    const adminLoginMessage = document.getElementById('adminLoginMessage');
    const languageCode = document.getElementById('language_code').value;

    // UI Feedback
    adminLoginMessage.textContent = js_texts.info_logging_in;
    adminLoginMessage.style.display = 'block';
    // Default color blue for info state
    adminLoginMessage.style.color = 'blue';

    const url = `/ajax/admin_login_handler.php`;
    const method = 'POST';

    // Mapping from the HTML Form
    const data = {
        username: form.adminUsername.value,
        password: form.adminPassword.value,
        language_code: languageCode
    };

    try {
        const result = await aAjaxCall(url, method, data);

        if (result.success) {
            adminLoginMessage.style.color = 'green';
            adminLoginMessage.textContent = (result.message || js_texts.success_admin_login) + ' ' + js_texts.info_redirecting;
            if (result.redirect_url) {
                window.location.href = result.redirect_url;
            }
            else {
                window.location.reload();
            }
        } else {
            adminLoginMessage.style.color = 'red';
            adminLoginMessage.textContent = result.message || js_texts.error_admin_login_failed;
        }
    }
    catch (error) {
        console.error('Admin Login Error:', error);
        const displayMessage = error.serverResponse?.message || error.message || js_texts.error_admin_system_failure;
        adminLoginMessage.style.color = 'red';
        adminLoginMessage.textContent = displayMessage;
    }
}

/**
 * Handles the admin logout process.
 * @returns {Promise<Object>} - Promise that resolves to { success: boolean, message: string }
 */
async function handleAdminLogout() {
    const languageCode = document.getElementById('language_code').value;
    const url = '/ajax/admin_logout_handler.php';
    const method = 'POST';
    const data = {
        language_code: languageCode
    };

    try {
        const result = await aAjaxCall(url, method, data);
        // Redirect on successful logout
        if (result.success) {
            if (result.redirect_url) {
                window.location.href = result.redirect_url;
            }
            else {
                window.location.reload();
            }
        }
    } catch (error) {
        // Error Handling
        console.error('Logout Error:', error);
        return { success: false, message: js_texts.unknown_server_error_message };
    }
}

/**
 * Handles client login with Unified JSON Payload (Admin-style).
 * @param {Event} event - The form submission event.
 */
async function handleClientLogin(event) {
    // Stop the default form submit
    event.preventDefault();

    const form = event.target;
    const loginMessage = document.getElementById('loginMessage');
    const languageCode = document.getElementById('language_code').value;

    const url = '/ajax/client_login_handler.php';
    const method = 'POST';

    // Mapping from the HTML Form
    const data = {
        username: form.username.value,
        password: form.password.value,
        language_code: languageCode
    };

    try {
        const result = await aAjaxCall(url, method, data);

        if (result.success) {
            if (result.redirect_url) {
                window.location.href = result.redirect_url;
            }
            else {
                window.location.reload();
            }
        } else {
            loginMessage.textContent = result.message || js_texts.error_client_login_failed;
            loginMessage.style.color = 'red';
        }
    } catch (error) {
        console.error('Login Error:', error);
        const displayMessage = error.serverResponse?.message || js_texts.error_login_failed;
        loginMessage.textContent = displayMessage;
        loginMessage.style.color = 'red';
    }
}


/**
 * Handles the client logout process.
 * @returns {Promise<Object>} - Promise that resolves to { success: boolean, message: string }
 */
async function handleClientLogout() {
    const languageCode = document.getElementById('language_code').value;
    const url = '/ajax/client_logout_handler.php';
    const method = 'POST';
    const data = {
        language_code: languageCode
    };

    try {
        const result = await aAjaxCall(url, method, data);
        // Redirect on successful logout
        if (result.success) {
            if (result.redirect_url) {
                window.location.href = result.redirect_url;
            }
            else {
                window.location.reload();
            }
        }
    } catch (error) {
        // Error Handling
        console.error('Logout Error:', error);
        return { success: false, message: js_texts.unknown_server_error_message };
    }
}

/**
 * Initializes a dynamic table with AJAX data fetching.
 * @param {string} containerId - Container element ID where the table will be rendered
 * @param {string} apiUrl - The API endpoint URL to fetch data from
 * @param {number} page - The page number for pagination (default: 1)
 * @param {number} limit - The number of records per page (default: 10)
 * @param {string} sortColumn - The column to sort by (default: 'id')
 * @param {string} sortDirection - The direction to sort by (default: 'asc')
 */
var filtersInitialized = false;
var currentSortColumn = 'id';
var currentSortDirection = 'desc';
var results_limit = 10;

async function initDynamicTable(containerId, apiUrl, page = 1, limit = results_limit, sortColumn = currentSortColumn, sortDirection = currentSortDirection) {
    try {
        const languageCode = document.getElementById('language_code').value;
        const search_term = document.getElementById('searchInput').value.trim();
        const activeFilters = FilterManager.getFilters();
        currentSortColumn = sortColumn;
        currentSortDirection = sortDirection;

        const params = {
            page: page,
            limit: limit,
            language_code: languageCode,
            search_term: search_term,
            filters: activeFilters,
            sort_column: currentSortColumn,
            sort_direction: currentSortDirection,
            filters_initialized: filtersInitialized
        };
        const response = await aAjaxCall(apiUrl, 'POST', params);

        if (response.success) {
            // Render the table rows inside 'table-root'
            renderTableRows('table-root', response.data, response.columns, currentSortColumn, currentSortDirection);
            // Render the pagination controls inside 'pagination-root'
            renderPagination('pagination-root', containerId, apiUrl, response.total_pages, response.current_page, limit);
            // Attach event listeners for row selection and actions
            attachTableListeners(apiUrl, page, limit, currentSortColumn, currentSortDirection);

            if (!filtersInitialized && response.filters) {
                FilterManager.render(
                    response.filters,
                    // Call initDynamicTable at first page when applying filters to reset pagination
                    () => initDynamicTable(containerId, apiUrl, 1, limit, currentSortColumn, currentSortDirection),
                    () => initDynamicTable(containerId, apiUrl, 1, limit, currentSortColumn, currentSortDirection)
                );
                filtersInitialized = true;
            }

            const trigger = document.getElementById('filterTrigger');
            if (trigger) {
                if (FilterManager.hasActiveFilters()) {
                    trigger.classList.add('filter-active');
                } else {
                    trigger.classList.remove('filter-active');
                }
            }

        }
    } catch (error) {
        console.error("Table Init Error:", error);
    }
}

/**
 * Renders table rows based on provided data.
 * @param {string} containerId - Container element ID where the table is rendered
 * @param {Array} data - Array of data objects to render as rows
 * @param {Array} columns - Array of column definitions, e.g. [{ field: 'id', title: 'ID' }, { field: 'name', title: 'Name' }]
 * @param {string} sortColumn - The column currently sorted by (used for displaying sort icons)
 * @param {string} sortDirection - The direction of sorting ('asc' or 'desc')
 */
function renderTableRows(containerId, data, columns, sortColumn, sortDirection) {
    const container = document.getElementById(containerId);

    let tableHtml = `
        <table class="custom-admin-table" id="dynamic-table">
            <thead>
                <tr>
                    <th class="col-checkbox"><input type="checkbox" id="select-all-entities"></th>
                    ${columns.map(col => {
        const isSorted = sortColumn === col.field;
        const icon = isSorted
            ? (sortDirection === 'asc' ? 'arrow-up.svg' : 'arrow-down.svg')
            : '';

        let classes = ['sortable-header'];
        if (col.field.includes('id')) {
            classes.push('col-id');
        }

        return `
        <th class="${classes.join(' ')}" data-field="${col.field}" style="cursor:pointer">
            <div class="header-content">
                ${col.title}
                ${isSorted ? `<img src="/images/${icon}" class="sort-icon" width="12">` : ''}
            </div>
        </th>`;
    }).join('')}
                    <th class="col-actions">${js_texts.actions}</th>
                </tr>
            </thead>
            <tbody id="table-body-root"></tbody>
        </table>
    `;
    container.innerHTML = tableHtml;

    const tbody = document.getElementById('table-body-root');

    if (data.length === 0) {
        tbody.innerHTML = `<tr><td colspan="${columns.length + 2}">${js_texts.error_no_records_found}</td></tr>`;
        return;
    }

    tbody.innerHTML = data.map(row => `
    <tr>
        <td class="col-checkbox">
            <input type="checkbox" class="row-checkbox" value="${row.id}">
        </td>
        ${columns.map(col => {
        // Check if the current column is the sorted column to apply the .sorted-column class
        const isSorted = sortColumn === col.field;
        const cellClass = isSorted ? 'class="sorted-column"' : '';

        const label = col.title || '';
        return `<td class="${cellClass}" data-label="${label}">${row[col.field] ?? ''}</td>`;

    }).join('')}
        <td>
            <a class="icon-btn edit-trigger" href="${row.edit_url}" title="${js_texts.view}">
                <img src="/images/enter.svg" alt="${js_texts.view}" width="25">
            </a>
        </td>
    </tr>
`).join('');

}

function renderPagination(paginationContainerId, tableContainerId, apiUrl, totalPages, currentPage, limit) {
    const container = document.getElementById(paginationContainerId);

    if (!container) return;

    // Clear previous pagination
    container.innerHTML = '';
    if (totalPages <= 1) return;

    const paginationDiv = document.createElement('div');
    paginationDiv.className = 'pagination-container';

    let buttonsHtml = '';
    for (let i = 1; i <= totalPages; i++) {
        const activeClass = i === currentPage ? 'active' : '';
        buttonsHtml += `<button class="page-btn ${activeClass}" data-page="${i}">${i}</button>`;
    }

    paginationDiv.innerHTML = buttonsHtml;
    container.appendChild(paginationDiv);

    paginationDiv.querySelectorAll('.page-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const targetPage = parseInt(this.getAttribute('data-page'));
            // Call the initDynamicTable with the new page number
            initDynamicTable(tableContainerId, apiUrl, targetPage, limit);
        });
    });
}

/**
 * Attaches event listeners for row selection and "Select All" functionality in the dynamic table.
 * This function should be called every time the table is rendered to ensure listeners are properly attached to new elements.
 */
function attachTableListeners(apiUrl, page, limit, sortColumn, sortDirection) {
    const selectAllCheckbox = document.getElementById('select-all-entities');
    const rowCheckboxes = document.querySelectorAll('.row-checkbox');
    const bulkDeleteBtn = document.getElementById('btn-bulk-delete');
    const selectedCountSpan = document.getElementById('selected-count');
    const tbody = document.getElementById('table-body-root');

    // Update the Toolbar (Button & Counter)
    const updateToolbar = () => {
        const selectedCount = document.querySelectorAll('.row-checkbox:checked').length;
        if (selectedCountSpan) selectedCountSpan.textContent = selectedCount;
        if (bulkDeleteBtn) bulkDeleteBtn.disabled = selectedCount === 0;
    };

    // Select All Logic
    if (selectAllCheckbox) {
        // Clear any previous state
        selectAllCheckbox.checked = false;

        selectAllCheckbox.addEventListener('change', function () {
            rowCheckboxes.forEach(cb => {
                cb.checked = this.checked;
                cb.closest('tr').classList.toggle('selected-row', this.checked);
            });
            updateToolbar();
        });
    }

    const thead = document.querySelector('#dynamic-table thead');
    if (thead) {
        thead.addEventListener('click', function (e) {
            const th = e.target.closest('.sortable-header');
            if (!th) return;

            const field = th.getAttribute('data-field');

            // If the same column is clicked, toggle the sort direction. Otherwise, set to ascending.
            if (sortColumn === field) {
                sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                sortColumn = field;
                sortDirection = 'asc';
            }

            const tableContainerId = 'table-root';
            initDynamicTable(tableContainerId, apiUrl, page, limit, sortColumn, sortDirection);
        });
    }

    // Click & Checkbox Logic
    if (tbody) {
        // Clear any previous listeners to avoid duplicates
        tbody.onclick = function (e) {
            // If click is on an icon button, ignore row selection
            if (e.target.closest('.icon-btn')) return;

            const row = e.target.closest('tr');
            if (!row) return;

            const checkbox = row.querySelector('.row-checkbox');
            if (checkbox !== null && checkbox !== undefined) {
                // If the click didn't occur directly on the checkbox, toggle it
                if (checkbox && e.target !== checkbox) {
                    checkbox.checked = !checkbox.checked;
                }

                row.classList.toggle('selected-row', checkbox.checked);

                // Sync "Select All" checkbox
                if (!checkbox.checked && selectAllCheckbox) {
                    selectAllCheckbox.checked = false;
                }
            }

            updateToolbar();
        };
    }

    // Initial toolbar state
    updateToolbar();
}

/**
 * Builds a dynamic form based on the provided schema and renders it inside a specified container.
 * @param {string} containerId - The ID of the container element where the form will be rendered
 * @param {string} submitUrl - The URL to which the form will be submitted
 * @param {Object} schema - The schema defining the structure of the form, including mainFields, localFields and cancel_url
 * @param {Array} languages - The available languages from the system
 */
function buildForm(containerId, submitUrl, schema, languages = []) {
    const root = document.getElementById(containerId);
    if (!root) return;

    // novalidate to handle validation with custom JS
    // ADDED enctype="multipart/form-data" to support file uploads
    let html = `<form data-action="${submitUrl}" id="active-entity-form" class="admin-form" novalidate enctype="multipart/form-data">`;

    // Render Main Fields
    html += `<section class="form-section">`;

    for (const [fieldId, config] of Object.entries(schema.mainFields)) {
        // Hidden fields
        if (config.type === 'hidden') {
            html += `<input type="hidden" id="main-${fieldId}" name="main[${fieldId}]" value="${config.default ?? ''}">`;
            continue;
        }

        const val = config.default ?? '';
        const inputId = `main-${fieldId}`;

        let attributesHtml = '';
        if (config.attributes) {
            attributesHtml = Object.entries(config.attributes)
                .map(([key, value]) => `${key}="${value}"`)
                .join(' ');
        }

        let inputHtml = '';

        // Create the appropriate input element based on the type
        if (config.type === 'select') {
            inputHtml = `<select id="${inputId}" name="main[${fieldId}]" class="form-control" ${attributesHtml} ${config.required ? 'required' : ''}>`;
            if (config.options) {
                config.options.forEach(opt => {
                    const isSelected = val == opt.value ? 'selected' : '';
                    inputHtml += `<option value="${opt.value}" ${isSelected}>${opt.label}</option>`;
                });
            }
            inputHtml += `</select>`;
        } else if (config.type === 'textarea') {
            inputHtml = `<textarea id="${inputId}" name="main[${fieldId}]" class="form-control" ${attributesHtml} ${config.required ? 'required' : ''}>${val}</textarea>`;
        }
        else if (config.type === 'file') {
            const buttonText = config.button_label || js_texts.select_file;
            const hasExistingFile = config.default && config.default !== '';

            let downloadLinkHtml = '';
            if (hasExistingFile) {
                downloadLinkHtml = `
            <div class="existing-file-info">
                <a href="${config.default}" download class="download-link existing-file-handler" title="${js_texts.download_file}">
                    <img src="/images/download.svg" alt="download" width="25">
                </a>
                <a href="#" class="delete-file-icon existing-file-handler" data-file="${config.filename}" data-table="${config.table}" data-id="${config.id}" data-file-label="${config.label}" title="${js_texts.delete_file}">
                    <img src="/images/delete.svg" alt="delete" width="25" style="cursor:pointer;">
                </a>                
            </div>`;
            }

            inputHtml = `<div class="custom-file-upload">
                                    ${downloadLinkHtml}
                                    <input type="file" id="${inputId}" name="main[${fieldId}]" 
                                        class="hidden-file-input" ${attributesHtml} ${config.required ? 'required' : ''} 
                                        onchange="updateFileName(this, '${inputId}-name')">
                                    
                                    <label for="${inputId}" class="btn-custom-file">
                                        <i class="fas fa-upload"></i> ${buttonText}
                                    </label>
                                    <span id="${inputId}-name" class="file-name-display">${js_texts.no_file_selected}</span>
                                </div>`;
        }
        else if (config.type === 'view_file') {
            const hasExistingFile = config.default && config.default !== '';

            let downloadLinkHtml = '';
            if (hasExistingFile) {
                downloadLinkHtml = `
            <div class="existing-file-info">
                <a href="${config.default}" download class="download-link existing-file-handler" title="${js_texts.download_file}">
                    <img src="/images/download.svg" alt="download" width="25">
                </a>
            </div>`;
            }
            else {
                downloadLinkHtml = `<div class="existing-file-info">-</div>`;
            }

            inputHtml = `<div class="custom-file-upload">
                                    ${downloadLinkHtml}
                                </div>`;
        }
        else if (config.type === 'custom_text') {
            inputHtml = `<div id="${inputId}" class="custom-text-display">${val}</div>`;
        }
        else if (config.type === 'custom_code') {
            inputHtml = `<div id="${inputId}">${val}</div>`;
        }
        else {
            inputHtml = `<input type="${config.type}" id="${inputId}" name="main[${fieldId}]" value="${val}" class="form-control" ${attributesHtml} ${config.required ? 'required' : ''}>`;
        }

        html += `
        <div class="form-input">
            <label for="${inputId}">${config.label} ${config.required ? '*' : ''}</label>
            ${inputHtml}
            <span class="error-msg" id="error-${inputId}"></span>
        </div>`;
    }
    html += `</section>`;


    // Render Local Fields
    const localFieldsEntries = Object.entries(schema.localFields ?? {});
    if (localFieldsEntries.length > 0) {
        html += '<section class="form-section">';
        languages.forEach(lang => {
            html += '<div class="language-block" data-lang-id="' + lang.language_id + '">';
            html += '<div class="lang-header">';
            html += '<img src="' + lang.flag + '" alt="' + lang.name + '" width="32">';
            html += '<span>' + lang.name + '</span>';
            html += '</div>';

            // Form schema localFields
            const fieldsForThisLang = Object.entries(schema.localFields[lang.language_id] ?? {});

            for (const [fieldId, config] of fieldsForThisLang) {
                const isInline = ['color', 'number'].includes(config.type);
                const groupClass = isInline ? 'form-group inline-group' : 'form-group';
                const inputId = 'lang-' + lang.language_id + '-' + fieldId;
                let attributesHtml = '';
                if (config.attributes) {
                    attributesHtml = Object.entries(config.attributes)
                        .map(([key, value]) => `${key}="${value}"`)
                        .join(' ');
                }

                const val = config.default ?? '';

                // Conditional logic to handle textarea, file, and standard inputs in local fields
                let inputHtml = '';
                if (config.type === 'textarea') {
                    inputHtml = '<textarea id="' + inputId + '" name="translations[' + lang.language_id + '][' + fieldId + ']" class="form-control" ' + (config.required ? 'required' : '') + '>' + val + '</textarea>';
                } else if (config.type === 'file') {
                    const buttonText = config.button_label || js_texts.select_file;
                    const hasExistingFile = config.default && config.default !== '';

                    let downloadLinkHtml = '';
                    if (hasExistingFile) {
                        downloadLinkHtml = `<div class="existing-file-info">
                                                <a href="${config.default}" download class="download-link existing-file-handler" title="${js_texts.download_file}">
                                                    <img src="/images/download.svg" alt="download" width="20">
                                                </a>
                                                <a href="#" class="delete-file-icon existing-file-handler" data-file="${config.filename}" data-table="${config.table}" data-id="${config.id}" data-file-label="${config.label}" title="${js_texts.delete_file}">
                                                    <img src="/images/delete.svg" alt="delete" width="25" style="cursor:pointer;">
                                                </a>                
                                            </div>`;
                    }

                    inputHtml = `<div class="custom-file-upload">
                                    ${downloadLinkHtml}
                                    <input type="file" id="${inputId}" name="translations[${lang.language_id}][${fieldId}]"  
                                        class="hidden-file-input" ${attributesHtml} ${config.required ? 'required' : ''} 
                                        onchange="updateFileName(this, '${inputId}-name')">
                                    
                                    <label for="${inputId}" class="btn-custom-file">
                                        <i class="fas fa-upload"></i> ${buttonText}
                                    </label>
                                    <span id="${inputId}-name" class="file-name-display">${js_texts.no_file_selected}</span>
                                </div>`;
                } else {
                    inputHtml = '<input type="' + config.type + '" id="' + inputId + '" name="translations[' + lang.language_id + '][' + fieldId + ']" class="form-control" ' + attributesHtml + ' ' + (config.required ? 'required' : '') + ' value="' + val + '">';
                }

                html += '<div class="' + groupClass + '">';
                html += '<label class="main-label">';
                html += '<span class="label-text">' + config.label + ' ' + (config.required ? '*' : '') + '</span>';
                html += inputHtml;
                html += '<span class="error-msg" id="error-' + inputId + '"></span>';
                html += '</label>';
                html += '</div>';
            }
            html += '</div>';
        });
        html += '</section>';
    }

    let submitButton = '';
    if (submitUrl !== '') {
        submitButton = '<button type="submit" class="btn-submit">' + js_texts.save + '</button>';
    }

    // Buttons
    html += `
        <div class="flex-container-start">
            <div class="wrapper">
                ${submitButton}
                <a href="${schema.cancel_url}" class="link-cancel">${js_texts.back}</a>
            </div>
        </div>`;

    // Render Metadata (Creation and Last Modified dates)
    if (schema.creation_date || schema.last_modified_date) {
        html += `<div class="form-metadata">`;

        if (schema.creation_date) {
            html += `
                <div class="metadata-item">
                    <span class="metadata-label">${js_texts.creation_date}:</span>
                    <span>${schema.creation_date}</span>
                </div>`;
        }

        if (schema.last_modified_date) {
            html += `
                <div class="metadata-item">
                    <span class="metadata-label">${js_texts.last_modified_date}:</span>
                    <span>${schema.last_modified_date}</span>
                </div>`;
        }

        html += `</div>`;
    }

    html += `</form>`; // Close the form tag

    root.innerHTML = html;

    const form = document.getElementById('active-entity-form');

    form.addEventListener('invalid', (function () {
        return function (e) {
            // Stop native html bubbles
            e.preventDefault();

            const errorSpan = document.getElementById('error-' + e.target.id);
            if (errorSpan) {
                // Use the js_texts for the error message to ensure localization
                errorSpan.textContent = js_texts.error_required_field;
                errorSpan.style.display = 'block';
                e.target.classList.add('input-error');
            }
        };
    })(), true);

    // Clear error on input
    form.addEventListener('input', function (e) {
        const errorSpan = document.getElementById('error-' + e.target.id);
        if (errorSpan) {
            errorSpan.textContent = '';
            errorSpan.style.display = 'none';
            e.target.classList.remove('input-error');
        }
    });

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        // If the form is invalid, the 'invalid' event will have already been triggered for each invalid field, so we just need to check validity here and stop submission if there are errors.
        if (!this.checkValidity()) {
            // Focus the first invalid field for better UX
            const firstError = this.querySelector(':invalid');
            if (firstError) firstError.focus();

            return false;
        }

        // The form is valid and we can proceed with submission
        handleFormSubmission(this);
    });
}

/**
 * Handles the form submission by AJAX request to the server.
 * @param {HTMLFormElement} formElement - The form element that is being submitted
 */
async function handleFormSubmission(formElement) {
    const url = formElement.getAttribute('data-action');

    if (url === '' || url === undefined || url === null) { return; }
    // formData contains all the form fields with their names and values
    const formData = new FormData(formElement);

    const langCodeEl = document.getElementById('language_code');
    if (langCodeEl) {
        formData.append('language_code', langCodeEl.value);
    }

    try {
        const response = await fetch(url, {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            if (result.message) {
                alert(result.message);
            }
            if (result.redirect_url) {
                window.location.href = result.redirect_url;
            }
        } else if (result.errors) {
            for (const [fieldId, message] of Object.entries(result.errors)) {
                const errorSpan = document.getElementById('error-' + fieldId);
                const inputField = document.getElementById(fieldId);

                if (errorSpan) {
                    errorSpan.textContent = message;
                    errorSpan.style.display = 'block';
                }

                if (inputField) {
                    inputField.classList.add('input-error');
                }
            }

            // Focus and scroll to the first error
            const firstErrorInput = formElement.querySelector('.input-error');
            if (firstErrorInput) {
                firstErrorInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
                firstErrorInput.focus();
            }
        } else if (!result.success) {
            if (result.message) {
                alert(result.message);
            }
            if (result.redirect_url) {
                window.location.href = result.redirect_url;
            }
        }
    } catch (error) {
        console.error("Submission Error:", error);
        alert(js_texts.error_generic_failure);
    }
}

function updateFileName(input, displayId) {
    const display = document.getElementById(displayId);
    if (input.files && input.files.length > 0) {
        display.textContent = input.files[0].name;
        display.style.color = "#000";
    } else {
        display.textContent = js_texts.no_file_selected;
        display.style.color = "#6c757d";
    }
}

/**
 * Creates and displays a loader overlay on the page.
 */
function showLoader() {
    if (document.getElementById('main-page-loader')) return;

    const loader = document.createElement('div');
    loader.id = 'main-page-loader';
    loader.className = 'custom-loader-overlay';

    loader.innerHTML = `
        <img src="/images/spinner.svg" alt="${js_texts.loading}">
    `;

    document.body.appendChild(loader);
}

/**
 * Removes the loader overlay from the page if it exists.
 */
function hideLoader() {
    const loader = document.getElementById('main-page-loader');
    if (loader) {
        loader.remove();
    }
}

/**
 * Clears previous errors from the form.
 */
function clearPopupErrors(containerId) {
    const container = document.getElementById(containerId);
    if (!container) return;

    container.querySelectorAll('.error').forEach(el => el.classList.remove('error'));
    container.querySelectorAll('.error-message').forEach(el => el.remove());
}

/**
 * Displays an error message for a specific field.
 */
function renderManualError(fieldId, message) {
    const field = document.getElementById(fieldId);
    if (!field) return;

    const parent = field.closest('.form-input');
    if (!parent) return;

    field.classList.add('error');

    // Create error message element
    const errorDiv = document.createElement('div');
    errorDiv.className = 'error-message';
    errorDiv.style.color = '#f44747';
    errorDiv.style.fontSize = '12px';
    errorDiv.style.marginTop = '4px';
    errorDiv.innerText = message;

    parent.appendChild(errorDiv);
}

/**
 * Validates the invoice charge form fields before submission.
 * Checks for required fields and valid numeric values, and displays error messages.
 * @returns {boolean} - Returns true if the form is valid, false otherwise.
 */
function invoiceChargeValidation() {
    const popupId = 'chargePopup';
    clearPopupErrors(popupId);

    const description = document.getElementById('charge_description');
    const cleanAmount = document.getElementById('charge_clean_amount');
    const totalAmount = document.getElementById('charge_total_display');

    let hasErrors = false;

    // Validation Description
    if (description.value.trim() === '') {
        renderManualError('charge_description', js_texts.error_charge_description_required);
        hasErrors = true;
    }

    // Validation Clean Amount
    const cleanVal = parseFloat(cleanAmount.value);
    if (isNaN(cleanVal) || cleanVal <= 0) {
        renderManualError('charge_clean_amount', js_texts.error_charge_value_required);
        hasErrors = true;
    }

    // Validation Total Amount
    const totalVal = parseFloat(totalAmount.value);
    if (isNaN(totalVal) || totalVal <= 0) {
        renderManualError('charge_total_display', js_texts.error_charge_total_value_required);
        hasErrors = true;
    }

    return !hasErrors;
}

/**
 * Resets the invoice charge popup fields to their default state.
 */
function resetPopupFields() {
    // Clear editing state
    editingIndex = null;

    // Clear input fields
    const desc = document.getElementById('charge_description');
    const clean = document.getElementById('charge_clean_amount');
    const total = document.getElementById('charge_total_display');
    const tax = document.getElementById('charge_tax');

    if (desc) desc.value = '';
    if (clean) clean.value = '';
    if (total) total.value = '0.00';
    // Reset tax dropdown to default 
    if (tax) tax.selectedIndex = 0;

    // Clear previous errors
    clearPopupErrors('chargePopup');
}

function updateGrandTotals() {
    let totalClean = 0;
    let totalTax = 0;
    let grandTotal = 0;

    const rows = document.querySelectorAll('.added-charge-row');

    rows.forEach(row => {
        const clean = parseFloat(row.querySelector('input[name*="clean_amount"]').value) || 0;
        const total = parseFloat(row.querySelector('input[name*="total_display"]').value) || 0;
        const taxAmount = total - clean;

        totalClean += clean;
        totalTax += taxAmount;
        grandTotal += total;
    });

    const cleanField = document.getElementById('main-total_clean_amount');
    const taxField = document.getElementById('main-total_tax_amount');
    const totalField = document.getElementById('main-total_amount');

    if (cleanField) cleanField.innerHTML = totalClean.toFixed(2) + ' €';
    if (taxField) taxField.innerHTML = totalTax.toFixed(2) + ' €';
    if (totalField) totalField.innerHTML = grandTotal.toFixed(2) + ' €';
}

/**
 * Handles PDF generation and download for various entity types.
 * * @param {string} type - The type of document ('invoice', 'plan', 'questionnaire')
 * @param {number} id - The ID of the record
 */
async function handlePdfDownload(type, id) {
    if (!id) return;

    // Determine the correct endpoint and data structure
    let url = '';
    let data = { id: id };

    switch (type) {
        case 'invoice':
            url = '/ajax/download_invoice_pdf.php';
            data = { invoice_id: id };
            break;
        case 'plan':
            url = '/ajax/download_plan_pdf.php';
            data = { plan_id: id };
            break;
        case 'questionnaire':
            url = '/ajax/download_questionnaire_pdf.php';
            data = { questionnaire_id: id };
            break;
        default:
            return;
    }

    try {
        showLoader();
        const result = await aAjaxCall(url, 'POST', data);

        if (result.success && result.download_url) {
            const link = document.createElement('a');
            // Add timestamp to bypass browser cache
            link.href = result.download_url + '?t=' + new Date().getTime();
            link.setAttribute('download', result.filename || 'document.pdf');
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
        hideLoader();
    } catch (error) {
        console.error('PDF Download Error:', error);
    }
}

/**
 * Fetches and renders today's appointments for the given dietitian.
 * * @param {string} containerId The DOM element ID where the table will be rendered.
 * @param {number|null} dietitianId The ID of the dietitian.
 */
async function initDashboardAppointments(containerId, dietitianId) {
    const container = document.getElementById(containerId);
    if (!container) return;

    const today = new Date();
    const yyyy = today.getFullYear();
    const mm = String(today.getMonth() + 1).padStart(2, '0');
    const dd = String(today.getDate()).padStart(2, '0');
    const todayStr = `${yyyy}-${mm}-${dd}`;

    const languageCode = document.getElementById('language_code').value;

    const params = {
        page: 1,
        limit: 50,
        language_code: languageCode,
        search_term: '',
        sort_column: 'appointment_date',
        sort_direction: 'ASC',
        filters_initialized: true,
        filters: {
            date_from: todayStr,
            date_to: todayStr,
            dietitian_id: dietitianId
        }
    };

    try {
        container.innerHTML = '<div class="dashboard-table-container"><img src="/images/spinner.svg" alt="Loading" width="30"></div>';

        const response = await aAjaxCall('/ajax/get_appointments.php', 'POST', params);

        if (response.success && response.data.length > 0) {
            const cols = response.columns;
            const getColTitle = (field) => cols.find(c => c.field === field)?.title;

            let html = `<table class="custom-admin-table dashboard-table">
                <thead>
                    <tr>
                        <th>${getColTitle('appointment_date')}</th>
                        <th>${getColTitle('client_name')}</th>
                        <th>${getColTitle('service')}</th>
                        <th class="col-actions">${js_texts.actions}</th>
                    </tr >
                </thead >
                <tbody>`;

            response.data.forEach(row => {
                html += `<tr>
                            <td data-label="${getColTitle('appointment_date')}">${row.appointment_date}</td>
                            <td data-label="${getColTitle('client_name')}">${row.client_name}</td>
                            <td data-label="${getColTitle('service')}">${row.service}</td>
                            <td>
                                <div class="actions-container">
                                    <a class="icon-btn edit-trigger" href="${row.edit_url}" title="${js_texts.view}">
                                        <img src="/images/enter.svg" alt="View" width="25">
                                    </a>
                                </div>
                            </td>
                        </tr>`;
            });

            html += `</tbody></table>`;
            container.innerHTML = html;
        } else {
            container.innerHTML = `<div class="dashboard-table-container no-records" >${js_texts.no_appointments_today}</div>`;
        }
    } catch (error) {
        container.innerHTML = `<div class="dashboard-table-container render-error" >${js_texts.error_get_data}</div>`;
    }
}

/**
 * Fetches and renders appointments with pending nutrition plans.
 * @param {string} containerId The DOM element ID where the table will be rendered.
 * @param {number|null} dietitianId The ID of the dietitian to filter by.
 */
async function initDashboardPendingPlans(containerId, dietitianId = null) {
    const container = document.getElementById(containerId);
    if (!container) return;

    const languageCode = document.getElementById('language_code').value;

    const params = {
        page: 1,
        limit: 10,
        language_code: languageCode,
        sort_column: 'appointment_date',
        sort_direction: 'DESC',
        filters_initialized: true,
        filters: {
            pending_plans: true,
            dietitian_id: dietitianId
        }
    };

    try {
        container.innerHTML = '<div style="text-align:center; padding: 20px;"><img src="/images/spinner.svg" alt="Loading" width="30"></div>';

        const response = await aAjaxCall('/ajax/get_appointments.php', 'POST', params);

        if (response.success && response.data.length > 0) {

            const cols = response.columns;
            const getColTitle = (field) => cols.find(c => c.field === field)?.title;

            let html = `<table class="custom-admin-table dashboard-table">
                <thead>
                    <tr>
                        <th>${getColTitle('appointment_date')}</th>
                        <th>${getColTitle('client_name')}</th>
                        <th>${getColTitle('service')}</th>
                        <th class="col-actions">${js_texts.actions}</th>
                    </tr >
                </thead >
                <tbody>`;

            response.data.forEach(row => {
                html += `<tr>
                    <td data-label="${getColTitle('appointment_date')}">${row.appointment_date}</td>
                    <td data-label="${getColTitle('client_name')}">${row.client_name}</td>
                    <td data-label="${getColTitle('service')}">${row.service}</td>
                    <td>
                        <div class="actions-container">
                            <a class="icon-btn edit-trigger" href="${row.edit_url}" title="${js_texts.add}">
                                <img src="/images/add.svg" alt="${js_texts.add}" width="25">
                            </a>
                        </div>
                    </td>
                </tr>`;
            });

            html += `</tbody></table>`;
            container.innerHTML = html;
        } else {
            container.innerHTML = `<div class="dashboard-table-container no-records" >${js_texts.no_pending_plans}</div>`;
        }
    } catch (error) {
        container.innerHTML = `<div class="dashboard-table-container render-error" >${js_texts.error_get_data}</div>`;
    }
}

async function initDashboardRevenue(containerId) {
    const container = document.getElementById(containerId);
    if (!container) return;

    container.innerHTML = '<div class="dashboard-table-container"><img src="/images/spinner.svg" alt="Loading" width="30"></div>';

    try {
        const result = await aAjaxCall('/ajax/get_revenue.php', 'POST', {});

        if (result.success && result.data.length > 0) {
            let html = `
                <table class="custom-admin-table dashboard-table">
                        <thead>
                            <tr>
                                <th>${js_texts.revenue_month}</th>
                                <th class="text-right">${js_texts.revenue_clean}</th>
                                <th class="text-right">${js_texts.revenue_vat}</th>
                                <th class="text-right">${js_texts.revenue_total}</th>
                                <th class="col-actions">${js_texts.revenue_growth}</th>
                            </tr>
                        </thead>
                        <tbody>`;

            result.data.forEach(row => {
                let changeHtml = '<span class="text-muted">-</span>';

                if (row.percentage_change !== null) {
                    const isPositive = row.percentage_change >= 0;
                    const color = isPositive ? '#28a745' : '#dc3545';
                    const htmlEntity = isPositive ? '&uarr;' : '&darr;';
                    const percentValue = Math.abs(row.percentage_change).toFixed(1);

                    changeHtml = `<span style="color: ${color}; font-weight: 600;">
                    ${htmlEntity} ${percentValue}%
                  </span>`;
                }

                html += `
                    <tr>
                        <td data-label="${js_texts.revenue_month}">${row.month_year}</td>
                        <td data-label="${js_texts.revenue_clean}" class="text-right">${parseFloat(row.total_net).toLocaleString('el-GR', { style: 'currency', currency: 'EUR' })}</td>
                        <td data-label="${js_texts.revenue_vat}" class="text-right">${parseFloat(row.total_tax).toLocaleString('el-GR', { style: 'currency', currency: 'EUR' })}</td>
                        <td data-label="${js_texts.revenue_total}" class="text-right"><strong>${parseFloat(row.total_gross).toLocaleString('el-GR', { style: 'currency', currency: 'EUR' })}</strong></td>
                        <td data-label="${js_texts.revenue_growth}">
                            <div class="actions-container">${changeHtml}</div>
                        </td>
                    </tr>`;
            });

            html += `</tbody></table>`;
            container.innerHTML = html;
        } else {
            container.innerHTML = `<div class="dashboard-table-container no-records" >${js_texts.revenue_no_results}</div>`;

        }
    } catch (error) {
        container.innerHTML = `<div class="dashboard-table-container render-error" >${js_texts.error_get_data}</div>`;
    }
}

async function initDashboardLatestClients(containerId) {
    const container = document.getElementById(containerId);
    if (!container) return;

    container.innerHTML = '<div class="dashboard-table-container"><img src="/images/spinner.svg" alt="Loading" width="30"></div>';

    try {
        const languageCode = document.getElementById('language_code').value;

        const data = {
            page: 1,
            limit: 10,
            language_code: languageCode,
            sort_column: 'creation_date',
            sort_direction: 'DESC',
            filters: []
        };

        const result = await aAjaxCall('/ajax/get_clients.php', 'POST', data);

        if (result.success && result.data.length > 0) {

            const cols = result.columns;
            const getColTitle = (field) => cols.find(c => c.field === field)?.title;

            let html = `<table class="custom-admin-table dashboard-table">
                <thead>
                    <tr>
                        <th>${js_texts.client_fullname}</th>
                        <th>${getColTitle('email')}</th>
                        <th>${getColTitle('phone')}</th>
                        <th>${getColTitle('creation_date')}</th>
                        <th class="col-actions">${js_texts.actions}</th>
                    </tr >
                </thead >
                <tbody>`;

            result.data.forEach(client => {
                html += `
                    <tr>
                        <td data-label="${getColTitle('first_name')}">${client.first_name} ${client.last_name}</td>
                        <td data-label="${getColTitle('email')}">${client.email}</td>
                        <td data-label="${getColTitle('phone')}">${client.phone}</td>
                        <td data-label="${getColTitle('creation_date')}">${client.creation_date}</td>
                        <td>
                            <div class="actions-container">
                                <a class="icon-btn edit-trigger" href="${client.edit_url}" title="${js_texts.view}">
                                    <img src="/images/enter.svg" alt="View" width="25">
                                </a>
                            </div>
                        </td>
                    </tr>`;
            });

            html += `</tbody></table>`;
            container.innerHTML = html;
        } else {
            container.innerHTML = `<div class="dashboard-table-container no-records" >${js_texts.client_no_results}</div>`;
        }
    } catch (error) {
        container.innerHTML = `<div class="dashboard-table-container render-error" >${js_texts.error_get_data}</div>`;
    }
}

/**
 * Calculates VAT values based on net or final price.
 * @param {number|null} net - The net price.
 * @param {number|null} final - The final price.
 * @param {string|number} vatFactor - The VAT percentage (e.g., "24%").
 * @returns {Object|null}
 */
function calculateVatValues(net, final, vatFactor) {
    if (vatFactor === undefined || vatFactor === null) return null;

    // Clear % character from the string and convert to number between 0 and 100
    const numericFactor = parseFloat(vatFactor.toString().replace('%', ''));
    if (isNaN(numericFactor)) return null;

    const vatRate = numericFactor / 100;
    let calculatedNet, calculatedFinal;

    if (net !== null && net !== undefined && net !== 0) {
        // Calculate final price from net
        calculatedNet = parseFloat(net);
        calculatedFinal = calculatedNet * (1 + vatRate);
    }
    else if (final !== null && final !== undefined && final !== 0) {
        // Calculate net price from final
        calculatedFinal = parseFloat(final);
        calculatedNet = calculatedFinal / (1 + vatRate);
    }
    else {
        return null;
    }

    return {
        net: Number(calculatedNet.toFixed(2)),
        final: Number(calculatedFinal.toFixed(2))
    };
}

const FilterManager = {
    state: {},
    config: [],

    /**
     * Renders the filter UI inside the popup based on the value_type
     */
    render: function (filtersConfig, onApply, onClear) {
        this.config = filtersConfig;
        const container = document.getElementById('filterFields');
        if (!container) return;

        const closeModal = () => {
            const modal = document.getElementById('filterPopup');
            const closeBtn = document.getElementById('closeFilter');
            if (closeBtn) {
                closeBtn.click();
            } else if (modal) {
                modal.classList.remove('active', 'show', 'open');
            }
        };

        let html = '<div class="filter-grid">';

        this.config.forEach(filter => {
            html += `<div class="form-group" style = "margin-bottom: 15px;" >
                <label class="label-text" style="display:block; margin-bottom:8px; font-weight:bold; font-size:0.85rem;">
                    ${filter.label}
                </label>`;

            // Render input based on value_type
            switch (filter.value_type) {
                case 'select':
                    const currentSelectVal = this.state[filter.id] ?? filter.default ?? '';
                    let optionsHtml = '';

                    if (filter.options) {
                        filter.options.forEach(option => {
                            // Compare as strings to handle numeric 0 vs empty string correctly
                            const isSelected = String(option.value) === String(currentSelectVal) ? 'selected' : '';
                            optionsHtml += `<option value = "${option.value}" ${isSelected}> ${option.label}</option > `;
                        });
                    }

                    html += `<select id = "filter_${filter.id}"
            class="form-control filter-input-select"
            style = "width: 100%; height: 38px; cursor: pointer;" >
                ${optionsHtml}
             </select > `;
                    break;

                case 'number':
                    // Convert the attributes object to a string
                    const attributesHtml = filter.attributes
                        ? Object.entries(filter.attributes)
                            .map(([key, value]) => `${key}="${value}"`)
                            .join(' ')
                        : '';

                    const currentNum = filter.default ?? 0;

                    html += `<input type = "number"
                                id = "filter_${filter.id}"
                                class="form-control filter-input-number"
                                value = "${currentNum}"
                                placeholder = "0"
                                ${attributesHtml}> `;
                    break;
                case 'date':
                    const currentDate = this.state[filter.id] || '';
                    html += `<input type = "date"
            id = "filter_${filter.id}"
            class="form-control filter-input-date"
            value = "${currentDate}" > `;
                    break;

                case 'color':
                    html += `<div class="checkbox-options" style = "display: grid; grid-template-columns: repeat(auto-fill, minmax(60px, 1fr)); gap: 10px;" > `;
                    if (filter.options) {
                        filter.options.forEach(option => {
                            const colorVal = option.color;
                            const isChecked = Array.isArray(this.state[filter.id]) && this.state[filter.id].includes(colorVal) ? 'checked' : '';

                            html += `
                <label class="option-group" style = "display: flex; align-items: center; justify-content: flex-start; cursor: pointer; margin: 0;" >
                    <input type="checkbox"
                        name="filter_${filter.id}"
                        value="${colorVal}"
                        class="filter-checkbox"
                        ${isChecked}
                        style="display:none;">
                        <span class="color-box"
                            style="background-color: ${colorVal}; width:30px; height:30px; border-radius:4px; border: 2px solid transparent; transition: all 0.2s; display:inline-block;"
                            title="${colorVal}">
                        </span>
                    </label>`;
                        });
                    }
                    html += `</div > `;
                    break;

                case 'checkbox_group':
                    html += `<div class="checkbox-options-group" > `;
                    if (filter.options) {
                        filter.options.forEach(option => {
                            const optVal = option.value;
                            const optLabel = option.text;
                            const isChecked = Array.isArray(this.state[filter.id]) && this.state[filter.id].includes(String(optVal)) ? 'checked' : '';

                            html += `
                <label class="option-group" >
                    <input type="checkbox"
                        name="filter_${filter.id}"
                        value="${optVal}"
                        class="filter-checkbox-generic"
                        ${isChecked}
                    >
                        <span class="option-label">${optLabel}</span>
                    </label>`;
                        });
                    }
                    html += `</div > `;
                    break;
            }
            html += `</div > `;
        });

        html += '</div>';
        html += `
                <div class="filter-actions">
                <button type="button" id="btn-apply-filters" class="action-btn btn-primary">${js_texts.filters_apply}</button>
                <button type="button" id="btn-clear-filters" class="action-btn btn-outline-danger">${js_texts.filters_clear}</button>
            </div > `;

        container.innerHTML = html;

        // --- Event Listeners ---

        // Date Inputs
        container.querySelectorAll('.filter-input-date').forEach(input => {
            const fieldId = input.id.replace('filter_', '');
            input.addEventListener('change', () => this.updateState(fieldId, input.value));
        });

        // Color Checkboxes
        container.querySelectorAll('.filter-checkbox').forEach(checkbox => {
            const fieldId = checkbox.name.replace('filter_', '');
            const box = checkbox.nextElementSibling;

            // Initial highlight if the checkbox is pre-checked based on state
            if (checkbox.checked) box.style.borderColor = '#007bff';

            checkbox.addEventListener('change', () => {
                if (!this.state[fieldId]) this.state[fieldId] = [];

                if (checkbox.checked) {
                    this.state[fieldId].push(checkbox.value);
                    // Highlight the color box when selected
                    box.style.borderColor = '#007bff';
                } else {
                    this.state[fieldId] = this.state[fieldId].filter(v => v !== checkbox.value);
                    box.style.borderColor = 'transparent';
                }

                if (this.state[fieldId].length === 0) delete this.state[fieldId];
            });
        });

        // Generic Checkbox Group Listeners
        container.querySelectorAll('.filter-checkbox-generic').forEach(checkbox => {
            const fieldId = checkbox.name.replace('filter_', '');

            checkbox.addEventListener('change', () => {
                if (!this.state[fieldId]) this.state[fieldId] = [];

                const val = String(checkbox.value);

                if (checkbox.checked) {
                    if (!this.state[fieldId].includes(val)) {
                        this.state[fieldId].push(val);
                    }
                } else {
                    this.state[fieldId] = this.state[fieldId].filter(v => v !== val);
                }

                // If the array is empty after removal, delete the key from state
                if (this.state[fieldId].length === 0) delete this.state[fieldId];
            });
        });

        // Number Inputs
        container.querySelectorAll('.filter-input-number').forEach(input => {
            const fieldId = input.id.replace('filter_', '');

            input.addEventListener('input', () => {
                this.updateState(fieldId, input.value);
            });
        });

        // Select Input Listeners
        container.querySelectorAll('.filter-input-select').forEach(select => {
            const fieldId = select.id.replace('filter_', '');

            select.addEventListener('change', () => {
                this.updateState(fieldId, select.value);
            });
        });

        // Apply Button
        document.getElementById('btn-apply-filters').addEventListener('click', () => {
            onApply();
            closeModal();
        });

        // Clear Button
        document.getElementById('btn-clear-filters').addEventListener('click', () => {
            this.state = {};
            this.render(this.config, onApply, onClear);
            if (onClear) onClear();
        });
    },

    updateState: function (id, value) {
        if (value && String(value).trim() !== '') {
            this.state[id] = value;
        } else {
            delete this.state[id];
        }
    },

    getFilters: function () {
        return this.state;
    },

    hasActiveFilters: function () {
        return Object.keys(this.state).some(key => {
            const val = this.state[key];
            if (Array.isArray(val)) return val.length > 0;
            return val !== undefined && val !== null && String(val).trim() !== '';
        });
    }
};

document.addEventListener('DOMContentLoaded', function () {
    const logoutButton = document.getElementById('logoutButton');
    const loginButton = document.getElementById('loginButton');
    const loginFormContainer = document.getElementById('loginFormContainer');
    const loginForm = document.getElementById('loginForm');
    const closeButton = document.getElementById('closeLogin');
    const nofollowLinks = document.querySelectorAll('a[rel~="nofollow"]');
    const languageButton = document.querySelector('.language-button');
    const languageContent = document.querySelector('.language-dropdown-content');
    const adminLoginForm = document.getElementById('adminLoginForm');
    const adminLogoutButton = document.getElementById('adminLogoutButton');
    // Elements related to the main data table and bulk actions
    const mainTable = document.getElementById('main-data-table');
    const selectAllCheckbox = document.getElementById('select-all-entities');
    const bulkDeleteBtn = document.getElementById('btn-bulk-delete');
    const selectedCountSpan = document.getElementById('selected-count');
    const searchTrigger = document.getElementById('searchTrigger');
    const searchInput = document.getElementById('searchInput');
    const filterTrigger = document.getElementById('filterTrigger');
    const filterPopup = document.getElementById('filterPopup');
    const closeFilter = document.getElementById('closeFilter');
    const managerClientSelect = document.querySelector('select#main-manager_client_id');
    const deleteBtn = document.querySelectorAll('.delete-file-icon');
    const questionnaireInput = document.getElementById('main-questionnaire_id');
    const nutritionPlanInput = document.getElementById('main-plan_id');
    const createAppointmentInvoiceBtn = document.getElementById('create-invoice');
    const appointmentInvoiceContainer = document.getElementById('main-appointment_invoice');
    const cancelInvoiceBtn = document.getElementById('cancel-invoice');
    const invoiceContainer = document.getElementById('main-invoice_actions');
    const addInvoiceCharge = document.getElementById('addInvoiceCharge');
    const closeChargePopup = document.querySelectorAll('.closeChargePopup');
    const closeChargePopupContainer = document.querySelector('.charge-popup-container');
    const saveCharge = document.getElementById('saveCharge');
    const inputClean = document.getElementById('charge_clean_amount');
    const inputTotal = document.getElementById('charge_total_display');
    const selectTax = document.getElementById('charge_tax');
    const chargesContainer = document.getElementById('main-invoice_charges');
    const popupCalcInputs = [document.getElementById('charge_clean_amount'), document.getElementById('charge_tax')];
    const downloadInvoicePDF = document.getElementById('download-invoice-pdf');
    const serviceCleanCost = document.getElementById('main-clean_cost');

    if (logoutButton) {
        logoutButton.addEventListener('click', function (e) {
            e.preventDefault();
            handleClientLogout();
        });
    }

    // Show/Hide Login Form
    if (loginButton) {
        loginButton.addEventListener('click', () => {
            // toggle class active
            loginFormContainer.classList.toggle('active');
        });
    }

    // Login Form Submit
    if (loginForm) {
        // Complete the connection for the client login form
        loginForm.addEventListener('submit', handleClientLogin);
    }

    if (closeButton && loginFormContainer) {
        closeButton.addEventListener('click', function (e) {
            loginFormContainer.classList.remove('active');
        });
    }

    if (nofollowLinks) {
        nofollowLinks.forEach(link => {
            link.addEventListener('click', function (event) {
                event.preventDefault();
            });
        });
    }

    // Toggle menu on click (mobile)
    if (languageButton && languageContent) {
        languageButton.addEventListener('click', function (e) {
            e.stopPropagation();
            languageContent.style.display = (languageContent.style.display === 'block') ? 'none' : 'block';
        });
        // Close when clicking anywhere else
        document.addEventListener('click', function () {
            languageContent.style.display = 'none';
        });
    }

    // Admin Login Form Submit
    if (adminLoginForm) {
        adminLoginForm.addEventListener('submit', handleAdminLogin);
    }

    if (adminLogoutButton) {
        adminLogoutButton.addEventListener('click', function (e) {
            e.preventDefault();
            handleAdminLogout();
        });
    }


    if (mainTable && selectAllCheckbox && bulkDeleteBtn && selectedCountSpan) {
        const rowCheckboxes = mainTable.querySelectorAll('.row-checkbox');

        // Update toolbar state based on selected checkboxes
        const updateToolbar = () => {
            const selectedCount = mainTable.querySelectorAll('.row-checkbox:checked').length;
            selectedCountSpan.textContent = selectedCount;
            bulkDeleteBtn.disabled = selectedCount === 0;
        };

        // Select All
        selectAllCheckbox.addEventListener('change', function () {
            rowCheckboxes.forEach(cb => {
                cb.checked = this.checked;
                cb.closest('tr').classList.toggle('selected-row', this.checked);
            });
            updateToolbar();
        });

        // Individual Row Click
        mainTable.querySelector('tbody').addEventListener('click', function (e) {
            // If click is on an icon button, ignore row selection
            if (e.target.closest('.icon-btn')) return;

            const row = e.target.closest('tr');
            if (!row) return;

            const checkbox = row.querySelector('.row-checkbox');

            // Toggle checkbox if click is outside of it
            if (e.target !== checkbox) {
                checkbox.checked = !checkbox.checked;
            }

            row.classList.toggle('selected-row', checkbox.checked);

            // If any checkbox is unchecked, uncheck "Select All"
            if (!checkbox.checked && selectAllCheckbox) {
                selectAllCheckbox.checked = false;
            }

            updateToolbar();
        });
    }

    if (bulkDeleteBtn && searchTrigger) {
        bulkDeleteBtn.addEventListener('click', async function () {
            // Get all selected checkboxes
            const selectedCheckboxes = document.querySelectorAll('#table-body-root .row-checkbox:checked');

            if (selectedCheckboxes.length === 0) return;

            const idsToDelete = Array.from(selectedCheckboxes).map(cb => cb.value);

            // User Confirmation
            const confirmMessage = bulkDeleteBtn.classList.contains('cancel-invoice-action')
                ? js_texts.confirm_bulk_cancel
                : js_texts.confirm_bulk_delete;

            if (!confirm(confirmMessage)) {
                return;
            }

            try {
                const deleteUrl = bulkDeleteBtn.getAttribute('data-delete-url');
                const languageCode = document.getElementById('language_code').value;
                filtersInitialized = false;

                const response = await aAjaxCall(deleteUrl, 'POST', {
                    ids: idsToDelete,
                    language_code: languageCode
                });

                if (response.success) {
                    alert(response.message || js_texts.success_bulk_delete);
                    // Refresh the table data after deletion
                    const tableContainerId = 'table-root';
                    // The API that populates the table
                    const apiUrl = searchTrigger.getAttribute('data-api-url');
                    initDynamicTable(tableContainerId, apiUrl);
                } else {
                    alert(response.message || js_texts.error_bulk_delete_failed);
                }
            } catch (error) {
                console.error("Bulk Delete Error:", error);
                const msg = error.serverResponse?.message || error.message || js_texts.error_generic_failure;
                alert(msg);
            }
        });
    }

    if (searchTrigger && searchInput) {
        searchTrigger.addEventListener('click', async function () {
            const searchIcon = document.getElementById('searchIcon');
            const spinner = document.getElementById('spinner');
            const tableContainerId = 'table-root';
            const apiUrl = searchTrigger.getAttribute('data-api-url');

            // Toggle icons
            if (searchIcon && spinner) {
                searchIcon.style.display = 'none';
                spinner.style.display = 'inline-block';
            }

            try {
                await initDynamicTable(tableContainerId, apiUrl, 1, results_limit, currentSortColumn, currentSortDirection);
            } catch (error) {
                console.error('Search error:', error);
            } finally {
                // Restore icons in success or failure
                if (searchIcon && spinner) {
                    spinner.style.display = 'none';
                    searchIcon.style.display = 'inline-block';
                }
            }
        });

        // Allow pressing Enter in the search input to trigger the search
        searchInput.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                searchTrigger.click();
            }
        });
    }

    var filterPopupEvents = false;
    if (filterTrigger && filterPopup) {
        filterTrigger.addEventListener('click', () => filterPopup.classList.add('active'));
        filterPopupEvents = true;
    }

    if (closeFilter) {
        closeFilter.addEventListener('click', () => filterPopup.classList.remove('active'));
        filterPopupEvents = true;
    }

    if (filterPopupEvents) {
        window.addEventListener('click', (e) => {
            if (e.target === filterPopup) filterPopup.classList.remove('active');
        });
    }

    if (managerClientSelect) {
        const fieldsToToggle = [
            '#main-active',
            '#main-username',
            '#main-password',
            '#main-confirm_password'
        ];

        if (managerClientSelect) {
            managerClientSelect.addEventListener('change', function () {
                const selectedValue = parseInt(this.value);

                fieldsToToggle.forEach(selector => {
                    const field = document.querySelector(selector);
                    if (field) {
                        if (selectedValue > 0) {
                            field.closest('.form-input').style.display = 'none';
                            field.value = '';
                        } else {
                            field.closest('.form-input').style.display = 'block';
                        }
                    }
                });
            });

            // Execute once to initialize edit pages
            managerClientSelect.dispatchEvent(new Event('change'));
        }
    }

    if (deleteBtn) {
        for (var i = 0; i < deleteBtn.length; i++) {
            deleteBtn[i].addEventListener('click', async function (e) {
                e.preventDefault();

                const filePath = this.getAttribute('data-file');
                const fileTable = this.getAttribute('data-table');
                const fileId = this.getAttribute('data-id');
                const fileLabel = this.getAttribute('data-file-label');

                const confirmMessage = js_texts.confirm_delete_file.replace('%s', fileLabel);
                if (!confirm(confirmMessage)) return;

                try {
                    const languageCode = document.getElementById('language_code').value;
                    const url = '/ajax/delete_file.php';
                    const method = 'POST';
                    const data = {
                        language_code: languageCode,
                        file_name: filePath,
                        table: fileTable,
                        id: fileId
                    };

                    const result = await aAjaxCall(url, method, data);
                    alert(result.message);
                    if (result.success) {
                        const wrapper = this.closest('.existing-file-info');
                        if (wrapper) wrapper.remove();

                        if (fileTable === 'questionnaire' && questionnaireInput) {
                            questionnaireInput.value = 0;
                        }
                        else if (fileTable === 'nutrition_plan' && nutritionPlanInput) {
                            nutritionPlanInput.value = 0;
                        }
                    }
                } catch (error) {
                    console.error('File Delete Error:', error);
                    alert(js_texts.unknown_server_error_message);
                }

                return false;
            });

        }
    }

    // Dynamically refresh appointment slots when the appointment date changes
    const appointmentDateInput = document.getElementById('main-appointment_date');
    const appointmentTimeSelect = document.getElementById('main-appointment_time');
    const appointmentIdInput = document.getElementById('main-appointment_id');

    if (appointmentDateInput && appointmentTimeSelect) {
        appointmentDateInput.addEventListener('change', async function () {
            const selectedDate = this.value;
            if (!selectedDate) return;

            try {
                appointmentTimeSelect.innerHTML = '<option value="">' + js_texts.loading + '</option>';

                const url = '/ajax/get_appointment_available_slots.php';
                const method = 'POST';

                const data = {
                    appointment_date: selectedDate,
                    language_code: document.getElementById('language_code').value
                };

                if (appointmentIdInput && appointmentIdInput.value) {
                    data.appointment_id = appointmentIdInput.value;
                }

                const response = await aAjaxCall(url, method, data);

                if (response && response.html_options) {
                    appointmentTimeSelect.innerHTML = response.html_options;
                }
                else if (response && response.message) {
                    alert(response.message);
                }
            } catch (error) {
                console.error('Error fetching appointment slots:', error);
                appointmentTimeSelect.innerHTML = '<option value="">' + js_texts.error_loading_slots + '</option>';
            }
        });
    }

    if (createAppointmentInvoiceBtn && appointmentInvoiceContainer) {
        createAppointmentInvoiceBtn.addEventListener('click', async function (e) {
            e.preventDefault();
            const appointmentId = this.getAttribute('data-appointment-id');

            try {
                const languageCode = document.getElementById('language_code').value;
                const url = '/ajax/create_appointment_invoice.php';
                const method = 'POST';
                const data = {
                    language_code: languageCode,
                    appointment_id: appointmentId
                };

                showLoader();

                const result = await aAjaxCall(url, method, data);
                if (result.success && result.html) {
                    appointmentInvoiceContainer.innerHTML = result.html;
                }

                hideLoader();

                if (result.message && result.message !== '') {
                    alert(result.message);
                }
                if (result.redirect_url) {
                    window.location.href = result.redirect_url;
                }
            } catch (error) {
                console.error('File Delete Error:', error);
                alert(js_texts.unknown_server_error_message);
            }

            return false;
        });

    }

    if (cancelInvoiceBtn && invoiceContainer) {
        cancelInvoiceBtn.addEventListener('click', async function (e) {
            e.preventDefault();

            // User Confirmation
            if (!confirm(js_texts.confirm_cancel_invoice)) {
                return;
            }

            const invoiceId = this.getAttribute('data-invoice-id');

            try {
                const languageCode = document.getElementById('language_code').value;
                const url = '/ajax/cancel_invoice.php';
                const method = 'POST';
                const data = {
                    language_code: languageCode,
                    invoice_id: invoiceId
                };

                showLoader();

                const result = await aAjaxCall(url, method, data);
                if (result.success && result.html) {
                    invoiceContainer.innerHTML = result.html;
                }

                hideLoader();

                if (result.message && result.message !== '') {
                    alert(result.message);
                }
            } catch (error) {
                alert(js_texts.unknown_server_error_message);
            }

            return false;
        });
    }

    if (closeChargePopup && closeChargePopupContainer) {
        for (var i = 0; i < closeChargePopup.length; i++) {
            closeChargePopup[i].addEventListener('click', async function (e) {
                e.preventDefault();
                closeChargePopupContainer.classList.remove('active');
                return false;
            });
        }
    }

    if (addInvoiceCharge && closeChargePopupContainer) {
        addInvoiceCharge.addEventListener('click', async function (e) {
            e.preventDefault();
            resetPopupFields();
            closeChargePopupContainer.classList.add('active');
            return false;
        });
    }

    let chargeIndex = 0;
    let editingIndex = null;

    if (saveCharge) {
        saveCharge.addEventListener('click', function () {
            if (!invoiceChargeValidation()) return false;

            const container = document.getElementById('main-invoice_charges');
            const desc = document.getElementById('charge_description').value;
            const clean = document.getElementById('charge_clean_amount').value;
            const taxSelect = document.getElementById('charge_tax');
            const taxId = taxSelect.value;
            const taxText = taxSelect.options[taxSelect.selectedIndex].text;
            const total = document.getElementById('charge_total_display').value;

            if (editingIndex !== null) {
                // Update existing row
                const row = document.querySelector(`.added-charge-row[data-index="${editingIndex}"]`);

                // Update Hidden Inputs
                row.querySelector(`input[name = "charge_description[${editingIndex}]"]`).value = desc;
                row.querySelector(`input[name = "charge_clean_amount[${editingIndex}]"]`).value = clean;
                row.querySelector(`input[name = "charge_tax_id[${editingIndex}]"]`).value = taxId;
                row.querySelector(`input[name = "charge_total_display[${editingIndex}]"]`).value = total;

                // Update Visual Text
                row.querySelector('.charge-text').innerHTML =
                    `${desc} | ${js_texts.charge_clean}: ${clean} € | ${js_texts.charge_vat}: ${taxText} | ${js_texts.charge_total}: <b>${total} €</b>`;

                // Reset state
                editingIndex = null;
                saveCharge.innerText = js_texts.add;
            } else {
                // Add new row
                const rowId = chargeIndex;
                const newRow = document.createElement('div');
                newRow.className = 'added-charge-row';
                newRow.setAttribute('data-index', rowId);
                newRow.style.cssText = 'display:flex; justify-content:space-between; align-items:center; padding:8px; border-bottom:1px solid #eee;';

                newRow.innerHTML = `
                <div class="charge-info" >
                    <span class="charge-text">${desc} | ${js_texts.charge_clean}: ${clean} € | ${js_texts.charge_vat}: ${taxText} | ${js_texts.charge_total}: <b>${total} €</b></span>
                    <input type="hidden" name="charge_description[${rowId}]" value="${desc}">
                    <input type="hidden" name="charge_clean_amount[${rowId}]" value="${clean}">
                    <input type="hidden" name="charge_tax_id[${rowId}]" value="${taxId}">
                    <input type="hidden" name="charge_total_display[${rowId}]" value="${total}">
                </div>
                <div class="charge-actions">
                    <img src="/images/edit.svg" class="edit-charge" style="cursor:pointer; width:18px; margin-right:10px;" title="${js_texts.edit}">
                    <img src="/images/delete.svg" class="delete-charge" style="cursor:pointer; width:18px;" title="${js_texts.delete}">
                </div>
            `;

                container.appendChild(newRow);
                chargeIndex++;
            }

            updateGrandTotals();
            closeChargePopupContainer.classList.remove('active');
            resetPopupFields();
        });
    }

    if (chargesContainer) {
        chargesContainer.addEventListener('click', function (e) {
            const row = e.target.closest('.added-charge-row');
            if (!row) return;
            const index = row.getAttribute('data-index');

            if (e.target.classList.contains('delete-charge')) {
                if (confirm(js_texts.confirm_delete_charge)) {
                    row.remove();
                    updateGrandTotals();
                }
            }

            if (e.target.classList.contains('edit-charge')) {
                editingIndex = parseInt(index);

                // Fill Popup with existing values
                document.getElementById('charge_description').value = row.querySelector(`input[name="charge_description[${index}]"]`).value;
                document.getElementById('charge_clean_amount').value = row.querySelector(`input[name="charge_clean_amount[${index}]"]`).value;
                document.getElementById('charge_tax').value = row.querySelector(`input[name="charge_tax_id[${index}]"]`).value;
                document.getElementById('charge_total_display').value = row.querySelector(`input[name="charge_total_display[${index}]"]`).value;

                closeChargePopupContainer.classList.add('active');
            }
        });
    }

    if (popupCalcInputs) {
        popupCalcInputs.forEach(input => {
            if (input) {
                input.addEventListener('input', function () {
                    const cleanVal = parseFloat(document.getElementById('charge_clean_amount').value) || 0;
                    const taxSelect = document.getElementById('charge_tax');
                    const factor = parseFloat(taxSelect.options[taxSelect.selectedIndex].getAttribute('data-factor')) || 0;

                    const total = cleanVal * (1 + factor);
                    document.getElementById('charge_total_display').value = total.toFixed(2);
                });
            }
        });
    }

    if (inputClean && inputTotal && selectTax) {
        inputClean.addEventListener('input', calcTotalFromClean);
        inputTotal.addEventListener('input', calcCleanFromTotal);
        selectTax.addEventListener('change', calcTotalFromClean);

        function calcTotalFromClean() {
            const cleanVal = parseFloat(inputClean.value) || 0;
            const factor = parseFloat(selectTax.options[selectTax.selectedIndex].getAttribute('data-factor')) || 0;

            const total = cleanVal * (1 + factor);
            inputTotal.value = total.toFixed(2);
        }

        function calcCleanFromTotal() {
            const totalVal = parseFloat(inputTotal.value) || 0;
            const factor = parseFloat(selectTax.options[selectTax.selectedIndex].getAttribute('data-factor')) || 0;

            const clean = totalVal / (1 + factor);
            inputClean.value = clean.toFixed(2);
        }
    }

    if (downloadInvoicePDF) {
        downloadInvoicePDF.addEventListener('click', async function (e) {
            e.preventDefault();

            const invoiceId = this.getAttribute('data-invoice-id');

            try {
                const languageCode = document.getElementById('language_code').value;
                // Point to the new download endpoint
                const url = '/ajax/download_invoice_pdf.php';
                const method = 'POST';
                const data = {
                    language_code: languageCode,
                    invoice_id: invoiceId
                };

                showLoader();

                const result = await aAjaxCall(url, method, data);

                if (result.success && result.download_url) {
                    const link = document.createElement('a');

                    // Append a timestamp to the URL to prevent caching issues
                    link.href = result.download_url + '?t=' + new Date().getTime();

                    link.setAttribute('download', result.filename);
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                }

                hideLoader();

                if (result.message && result.message !== '') {
                    alert(result.message);
                }

                if (!result.success && result.errors) {
                    alert(result.errors.join('\n'));
                }
            } catch (error) {
                hideLoader();
                alert(js_texts.unknown_server_error_message);
            }

            return false;
        });
    }

    if (serviceCleanCost) {
        serviceCleanCost.addEventListener('blur', function () {
            const cleanVal = this.value;
            const taxVal = document.getElementById('main-tax_id').textContent;
            const vatResults = calculateVatValues(cleanVal, null, taxVal);
            if (vatResults) {
                const totalVal = vatResults.final;
                let totalString = '';
                if (!isNaN(totalVal)) {
                    totalString = totalVal.toFixed(2) + ' €';
                }
                document.getElementById('main-final_cost').innerHTML = totalString;
            }
        });
    }
});

