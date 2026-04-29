<?php
if (empty($auth->getUser()['client_id']))
{
    header('Location: ' . $seoUrl->generate('index.php'));
    exit();
}

if (!$_GET['id'] || !is_numeric($_GET['id']))
{
    header('Location: ' . $seoUrl->generate('portal/questionnaire.php'));
    exit();
}
$breadcrumb = new Breadcrumb();
$breadcrumb->addItem(TEXT_QUESTIONNAIRE, $seoUrl->generate('portal/questionnaire.php'));
$breadcrumb->addItem(PAGE_TITLE, null);

$file_manager = new FileManager();

$sql = "SELECT *
        FROM questionnaire q
        JOIN appointment a ON a.appointment_id = q.appointment_id
        WHERE q.questionnaire_id = :questionnaire_id
        AND (
              a.client_id = :client_id 
              OR a.client_id IN (
                  SELECT dependent_client_id 
                  FROM client_relationships 
                  WHERE manager_client_id = :manager_client_id
            )
        )";

$questionnaire = $db->query($sql, [
    ':questionnaire_id' => $_GET['id'],
    ':client_id' => $auth->getUser()['client_id'],
    ':manager_client_id' => $auth->getUser()['client_id']
])->fetch();

if (!$questionnaire)
{
    header("HTTP/1.0 404 Not Found");
    exit();
}

$languagesAll = $languages->getLanguages();

$form_schema = [
    'mainFields' => [
        'questionnaire_id' => [
            'type'     => 'hidden',
            'label'    => '',
            'required' => true,
            'default'  => $questionnaire['questionnaire_id']
        ],
        'appointment_date' => [
            'type'     => 'date',
            'label'    => LABEL_APPOINTMENT_DATE,
            'required' => false,
            'default'  => (new DateTime($questionnaire['appointment_date']))->format('Y-m-d'),
            'attributes' => ['disabled' => 'true']
        ],
        'appointment_time' => [
            'type'     => 'select',
            'label'    => LABEL_APPOINTMENT_TIME,
            'required' => true,
            'default'  => (new DateTime($questionnaire['appointment_date']))->format('Y-m-d H:i'),
            'options'  => getAppointmentSlots(
                (new DateTime($questionnaire['appointment_date']))->format('Y-m-d'),
                (new DateTime($questionnaire['appointment_date']))->format('Y-m-d H:i')
            ),
            'attributes' => ['disabled' => 'true']
        ],
        'service_id' => [
            'type'     => 'select',
            'label'    => LABEL_SERVICE,
            'required' => true,
            'default'  => $questionnaire['service_id'],
            'options'  => getServicesOptions(),
            'attributes' => ['disabled' => 'true']
        ],
        'client_id' => [
            'type'     => 'select',
            'label'    => LABEL_CLIENT,
            'required' => true,
            'default'  => $questionnaire['client_id'],
            'options'  => getClientsOptions(),
            'attributes' => ['disabled' => 'true']
        ],
        'dietitian_id' => [
            'type'     => 'select',
            'label'    => LABEL_DIETITIAN,
            'required' => true,
            'default'  => $questionnaire['dietitian_id'],
            'options'  => getDietitiansOptions(),
            'attributes' => ['disabled' => 'true']
        ],
        'title' => [
            'type'     => 'text',
            'label'    => LABEL_QUESTIONNAIRE_TITLE,
            'required' => true,
            'default'  => $questionnaire['title'],
            'attributes' => ['disabled' => 'true']
        ],
        'file_path' => [
            'type'     => 'view_file',
            'label'    => LABEL_QUESTIONNAIRE_FILE,
            'required' => false,
            'default'  => $file_manager->getFileUrl($questionnaire['file_path'])
        ]
    ],
    'cancel_url' => $seoUrl->generate('portal/questionnaire.php'),
    'creation_date' => (new DateTime($questionnaire['creation_date']))->format('d/m/Y H:i:s'),
    'last_modified_date' => (new DateTime($questionnaire['last_modified_date']))->format('d/m/Y H:i:s')
];
