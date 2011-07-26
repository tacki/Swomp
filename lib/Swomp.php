<?php
namespace Swomp;
use Swomp\Exceptions\DirectoryException;
use Swomp\Filters\JsCompressor;
use Swomp\Filters\CssCompressor;
use Swomp\Caches\CacheInterface;
use Swomp\Caches\ArrayCache;
use Swomp\Store\FileStore;

class Swomp
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
     * @var Swomp\Store\FileStore
     */
    private $fileStoreManager;

    /**
     * @var array
     */
    private $registeredFiles = array();

    /**
     * @var int
     */
    private $cacheLifetime = 3600;

    /**
     * Swomp Constructor
     */
    public function __construct()
    {
        // Register Autoloading
        spl_autoload_register(array($this, 'autoloader'));

        // Default Cache Manager
        $this->cacheManager = new ArrayCache;

        // File Store Manager
        $this->fileStoreManager = new FileStore($this->fileStoreDirectory);
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
     * Return the Source Directories
     * @return array
     */
    public function getSourceDirectory()
    {
        return $this->sourceDirs;
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
     * Get Cache Manager
     * @return Swomp\Caches\CacheInterface
     */
    public function getCacheManager()
    {
        return $this->cacheManager;
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

        $result = array();
        if ($type == "css" || $type == "js") {
            foreach ($this->registeredFiles as $filename) {
                if (pathinfo($filename, PATHINFO_EXTENSION) == $type) {
                    $result[] = $filename;
                }
            }
        } else {
            $result = $this->registeredFiles;
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
        $filepath = $this->findFileInSourceDir($filename);

        return $this->retrieveStorePath($filepath);
    }

    /**
     * Get a combined File of all known Css Files
     * @return string
     */
    public function getCombinedCssStorePath()
    {
        return $this->retrieveCombinedCssStorePath();
    }


    /**
     * Retrieve Buffer from Cache (if available)
     * @param string $filepath Full Path of a File
     * @param string $filehash Hash of the File
     * @return string
     */
    private function retrieveBufferFromCache($filepath, $filehash=false)
    {
        if (!$filehash) {
            $filehash = $this->generateHash($filepath);
        }

        if ($this->getCacheManager()->contains($filehash)) {
            $buffer = $this->getCacheManager()->fetch($filehash);
        } elseif ($this->getFileStoreManager()->contains($filehash, pathinfo($filepath, PATHINFO_EXTENSION))) {
            $buffer = $this->getFileStoreManager()->fetch($filehash, pathinfo($filepath, PATHINFO_EXTENSION));
        } else {
            // Generate compressed Buffer
            $buffer = $this->compress($filepath, $filehash);
            // Add Buffer to Cache and write to Store
            $this->getCacheManager()->save($filehash, $buffer, $this->cacheLifetime);
            $this->getFileStoreManager()->add($filehash, pathinfo($filepath, PATHINFO_EXTENSION), $buffer);
        }

        return $buffer;
    }

    /**
     * Retrieve Store Path
     * @param string $filepath Full Path of a File
     * @param string $filehash Hash of the File
     * @return string
     */
    private function retrieveStorePath($filepath, $filehash=false)
    {
        if (!$filehash) {
            $filehash = $this->generateHash($filepath);
        }

        if ($path = $this->getFileStoreManager()->getPath($filehash, pathinfo($filepath, PATHINFO_EXTENSION))) {
            return $path;
        } else {
            // File does not exist in Store
            $buffer = $this->retrieveBufferFromCache($filepath, $filehash);

            $path = $this->getFileStoreManager()->add($filehash, pathinfo($filepath, PATHINFO_EXTENSION), $buffer);
        }

        return $path;
    }

    /**
     * Retrieve Combined Css File
     * @return string;
     */
    private function retrieveCombinedCssStorePath()
    {
        // Generate Hash from File-Hashes;
        $filehash = md5(implode("", array_keys($this->getRegisteredFiles("css"))));

        if ($path = $this->getFileStoreManager()->getPath($filehash, "css")) {
            return $path;
        } else {
            $buffer = "";

            foreach ($this->getRegisteredFiles('css') as $filepath) {
                $buffer .= $this->retrieveBufferFromCache($filepath)."\n";
            }

            $path = $this->getFileStoreManager()->add($filehash, "css", $buffer);
        }

        return $path;
    }

    /**
     * Get File Store Manager
     * @return Swomp\Store\FileStore
     */
    private function getFileStoreManager()
    {
        return $this->fileStoreManager;
    }

    /**
     * Generate a Hash based on the File Modification Date and the Filename
     * @param string $filename
     * @return string
     */
    private function generateHash($filepath)
    {
        $fmodtime = filemtime($filepath);

        return md5($filepath.$fmodtime);
    }

    /**
     * Compress the given File
     * @param string $filename Full Path of Filename
     * @return string Compressed Data
     */
    private function compress($filepath, $filehash)
    {
        switch (pathinfo($filepath, PATHINFO_EXTENSION)) {
            case 'css':
                if ($path = $this->getFileStoreManager()->getPath($filehash, "css")) {
                    $buffer = file_get_contents($path);
                } else {
                    $cssCompressor = new CssCompressor();
                    $buffer = $cssCompressor->compress(file_get_contents($filepath));
                }
                break;

            case 'js':
                if ($path = $this->getFileStoreManager()->getPath($filehash, "js")) {
                    $buffer = file_get_contents($path);
                } else {
                    $jsCompressor = new JsCompressor();
                    $buffer = $jsCompressor->compress(file_get_contents($filepath));
                }
                break;
        }

        return $buffer;
    }

    /**
     * Find File in given Source Dirs
     * @param string $filepart
     * @return string Full Path of this file
     */
    private function findFileInSourceDir($filename)
    {
        // Already full Path
        if (is_file($filename)) {
            return realpath($filename);
        }

        foreach ($this->getSourceDirectory() as $sourceDir)
        {
            if (is_file($sourceDir.DIRECTORY_SEPARATOR.$filename)) {
                return realpath($sourceDir.DIRECTORY_SEPARATOR.$filename);
            }
        }

        throw new DirectoryException("Cannot find File $filename in any of our Source Directories");
    }

    /**
     * Class Autoloader
     * @param string $className Called classname
     */
    private function autoloader($className) {
        // Strip our Project Namespace
        $className = str_replace(__NAMESPACE__.'\\', '', $className);
        $filename = str_replace('\\', DIRECTORY_SEPARATOR, $className);

        require $filename . '.php';
    }

    /**
     * Output the given Buffer and send it to the Browser
     * @param string $buffer
     */
    private function outputCss($buffer)
    {
        ob_start("ob_gzhandler");
        header('Cache-Control: public');
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $this->cacheLifetime) . ' GMT');
        header("Content-type: text/css");

        echo $buffer;
        ob_flush();
    }

    /**
     * Output the given Buffer and send it to the Browser
     * @param string $buffer
     */
    private function outputJs($buffer)
    {
        ob_start("ob_gzhandler");
        header('Cache-Control: public');
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $this->cacheLifetime) . ' GMT');
        header("Content-type: application/x-javascript");

        echo $buffer;
        ob_flush();
    }

    /**
     * Register Files in all known Source Directories
     */
    private function registerFiles()
    {
        foreach ($this->sourceDirs as $directory) {
            $filelist = $this->getDirList($directory, true, 'files', '/.\.css$|.\.js$/');

            // Generate Hashes
            foreach ($filelist as $filename) {
                $hash = $this->generateHash($filename);

                $this->registeredFiles[$hash] = $filename;
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
}