<div class="menu-options">
    <div class="menu-options-wrapper">
        <?php
        $current_page = basename($_SERVER['SCRIPT_NAME']);

        $menu_items = [
            [
                'text'    => TEXT_ACCOUNT,
                'url'     => 'portal/account.php',
                'matches' => ['account.php', 'account_edit.php']
            ],
            [
                'text'    => TEXT_APPOINTMENT,
                'url'     => 'portal/appointment.php',
                'matches' => ['appointment.php', 'create_appointment.php', 'view_appointment.php']
            ],
            [
                'text'    => TEXT_NUTRITION_PLAN,
                'url'     => 'portal/nutrition_plan.php',
                'matches' => ['nutrition_plan.php', 'view_nutrition_plan.php']
            ],
            [
                'text'    => TEXT_QUESTIONNAIRE,
                'url'     => 'portal/questionnaire.php',
                'matches' => ['questionnaire.php', 'view_questionnaire.php']
            ],
            [
                'text'    => TEXT_CLIENTS,
                'url'     => 'portal/clients.php',
                'matches' => ['clients.php', 'create_client.php', 'view_client.php']
            ],
            [
                'text'    => TEXT_INVOICES,
                'url'     => 'portal/invoice.php',
                'matches' => ['invoice.php', 'view_invoice.php']
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