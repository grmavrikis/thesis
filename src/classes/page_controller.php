<?php

class PageController
{
    private string $languageDirectory;
    private string $relativePath;
    private string $basePath;
    private string $controllerPath;

    /**
     * @param string $languageDirectory The language directory (e.g., 'english')
     * @param string $basePath The absolute root path of the project
     */
    public function __construct(string $languageDirectory, string $basePath)
    {
        $this->languageDirectory = $languageDirectory;
        $this->basePath = rtrim($basePath, DIRECTORY_SEPARATOR);

        /**
         * $_SERVER['PHP_SELF'] returns the path from root (e.g., /ajax/create_account_submit.php)
         * We strip the leading slash to build our internal paths.
         */
        $this->relativePath = ltrim($_SERVER['PHP_SELF'], '/');

        $this->loadLanguageFile();
        $this->setControllerFile();
    }

    /**
     * Loads the language defines based on the directory structure.
     * Path: languages/{lang}/{path_to_file}
     */
    private function loadLanguageFile(): void
    {
        $langPath = $this->basePath . DIRECTORY_SEPARATOR .
            'languages' . DIRECTORY_SEPARATOR .
            $this->languageDirectory . DIRECTORY_SEPARATOR .
            $this->relativePath;

        if (file_exists($langPath))
        {
            require_once $langPath;
        }
        else
        {
            // Fallback to greek if the specific language file is missing
            $fallback = $this->basePath . DIRECTORY_SEPARATOR .
                'languages' . DIRECTORY_SEPARATOR .
                'greek' . DIRECTORY_SEPARATOR .
                $this->relativePath;

            if (file_exists($fallback))
            {
                require_once $fallback;
            }
        }
    }

    /**
     * Sets the controller logic based on the directory structure.
     * Path: controllers/{path_to_file}
     */
    private function setControllerFile(): void
    {
        $controllerPath = $this->basePath . DIRECTORY_SEPARATOR .
            'controllers' . DIRECTORY_SEPARATOR .
            $this->relativePath;

        if (file_exists($controllerPath))
        {
            $this->controllerPath = $controllerPath;
        }
    }

    /**
     * Gets the controller file.
     */
    public function getControllerFile(): string
    {
        return $this->controllerPath;
    }
}
