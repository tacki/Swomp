<?php

namespace Swomp\Store;

class FileStore
{
    /**
     * @var string
     */
    private $extension = ".cache";

    /**
     * @var string
     */
    private $cacheDir;

    /**
     * FileCache constructor
     * @param string $cacheDirectory
     * @throws DirectoryException
     */
    public function __construct($cacheDirectory)
    {
        if (!is_dir($cacheDirectory)) {
            throw new DirectoryException("Path to Cache Directory is invalid");
        }

        $this->cacheDir = $cacheDirectory;
    }

    /**
     * Generate Path to the Store File
     * @param string $filename
     * @param string $filetype
     * @return string|false
     */
    public function getPath($filename, $type)
    {
        if ($this->contains($filename, $type)) {
            return $this->createPath($filename, $type);
        } else {
            return false;
        }
    }

    /**
     * Write content to File
     * @param string $filename
     * @param string $type
     * @param string $content
     * @return string Path of the added File
     */
    public function add($filename, $type, $content)
    {
        $path = $this->createPath($filename, $type);

        file_put_contents($path, $content);

        return $path;
    }


    /**
     * Check if the given Store-File exists
     * @param string $filename
     * @param string $type
     * @return boolean
     */
    public function contains($filename, $type)
    {
        return is_file($this->createPath($filename, $type));
    }

    /**
     * Get Content of Store-File
     * @param string $filename
     * @param string $type
     */
    public function fetch($filename, $type)
    {
        if ($this->contains($filename, $type)) {
            return file_get_contents($this->createPath($filename, $type));
        }

        return false;
    }

    /**
     * Delete Store-File
     * @param string $filename
     * @param string $type
     */
    public function delete($filename, $type)
    {
        unlink($this->createPath($filename, $type));

        return true;
    }

    /**
     * Create Path to Store-File
     * @param string $filename
     * @param string $type
     */
    private function createPath($filename, $type)
    {
        return $this->cacheDir.DIRECTORY_SEPARATOR.$filename.$this->extension.".$type";
    }
}