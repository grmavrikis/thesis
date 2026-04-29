<?php

class FileManager
{
    private $upload_dir;
    private $download_url;
    // max_size: 10 MB in bytes (10 * 1024 * 1024)
    private $max_size = 10485760;
    // default_allowed: Only safe images and documents
    private $default_allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'zip'];

    public function __construct($upload_dir = '../uploads/', $download_url = '/uploads/')
    {
        $this->upload_dir = $upload_dir;
        $this->download_url = $download_url;
        // Create the directory if it doesn't exist 
        if (!is_dir($this->upload_dir))
        {
            mkdir($this->upload_dir, 0775, true);
        }
    }

    /**
     * Validates a file based on size, extension, and PHP upload errors.
     * @param string|null $name Original filename.
     * @param int $size File size in bytes.
     * @param int $error PHP upload error code.
     * @param array $allowed_extensions Array of allowed extensions (e.g., ['jpg', 'png']).
     * @return string|bool Returns true if valid, or an error message string if invalid.
     */
    public function validate($name, $size, $error, $allowed_extensions = [])
    {
        // Check for PHP upload errors
        if ($error === UPLOAD_ERR_NO_FILE) return ERROR_NO_FILE_UPLOADED;
        if ($error !== UPLOAD_ERR_OK) return sprintf(ERROR_FILE_UPLOAD_ERROR, $error);

        // Size validation
        if ($size > $this->max_size) return sprintf(ERROR_FILE_SIZE_ERROR, $this->max_size / (1024 * 1024) . "MB"/* bytes => MB */);

        // Extension Validation
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));

        // If no extensions provided, use the safe default whitelist
        $whitelist = !empty($allowed_extensions) ? $allowed_extensions : $this->default_allowed;

        if (!in_array($extension, $whitelist))
        {
            return sprintf(ERROR_FILE_EXTENSION_ERROR, implode(', ', $whitelist));
        }

        // Block dangerous extensions for security.
        // Overrides check already done for allowed_extensions provided by the user.
        $blacklisted = ['php', 'php3', 'php4', 'php5', 'phtml', 'phps', 'phar', 'exe', 'bat', 'js', 'html', 'htm', 'htaccess'];
        if (in_array($extension, $blacklisted))
        {
            return ERROR_FILE_EXTENSION_ERROR;
        }

        return true;
    }

    /**
     * Returns the full URL for the form schema if the file exists on the server.
     * @param string $filename The name of the file stored in the database.
     * @return string The public URL or an empty string if file doesn't exist.
     */
    public function getFileUrl($filename)
    {
        if (!empty($filename) && file_exists($this->upload_dir . $filename))
        {
            return $this->download_url . $filename;
        }
        return '';
    }

    /**
     * Handles the file upload process.
     * @param string|null $name The original name of the file (from $_FILES).
     * @param string|null $tmp_name The temporary path of the file (from $_FILES).
     * @param int|null $error The error code (from $_FILES).
     * @param string $prefix The name prefix for the file (e.g., "image_5").
     * @return string|null The new filename on success, or null if no file was uploaded.
     */
    public function handleUpload($name, $tmp_name, $error, $prefix)
    {
        if (!empty($name) && $error === UPLOAD_ERR_OK)
        {
            $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            $new_filename = $prefix . "." . $extension;

            // Cleanup old files matching the prefix
            foreach (glob($this->upload_dir . $prefix . ".*") as $old_file)
            {
                if (is_file($old_file))
                {
                    $this->deleteFile(basename($old_file));
                }
            }

            if (move_uploaded_file($tmp_name, $this->upload_dir . $new_filename))
            {
                return $new_filename;
            }
        }
        return null;
    }

    /**
     * Deletes a file from the server.
     * @param string|null $filename The name of the file to delete.
     * @return bool True if deleted or didn't exist, false if deletion failed.
     */
    public function deleteFile($filename)
    {
        if (empty($filename))
        {
            return true;
        }

        $file_path = $this->upload_dir . $filename;

        if (file_exists($file_path) && is_file($file_path))
        {
            return unlink($file_path);
        }

        return true; // File does not exist
    }
}
