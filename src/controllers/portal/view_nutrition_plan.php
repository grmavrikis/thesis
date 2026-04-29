<?php
if (empty($auth->getUser()['client_id']))
{
    header('Location: ' . $seoUrl->generate('index.php'));
    exit();
}

if (!$_GET['id'] || !is_numeric($_GET['id']))
{
    header('Location: ' . $seoUrl->generate('portal/nutrition_plan.php'));
    exit();
}
$breadcrumb = new Breadcrumb();
$breadcrumb->addItem(TEXT_NUTRITION_PLAN, $seoUrl->generate('portal/nutrition_plan.php'));
$breadcrumb->addItem(PAGE_TITLE, null);

$file_manager = new FileManager();

$sql = "SELECT *
        FROM nutrition_plan p
        JOIN appointment a ON a.appointment_id = p.appointment_id
        WHERE p.plan_id = :plan_id 
        AND (
              a.client_id = :client_id 
              OR a.client_id IN (
                  SELECT dependent_client_id 
                  FROM client_relationships 
                  WHERE manager_client_id = :manager_client_id
            )
        )";

$nutrition_plan = $db->query($sql, [':plan_id' => $_GET['id'], ':client_id' => $auth->getUser()['client_id'], ':manager_client_id' => $auth->getUser()['client_id']])->fetch();

if (!$nutrition_plan)
{
    header("HTTP/1.0 404 Not Found");
    exit();
}

$languagesAll = $languages->getLanguages();

$form_schema = [
    'mainFields' => [
        'plan_id' => [
            'type'     => 'hidden',
            'label'    => '',
            'required' => true,
            'default'  => $nutrition_plan['plan_id']
        ],
        'appointment_date' => [
            'type'     => 'date',
            'label'    => LABEL_APPOINTMENT_DATE,
            'required' => false,
            'default'  => (new DateTime($nutrition_plan['appointment_date']))->format('Y-m-d'),
            'attributes' => ['disabled' => 'true']
        ],
        'appointment_time' => [
            'type'     => 'select',
            'label'    => LABEL_APPOINTMENT_TIME,
            'required' => true,
            'default'  => (new DateTime($nutrition_plan['appointment_date']))->format('Y-m-d H:i'),
            'options'  => getAppointmentSlots(
                (new DateTime($nutrition_plan['appointment_date']))->format('Y-m-d'),
                (new DateTime($nutrition_plan['appointment_date']))->format('Y-m-d H:i')
            ),
            'attributes' => ['disabled' => 'true']
        ],
        'service_id' => [
            'type'     => 'select',
            'label'    => LABEL_SERVICE,
            'required' => true,
            'default'  => $nutrition_plan['service_id'],
            'options'  => getServicesOptions(),
            'attributes' => ['disabled' => 'true']
        ],
        'client_id' => [
            'type'     => 'select',
            'label'    => LABEL_CLIENT,
            'required' => true,
            'default'  => $nutrition_plan['client_id'],
            'options'  => getClientsOptions(),
            'attributes' => ['disabled' => 'true']
        ],
        'dietitian_id' => [
            'type'     => 'select',
            'label'    => LABEL_DIETITIAN,
            'required' => true,
            'default'  => $nutrition_plan['dietitian_id'],
            'options'  => getDietitiansOptions(),
            'attributes' => ['disabled' => 'true']
        ],
        'title' => [
            'type'     => 'text',
            'label'    => LABEL_NUTRITION_PLAN_TITLE,
            'required' => true,
            'default'  => $nutrition_plan['title'],
            'attributes' => ['disabled' => 'true']
        ],
        'file_path' => [
            'type'     => 'view_file',
            'label'    => LABEL_NUTRITION_PLAN_FILE,
            'required' => false,
            'default'  => $file_manager->getFileUrl($nutrition_plan['file_path'])
        ]
    ],
    'cancel_url' => $seoUrl->generate('portal/nutrition_plan.php'),
    'creation_date' => (new DateTime($nutrition_plan['creation_date']))->format('d/m/Y H:i:s'),
    'last_modified_date' => (new DateTime($nutrition_plan['last_modified_date']))->format('d/m/Y H:i:s')
];
