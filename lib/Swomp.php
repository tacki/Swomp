<?php
/**
 * Swamp Main Object
 *
 * @author Markus Schlegel <g42@gmx.net>
 * @copyright Copyright (C) 2011 Markus Schlegel
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

/**
 * Namespaces
 */
namespace Swomp;
use Swomp\Caches\CacheInterface;
use Swomp\Caches\ArrayCache;
use Swomp\Elements\Ressource;
use Swomp\Exceptions\DirectoryException;
use Swomp\Exceptions\SwompException;
use Swomp\Filters\JsCompressor;
use Swomp\Filters\CssCompressor;

/**
 * Swamp Main Object
 */
class Main
{
    /**
     * @var array
     */
    private $sourceDirs = array();

    /**
     * @var Swomp\Caches\CacheInterface
     */
    private $cacheManager;

    /**
     * @var string
     */
    private $fileStoreDirectory = "store";

    /**
     * @var array
     */
    private $registeredFiles = array();

    /**
     * @var int
     */
    private $cacheLifetime = 86400; // one Day

    /**
     * Swomp Constructor
     */
    public function __construct()
    {
        // Register Autoloading
        spl_autoload_register(array($this, 'autoloader'));

        // Default Cache Manager
        $this->cacheManager = new ArrayCache;
    }

    /**
     * Return the Source Directories
     * @return array
     */
    public function getSourceDirectory()
    {
        return $this->sourceDirs;
    }

    /**
     * Set the Source Directory
     * @param string|array $path
     * @throws DirectoryException
     */
    public function setSourceDirectory($path)
    {
        if (!is_array($path)) {
            $path = array($path);
        }

        foreach ($path as $entry) {
            if (!is_readable($entry)) {
                throw new DirectoryException("Source Directory $entry is not readable");
            }
        }

        $this->sourceDirs = $path;
    }

    /**
     * Add a source Directory
     * @param string|array $path
     * @throws Swomp\Exceptions\DirectoryException
     */
    public function addSourceDirectory($path)
    {
        if (!is_array($path)) {
            $path = array($path);
        }

        foreach ($path as $entry) {
            if (!is_readable($entry)) {
                throw new DirectoryException("Source Directory $entry is not readable");
            }
        }

        $this->sourceDirs = array_merge($this->sourceDirs, $path);
    }

    /**
     * Get Cache Manager
     * @return Swomp\Caches\CacheInterface
     */
    public function getCacheManager()
    {
        return $this->cacheManager;
    }

    /**
     * Set a new Cache Manager
     * @param Swomp\Caches\CacheInterface $cacheManager
     */
    public function setCacheManager(CacheInterface $cacheManager)
    {
        $this->cacheManager = $cacheManager;
    }

    /**
     * Get File Store Directory
     * @return string
     */
    public function getFileStoreDirectory()
    {
        return $this->fileStoreDirectory;
    }

    /**
     * Set File Store Directory
     * @param string $fileStoreDirectory
     */
    public function setFileStoreDirectory($fileStoreDirectory)
    {
        $this->fileStoreDirectory = $fileStoreDirectory;
    }

    /**
     * Get Cache Lifetime
     * @return int
     */
    public function getCacheLifetime()
    {
        return $this->cacheLifetime;
    }

    /**
     * Set Cache Lifetime
     * @param int $cacheLifetime
     */
    public function setCacheLifetime($cacheLifetime)
    {
        $this->cacheLifetime = $cacheLifetime;
    }


    /**
     * Return a List of Files from the Source Directories, known to Swomp
     * @param $type css|js
     * @return array
     */
    public function getRegisteredFiles($type=false)
    {
        if (!count($this->registeredFiles)) {
            $this->registerFiles();
        }

        if (!$type) {
            return $this->registeredFiles;
        }

        $result = array();

        foreach ($this->registeredFiles as $ressource) {
            if ($ressource->getType() == $type) {
                $result[] = $ressource;
            }
        }

        return $result;
    }

    /**
     * Get the representation of the given Filename inside our Store
     * @param string $filename
     * @return string
     */
    public function getStorePath($filename)
    {
        $ressource = $this->findFileInSourceDir($filename);

        if ($path = $ressource->getStorePath()) {
            return $path;
        } else {
            $this->createStoreEntry($ressource);

            return $ressource->getStorePath();
        }
    }

    /**
     * Get Combined File
     * @param string $type Ressource Type (e.g. 'css')
     * @param array $includes Files to include
     * @param array $excludes Files to exclude
     * @return string;
     */
    public function getCombinedStorePath($type, array $includes=null, array $excludes=null)
    {
        $content = "";
        $hash    = "";

        foreach ($this->getRegisteredFiles($type) as $ressource) {
            // Includes/Excludes handling
            if ($includes) {
                if (!in_array($ressource->getFileName(), $includes)) {
                    continue;
                }
            }
            if ($excludes) {
                if (in_array($ressource->getFileName(), $excludes)) {
                    continue;
                }
            }

            // Retrieve Ressource
            if ($this->getCacheManager()->contains($ressource->getHash())) {
                // Get Ressource from Cache
                $ressource = $this->getCacheManager()->fetch($ressource->getHash());
            } elseif ($ressource->loadContentFromStore()) {
                // Get Ressource from StoreFile
                $this->getCacheManager()->save($ressource->getHash(), $ressource, $this->cacheLifetime);
            } else {
                $this->createStoreEntry($ressource);
                $this->getCacheManager()->save($ressource->getHash(), $ressource, $this->cacheLifetime);
            }

            $content .= $ressource->getContent() . "\n";
            $hash    .= $ressource->getHash();
        }

        if (strlen($content) && strlen($hash)) {
            $combRessource = new Ressource($this->fileStoreDirectory);
            $combRessource->setHash(md5($hash));
            $combRessource->setContent($content);
            $combRessource->setType($type);
            $combRessource->writeToStore();

            return $combRessource->getStorePath();
        } else {
            throw SwompException("Cannot find any Ressource of this Type ($type)");
        }

    }

    /**
     * Create Store Entry
     * @param Swomp\Elements\Ressource $ressource File Ressource
     */
    private function createStoreEntry(Ressource $ressource)
    {
        // Load from Source File
        $ressource->loadContentFromSource();

        // Compress Content
        $this->compressContent($ressource);

        // Write Ressource to Store
        $ressource->writeToStore();

        // Add Ressource to Cache
        $this->getCacheManager()->save($ressource->getHash(), $ressource, $this->cacheLifetime);
    }

    /**
     * Compress the given Ressource
     * @param Swomp\Elements\Ressource $ressource File Ressource
     */
    private function compressContent(Ressource $ressource)
    {
        switch ($ressource->getType()) {
            case 'css':
                $cssCompressor = new CssCompressor();
                $buffer = $cssCompressor->compress($ressource->getContent());
                $ressource->setContent($buffer);
                break;

            case 'js':
                $jsCompressor = new JsCompressor();
                $buffer = $jsCompressor->compress($ressource->getContent());
                $ressource->setContent($buffer);
                break;
        }
    }

    /**
     * Find File in given Source Dirs
     * @param string $filename
     * @throws DirectoryException
     * @return Swomp\Elements\Ressource File Ressource
     */
    private function findFileInSourceDir($filename)
    {
        foreach ($this->getRegisteredFiles() as $fileRessource) {
            if ($filename === $fileRessource->getFilename()) {
                // Filename found

                return $fileRessource;
            } elseif ($filename === $fileRessource->getFilepath()) {
                // Filename was a Full Path (and found)

                return $fileRessource;
            }

        }

        throw new DirectoryException("Cannot find File $filename in any of our Source Directories");
    }

    /**
     * Register Files in all known Source Directories
     */
    private function registerFiles()
    {
        foreach ($this->sourceDirs as $directory) {
            $files = $this->getDirList($directory, true, 'files', '/.\.css$|.\.js$/');

            foreach ($files as $file) {
                // Create Ressource Object
                $fileRessource = new Ressource($this->fileStoreDirectory);
                $fileRessource->setFilepath($file);
                $hash = $fileRessource->generateHash();
                $fileRessource->setHash($hash);

                $this->registeredFiles[] = $fileRessource;
            }
        }
    }

    /**
     * Get Content of a given Directory
     * @param string $directory Full Path of the Directory
     * @param string $fullpath Show full Path in result Array
     * @param string $collect Collect all|directories|files Entries
     * @param string $regexp Regexp Filter the Files/Directories
     * @return array|bool Array of Directories and Files or false if no directory given
     */
    private function getDirList($directory, $fullpath=false, $collect='all', $regexp=false)
    {
        $directory = realpath($directory)."/";

        if (is_dir($directory)) {
            $dir 		= array();
            $dircontent = scandir($directory);

            foreach ($dircontent as $path) {
                if (substr($path, 0, 1) == ".") {
                    // Don't use .* Files/Directories
                    continue;
                }
                if (is_dir($directory . $path) && $collect != 'files') {
                    if ($fullpath) {
                        $path = $directory . $path;
                    }
                    if ($regexp && preg_match($regexp, $path)) {
                        $dir['directories'][] = $path;
                    }
                } elseif (is_file($directory . $path) && $collect != 'directories') {
                    if ($fullpath) {
                        $path = $directory . $path;
                    }
                    if ($regexp && preg_match($regexp, $path)) {
                        $dir['files'][] = $path;
                    }
                }
            }

            switch ($collect) {
                case 'all':
                    return $dir;

                case 'directories':
                    return $dir['directories'];

                case 'files':
                    return $dir['files'];
            }

        } else {
            return false;
        }
    }

    /**
     * Class Autoloader
     * @param string $className Called classname
     */
    private function autoloader($className) {
        // Strip our Project Namespace
        $className = str_replace(__NAMESPACE__.'\\', '', $className);
        // FQ-Namespace = Pathname
        $filename = str_replace('\\', DIRECTORY_SEPARATOR, $className);

        require $filename . '.php';
    }
}