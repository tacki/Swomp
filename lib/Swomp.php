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
use Swomp\Exceptions\SwompException;
use Swomp\Filters\FilterInterface;
use Swomp\Store\Catalog;

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
    private $ressources = array();

    /**
     * @var array
     */
    private $filters = array();

    /**
     * @var int
     */
    private $cacheLifetime = 86400; // one Day

    /**
     * @var array
     */
    private $catalog;

    /**
     * Swomp Constructor
     */
    public function __construct()
    {
        // Register Autoloading
        spl_autoload_register(array($this, 'autoloader'));

        // Default Cache Manager
        $this->cacheManager = new ArrayCache;

        // FileStore Catalog initialization
        $this->fileStoreDirectory = realpath($this->fileStoreDirectory);
        $this->catalog            = new Catalog($this->fileStoreDirectory);

        // Add default Filters
        $this->addFilter("CssCompressor");
        $this->addFilter("JsCompressor");
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
     * @throws SwompException
     */
    public function setSourceDirectory($path)
    {
        if (!is_array($path)) {
            $path = array($path);
        }

        foreach ($path as $entry) {
            if (!is_readable($entry)) {
                throw new SwompException("Source Directory $entry is not readable");
            }
        }

        $this->sourceDirs = $path;
    }

    /**
     * Add a source Directory
     * @param string|array $path
     * @throws Swomp\Exceptions\SwompException
     */
    public function addSourceDirectory($path)
    {
        if (!is_array($path)) {
            $path = array($path);
        }

        foreach ($path as $entry) {
            if (!is_readable($entry)) {
                throw new SwompException("Source Directory $entry is not readable");
            }
        }

        $this->sourceDirs = array_merge($this->sourceDirs, $path);
    }

    /**
     * Add a Filter
     * @param string|FilterInterface $filter Filtername or instance of FilterInterface
     * @param int $priority Lower Priority = earlier call
     * @throws SwompException
     */
    public function addFilter($filter, $priority=50)
    {
        // choose the right priority
        while (isset($this->filters[$priority])) {
            $priority++;
        }

        if ($filter instanceof FilterInterface) {
            $this->filters[$priority] = $filter;

            ksort($this->filters);
        } else {
            $filtername = ucfirst($filter);
            $classname  = "Swomp\\Filters\\$filtername";

            if (class_exists($classname)) {
                $this->filters[$priority] = new $classname;

                ksort($this->filters);
            } else {
                throw new SwompException("Cannot find Filter $filtername");
            }
        }
    }

    /**
     * Get Filters
     * @return array
     */
    public function getFilters()
    {
        return $this->filters;
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
        $this->fileStoreDirectory = realpath($fileStoreDirectory);

        $this->getCatalog()->setFileStoreDirectory($this->fileStoreDirectory);
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
     * Get Catalog
     * @return Swomp\Store\Catalog
     */
    public function getCatalog()
    {
        return $this->catalog;
    }

    /**
     * Set Catalog
     * @param Swomp\Store\Catalog $catalog
     */
    public function setCatalog(Catalog $catalog)
    {
        $this->catalog = $catalog;
    }

    /**
     * Return a List of Ressources from the Source Directories, known to Swomp
     * @param $type css|js
     * @return array
     */
    public function getRessources($type=false)
    {
        if (!count($this->ressources)) {
            $this->registerRessources();
        }

        if (!$type) {
            return $this->ressources;
        }

        $result = array();

        foreach ($this->ressources as $ressource) {
            if ($ressource->getType() == $type) {
                $result[] = $ressource;
            }
        }

        return $result;
    }

    /**
     * Get the Compiled Path of the given Source-Filename inside our Store
     * @param string $filename
     * @return string
     */
    public function getStorePath($filename)
    {
        $ressource = $this->findRessourceByFilename($filename);

        // Load all needed Data into the Ressource
        $this->populateRessource($ressource);

        return $ressource->getStorePath();
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

        foreach ($this->getRessources($type) as $ressource) {
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

            // Load all needed Data into the Ressource
            $this->populateRessource($ressource);

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
     * Output a single File
     * @param string $filename Name of a File from our Source-Directories
     */
    public function output($filename)
    {
        ob_start("ob_gzhandler");

        $ressource = $this->findRessourceByFilename($filename);

        switch ($ressource->getType()) {
            case "css":
                header("Content-type: text/css");
                break;

            case "js":
                header ("content-type: text/javascript");
                break;
        }
        header ("cache-control: must-revalidate; max-age: ".(int)$this->getCacheLifetime());
        header ("expires: " . gmdate ("D, d M Y H:i:s", time() + (int)$this->getCacheLifetime()) . " GMT");

        // Load all needed Data into the Ressource
        $this->populateRessource($ressource);

        echo $ressource->getContent();

        ob_flush();
    }

    /**
     * Output Combined File
     * @param string $type Ressource Type (e.g. 'css')
     * @param array $includes Files to include
     * @param array $excludes Files to exclude
     */
    public function outputCombined($type, array $includes=null, array $excludes=null)
    {
        ob_start("ob_gzhandler");

        $content = "";

        foreach ($this->getRessources($type) as $ressource) {
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

            // Load all needed Data into the Ressource
            $this->populateRessource($ressource);

            $content .= $ressource->getContent() . "\n";

        }

        switch ($type) {
            case "css":
                header("Content-type: text/css");
                break;

            case "js":
                header ("content-type: text/javascript");
                break;
        }
        header ("cache-control: must-revalidate; max-age: ".(int)$this->getCacheLifetime());
        header ("expires: " . gmdate ("D, d M Y H:i:s", time() + (int)$this->getCacheLifetime()) . " GMT");

        echo $content;

        ob_flush();
    }

    /**
     * Clear the Store and remove all .css and .js in it
     */
    public function clearStore()
    {
        // Remove Cache Files known to the Catalog
        foreach ($this->getCatalog()->getRegister() as $entry) {
            if (is_file($entry['storepath'])) {
                unlink($entry['storepath']);
            }
        }

        // Remove Catalog File
        if (is_file($this->getCatalog()->getCatalogPath())) {
            unlink($this->getCatalog()->getCatalogPath());
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

        // Apply Filter
        $this->applyFilter($ressource);

        // Write Ressource to Store
        $ressource->writeToStore();

        // Add Ressource to Cache
        $this->getCacheManager()->save($ressource->getHash(), $ressource, $this->cacheLifetime);
    }

    /**
     * Apply all registered Filters
     * @param Swomp\Elements\Ressource $ressource File Ressource
     */
    private function applyFilter(Ressource $ressource)
    {
        foreach ($this->getFilters() as $filter) {
            if (in_array($ressource->getType(), $filter->getTypes())) {
                $buffer = $filter->apply($ressource->getContent());
                $ressource->setContent($buffer);
            }
        }
    }

    /**
     * Populate Ressource and fill it with all needed Data
     * @param Swomp\Elements\Ressource $ressource
     */
    private function populateRessource($ressource)
    {
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
    }

    /**
     * Find File in given Source Dirs
     * @param string $filename
     * @throws SwompException
     * @return Swomp\Elements\Ressource File Ressource
     */
    private function findRessourceByFilename($filename)
    {
        foreach ($this->getRessources() as $ressource) {
            if ($filename === $ressource->getFilename()) {
                // Filename found

                return $ressource;
            } elseif ($filename === $ressource->getFilepath()) {
                // Filename was a Full Path (and found)

                return $ressource;
            }

        }

        throw new SwompException("Cannot find File $filename in any of our Source Directories");
    }

    /**
     * Register Ressources in all known Source Directories
     */
    private function registerRessources()
    {
        foreach ($this->sourceDirs as $directory) {
            $files = $this->getDirList($directory, true, 'files', '/.\.css$|.\.js$/');

            foreach ($files as $filepath) {
                // Create Ressource Object
                $ressource = new Ressource($this->fileStoreDirectory);
                $ressource->setFilepath($filepath);
                $hash = $ressource->generateHash();
                $ressource->setHash($hash);

                $this->ressources[] = $ressource;
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