<?php

class SeoUrl
{
    private Languages $languages;

    /**
     * Mapping: Real PHP file -> Desired SEO Path 
     */
    private array $specialRoutes = [
        'administration/view_appointment_status.php' => 'administration/appointment_status/',
        'administration/view_dietitian.php' => 'administration/dietitian/',
        'administration/view_tax.php' => 'administration/tax/',
        'administration/view_service.php' => 'administration/service/',
        'administration/view_questionnaire_type.php' => 'administration/questionnaire_type/',
        'administration/view_client.php' => 'administration/client/',
        'administration/view_appointment.php' => 'administration/appointment/',
        'administration/view_questionnaire.php' => 'administration/questionnaire/',
        'administration/view_nutrition_plan.php' => 'administration/nutrition_plan/',
        'administration/view_invoice.php' => 'administration/invoice/',
        'portal/view_appointment.php' => 'portal/appointment/',
        'portal/view_nutrition_plan.php' => 'portal/nutrition_plan/',
        'portal/view_questionnaire.php' => 'portal/questionnaire/',
        'portal/view_client.php' => 'portal/client/',
        'portal/view_invoice.php' => 'portal/invoice/'
    ];

    public function __construct(Languages $languages)
    {
        $this->languages = $languages;
    }

    /**
     * Generates SEO-friendly URLs.
     */
    public function generate(string $filename, array $params = [], string $targetLangCode = ''): string
    {
        $filename = ltrim($filename, '/');
        if ($filename === 'index.php')
        {
            $filename = '';
        }

        $defaultLang = $this->languages->getDefaultLanguage();

        // Determine the target language code from argument or session
        $targetCode = !empty($targetLangCode) ? $targetLangCode : $this->languages->getLanguage()['code'];

        // Build dynamic prefix based on the language directory from configuration
        $prefix = "/";
        if ($targetCode !== $defaultLang['code'])
        {
            $allLanguages = $this->languages->getLanguages();
            // Look for the directory corresponding to the target code
            $dir = $targetCode;
            foreach ($allLanguages as $l)
            {
                if ($l['code'] === $targetCode)
                {
                    $dir = $l['directory'];
                    break;
                }
            }
            $prefix = "/" . $dir . "/";
        }

        // Remove language_code from parameters to keep the URL clean
        if (isset($params['language_code']))
        {
            unset($params['language_code']);
        }

        // Handle special routes defined in the mapping array
        if (isset($this->specialRoutes[$filename]))
        {
            $prettyPath = $this->specialRoutes[$filename];
            if (isset($params['id']))
            {
                $id = $params['id'];
                unset($params['id']);
                $queryString = !empty($params) ? '?' . http_build_query($params) : '';
                // Use regex to prevent double slashes in the final path
                return preg_replace('#/+#', '/', $prefix . $prettyPath . $id) . $queryString;
            }
        }

        $queryString = !empty($params) ? '?' . http_build_query($params) : '';
        return preg_replace('#/+#', '/', $prefix . $filename) . $queryString;
    }
}
