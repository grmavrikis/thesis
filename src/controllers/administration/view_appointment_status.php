<?php
if (empty($auth->getUser()['admin_id']))
{
    header('Location: ' . $seoUrl->generate('administration/login.php'));
    exit();
}

if (!$_GET['id'] || !is_numeric($_GET['id']))
{
    header('Location: ' . $seoUrl->generate('administration/appointment_status.php'));
    exit();
}

$breadcrumb = new Breadcrumb();
$breadcrumb->addItem(TEXT_APPOINTMENT_STATUS, $seoUrl->generate('administration/appointment_status.php'));
$breadcrumb->addItem(PAGE_TITLE, null);

$sql = "SELECT * FROM appointment_status s
        WHERE s.appointment_status_id = ?";

$appointment_status = $db->query($sql, [$_GET['id']])->fetch();

if (!$appointment_status)
{
    header("HTTP/1.0 404 Not Found");
    exit();
}

$sql_description = "SELECT * FROM appointment_status_description d
                    WHERE d.appointment_status_id = ?";

$appointment_status_description = $db->query($sql_description, [$_GET['id']])->fetchAll();

$form_schema = [
    'mainFields' => [
        'appointment_status_id' => [
            'type'     => 'hidden',
            'label'    => '',
            'required' => true,
            'default'  => $appointment_status['appointment_status_id']
        ],
        'is_default' => [
            'type'     => 'select',
            'label'    => TEXT_ITEMS_DEFAULT,
            'required' => false,
            'default'  => $appointment_status['is_default'],
            'options'  => getBooleanOptions()
        ],
        'color' => [
            'type'     => 'color',
            'label'    => TEXT_ITEMS_COLOR,
            'required' => true,
            'default'  => $appointment_status['color']
        ],
        'sort_order' => [
            'type'     => 'number',
            'label'    => TEXT_ITEMS_SORT_ORDER,
            'required' => false,
            'default'  => $appointment_status['sort_order']
        ]
    ],
    'cancel_url' => $seoUrl->generate('administration/appointment_status.php'),
    'creation_date' => (new DateTime($appointment_status['creation_date']))->format('d/m/Y H:i:s'),
    'last_modified_date' => (new DateTime($appointment_status['last_modified_date']))->format('d/m/Y H:i:s')
];

$languagesAll = $languages->getLanguages();
foreach ($languagesAll as $lang)
{
    $lang_id = $lang['language_id'];
    $desc = array_filter($appointment_status_description, function ($d) use ($lang_id)
    {
        return $d['language_id'] == $lang_id;
    });

    // Reindex array after filtering
    $desc = array_values($desc);

    $form_schema['localFields'][$lang_id] = [
        'title' => [
            'type'     => 'text',
            'label'    => TEXT_ITEMS_TITLE,
            'required' => true,
            'default'  => $desc[0]['title'] ?? ''
        ]
    ];
}
