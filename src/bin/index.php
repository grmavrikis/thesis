<?php
require_once 'includes/init.php';
?>
<!DOCTYPE html>
<html lang="el">

<?php
require_once 'html/head.php';
?>

<body class="setup">
    <input type="hidden" id="language_code" value="<?php echo $language['code']; ?>">
    <div class="terminal">
        <div id="statusMessage" class="command"><?php echo TEXT_INSTALL_DESCRIPTION_INSTALL; ?></div>
    </div>
    <div class="initialize-action">
        <button id="initButton"><?php echo TEXT_INSTALL_BUTTON_ENTER_APPLICATION; ?></button>
    </div>
    <script>
        const languageCode = document.getElementById('language_code').value;
        /**
         * Runs the data population process in a single batch request.
         * @returns {Promise<void>}
         */
        function runDataPopulationProcess() {
            // Get the full test data object
            return aAjaxCall(`/ajax/get_test_data.php`, 'POST', {
                    language_code: languageCode
                })
                .then(dataMap => {
                    if (typeof dataMap !== 'object' || Object.keys(dataMap).length === 0) {
                        throw new Error(js_texts.error_data_fetch_failed);
                    }

                    // Send the entire map to the server to maintain ID context and transaction integrity
                    return aAjaxCall(`/ajax/populate_database.php`, 'POST', dataMap);
                })
                .then(response => {
                    const terminalContainer = document.querySelector('.terminal');
                    if (!terminalContainer) return;

                    if (response.success && response.stats) {
                        // Iterate through the server report and update terminal UI
                        Object.keys(response.stats).forEach(tableName => {
                            const count = response.stats[tableName];
                            const outputDiv = document.createElement('div');
                            outputDiv.classList.add('output');

                            // Reusing your existing translation keys
                            outputDiv.innerHTML = js_texts.success_data_inserted
                                .replace('{count}', count)
                                .replace('{table}', tableName);

                            terminalContainer.appendChild(outputDiv);
                        });
                    } else {
                        throw new Error(response.message || "Unknown error during data population");
                    }

                    terminalContainer.scrollTop = terminalContainer.scrollHeight;
                    return response;
                });
        }

        /**
         * Runs the table creation process.
         * Uses Promise Chaining for sequential table creation.
         * @returns {Promise<void>}
         */
        function runTableCreationProcess() {
            // Get the database schema from the server
            aAjaxCall('/ajax/get_database_schema.php', 'POST', {})
                .then(response => {

                    // Error Handling for Invalid Schema Response
                    if (!response.success || !response.tables || !Array.isArray(response.tables)) {
                        const terminalContainer = document.querySelector('.terminal');
                        if (terminalContainer) {
                            const outputDiv = document.createElement('div');
                            outputDiv.classList.add('output', 'error');
                            outputDiv.innerHTML = js_texts.error_invalid_schema;
                            terminalContainer.appendChild(outputDiv);
                            terminalContainer.scrollTop = terminalContainer.scrollHeight;
                        }
                        throw new Error(js_texts.error_invalid_database_schema);
                    }

                    const tables = response.tables;
                    let promiseChain = Promise.resolve();

                    tables.forEach(tableData => {
                        promiseChain = promiseChain.then(() => {
                            const tableName = tableData.table_name;
                            tableData.language_code = document.getElementById('language_code').value;
                            return aAjaxCall(`/ajax/create_sql_table.php`, 'POST', tableData)
                                .then(response => {
                                    const terminalContainer = document.querySelector('.terminal');
                                    if (!terminalContainer) {
                                        console.error("DOM Error: Terminal element not found.");
                                        return;
                                    }

                                    const outputDiv = document.createElement('div');
                                    outputDiv.classList.add('output');
                                    let messagePrefix = '';

                                    if (response.success) {
                                        messagePrefix = 'Success: ';
                                    } else {
                                        messagePrefix = 'Error: ';
                                        outputDiv.classList.add('error');
                                    }

                                    outputDiv.innerHTML = `<span>${messagePrefix}</span> ${response.message}`;
                                    terminalContainer.appendChild(outputDiv);
                                    terminalContainer.scrollTop = terminalContainer.scrollHeight;

                                    if (!response.success) {
                                        // if table creation failed, throw error to stop the chain
                                        throw new Error(js_texts.error_table_creation_failed.replace('{table}', tableName).replace('{message}', response.message));
                                    }
                                    return response;
                                });
                        });
                    });

                    // Data Population after Table Creation
                    return promiseChain.then(() => {
                        return runDataPopulationProcess();
                    });
                })
                .then(() => {
                    // Chain Complete - Show Final Success Message and Init Button
                    const terminalContainer = document.querySelector('.terminal');
                    const initBtn = document.getElementById('initButton');

                    if (terminalContainer) {
                        const outputDiv = document.createElement('div');
                        outputDiv.classList.add('command');
                        outputDiv.innerHTML = js_texts.success_setup_complete;
                        terminalContainer.appendChild(outputDiv);
                        terminalContainer.scrollTop = terminalContainer.scrollHeight;
                    }

                    if (initBtn) {
                        initBtn.style.display = 'inline-block';
                    }
                })
                .catch(error => {
                    // Error Handling
                    const terminalContainer = document.querySelector('.terminal');
                    const isAlreadyHandled = error.message.includes("Invalid schema response");

                    if (terminalContainer && !isAlreadyHandled) {
                        const outputDiv = document.createElement('div');
                        outputDiv.classList.add('output', 'error');

                        let errorMessageText = error.message;
                        const serverErrorJson = error.serverResponse;

                        if (serverErrorJson) {
                            // Use the object sent by aAjaxCall
                            const friendlyMessage = serverErrorJson.message || js_texts.unknown_server_error;
                            let details = serverErrorJson.error_details || '';
                            let sql = serverErrorJson.sql || 'N/A';

                            // Construct detailed error message
                            let detailsPart = details;
                            if (sql !== 'N/A') {
                                if (detailsPart) {
                                    detailsPart += ' | ';
                                }
                                detailsPart += `SQL: ${sql}`;
                            }

                            if (detailsPart) {
                                errorMessageText = `${friendlyMessage} <br>${detailsPart}`;
                            } else {
                                errorMessageText = friendlyMessage;
                            }
                        }

                        outputDiv.innerHTML = `<div class="command error">${errorMessageText}</div>`;

                        terminalContainer.appendChild(outputDiv);
                        terminalContainer.scrollTop = terminalContainer.scrollHeight;
                    }
                });
        }

        // Flag to prevent the process from running more than once
        let isStarted = false;

        // Enter Keyboard Listener
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Enter' && !isStarted) {
                isStarted = true;

                // Update terminal that the process has started
                const status = document.getElementById('statusMessage');
                if (status) {
                    status.innerText = '<?php echo TEXT_INSTALL_INITIALIZATION_PROCESSS_STARTED; ?>';
                }

                // Start the table creation process
                runTableCreationProcess();
            }
            // If F8 is pressed, switch language
            if (event.key === 'F8') {
                const currentPath = window.location.pathname;
                if (currentPath === '/' || currentPath === '/index.php') {
                    window.location.href = '/english/';
                } else {
                    window.location.href = '/';
                }
            }
        });

        document.getElementById('initButton').addEventListener('click', async function() {
            try {
                this.disabled = true;
                this.innerText = '<?php echo TEXT_INSTALL_INITIALIZING; ?>';
                const languageCode = document.getElementById('language_code').value;
                const response = await aAjaxCall(`/ajax/initialize_application.php`, 'POST', {
                    language_code: languageCode
                });
                if (response.success) {
                    window.location.href = response.redirect_url;
                } else {
                    alert('<?php echo TEXT_INSTALL_INITIALIZATION_ERROR; ?>' + ' ' + response.message);
                    this.disabled = false;
                    this.innerText = '<?php echo TEXT_INSTALL_BUTTON_ENTER_APPLICATION; ?>';
                }
            } catch (error) {
                console.error(error);
                alert('<?php echo TEXT_INSTALL_INITIALIZATION_ERROR; ?>');
            }
        });
    </script>
</body>

</html>