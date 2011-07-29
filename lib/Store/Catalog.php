<?php
/**
 * Catalog Class
 *
 * @author Markus Schlegel <g42@gmx.net>
 * @copyright Copyright (C) 2011 Markus Schlegel
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

/**
 * Namespaces
 */
namespace Swomp\Store;
use Swomp\Elements\Ressource;

/**
 * Catalog Class
 */
class Catalog
{
    /**
     * @var array
     */
    private $register = array();

    /**
     * @var string
     */
    private $fileStoreDirectory;

    /**
     * @var string
     */
    private $catalogName = "catalog.json";

    /**
     * @var string
     */
    private $initJson = "";

    /**
     * Catalog Constructor
     * @param string $fileStoreDirectory
     */
    public function __construct($fileStoreDirectory)
    {
        $this->fileStoreDirectory = $fileStoreDirectory;

        // Initialize Catalog File
        $this->initCatalogFile();
    }

    /**
     * Catalog Destructor
     */
    public function __destruct()
    {
        if (count($this->register)) {
            $buffer = json_encode($this->register);

            if ($buffer != $this->initJson) {
                // only write if needed
                file_put_contents($this->getCatalogPath(), $buffer);
            }
        }
    }
    /**
     * Get Register
     * @return array
     */
    public function getRegister()
    {
        return $this->register;
    }

    /**
     * Set Register
     * @param array $register
     */
    public function setRegister(array $register)
    {
        $this->register = $register;
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
        if ($fileStoreDirectory != $this->fileStoreDirectory) {
            // Re-Initialize Catalog File
            $this->initCatalogFile();
        }

        $this->fileStoreDirectory = $fileStoreDirectory;
    }

    /**
     * Get Catalog Name
     * @return string
     */
    public function getCatalogName()
    {
        return $this->catalogName;
    }

    /**
     * Set Catalog Name
     * @param string $catalogName
     */
    public function setCatalogName($catalogName)
    {
        if ($catalogName != $this->catalogName) {
            // Re-Initialize Catalog File
            $this->initCatalogFile();
        }

        $this->catalogName = $catalogName;
    }

    /**
     * Get full Catalog Path
     * @return string
     */
    public function getCatalogPath()
    {
        return $this->getFileStoreDirectory() . DIRECTORY_SEPARATOR . $this->getCatalogName();
    }

    /**
    * Add Entry to Catalog and remove any existing with the same filepath
    * @param Swomp\Elements\Ressource $ressource File Ressource
    */
    public function add(Ressource $ressource)
    {
        if (($hash = $this->contains($ressource)) && $hash != $ressource->getHash()) {
            // $hash = md5-hash of the old/outdated ressource
            $this->removeFromCatalog($ressource);
            // Remove the *OLD* ressource in the filestore
            $ressource->getFileStore()->delete($hash, $ressource->getType());
        }

        $this->register[$ressource->getHash()] = array( "filepath" => $ressource->getFilePath(),
                                                        "storepath" => $ressource->getStorePath(),
        );
    }

    /**
    * Check if an Entry is in our Catalog
    * @param Swomp\Elements\Ressource $ressource File Ressource
    * @return boolean the hash of the found ressource, else false
    */
    public function contains(Ressource $ressource)
    {
        foreach ($this->getRegister() as $hash => $entry) {
            if ($entry['filepath'] == $ressource->getFilePath() && $entry['storepath'] == $ressource->getStorePath()) {
                return $hash;
            }
        }

        return false;
    }

    /**
    * Remove Entry from Catalog
    * @param Swomp\Elements\Ressource $ressource File Ressource
    */
    public function remove(Ressource $ressource)
    {
        if ($hash = $this->contains($ressource)) {
            unset($this->register[$hash]);
        }
    }

    /**
     * Initialize Catalog File
     */
    private function initCatalogFile()
    {
        if (is_file($this->getFileStoreDirectory()."/".$this->getCatalogName())) {
            $buffer = file_get_contents($this->getCatalogPath());
            // remember initial content
            $this->initJson = $buffer;
            $this->register = json_decode($buffer, true);
        }
    }
}