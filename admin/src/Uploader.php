<?php

namespace Formwork\Admin;

use Formwork\Admin\Exceptions\LocalizedException;
use Formwork\Core\Formwork;
use Formwork\Utils\FileSystem;
use Formwork\Utils\HTTPRequest;
use Formwork\Utils\MimeType;

class Uploader
{
    protected $destination;

    protected $options;

    protected $uploadedFiles;

    protected static $errorMessages = array(
        UPLOAD_ERR_INI_SIZE   => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
        UPLOAD_ERR_FORM_SIZE  => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
        UPLOAD_ERR_PARTIAL    => 'The uploaded file was only partially uploaded',
        UPLOAD_ERR_NO_FILE    => 'No file was uploaded',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
        UPLOAD_ERR_EXTENSION  => 'A PHP extension stopped the file upload'
    );

    protected static $errorLanguageStrings = array(
        UPLOAD_ERR_INI_SIZE   => 'uploader.error.size',
        UPLOAD_ERR_FORM_SIZE  => 'uploader.error.size',
        UPLOAD_ERR_PARTIAL    => 'uploader.error.partial',
        UPLOAD_ERR_NO_FILE    => 'uploader.error.no-file',
        UPLOAD_ERR_NO_TMP_DIR => 'uploader.error.no-temp',
        UPLOAD_ERR_CANT_WRITE => 'uploader.error.cannot-write',
        UPLOAD_ERR_EXTENSION  => 'uploader.error.php-extension'
    );

    public function __construct($destination, $options = array())
    {
        $this->destination = FileSystem::normalize($destination);
        $this->options = array_merge($this->defaults(), $options);
    }

    public function defaults()
    {
        $mimeTypes = array_map(
            array(MimeType::class, 'fromExtension'),
            Formwork::instance()->option('files.allowed_extensions')
        );
        return array(
            'allowedMimeTypes' => $mimeTypes,
            'overwrite' => false,
        );
    }

    public function upload($name = null)
    {
        if (!HTTPRequest::hasFiles()) {
            return false;
        }
        $count = count(HTTPRequest::files());

        foreach (HTTPRequest::files() as $file) {
            if ($file['error'] === 0) {
                if (is_null($name) || $count > 1) {
                    $name = $file['name'];
                }
                $this->move($file['tmp_name'], $this->destination, $name);
            } else {
                throw new LocalizedException(static::$errorMessages[$file['error']], static::$errorLanguageStrings[$file['error']]);
            }
        }

        return true;
    }

    public function isAllowedMimeType($mimeType)
    {
        if (is_null($this->options['allowedMimeTypes'])) {
            return true;
        }
        return in_array($mimeType, (array) $this->options['allowedMimeTypes']);
    }

    public function uploadedFiles()
    {
        return $this->uploadedFiles;
    }

    private function move($source, $destination, $filename)
    {
        $mimeType = FileSystem::mimeType($source);

        if (!$this->isAllowedMimeType($mimeType)) {
            throw new LocalizedException('MIME type ' . $mimeType . ' is not allowed', 'uploader.error.mime-type');
        }

        $destination = FileSystem::normalize($destination);

        if (FileSystem::basename($filename)[0] === '.') {
            throw new LocalizedException('Hidden file ' . $filename . ' not allowed', 'uploader.error.hidden-files');
        }

        $name = str_replace(array(' ', '.'), '-', FileSystem::name($filename));
        $extension = strtolower(FileSystem::extension($filename));

        if (empty($extension)) {
            $mimeToExt = MimeType::toExtension($mimeType);
            $extension = is_array($mimeToExt) ? $mimeToExt[0] : $mimeToExt;
        }

        $filename = $name . '.' . $extension;

        if (!(bool) preg_match('/^[a-z0-9_-]+(?:\.[a-z0-9]+)?$/i', $filename)) {
            throw new LocalizedException('Invalid file name ' . $filename, 'uploader.error.file-name');
        }

        if (!$this->options['overwrite'] && FileSystem::exists($destination . $filename)) {
            return false;
        }

        if (@move_uploaded_file($source, $destination . $filename)) {
            $this->uploadedFiles[] = $filename;
            return true;
        }

        return false;
    }
}
