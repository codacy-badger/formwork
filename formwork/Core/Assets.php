<?php

namespace Formwork\Core;

use Formwork\Utils\FileSystem;
use Formwork\Utils\Uri;

class Assets
{
    /**
     * Base path where asset files are located
     *
     * @var string
     */
    protected $basePath;

    /**
     * Base URI from which assets are accessible
     *
     * @var string
     */
    protected $baseUri;

    /**
     * Create a new Assets instance
     *
     * @param string $basePath
     * @param string $baseUri
     */
    public function __construct($basePath, $baseUri)
    {
        $this->basePath = FileSystem::normalize($basePath);
        $this->baseUri = Uri::normalize($baseUri);
    }

    /**
     * Get asset version, if possible, based on its last modified time
     *
     * @param string $path Requested asset path
     *
     * @return string|null
     */
    public function version($path)
    {
        $file = $this->basePath . strtr(trim($path, '/'), '/', DS);
        if (FileSystem::exists($file)) {
            return dechex(FileSystem::lastModifiedTime($file));
        }
        return null;
    }

    /**
     * Get asset URI optionally followed by a version query parameter
     *
     * @param string $path           Requested asset path
     * @param bool   $includeVersion Whether to include asset version
     *
     * @return string
     */
    public function uri($path, $includeVersion = false)
    {
        $uri = $this->baseUri . trim($path, '/');
        if ($includeVersion && !is_null($version = $this->version($path))) {
            $uri .= '?v=' . $version;
        }
        return $uri;
    }
}
