<?php

/**
 * Returns a JSON response and terminates the script.
 * @param bool $success - Whether the operation was successful.
 * @param string $message - The message to send to the client.
 * @param array $extra_data - Extra data (e.g., user_id).
 * @return void
 */
function sendResponse(bool $success, string $message, array $extra_data = []): void
{
    $response = [
        'success' => $success,
        'message' => $message,
    ];

    if (!empty($extra_data))
    {
        $response = array_merge($response, $extra_data);
    }

    echo json_encode($response);
    exit;
}

/**
 * Returns a list of gender options for dropdowns.
 * @return array - The list of gender options (value, label).
 */
function getGendersOptions($all = 0)
{
    $options = [];
    if ($all == 1)
    {
        $options[] = ['value' => 0, 'label' => TEXT_ALL];
    }
    $options[] = ['value' => 'M', 'label' => GENDER_MALE];
    $options[] = ['value' => 'F', 'label' => GENDER_FEMALE];

    return $options;
}

/**
 * Returns a list of options for the status of the user account for dropdowns.
 * @return array - The list of options (value, label).
 */
function getUserStatusOptions()
{
    return [
        ['value' => 0, 'label' => TEXT_USERS_ALL],
        ['value' => 1, 'label' => TEXT_USERS_ACTIVE],
        ['value' => 2, 'label' => TEXT_USERS_INACTIVE]
    ];
}

/**
 * Returns a list of options for the status of the client for dropdowns.
 * @return array - The list of options (value, label).
 */
function getClientStatusOptions()
{
    return [
        ['value' => 0, 'label' => TEXT_CLIENTS_ALL],
        ['value' => 1, 'label' => TEXT_CLIENTS_MANAGER],
        ['value' => 2, 'label' => TEXT_CLIENTS_DEPENDENT]
    ];
}

/**
 * Returns a list of yes/no options for dropdowns.
 * @return array - The list of options (value, label).
 */
function getBooleanOptions()
{
    return [
        ['value' => 0, 'label' => TEXT_NO],
        ['value' => 1, 'label' => TEXT_YES]
    ];
}

/**
 * Returns a list of all available manager clients for selection.
 * Filters out clients who are already marked as dependents in a relationship.
 * @return array - The formatted list of manager options.
 */
function getManagerClientsOptions($client_id = '')
{
    global $db;

    $sql = "SELECT 
            c.client_id AS value, 
            CONCAT_WS(' ', c.last_name, c.first_name, c.phone) AS label
            FROM client c
            WHERE NOT EXISTS (
                SELECT 1 
                FROM client_relationships cr 
                WHERE cr.dependent_client_id = c.client_id
            ) "
        . (!empty($client_id) ? ' AND c.client_id<>?' : '')
        . " ORDER BY c.last_name, c.first_name DESC";

    $params = [];
    if (!empty($client_id))
    {
        $params = [$client_id];
    }
    $results = $db->query($sql, $params)->fetchAll();

    $options = [];
    $options[] = ['value' => 0, 'label' => TEXT_NONE];
    $options = array_merge($options, $results);

    return $options;
}

/**
 * Returns a list of all available services for selection.
 * @return array - The formatted list of services options.
 */
function getServicesOptions()
{
    global $db, $language;

    $sql = "SELECT 
            s.service_id AS value, 
            sd.title AS label
            FROM service s
            JOIN service_description sd ON sd.service_id=s.service_id
            WHERE sd.language_id = :language_id
            ORDER BY s.sort_order ASC";

    $params = [':language_id' => $language['id']];

    $results = $db->query($sql, $params)->fetchAll();

    $options = [];
    $options[] = ['value' => 0, 'label' => TEXT_SELECT_SERVICE];
    $options = array_merge($options, $results);

    return $options;
}

/**
 * Returns a list of all available clients for selection.
 * @return array - The formatted list of clients options.
 */
function getClientsOptions()
{
    global $db, $auth;

    $sql = "SELECT 
            c.client_id AS value, 
            CONCAT_WS(' ', c.last_name, c.first_name, c.phone) AS label
            FROM client c";

    $params = [];
    if (!empty($auth->getUser()['client_id']))
    {
        $clients = [];
        $clients[] = $auth->getUser()['client_id'];

        $query = "SELECT dependent_client_id FROM client_relationships
                WHERE manager_client_id = :client_id";
        $params = [':client_id' => $auth->getUser()['client_id']];
        $results = $db->query($query, $params)->fetchAll();

        foreach ($results as $row)
        {
            $clients[] = $row['dependent_client_id'];
        }

        $inData = $db->prepareIn($clients, 'client_id');

        $sql .= " WHERE c.client_id IN ({$inData['placeholders']})";
        $params = $inData['params'];
    }

    $results = $db->query($sql, $params)->fetchAll();

    $options = [];
    $options[] = ['value' => 0, 'label' => TEXT_SELECT_CLIENT];
    $options = array_merge($options, $results);

    return $options;
}

/**
 * Returns a list of all available dietitians for selection.
 * @return array - The formatted list of dietitians options.
 */
function getDietitiansOptions($include_default = false, $dietitian_id = [])
{
    global $db, $auth;

    $admin_sql = '';
    $params = [];
    if (isset($auth->getUser()['admin_id']))
    {
        $admin_sql = '(d.dietitian_id = :current_id) DESC, ';
        $params = [':current_id' => $auth->getUser()['admin_id']];
    }

    $where = '';
    if (!empty($dietitian_id))
    {
        $inData = $db->prepareIn($dietitian_id, 'dietitian_id');
        $where = " WHERE d.dietitian_id IN ({$inData['placeholders']})";
        $params = array_merge($params, $inData['params']);
    }

    $sql = "SELECT 
            d.dietitian_id AS value, 
            CONCAT_WS(' ', d.last_name, d.first_name, d.phone) AS label
            FROM dietitian d
            $where
            ORDER BY $admin_sql d.last_name ASC, d.first_name ASC";

    $results = $db->query($sql, $params)->fetchAll();

    $options = [];
    if ($include_default)
    {
        $options[] = ['value' => 0, 'label' => TEXT_SELECT_DIETITIAN];
    }
    $options = array_merge($options, $results);

    return $options;
}

/**
 * Fetches a list of all available dietitians for selection dropdowns.
 * If a dietitian is currently logged in, they are moved to the top of the list.
 * All other records are sorted alphabetically by last name and first name.
 *
 * @param bool $include_default Whether to include a default "Select" option at index 0.
 * @return array The formatted list of dietitian options with 'value' and 'label' keys.
 */
function getAppointmentStatusOptions($include_default = false)
{
    global $db, $language;

    $sql = "SELECT 
            a.appointment_status_id AS value, 
            ad.title AS label
            FROM appointment_status a
            JOIN appointment_status_description ad ON a.appointment_status_id=ad.appointment_status_id
            WHERE ad.language_id = :language_id
            ORDER BY a.sort_order ASC";

    $params = [':language_id' => $language['id']];

    $results = $db->query($sql, $params)->fetchAll();

    $options = [];
    if ($include_default)
    {
        $options[] = ['value' => 0, 'label' => TEXT_SELECT_APPOINTMENT_STATUS];
    }
    $options = array_merge($options, $results);

    return $options;
}

/**
 * Returns a list of invoice status for dropdowns.
 * @return array - The list of invoice status options (value, label).
 */
function getInvoiceStatusOptions()
{
    $options = [];
    $options[] = ['value' => 0, 'label' => TEXT_ALL];
    $options[] = ['value' => 1, 'label' => TEXT_COMPLETED];
    $options[] = ['value' => 2, 'label' => TEXT_CANCELED];

    return $options;
}
/**
 * Returns available appointment slots (Mon-Fri, 09:00-17:00).
 * * @param string $date The date we are checking (YYYY-MM-DD)
 * @param string $taken_slot The full datetime of the current appointment (YYYY-MM-DD HH:ii)
 * @return array Formatted options for selection
 */
function getAppointmentSlots($date = '', $taken_slot = '')
{
    global $db;
    $options = [];

    if (empty($date))
    {
        return [['value' => 0, 'label' => TEXT_NO_AVAILABLE_APPOINTMENT_SLOTS]];
    }

    // Weekend Check
    $dayOfWeek = (int)date('N', strtotime($date));
    if ($dayOfWeek > 5)
    {
        return [['value' => 0, 'label' => TEXT_NO_AVAILABLE_APPOINTMENT_SLOTS]];
    }

    $today = date('Y-m-d');
    $current_time = date('H:i');
    $is_past_date = ($date < $today);
    $is_today = ($date == $today);

    $dt = new DateTime($taken_slot);
    $taken_date = $dt->format('Y-m-d');
    $taken_time = $dt->format('H:i');

    // Fetch busy slots
    $params = [':date' => $date];
    $extra_where = "";

    // If the selected date is the date of the taken_slot, exclude that time from the "busy" list
    if ($date === $taken_date && !empty($taken_time))
    {
        $extra_where = " AND DATE_FORMAT(a.appointment_date, '%H:%i') != :taken_slot ";
        $params[':taken_slot'] = $taken_time;
    }

    $sql_busy = "SELECT DATE_FORMAT(a.appointment_date, '%H:%i') as busy_time 
                FROM appointment a
                JOIN appointment_status ast ON a.appointment_status_id = ast.appointment_status_id
                WHERE DATE(a.appointment_date) = :date 
                AND TIME(a.appointment_date) BETWEEN '09:00:00' AND '17:00:00'
                AND ast.is_default=1
                $extra_where";

    $busy_results = $db->query($sql_busy, $params)->fetchAll();
    $busy_slots = array_column($busy_results, 'busy_time');

    // 4. Generate Slots (09:00 - 17:00)
    for ($minutes = 540; $minutes <= 1020; $minutes += 30)
    {
        $h = floor($minutes / 60);
        $m = $minutes % 60;
        $time_24 = sprintf('%02d:%02d', $h, $m);

        // CASE 1: Past Date
        if ($is_past_date)
        {
            // Show ONLY the taken slot if the date matches.
            if ($date === $taken_date && $time_24 === $taken_time)
            {
                // Allowed
            }
            else
            {
                continue;
            }
        }
        // CASE 2: Today
        elseif ($is_today)
        {
            // Filter past hours, but keep the taken_slot even if it's in the past
            if ($time_24 < $current_time && !($date === $taken_date && $time_24 === $taken_time))
            {
                continue;
            }
            // Filter busy slots
            if (in_array($time_24, $busy_slots))
            {
                continue;
            }
        }
        // CASE 3: Future
        else
        {
            // Filter busy slots
            if (in_array($time_24, $busy_slots))
            {
                continue;
            }
        }

        $options[] = [
            'value' => $date . ' ' . $time_24,
            'label' => $time_24
        ];
    }

    if (empty($options))
    {
        $options[] = [
            'value' => 0,
            'label' => TEXT_NO_AVAILABLE_APPOINTMENT_SLOTS
        ];
    }

    return $options;
}

/**
 * Returns html code for link to view appointment invoice.
 * @param string $invoice_id The invoice to show
 * @return string Html link element to view invoice
 */
function viewAppointmentInvoiceHtml($invoice_id, $appointment_id)
{
    global $seoUrl;

    return '<a href="' . $seoUrl->generate('administration/view_invoice.php', ['id' => $invoice_id, 'appointment_id' => $appointment_id]) . '" class="view-invoice">' . TEXT_VIEW_INVOICE . '</a>';
}

/**
 * Returns html code for link to create appointment invoice.
 * @param string $appointment_id The appointment to invoice
 * @return string Html link element to create invoice
 */
function createAppointmentInvoiceHtml($appointment_id)
{
    return '<a href="#" id="create-invoice" class="create-invoice invoice-action" data-appointment-id="' . $appointment_id . '">' . TEXT_CREATE_INVOICE . '</a>';
}

/**
 * Retrieves and formats the snapshotted issuer's details from a specific invoice.
 *
 * @param int $invoice_id The ID of the invoice.
 * @return string The formatted HTML string of snapshotted issuer details.
 */
function getInvoiceIssuerDetailsFromSnapshot($invoice_id)
{
    global $db;

    if (empty($invoice_id) || !is_numeric($invoice_id))
    {
        return '';
    }

    $sql = "SELECT 
                issuer_title, 
                issuer_name, 
                issuer_vat, 
                issuer_tax_office, 
                issuer_address_street, 
                issuer_address_number, 
                issuer_city, 
                issuer_postal_code,
                issuer_phone,
                issuer_email
            FROM invoice 
            WHERE invoice_id = :invoice_id";

    $results = $db->query($sql, [':invoice_id' => $invoice_id])->fetch();

    if (!$results)
    {
        return '';
    }

    $html = '<div class="issuer-details" style="line-height: 1.6; color: #333;">';

    $html .= '    <div class="issuer-name" style="font-size: 1.1rem; margin-bottom: 4px;">' . htmlspecialchars($results['issuer_name']) . '</div>';

    $html .= '    <div class="issuer-title" style="font-size: 1.1rem; margin-bottom: 4px;">' . htmlspecialchars($results['issuer_title']) . '</div>';

    $html .= '    <div class="issuer-address">';
    $html .=          htmlspecialchars($results['issuer_address_street'] . ' ' . $results['issuer_address_number']);
    $html .= '        <br>' . htmlspecialchars($results['issuer_postal_code']) . ' ' . htmlspecialchars($results['issuer_city']);
    $html .= '    </div>';


    // Contact Info
    $contact_parts = [];
    if (!empty($results['issuer_phone']))
    {
        $contact_parts[] = LABEL_PHONE_SHORT . ' ' . htmlspecialchars($results['issuer_phone']);
    }
    if (!empty($results['issuer_email']))
    {
        $contact_parts[] = LABEL_EMAIL . ' ' . htmlspecialchars($results['issuer_email']);
    }

    if (!empty($contact_parts))
    {
        $html .= '    <div class="issuer-contact" style="margin-top: 4px;">';
        $html .=          implode(' | ', $contact_parts);
        $html .= '    </div>';
    }

    $html .= '<div class="issuer-tax-info" style="margin-top: 8px; padding-top: 8px; border-top: 1px solid #eee;">';
    $html .= LABEL_COMPANY_VAT_NUMBER . ' ' . htmlspecialchars($results['issuer_vat']) . ' | ';
    $html .= LABEL_COMPANY_TAX_OFFICE . ' ' . htmlspecialchars($results['issuer_tax_office']);
    $html .= '</div>';

    $html .= '</div>';

    return $html;
}
