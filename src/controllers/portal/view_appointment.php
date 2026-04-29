<?php
if (empty($auth->getUser()['client_id']))
{
    header('Location: ' . $seoUrl->generate('index.php'));
    exit();
}

if (!$_GET['id'] || !is_numeric($_GET['id']))
{
    header('Location: ' . $seoUrl->generate('portal/appointment.php'));
    exit();
}
$breadcrumb = new Breadcrumb();
$breadcrumb->addItem(TEXT_APPOINTMENT, $seoUrl->generate('portal/appointment.php'));
$breadcrumb->addItem(PAGE_TITLE, null);

$file_manager = new FileManager();

$sql = "SELECT a.*,
               q.questionnaire_id AS questionnaire_id,
               q.title AS questionnaire_title,
               q.file_path AS questionnaire_file_path,
               n.plan_id AS plan_id,
               n.title AS plan_title,
               n.file_path AS plan_file_path,
               i.invoice_id AS appointment_valid_invoice
        FROM appointment a
        LEFT JOIN questionnaire q ON a.appointment_id = q.appointment_id
        LEFT JOIN nutrition_plan n ON a.appointment_id = n.appointment_id
        LEFT JOIN invoice i ON a.invoice_id = i.invoice_id AND i.canceled=0
        WHERE a.appointment_id = :appointment_id 
        AND (
              a.client_id = :client_id 
              OR a.client_id IN (
                  SELECT dependent_client_id 
                  FROM client_relationships 
                  WHERE manager_client_id = :manager_client_id
            )
        )";

$appointment = $db->query($sql, [':appointment_id' => $_GET['id'], ':client_id' => $auth->getUser()['client_id'], ':manager_client_id' => $auth->getUser()['client_id']])->fetch();

if (!$appointment)
{
    header("HTTP/1.0 404 Not Found");
    exit();
}

$appt_date = (new DateTime($appointment['appointment_date']))->format('Y-m-d');
$today = date('Y-m-d');

// min must be the lower date from today or appointment date
$min_limit = ($appt_date < $today) ? $appt_date : $today;

$form_schema = [
    'mainFields' => [
        'appointment_id' => [
            'type'     => 'hidden',
            'label'    => '',
            'required' => true,
            'default'  => $appointment['appointment_id']
        ],
        'appointment_date' => [
            'type'     => 'date',
            'label'    => LABEL_APPOINTMENT_DATE,
            'required' => false,
            'default'  => (new DateTime($appointment['appointment_date']))->format('Y-m-d'),
            'attributes' => ['min' => $min_limit, 'disabled' => 'true']
        ],
        'appointment_time' => [
            'type'     => 'select',
            'label'    => LABEL_APPOINTMENT_TIME,
            'required' => true,
            'default'  => (new DateTime($appointment['appointment_date']))->format('Y-m-d H:i'),
            'options'  => getAppointmentSlots(
                (new DateTime($appointment['appointment_date']))->format('Y-m-d'),
                (new DateTime($appointment['appointment_date']))->format('Y-m-d H:i')
            ),
            'attributes' => ['disabled' => 'true']
        ],
        'service_id' => [
            'type'     => 'select',
            'label'    => LABEL_SERVICE,
            'required' => true,
            'default'  => $appointment['service_id'],
            'options'  => getServicesOptions(),
            'attributes' => ['disabled' => 'true']
        ],
        'client_id' => [
            'type'     => 'select',
            'label'    => LABEL_CLIENT,
            'required' => true,
            'default'  => $appointment['client_id'],
            'options'  => getClientsOptions(),
            'attributes' => ['disabled' => 'true']
        ],
        'dietitian_id' => [
            'type'     => 'select',
            'label'    => LABEL_DIETITIAN,
            'required' => true,
            'default'  => $appointment['dietitian_id'],
            'options'  => getDietitiansOptions(),
            'attributes' => ['disabled' => 'true']
        ],
        'appointment_status_id' => [
            'type'     => 'select',
            'label'    => LABEL_APPOINTMENT_STATUS,
            'required' => true,
            'default'  => $appointment['appointment_status_id'],
            'options'  => getAppointmentStatusOptions(),
            'attributes' => ['disabled' => 'true']
        ]
    ],
    'cancel_url' => $seoUrl->generate('portal/appointment.php'),
    'creation_date' => (new DateTime($appointment['creation_date']))->format('d/m/Y H:i:s'),
    'last_modified_date' => (new DateTime($appointment['last_modified_date']))->format('d/m/Y H:i:s')
];

$form_schema['mainFields']['questionnaire_title'] = [
    'type'     => 'text',
    'label'    => LABEL_QUESTIONNAIRE_TITLE,
    'required' => false,
    'default'  => $appointment['questionnaire_title'],
    'attributes' => ['disabled' => 'true']
];

$form_schema['mainFields']['questionnaire_file_path'] = [
    'type'     => 'view_file',
    'label'    => LABEL_QUESTIONNAIRE_FILE,
    'required' => false,
    'default'  => $file_manager->getFileUrl($appointment['questionnaire_file_path'])
];

$form_schema['mainFields']['plan_title'] = [
    'type'     => 'text',
    'label'    => LABEL_NUTRITION_PLAN_TITLE,
    'required' => false,
    'default'  => $appointment['plan_title'],
    'attributes' => ['disabled' => 'true']
];

$form_schema['mainFields']['plan_file_path'] = [
    'type'     => 'view_file',
    'label'    => LABEL_NUTRITION_PLAN_FILE,
    'required' => false,
    'default'  => $file_manager->getFileUrl($appointment['plan_file_path'])
];

if (!empty($appointment['appointment_valid_invoice']))
{
    $invoice_output = '<a href="' . $seoUrl->generate('portal/view_invoice.php', ['id' => $appointment['appointment_valid_invoice'], 'appointment_id' => $appointment['appointment_id']]) . '" class="view-invoice">' . TEXT_VIEW_INVOICE . '</a>';
}
else
{
    $invoice_output = '<div class="existing-file-info">-</div>';
}

$form_schema['mainFields']['appointment_invoice'] = [
    'type'     => 'custom_code',
    'label'    => LABEL_INVOICE,
    'required' => false,
    'default'  => $invoice_output
];
