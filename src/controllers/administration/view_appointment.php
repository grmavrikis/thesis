<?php
if (empty($auth->getUser()['admin_id']))
{
    header('Location: ' . $seoUrl->generate('administration/login.php'));
    exit();
}

if (!$_GET['id'] || !is_numeric($_GET['id']))
{
    header('Location: ' . $seoUrl->generate('administration/appointment.php'));
    exit();
}
$breadcrumb = new Breadcrumb();
$breadcrumb->addItem(TEXT_APPOINTMENT, $seoUrl->generate('administration/appointment.php'));
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
        WHERE a.appointment_id = ?";

$appointment = $db->query($sql, [$_GET['id']])->fetch();

if (!$appointment)
{
    header("HTTP/1.0 404 Not Found");
    exit();
}

$extra_attributes = [];
if ($auth->getUser()['admin_id'] != $appointment['dietitian_id'])
{
    $extra_attributes = ['disabled' => 'true'];
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
            'attributes' => ['min' => $min_limit]
        ],
        'appointment_time' => [
            'type'     => 'select',
            'label'    => LABEL_APPOINTMENT_TIME,
            'required' => true,
            'default'  => (new DateTime($appointment['appointment_date']))->format('Y-m-d H:i'),
            'options'  => getAppointmentSlots(
                (new DateTime($appointment['appointment_date']))->format('Y-m-d'),
                (new DateTime($appointment['appointment_date']))->format('Y-m-d H:i')
            )
        ],
        'service_id' => [
            'type'     => 'select',
            'label'    => LABEL_SERVICE,
            'required' => true,
            'default'  => $appointment['service_id'],
            'options'  => getServicesOptions()
        ],
        'client_id' => [
            'type'     => 'select',
            'label'    => LABEL_CLIENT,
            'required' => true,
            'default'  => $appointment['client_id'],
            'options'  => getClientsOptions()
        ],
        'dietitian_id' => [
            'type'     => 'select',
            'label'    => LABEL_DIETITIAN,
            'required' => true,
            'default'  => $appointment['dietitian_id'],
            'options'  => getDietitiansOptions()
        ],
        'appointment_status_id' => [
            'type'     => 'select',
            'label'    => LABEL_APPOINTMENT_STATUS,
            'required' => true,
            'default'  => $appointment['appointment_status_id'],
            'options'  => getAppointmentStatusOptions()
        ]
    ],
    'cancel_url' => $seoUrl->generate('administration/appointment.php'),
    'creation_date' => (new DateTime($appointment['creation_date']))->format('d/m/Y H:i:s'),
    'last_modified_date' => (new DateTime($appointment['last_modified_date']))->format('d/m/Y H:i:s')
];

if ($auth->getUser()['admin_id'] == $appointment['dietitian_id'])
{
    $form_schema['mainFields']['questionnaire_id'] = [
        'type'     => 'hidden',
        'label'    => '',
        'required' => false,
        'default'  => $appointment['questionnaire_id']
    ];

    $form_schema['mainFields']['questionnaire_title'] = [
        'type'     => 'text',
        'label'    => LABEL_QUESTIONNAIRE_TITLE,
        'required' => false,
        'default'  => $appointment['questionnaire_title'],
        'attributes' => $extra_attributes
    ];

    $form_schema['mainFields']['questionnaire_file_path'] = [
        'type'     => 'file',
        'label'    => LABEL_QUESTIONNAIRE_FILE,
        'required' => false,
        'default'  => $file_manager->getFileUrl($appointment['questionnaire_file_path']),
        'filename' => $appointment['questionnaire_file_path'],
        'id'       => $appointment['questionnaire_id'],
        'table'    => 'questionnaire'
    ];

    $form_schema['mainFields']['plan_id'] = [
        'type'     => 'hidden',
        'label'    => '',
        'required' => false,
        'default'  => $appointment['plan_id'],
        'attributes'
    ];

    $form_schema['mainFields']['plan_title'] = [
        'type'     => 'text',
        'label'    => LABEL_NUTRITION_PLAN_TITLE,
        'required' => false,
        'default'  => $appointment['plan_title']
    ];

    $form_schema['mainFields']['plan_file_path'] = [
        'type'     => 'file',
        'label'    => LABEL_NUTRITION_PLAN_FILE,
        'required' => false,
        'default'  => $file_manager->getFileUrl($appointment['plan_file_path']),
        'filename' => $appointment['plan_file_path'],
        'id'       => $appointment['plan_id'],
        'table'    => 'nutrition_plan'
    ];
}
else
{
    $form_schema['mainFields']['questionnaire_title'] = [
        'type'     => 'text',
        'label'    => LABEL_QUESTIONNAIRE_TITLE,
        'required' => false,
        'default'  => $appointment['questionnaire_title'],
        'attributes' => $extra_attributes
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
        'attributes' => $extra_attributes
    ];

    $form_schema['mainFields']['plan_file_path'] = [
        'type'     => 'view_file',
        'label'    => LABEL_NUTRITION_PLAN_FILE,
        'required' => false,
        'default'  => $file_manager->getFileUrl($appointment['plan_file_path'])
    ];
}

if (!empty($appointment['appointment_valid_invoice']))
{
    $invoice_output = viewAppointmentInvoiceHtml($appointment['appointment_valid_invoice'], $appointment['appointment_id']);
}
else
{
    $invoice_output = createAppointmentInvoiceHtml($_GET['id']);
}

$form_schema['mainFields']['appointment_invoice'] = [
    'type'     => 'custom_code',
    'label'    => LABEL_INVOICE,
    'required' => false,
    'default'  => $invoice_output
];
