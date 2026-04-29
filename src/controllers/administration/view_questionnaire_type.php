<?php
if (empty($auth->getUser()['admin_id']))
{
    header('Location: ' . $seoUrl->generate('administration/login.php'));
    exit();
}

if (!$_GET['id'] || !is_numeric($_GET['id']))
{
    header('Location: ' . $seoUrl->generate('administration/questionnaire_type.php'));
    exit();
}

$breadcrumb = new Breadcrumb();
$breadcrumb->addItem(TEXT_QUESTIONNAIRE_TYPES, $seoUrl->generate('administration/questionnaire_type.php'));
$breadcrumb->addItem(PAGE_TITLE, null);

$sql = "SELECT * FROM questionnaire_type q
        WHERE q.questionnaire_type_id = ?";

$questionnaire_type = $db->query($sql, [$_GET['id']])->fetch();

if (!$questionnaire_type)
{
    header("HTTP/1.0 404 Not Found");
    exit();
}

$sql_description = "SELECT * FROM questionnaire_type_description qd
                    WHERE qd.questionnaire_type_id = ?";

$questionnaire_type_description = $db->query($sql_description, [$_GET['id']])->fetchAll();

$file_manager = new FileManager();

$form_schema = [
    'mainFields' => [
        'questionnaire_type_id' => [
            'type'     => 'hidden',
            'label'    => '',
            'required' => true,
            'default'  => $questionnaire_type['questionnaire_type_id']
        ],
        'sort_order' => [
            'type'     => 'number',
            'label'    => TEXT_ITEMS_SORT_ORDER,
            'required' => true,
            'default'  => $questionnaire_type['sort_order']
        ]
    ],
    'cancel_url' => $seoUrl->generate('administration/questionnaire_type.php'),
    'creation_date' => (new DateTime($questionnaire_type['creation_date']))->format('d/m/Y H:i:s'),
    'last_modified_date' => (new DateTime($questionnaire_type['last_modified_date']))->format('d/m/Y H:i:s')
];

$languagesAll = $languages->getLanguages();
foreach ($languagesAll as $lang)
{
    $lang_id = $lang['language_id'];
    $desc = array_filter($questionnaire_type_description, function ($d) use ($lang_id)
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
        ],
        'template_file' => [
            'type'     => 'file',
            'label'    => TEXT_TEMPLATE_FILE,
            'required' => false,
            'default'  => $file_manager->getFileUrl($desc[0]['template_file']),
            'filename' => $desc[0]['template_file']
        ]
    ];
}
