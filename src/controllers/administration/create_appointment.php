<?php
if (empty($auth->getUser()['admin_id']))
{
    header('Location: ' . $seoUrl->generate('administration/login.php'));
    exit();
}

$breadcrumb = new Breadcrumb();
$breadcrumb->addItem(TEXT_APPOINTMENT, $seoUrl->generate('administration/appointment.php'));
$breadcrumb->addItem(PAGE_TITLE, null);

$form_schema = [
    'mainFields' => [
        'appointment_date' => [
            'type'     => 'date',
            'label'    => LABEL_APPOINTMENT_DATE,
            'required' => true,
            'default'  => '',
            'attributes' => [
                'min' => date('Y-m-d')
            ]
        ],
        'appointment_time' => [
            'type'     => 'select',
            'label'    => LABEL_APPOINTMENT_TIME,
            'required' => true,
            'default'  => 0,
            'options'  => getAppointmentSlots()
        ],
        'service_id' => [
            'type'     => 'select',
            'label'    => LABEL_SERVICE,
            'required' => true,
            'default'  => 0,
            'options'  => getServicesOptions()
        ],
        'client_id' => [
            'type'     => 'select',
            'label'    => LABEL_CLIENT,
            'required' => true,
            'default'  => 0,
            'options'  => getClientsOptions()
        ],
        'dietitian_id' => [
            'type'     => 'select',
            'label'    => LABEL_DIETITIAN,
            'required' => true,
            'default'  => $auth->getUser()['admin_id'],
            'options'  => getDietitiansOptions()
        ]
    ],
    'cancel_url' => $seoUrl->generate('administration/appointment.php')
];
