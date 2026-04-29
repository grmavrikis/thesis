<?php
if (empty($auth->getUser()['client_id']))
{
    header('Location: ' . $seoUrl->generate('index.php'));
    exit();
}

$breadcrumb = new Breadcrumb();
$breadcrumb->addItem(TEXT_APPOINTMENT, $seoUrl->generate('portal/appointment.php'));
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
            'default'  => $auth->getUser()['client_id'],
            'options'  => getClientsOptions()
        ],
        'dietitian_id' => [
            'type'     => 'select',
            'label'    => LABEL_DIETITIAN,
            'required' => true,
            'default'  => 0,
            'options'  => getDietitiansOptions()
        ]
    ],
    'cancel_url' => $seoUrl->generate('portal/appointment.php')
];
