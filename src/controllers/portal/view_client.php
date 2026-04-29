<?php
if (empty($auth->getUser()['client_id']))
{
    header('Location: ' . $seoUrl->generate('index.php'));
    exit();
}

if (!$_GET['id'] || !is_numeric($_GET['id']))
{
    header('Location: ' . $seoUrl->generate('portal/clients.php'));
    exit();
}

$breadcrumb = new Breadcrumb();
$breadcrumb->addItem(TEXT_CLIENTS, $seoUrl->generate('portal/clients.php'));
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
        WHERE c.client_id = :client_id
        AND (
              c.client_id = :client_id_2
              OR c.client_id IN (
                  SELECT dependent_client_id 
                  FROM client_relationships 
                  WHERE manager_client_id = :manager_client_id
            )
        )";

$client = $db->query($sql, [
    ':client_id' => $_GET['id'],
    ':client_id_2' => $auth->getUser()['client_id'],
    ':manager_client_id' => $auth->getUser()['client_id']
])->fetch();

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
            'required' => false,
            'default'  => $client['email']
        ],
        'phone' => [
            'type'     => 'tel',
            'label'    => LABEL_PHONE,
            'required' => false,
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
    'cancel_url' => $seoUrl->generate('portal/clients.php'),
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
        'type'     => 'hidden',
        'label'    => '',
        'required' => true,
        'default'  => $client['manager_client_id']
    ];
}
else
{
    $form_schema['mainFields']['email']['required'] = true;
    $form_schema['mainFields']['phone']['required'] = true;
}
