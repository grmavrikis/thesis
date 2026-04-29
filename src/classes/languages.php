<?php

class Languages
{
    private $defaultLanguageCode = 'el';

    /**
     * @param string $languageCode The language code to set (e.g., 'en', 'el')
     */
    public function __construct($languageCode = '')
    {
        if (empty($languageCode))
        {
            $languageCode = $this->getUserLanguage();
        }

        if (empty($languageCode))
        {
            $languageCode = $this->defaultLanguageCode;
        }

        $db = new Database();

        // Check if table languages exists
        $checkTable = $db->query(
            "SELECT COUNT(*) as table_count
                FROM information_schema.tables 
                WHERE table_schema = ? AND table_name = 'language'",
            [DB_NAME]
        )->fetch();

        $isValid = false;

        if ($checkTable['table_count'] > 0)
        {
            // If table exists, check for el and en codes
            $codesCount = $db->query("SELECT language_id FROM language WHERE code IN ('el', 'en')")->rowCount();

            if ($codesCount == 2)
            {
                $isValid = true;
            }
        }

        if ($isValid)
        {
            $this->setLanguageByCode($languageCode);
        }
        else
        {
            // If the languages table does not exist, default to Greek and also support English based on URL
            $input_data = json_decode(file_get_contents("php://input"), true);
            if (
                !empty($_GET['language_code']) && $_GET['language_code'] == 'en' ||
                !empty($_POST['language_code']) && $_POST['language_code'] == 'en' ||
                !empty($input_data['language_code']) && $input_data['language_code'] == 'en'
            )
            {
                $_SESSION['language_id']        = 2;
                $_SESSION['language_name']      = 'English';
                $_SESSION['language_code']      = 'en';
                $_SESSION['language_flag']      = '/images/english.svg';
                $_SESSION['language_directory'] = 'english';
            }
            else
            {
                $_SESSION['language_id']        = 1;
                $_SESSION['language_name']      = 'Greek';
                $_SESSION['language_code']      = 'el';
                $_SESSION['language_flag']      = '/images/greek.svg';
                $_SESSION['language_directory'] = 'greek';
            }
        }
    }

    /**
     * Sets the language in the session by looking it up in the database based on the code.
     * @param string $code The language code (e.g., 'en', 'el')
     * @throws Exception If the language code is not found in the database.
     */
    public function setLanguageByCode(string $code): void
    {
        $sql = "SELECT language_id, name, code, flag, directory FROM language WHERE code = ? LIMIT 1";
        $db = new Database();
        $result = $db->query($sql, [$code])->fetch();
        if ($result)
        {
            $_SESSION['language_id']        = $result['language_id'];
            $_SESSION['language_name']      = $result['name'];
            $_SESSION['language_code']      = $result['code'];
            $_SESSION['language_flag']      = $result['flag'];
            $_SESSION['language_directory'] = $result['directory'];
        }
        else
        {
            throw new Exception("Language code '{$code}' not found in database.");
        }
    }

    /**
     * Gets the current language details from the session.
     * @return array Associative array with keys: id, name, code, flag, directory
     */
    public function getLanguage(): array
    {
        $lang = [
            'id'        => $_SESSION['language_id'],
            'name'      => $_SESSION['language_name'],
            'code'      => $_SESSION['language_code'],
            'flag'      => $_SESSION['language_flag'],
            'directory' => $_SESSION['language_directory']
        ];
        return $lang;
    }

    /**
     * Gets all available languages from the database.
     * @return array List of associative arrays for each language.
     */
    public function getLanguages(): array
    {
        $sql = "SELECT language_id, name, code, flag, directory FROM language";
        $db = new Database();
        return $db->query($sql)->fetchAll();
    }

    /**
     * Gets the default language details from the database.
     * @return array Associative array with keys: id, name, code, flag,
     */
    public function getDefaultLanguage(): array
    {
        $sql = "SELECT language_id, name, code, flag, directory FROM language WHERE code = ?";
        $db = new Database();
        return $db->query($sql, [$this->defaultLanguageCode])->fetch();
    }

    /**
     * Get user language.
     * @return string The user's language code or an empty string if not set.
     */
    public function getUserLanguage(): string
    {
        $language_code = '';

        if (!empty($_GET['language_code']))
        {
            $language_code = $_GET['language_code'];
        }
        else if (!empty($_POST['language_code']))
        {
            $language_code = $_POST['language_code'];
        }
        else
        {
            $input_data = json_decode(file_get_contents("php://input"), true);
            if (!empty($input_data['language_code']))
            {
                $language_code = $input_data['language_code'];
            }
        }
        return $language_code;
    }
}
