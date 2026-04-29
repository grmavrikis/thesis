<?php
if (empty($auth->getUser()['admin_id']))
{
    header('Location: ' . $seoUrl->generate('administration/login.php'));
    exit();
}

if (!$_GET['id'] || !is_numeric($_GET['id']))
{
    header('Location: ' . $seoUrl->generate('administration/clients.php'));
    exit();
}

$breadcrumb = new Breadcrumb();
$breadcrumb->addItem(TEXT_CLIENTS, $seoUrl->generate('administration/clients.php'));
$breadcrumb->addItem(PAGE_TITLE, null);

$sql = "SELECT 
        c.client_id,
        c.first_name,
        c.last_name,
        c.email,
        c.phone,
        c.dob,
        c.gender,
        c.creation_date,
        c.last_modified_date,
        c.user_id,
        u.active,
        u.username,
        cr.manager_client_id /* If the user is a dependent client get the manager_client_id*/
        FROM client c
        LEFT JOIN user u ON c.user_id = u.user_id
        LEFT JOIN client_relationships cr ON c.client_id=cr.dependent_client_id 
        WHERE c.client_id = ?";

$client = $db->query($sql, [$_GET['id']])->fetch();

if (!$client)
{
    header("HTTP/1.0 404 Not Found");
    exit();
}

$form_schema = [
    'mainFields' => [
        'client_id' => [
            'type'     => 'hidden',
            'label'    => '',
            'required' => true,
            'default'  => $client['client_id']
        ],
        'user_id' => [
            'type'     => 'hidden',
            'label'    => '',
            'required' => false,
            'default'  => $client['user_id']
        ],
        'firstname' => [
            'type'     => 'text',
            'label'    => LABEL_FIRSTNAME,
            'required' => true,
            'default'  => $client['first_name']
        ],
        'lastname' => [
            'type'     => 'text',
            'label'    => LABEL_LASTNAME,
            'required' => true,
            'default'  => $client['last_name']
        ],
        'email' => [
            'type'     => 'email',
            'label'    => LABEL_EMAIL,
            'required' => true,
            'default'  => $client['email']
        ],
        'phone' => [
            'type'     => 'tel',
            'label'    => LABEL_PHONE,
            'required' => true,
            'default'  => $client['phone']
        ],
        'dob' => [
            'type'     => 'date',
            'label'    => LABEL_DOB,
            'required' => true,
            'default'  => $client['dob']
        ],
        'gender' => [
            'type'     => 'select',
            'label'    => LABEL_GENDER,
            'required' => true,
            'default'  => $client['gender'],
            'options'  => getGendersOptions()
        ]
    ],
    'cancel_url' => $seoUrl->generate('administration/clients.php'),
    'creation_date' => (new DateTime($client['creation_date']))->format('d/m/Y H:i:s'),
    'last_modified_date' => (new DateTime($client['last_modified_date']))->format('d/m/Y H:i:s')
];

$sql = "SELECT 1 FROM client_relationships
        WHERE manager_client_id = ? LIMIT 1";
$is_already_manager = $db->query($sql, [$_GET['id']])->fetch();
// Don't add the select manager client option if the user is already a manager
if (!$is_already_manager)
{
    $form_schema['mainFields']['manager_client_id'] = [
        'type'     => 'select',
        'label'    => LABEL_MANAGER_CLIENT,
        'required' => false,
        'default'  => $client['manager_client_id'],
        'options'  => getManagerClientsOptions($_GET['id'])
    ];
}
else
{
    $sql = "SELECT CONCAT_WS(' ', c.last_name, c.first_name, c.phone) AS client_info 
            FROM client_relationships cr
            JOIN client c ON c.client_id=cr.dependent_client_id
            WHERE cr.manager_client_id = ? ";
    $dependent = $db->query($sql, [$_GET['id']])->fetchAll();

    $info_list = array_column($dependent, 'client_info');
    $formatted_output = implode('<br>', $info_list);

    $form_schema['mainFields']['dependent_clients'] = [
        'type'     => 'custom_text',
        'label'    => LABEL_DEPENDENT_CLIENTS,
        'required' => false,
        'default'  => $formatted_output
    ];
}

$form_schema['mainFields']['active'] = [
    'type'     => 'select',
    'label'    => LABEL_ACTIVE,
    'required' => false,
    'default'  => $client['active'],
    'options'  => getBooleanOptions()
];

$form_schema['mainFields']['username'] = [
    'type'     => 'text',
    'label'    => LABEL_USERNAME,
    'required' => false,
    'default'  => $client['username']
];

$form_schema['mainFields']['password'] = [
    'type'     => 'password',
    'label'    => LABEL_NEW_PASSWORD,
    'required' => false,
    'default'  => ''
];

$form_schema['mainFields']['confirm_password'] = [
    'type'     => 'password',
    'label'    => LABEL_CONFIRM_NEW_PASSWORD,
    'required' => false,
    'default'  => ''
];
