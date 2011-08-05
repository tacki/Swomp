<?php
/**
 * Ressource Object
 *
 * @author Markus Schlegel <g42@gmx.net>
 * @copyright Copyright (C) 2011 Markus Schlegel
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

/**
 * Namespaces
 */
namespace Swomp\Elements;
use Swomp\Exceptions\SwompException;
use Swomp\Store\FileStore;

/**
 * Ressource Object
 */
class Ressource
{
    /**
     * @var Swomp\Store\FileStore
     */
    private $fileStore;

    /**
     * @var string
     */
    private $fileName;

    /**
     * @var string
     */
    private $filePath;

    /**
     * @var string
     */
    private $hash;

    /**
     * @var string
     */
    private $content;

    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $storePath;


    /**
     * Ressource constructor
     * @param string $cacheDirectory
     * @throws SwompException
     */
    public function __construct($cacheDirectory)
    {
        if (!is_dir($cacheDirectory)) {
            throw new SwompException("Path to Cache Directory ($cacheDirectory) is invalid");
        }

        $this->fileStore = new FileStore($cacheDirectory);
    }

    /**
     * Get Filename
     * @return string
     */
    public function getFileName()
    {
        return $this->fileName;
    }

    /**
     * Set Filename
     * @param string $fileName
     */
    public function setFileName($fileName)
    {
        $this->fileName = $fileName;
    }

    /**
     * Get Filepath
     * @return string
     */
    public function getFilePath()
    {
        return $this->filePath;
    }

    /**
     * Set Filepath
     * @param string $filePath
     */
    public function setFilePath($filePath)
    {
        $pathinfo = pathinfo($filePath);

        $this->setFilename($pathinfo['basename']);
        $this->setType($pathinfo['extension']);
        $this->filePath = $filePath;
    }

    /**
     * Get Hash
     * @return string
     */
    public function getHash()
    {
        if (!$this->hash) {
            $this->hash = $this->generateHash();
        }

        return $this->hash;
    }

    /**
     * Set Hash
     * @param string $hash
     */
    public function setHash($hash)
    {
        $this->hash = $hash;
    }

    /**
     * Generate a Hash based on the File
     * @throws SwompException
     * @return bool The generated Hash if successful, else false
     */
    public function generateHash()
    {
        if (is_file($this->getFilepath())) {
            return md5_file($this->getFilePath());
        }

        throw new SwompException("Cannot read File {$this->getFilepath()}");
    }

    /**
     * Get Content
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set Content
     * @param string $content
     */
    public function setContent($content)
    {
        $this->content = $content;
    }

    /**
     * Get Type (file extension)
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set Type (file extension)
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * Get Store Path
     * @return string|bool The Path or false if the path is not found
     */
    public function getStorePath()
    {
        if ($this->storePath) {
            return $this->storePath;
        } elseif ($path = $this->getFileStore()->getPath($this->getHash(), $this->getType())) {
            $this->setStorePath($path);

            return $path;
        } else {
            return false;
        }
    }

    /**
     * Set Store Path
     * @param string $storePath
     */
    public function setStorePath($storePath)
    {
        $this->storePath = $storePath;
    }

    /**
     * Get File Store Object
     * @return Swomp\Store\FileStore
     */
    public function getFileStore()
    {
        return $this->fileStore;
    }

    /**
     * Set File Store Object
     * @param Swomp\Store\FileStore $fileStore
     */
    public function setFileStore(FileStore $fileStore)
    {
        $this->fileStore = $fileStore;
    }

    /**
     * Load Content of the original Source File into this Ressource
     * @throws SwompException
     */
    public function loadContentFromSource()
    {
        if (is_file($this->getFilePath())) {
            $this->setContent(file_get_contents($this->getFilePath()));
        } else {
            throw new SwompException("Cannot load Content to Ressource from Source {$this->getFilePath()}");
        }
    }

    /**
     * Load Content of the Store File into this Ressource
     * @return bool true if successful, else false
     */
    public function loadContentFromStore()
    {
        if (is_file($this->getStorePath())) {
            $this->setContent(file_get_contents($this->getStorePath()));
            return true;
        } else {
            return false;
        }
    }

    /**
     * Write Content to File Store
     * @throws SwompException
     */
    public function writeToStore()
    {
        if ($this->getHash() && $this->getType() && $this->getContent()) {
            $path = $this->getFileStore()->write($this->getHash(),
                                                 $this->getType(),
                                                 $this->getContent()
            );

            $this->setStorePath($path);
        } else {
            throw new SwompException("Cannot write to Store - Hash, Type and Content is required");
        }
    }

    /**
     * Remove the File from Store
     * @throws SwompException
     */
    public function removeFromStore()
    {
        if ($this->getHash() && $this->getType()) {
            $this->getFileStore()->delete($this->getHash(), $this->getType());
        } else {
            throw new SwompException("Cannot remove from Store - Hash and Type is required");
        }
    }

}