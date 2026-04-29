<?php
header('Content-Type: application/json; charset=utf-8');

$schema = [
    [
        'table_name' => 'language',
        'fields' => [
            [
                'name' => 'language_id',
                'type' => 'INT',
                'constraints' => ['UNSIGNED', 'NOT NULL', 'AUTO_INCREMENT'],
                'is_primary' => true
            ],
            [
                'name' => 'name',
                'type' => 'VARCHAR(32)',
                'constraints' => ['NOT NULL']
            ],
            [
                'name' => 'code',
                'type' => 'CHAR(2)',
                'constraints' => ['NOT NULL']
            ],
            [
                'name' => 'flag',
                'type' => 'VARCHAR(64)',
                'constraints' => ['NOT NULL']
            ],
            [
                'name' => 'directory',
                'type' => 'VARCHAR(32)',
                'constraints' => ['NOT NULL']
            ]
        ],
        'unique_keys' => [
            ['code'],
            ['directory']
        ]
    ],
    [
        'table_name' => 'user',
        'fields' => [
            [
                'name' => 'user_id',
                'type' => 'INT',
                'constraints' => ['UNSIGNED', 'NOT NULL', 'AUTO_INCREMENT'],
                'is_primary' => true
            ],
            [
                'name' => 'username',
                'type' => 'VARCHAR(100)',
                'constraints' => ['NOT NULL']
            ],
            [
                'name' => 'password_hash',
                'type' => 'CHAR(60)',
                'constraints' => ['NOT NULL']
            ],
            [
                'name' => 'active',
                'type' => 'TINYINT(1)',
                'constraints' => ['NOT NULL', 'DEFAULT 0']
            ],
            [
                'name' => 'creation_date',
                'type' => 'DATETIME',
                'constraints' => ['NOT NULL', 'DEFAULT CURRENT_TIMESTAMP'],
                'is_index' => true
            ],
            [
                'name' => 'last_modified_date',
                'type' => 'DATETIME',
                'constraints' => ['DEFAULT CURRENT_TIMESTAMP', 'ON UPDATE CURRENT_TIMESTAMP']
            ]
        ],
        'unique_keys' => [
            ['username']
        ]
    ],
    [
        'table_name' => 'dietitian',
        'fields' => [
            [
                'name' => 'dietitian_id',
                'type' => 'INT',
                'constraints' => ['UNSIGNED', 'NOT NULL', 'AUTO_INCREMENT'],
                'is_primary' => true
            ],
            [
                'name' => 'user_id',
                'type' => 'INT',
                'constraints' => ['UNSIGNED', 'NOT NULL'],
                'is_fk' => true,
                'references' => 'user(user_id)'
            ],
            [
                'name' => 'first_name',
                'type' => 'VARCHAR(100)',
                'constraints' => ['NOT NULL']
            ],
            [
                'name' => 'last_name',
                'type' => 'VARCHAR(100)',
                'constraints' => ['NOT NULL']
            ],
            [
                'name' => 'email',
                'type' => 'VARCHAR(256)',
                'constraints' => ['NOT NULL']
            ],
            [
                'name' => 'phone',
                'type' => 'VARCHAR(20)',
                'constraints' => ['NOT NULL']
            ],
            [
                'name' => 'creation_date',
                'type' => 'DATETIME',
                'constraints' => ['NOT NULL', 'DEFAULT CURRENT_TIMESTAMP'],
                'is_index' => true
            ],
            [
                'name' => 'last_modified_date',
                'type' => 'DATETIME',
                'constraints' => ['DEFAULT CURRENT_TIMESTAMP', 'ON UPDATE CURRENT_TIMESTAMP']
            ]
        ],
        'unique_keys' => [
            ['email']
        ],
        'fulltext_keys' => ['first_name', 'last_name', 'email', 'phone']
    ],
    [
        'table_name' => 'client',
        'fields' => [
            [
                'name' => 'client_id',
                'type' => 'INT',
                'constraints' => ['UNSIGNED', 'NOT NULL', 'AUTO_INCREMENT'],
                'is_primary' => true
            ],
            [
                'name' => 'user_id',
                'type' => 'INT',
                'constraints' => ['UNSIGNED'],
                'is_fk' => true,
                'references' => 'user(user_id)'
            ],
            [
                'name' => 'first_name',
                'type' => 'VARCHAR(100)',
                'constraints' => ['NOT NULL']
            ],
            [
                'name' => 'last_name',
                'type' => 'VARCHAR(100)',
                'constraints' => ['NOT NULL']
            ],
            [
                'name' => 'email',
                'type' => 'VARCHAR(256)',
                'constraints' => ['NULL']
            ],
            [
                'name' => 'phone',
                'type' => 'VARCHAR(20)',
                'constraints' => ['NULL']
            ],
            [
                'name' => 'dob',
                'type' => 'DATE',
                'constraints' => ['NOT NULL']
            ],
            [
                'name' => 'gender',
                'type' => "ENUM('M', 'F')",
                'constraints' => ['NOT NULL']
            ],
            [
                'name' => 'creation_date',
                'type' => 'DATETIME',
                'constraints' => ['NOT NULL', 'DEFAULT CURRENT_TIMESTAMP'],
                'is_index' => true
            ],
            [
                'name' => 'last_modified_date',
                'type' => 'DATETIME',
                'constraints' => ['DEFAULT CURRENT_TIMESTAMP', 'ON UPDATE CURRENT_TIMESTAMP']
            ]
        ],
        'unique_keys' => [
            ['email']
        ],
        'fulltext_keys' => ['first_name', 'last_name', 'email', 'phone']
    ],
    [
        'table_name' => 'client_relationships',
        'fields' => [
            [
                'name' => 'relationship_id',
                'type' => 'INT',
                'constraints' => ['UNSIGNED', 'NOT NULL', 'AUTO_INCREMENT'],
                'is_primary' => true
            ],
            [
                'name' => 'manager_client_id',
                'type' => 'INT',
                'constraints' => ['UNSIGNED', 'NOT NULL'],
                'is_fk' => true,
                'references' => 'client(client_id)'
            ],
            [
                'name' => 'dependent_client_id',
                'type' => 'INT',
                'constraints' => ['UNSIGNED', 'NOT NULL'],
                'is_fk' => true,
                'references' => 'client(client_id)'
            ],
            [
                'name' => 'creation_date',
                'type' => 'DATETIME',
                'constraints' => ['NOT NULL', 'DEFAULT CURRENT_TIMESTAMP'],
                'is_index' => false
            ]
        ],
        'unique_keys' => [
            ['manager_client_id', 'dependent_client_id']
        ]
    ],
    [
        'table_name' => 'tax',
        'fields' => [
            [
                'name' => 'tax_id',
                'type' => 'INT',
                'constraints' => ['UNSIGNED', 'NOT NULL', 'AUTO_INCREMENT'],
                'is_primary' => true
            ],
            [
                'name' => 'factor',
                'type' => 'DECIMAL(4, 4)', // for example, 0.2400 for 24% tax
                'constraints' => ['NOT NULL', 'CHECK (factor >= 0 AND factor <= 1)'] // value check (0 to 1)
            ],
            [
                'name' => 'creation_date',
                'type' => 'DATETIME',
                'constraints' => ['NOT NULL', 'DEFAULT CURRENT_TIMESTAMP'],
                'is_index' => true
            ],
            [
                'name' => 'last_modified_date',
                'type' => 'DATETIME',
                'constraints' => ['DEFAULT CURRENT_TIMESTAMP', 'ON UPDATE CURRENT_TIMESTAMP']
            ]
        ]
    ],
    [
        'table_name' => 'invoice',
        'fields' => [
            [
                'name' => 'invoice_id',
                'type' => 'INT',
                'constraints' => ['UNSIGNED', 'NOT NULL', 'AUTO_INCREMENT'],
                'is_primary' => true
            ],
            [
                'name' => 'issuer_title',
                'type' => 'VARCHAR(255)',
                'constraints' => ['NOT NULL'],
                'is_index' => true
            ],
            [
                'name' => 'issuer_name',
                'type' => 'VARCHAR(255)',
                'constraints' => ['NOT NULL'],
                'is_index' => true
            ],
            [
                'name' => 'issuer_vat',
                'type' => 'VARCHAR(20)',
                'constraints' => ['NOT NULL'],
                'is_index' => true
            ],
            [
                'name' => 'issuer_tax_office',
                'type' => 'VARCHAR(100)',
                'constraints' => ['NOT NULL'],
                'is_index' => true
            ],
            [
                'name' => 'issuer_address_street',
                'type' => 'VARCHAR(150)',
                'constraints' => ['NOT NULL'],
                'is_index' => true
            ],
            [
                'name' => 'issuer_address_number',
                'type' => 'VARCHAR(20)',
                'constraints' => ['NOT NULL'],
                'is_index' => true
            ],
            [
                'name' => 'issuer_city',
                'type' => 'VARCHAR(100)',
                'constraints' => ['NOT NULL'],
                'is_index' => true
            ],
            [
                'name' => 'issuer_postal_code',
                'type' => 'VARCHAR(20)',
                'constraints' => ['NOT NULL'],
                'is_index' => true
            ],
            [
                'name' => 'issuer_phone',
                'type' => 'VARCHAR(20)',
                'constraints' => ['NOT NULL'],
                'is_index' => true
            ],
            [
                'name' => 'issuer_email',
                'type' => 'VARCHAR(256)',
                'constraints' => ['NOT NULL'],
                'is_index' => true
            ],
            [
                'name' => 'serial_number',
                'type' => 'INT',
                'constraints' => ['UNSIGNED', 'NOT NULL'],
                'is_index' => true
            ],
            [
                'name' => 'issue_date',
                'type' => 'DATETIME',
                'constraints' => ['NOT NULL', 'DEFAULT CURRENT_TIMESTAMP'],
                'is_index' => true
            ],
            [
                'name' => 'canceled', // 0: not canceled, 1: canceled
                'type' => 'TINYINT(1)',
                'constraints' => ['NOT NULL', 'DEFAULT 0']
            ],
            [
                'name' => 'client_id',
                'type' => 'INT',
                'constraints' => ['UNSIGNED', 'NOT NULL'],
                'is_fk' => true,
                'references' => 'client(client_id)'
            ],
            [
                'name' => 'dietitian_id',
                'type' => 'INT',
                'constraints' => ['UNSIGNED', 'NOT NULL'],
                'is_fk' => true,
                'references' => 'dietitian(dietitian_id)'
            ],
            [
                'name' => 'creation_date',
                'type' => 'DATETIME',
                'constraints' => ['NOT NULL', 'DEFAULT CURRENT_TIMESTAMP'],
                'is_index' => true
            ],
            [
                'name' => 'last_modified_date',
                'type' => 'DATETIME',
                'constraints' => ['DEFAULT CURRENT_TIMESTAMP', 'ON UPDATE CURRENT_TIMESTAMP']
            ]
        ],
        'unique_keys' => [
            ['serial_number']
        ]
    ],
    [
        'table_name' => 'invoice_charge',
        'fields' => [
            [
                'name' => 'invoice_charge_id',
                'type' => 'INT',
                'constraints' => ['UNSIGNED', 'NOT NULL', 'AUTO_INCREMENT'],
                'is_primary' => true
            ],
            [
                'name' => 'invoice_id',
                'type' => 'INT',
                'constraints' => ['UNSIGNED', 'NOT NULL'],
                'is_fk' => true,
                'references' => 'invoice(invoice_id)'
            ],
            [
                'name' => 'description',
                'type' => 'VARCHAR(100)',
                'constraints' => ['NOT NULL']
            ],
            [
                'name' => 'clean_amount',
                'type' => 'DECIMAL(10, 2)',
                'constraints' => ['NOT NULL']
            ],
            [
                'name' => 'tax_amount',
                'type' => 'DECIMAL(10, 2)',
                'constraints' => ['NOT NULL']
            ],
            [
                'name' => 'tax_id',
                'type' => 'INT',
                'constraints' => ['UNSIGNED', 'NOT NULL'],
                'is_fk' => true,
                'references' => 'tax(tax_id)'
            ]
        ]
    ],

    [
        'table_name' => 'service',
        'fields' => [
            [
                'name' => 'service_id',
                'type' => 'INT',
                'constraints' => ['UNSIGNED', 'NOT NULL', 'AUTO_INCREMENT'],
                'is_primary' => true
            ],
            [
                'name' => 'sort_order',
                'type' => 'INT',
                'constraints' => ['NOT NULL', 'DEFAULT 0'],
                'is_index' => true
            ],
            [
                'name' => 'clean_cost',
                'type' => 'DECIMAL(10, 2)',
                'constraints' => ['NOT NULL']
            ],
            [
                'name' => 'tax_id',
                'type' => 'INT',
                'constraints' => ['UNSIGNED', 'NOT NULL'],
                'is_fk' => true,
                'references' => 'tax(tax_id)'
            ],
            [
                'name' => 'creation_date',
                'type' => 'DATETIME',
                'constraints' => ['NOT NULL', 'DEFAULT CURRENT_TIMESTAMP'],
                'is_index' => true
            ],
            [
                'name' => 'last_modified_date',
                'type' => 'DATETIME',
                'constraints' => ['DEFAULT CURRENT_TIMESTAMP', 'ON UPDATE CURRENT_TIMESTAMP']
            ]
        ]
    ],
    [
        'table_name' => 'service_description',
        'fields' => [
            [
                'name' => 'service_id',
                'type' => 'INT',
                'constraints' => ['UNSIGNED', 'NOT NULL'],
                'is_fk' => true,
                'references' => 'service(service_id)'
            ],
            [
                'name' => 'language_id',
                'type' => 'INT',
                'constraints' => ['UNSIGNED', 'NOT NULL'],
                'is_fk' => true,
                'references' => 'language(language_id)'
            ],
            [
                'name' => 'title',
                'type' => 'VARCHAR(100)',
                'constraints' => ['NOT NULL']
            ]
        ],
        'primary_keys' => ['service_id', 'language_id']
    ],
    [
        'table_name' => 'appointment_status',
        'fields' => [
            [
                'name' => 'appointment_status_id',
                'type' => 'INT',
                'constraints' => ['UNSIGNED', 'NOT NULL', 'AUTO_INCREMENT'],
                'is_primary' => true
            ],
            [
                'name' => 'is_default',
                'type' => 'TINYINT(1)',
                'constraints' => ['NOT NULL', 'DEFAULT 0']
            ],
            [
                'name' => 'sort_order',
                'type' => 'INT',
                'constraints' => ['NOT NULL', 'DEFAULT 0'],
                'is_index' => true
            ],
            [
                'name' => 'color',
                'type' => 'CHAR(7)', // format #RRGGBB
                'constraints' => ['NOT NULL']
            ],
            [
                'name' => 'creation_date',
                'type' => 'DATETIME',
                'constraints' => ['NOT NULL', 'DEFAULT CURRENT_TIMESTAMP'],
                'is_index' => true
            ],
            [
                'name' => 'last_modified_date',
                'type' => 'DATETIME',
                'constraints' => ['DEFAULT CURRENT_TIMESTAMP', 'ON UPDATE CURRENT_TIMESTAMP']
            ]
        ]
    ],
    [
        'table_name' => 'appointment_status_description',
        'fields' => [
            [
                'name' => 'appointment_status_id',
                'type' => 'INT',
                'constraints' => ['UNSIGNED', 'NOT NULL'],
                'is_fk' => true,
                'references' => 'appointment_status(appointment_status_id)',
                'is_index' => true
            ],
            [
                'name' => 'language_id',
                'type' => 'INT',
                'constraints' => ['UNSIGNED', 'NOT NULL'],
                'is_fk' => true,
                'references' => 'language(language_id)'
            ],
            [
                'name' => 'title',
                'type' => 'VARCHAR(100)',
                'constraints' => ['NOT NULL']
            ]
        ],
        'primary_keys' => ['appointment_status_id', 'language_id']
    ],
    [
        'table_name' => 'appointment',
        'fields' => [
            [
                'name' => 'appointment_id',
                'type' => 'INT',
                'constraints' => ['UNSIGNED', 'NOT NULL', 'AUTO_INCREMENT'],
                'is_primary' => true
            ],
            [
                'name' => 'appointment_date',
                'type' => 'DATETIME',
                'constraints' => ['NOT NULL'],
                'is_index' => true
            ],
            [
                'name' => 'dietitian_id',
                'type' => 'INT',
                'constraints' => ['UNSIGNED', 'NOT NULL'],
                'is_fk' => true,
                'references' => 'dietitian(dietitian_id)'
            ],
            [
                'name' => 'client_id',
                'type' => 'INT',
                'constraints' => ['UNSIGNED', 'NOT NULL'],
                'is_fk' => true,
                'references' => 'client(client_id)'
            ],
            [
                'name' => 'service_id',
                'type' => 'INT',
                'constraints' => ['UNSIGNED', 'NOT NULL'],
                'is_fk' => true,
                'references' => 'service(service_id)'
            ],
            [
                'name' => 'appointment_status_id',
                'type' => 'INT',
                'constraints' => ['UNSIGNED', 'NOT NULL'],
                'is_fk' => true,
                'references' => 'appointment_status(appointment_status_id)'
            ],
            [
                'name' => 'invoice_id',
                'type' => 'INT',
                'constraints' => ['UNSIGNED'],
                'is_fk' => true,
                'references' => 'invoice(invoice_id)'
            ],
            [
                'name' => 'creation_date',
                'type' => 'DATETIME',
                'constraints' => ['NOT NULL', 'DEFAULT CURRENT_TIMESTAMP'],
                'is_index' => true
            ],
            [
                'name' => 'last_modified_date',
                'type' => 'DATETIME',
                'constraints' => ['DEFAULT CURRENT_TIMESTAMP', 'ON UPDATE CURRENT_TIMESTAMP']
            ]
        ]
    ],
    [
        'table_name' => 'questionnaire_type',
        'fields' => [
            [
                'name' => 'questionnaire_type_id',
                'type' => 'INT',
                'constraints' => ['UNSIGNED', 'NOT NULL', 'AUTO_INCREMENT'],
                'is_primary' => true
            ],
            [
                'name' => 'sort_order',
                'type' => 'INT',
                'constraints' => ['NOT NULL', 'DEFAULT 0'],
                'is_index' => true
            ],
            [
                'name' => 'creation_date',
                'type' => 'DATETIME',
                'constraints' => ['NOT NULL', 'DEFAULT CURRENT_TIMESTAMP'],
                'is_index' => true
            ],
            [
                'name' => 'last_modified_date',
                'type' => 'DATETIME',
                'constraints' => ['DEFAULT CURRENT_TIMESTAMP', 'ON UPDATE CURRENT_TIMESTAMP']
            ]
        ]
    ],
    [
        'table_name' => 'questionnaire_type_description',
        'fields' => [
            [
                'name' => 'questionnaire_type_id',
                'type' => 'INT',
                'constraints' => ['UNSIGNED', 'NOT NULL'],
                'is_fk' => true,
                'references' => 'questionnaire_type(questionnaire_type_id)'
            ],
            [
                'name' => 'language_id',
                'type' => 'INT',
                'constraints' => ['UNSIGNED', 'NOT NULL'],
                'is_fk' => true,
                'references' => 'language(language_id)'
            ],
            [
                'name' => 'title',
                'type' => 'VARCHAR(100)',
                'constraints' => ['NOT NULL']
            ],
            [
                'name' => 'template_file',
                'type' => 'VARCHAR(256)',
                'constraints' => ['NULL']
            ]
        ],
        'primary_keys' => ['questionnaire_type_id', 'language_id']
    ],
    [
        'table_name' => 'questionnaire',
        'fields' => [
            [
                'name' => 'questionnaire_id',
                'type' => 'INT',
                'constraints' => ['UNSIGNED', 'NOT NULL', 'AUTO_INCREMENT'],
                'is_primary' => true
            ],
            [
                'name' => 'title',
                'type' => 'VARCHAR(100)',
                'constraints' => ['NOT NULL']
            ],
            [
                'name' => 'file_path',
                'type' => 'VARCHAR(256)',
                'constraints' => ['NOT NULL']
            ],
            [
                'name' => 'appointment_id',
                'type' => 'INT',
                'constraints' => ['UNSIGNED'],
                'is_fk' => true,
                'references' => 'appointment(appointment_id)'
            ],
            [
                'name' => 'creation_date',
                'type' => 'DATETIME',
                'constraints' => ['NOT NULL', 'DEFAULT CURRENT_TIMESTAMP'],
                'is_index' => true
            ],
            [
                'name' => 'last_modified_date',
                'type' => 'DATETIME',
                'constraints' => ['DEFAULT CURRENT_TIMESTAMP', 'ON UPDATE CURRENT_TIMESTAMP']
            ]
        ]
    ],
    [
        'table_name' => 'nutrition_plan',
        'fields' => [
            [
                'name' => 'plan_id',
                'type' => 'INT',
                'constraints' => ['UNSIGNED', 'NOT NULL', 'AUTO_INCREMENT'],
                'is_primary' => true
            ],
            [
                'name' => 'title',
                'type' => 'VARCHAR(100)',
                'constraints' => ['NOT NULL']
            ],
            [
                'name' => 'file_path',
                'type' => 'VARCHAR(256)',
                'constraints' => ['NOT NULL']
            ],
            [
                'name' => 'appointment_id',
                'type' => 'INT',
                'constraints' => ['UNSIGNED'],
                'is_fk' => true,
                'references' => 'appointment(appointment_id)'
            ],
            [
                'name' => 'creation_date',
                'type' => 'DATETIME',
                'constraints' => ['NOT NULL', 'DEFAULT CURRENT_TIMESTAMP'],
                'is_index' => true
            ],
            [
                'name' => 'last_modified_date',
                'type' => 'DATETIME',
                'constraints' => ['DEFAULT CURRENT_TIMESTAMP', 'ON UPDATE CURRENT_TIMESTAMP']
            ]
        ]
    ],
    [
        'table_name' => 'invoice_settings',
        'fields' => [
            [
                'name' => 'invoice_settings_id',
                'type' => 'INT',
                'constraints' => ['UNSIGNED', 'NOT NULL', 'AUTO_INCREMENT'],
                'is_primary' => true
            ],
            [
                'name' => 'company_name',
                'type' => 'VARCHAR(255)',
                'constraints' => ['NOT NULL']
            ],
            [
                'name' => 'company_title',
                'type' => 'VARCHAR(255)',
                'constraints' => ['NOT NULL']
            ],
            [
                'name' => 'vat_number',
                'type' => 'VARCHAR(20)',
                'constraints' => ['NOT NULL']
            ],
            [
                'name' => 'tax_office',
                'type' => 'VARCHAR(100)',
                'constraints' => ['NOT NULL']
            ],
            [
                'name' => 'address_street',
                'type' => 'VARCHAR(150)',
                'constraints' => ['NOT NULL']
            ],
            [
                'name' => 'address_number',
                'type' => 'VARCHAR(20)',
                'constraints' => ['NOT NULL']
            ],
            [
                'name' => 'city',
                'type' => 'VARCHAR(100)',
                'constraints' => ['NOT NULL']
            ],
            [
                'name' => 'postal_code',
                'type' => 'VARCHAR(20)',
                'constraints' => ['NOT NULL']
            ],
            [
                'name' => 'email',
                'type' => 'VARCHAR(256)',
                'constraints' => ['NOT NULL']
            ],
            [
                'name' => 'phone',
                'type' => 'VARCHAR(20)',
                'constraints' => ['NOT NULL']
            ],
            [
                'name' => 'logo_path',
                'type' => 'VARCHAR(255)',
                'constraints' => ['NULL']
            ],
            [
                'name' => 'last_modified_date',
                'type' => 'DATETIME',
                'constraints' => ['NOT NULL', 'DEFAULT CURRENT_TIMESTAMP', 'ON UPDATE CURRENT_TIMESTAMP']
            ]
        ]
    ]
];

echo json_encode(['success' => true, 'tables' => $schema]);
exit;
