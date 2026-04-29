<div class="menu-options">
    <div class="menu-options-wrapper">
        <?php
        $current_page = basename($_SERVER['SCRIPT_NAME']);

        $menu_items = [
            [
                'text'    => TEXT_DASHBOARD,
                'url'     => 'administration/dashboard.php',
                'matches' => ['dashboard.php']
            ],
            [
                'text'    => TEXT_ACCOUNT,
                'url'     => 'administration/admin_account.php',
                'matches' => ['admin_account.php', 'admin_account_edit.php']
            ],
            [
                'text'    => TEXT_INVOICE_SETTINGS,
                'url'     => 'administration/invoice_settings.php',
                'matches' => ['invoice_settings.php', 'invoice_settings_edit.php']
            ],
            [
                'text'    => TEXT_APPOINTMENT,
                'url'     => 'administration/appointment.php',
                'matches' => ['appointment.php', 'create_appointment.php', 'view_appointment.php']
            ],
            [
                'text'    => TEXT_CLIENTS,
                'url'     => 'administration/clients.php',
                'matches' => ['clients.php', 'create_client.php', 'view_client.php']
            ],
            [
                'text'    => TEXT_INVOICES,
                'url'     => 'administration/invoice.php',
                'matches' => ['invoice.php', 'create_invoice.php', 'view_invoice.php']
            ],
            [
                'text'    => TEXT_DIETITIANS,
                'url'     => 'administration/dietitian.php',
                'matches' => ['dietitian.php', 'create_dietitian.php', 'view_dietitian.php']
            ],
            [
                'text'    => TEXT_QUESTIONNAIRE,
                'url'     => 'administration/questionnaire.php',
                'matches' => ['questionnaire.php', 'view_questionnaire.php']
            ],
            [
                'text'    => TEXT_NUTRITION_PLAN,
                'url'     => 'administration/nutrition_plan.php',
                'matches' => ['nutrition_plan.php', 'view_nutrition_plan.php']
            ],
            [
                'text'    => TEXT_APPOINTMENT_STATUS,
                'url'     => 'administration/appointment_status.php',
                'matches' => ['appointment_status.php', 'create_appointment_status.php', 'view_appointment_status.php']
            ],
            [
                'text'    => TEXT_SERVICE,
                'url'     => 'administration/service.php',
                'matches' => ['service.php', 'create_service.php', 'view_service.php']
            ],
            [
                'text'    => TEXT_TAXES,
                'url'     => 'administration/tax.php',
                'matches' => ['tax.php', 'create_tax.php', 'view_tax.php']
            ],
            [
                'text'    => TEXT_QUESTIONNAIRE_TYPES,
                'url'     => 'administration/questionnaire_type.php',
                'matches' => ['questionnaire_type.php', 'create_questionnaire_type.php', 'view_questionnaire_type.php']
            ]
        ];

        foreach ($menu_items as $item)
        {
            if (in_array($current_page, $item['matches']))
            {
                echo '<a href="#" rel="nofollow" class="menu-option active">' . $item['text'] . '</a>';
            }
            else
            {
                echo '<a class="menu-option" href="' . $seoUrl->generate($item['url']) . '">' . $item['text'] . '</a>';
            }
        }
        ?>
    </div>
</div>