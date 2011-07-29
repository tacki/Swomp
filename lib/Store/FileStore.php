<?php
/**
 * File Store Manager
 *
 * @author Markus Schlegel <g42@gmx.net>
 * @copyright Copyright (C) 2011 Markus Schlegel
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

/**
 * Namespaces
 */
namespace Swomp\Store;
use Swomp\Exceptions\SwompException;

/**
 * File Store Manager
 */
class FileStore
{
    /**
     * @var string
     */
    private $extension = ".cache";

    /**
     * @var string
     */
    private $directory;

    /**
     * FileStore constructor
     * @param string $directory
     * @throws SwompException
     */
    public function __construct($directory)
    {
        if (!is_dir($directory)) {
            throw new SwompException("Path to Cache Directory is invalid");
        }

        $this->directory = $directory;
    }

    /**
     * Get the Path of a File in Store if it exists
     * @param string $filename
     * @param string $type
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
     * Get Directory
     * @return string
     */
    public function getDirectory()
    {
        return $this->directory;
    }

    /**
     * Set Directory
     * @param string$directory
     */
    public function setDirectory($directory)
    {
        $this->directory = $directory;
    }

    /**
     * Write content to File
     * @param string $filename
     * @param string $type
     * @param string $content
     * @return string Path of the added File
     */
    public function write($filename, $type, $content)
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
    public function read($filename, $type)
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
        if ($this->contains($filename, $type)) {
            unlink($this->createPath($filename, $type));
        }
    }

    /**
     * Create Path to Store-File
     * @param string $filename
     * @param string $type
     */
    private function createPath($filename, $type)
    {
        return $this->directory.DIRECTORY_SEPARATOR.$filename.$this->extension.".$type";
    }

}