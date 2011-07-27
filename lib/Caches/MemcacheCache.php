<?php
/**
 * Memcache Cache
 *
 * @author Markus Schlegel <g42@gmx.net>
 * @copyright Copyright (C) 2011 Markus Schlegel
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

/**
 * Namespaces
 */
namespace Swomp\Caches;
use Swomp\Caches\CacheInterface;
use Memcache;

/**
 * Memcache Cache
 */
class MemcacheCache implements CacheInterface
{
    /**
     * Memcache Object
     * @var Memcache;
     */
    private $memcache;


    /**
     * Initialize with Memcache Object
     * @param Memcache $memcache
     */
    public function __construct($host='localhost', $port=11211)
    {
        $this->memcache = new Memcache;
        $this->memcache->connect($host, $port);
    }

    /**
     * @see Swomp\Caches.CacheInterface::contains()
     */
    public function contains($id)
    {
        if ($this->fetch($id)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @see Swomp\Caches.CacheInterface::fetch()
     */
    public function fetch($id)
    {
        return $this->memcache->get($id);
    }

    /**
     * @see Swomp\Caches.CacheInterface::save()
     */
    public function save($id, $data, $lifetime=0)
    {
        return $this->memcache->set($id, $data, 0, $lifetime);
    }

    /**
     * @see Swomp\Caches.CacheInterface::delete()
     */
    public function delete($id)
    {
        return $this->memcache->delete($id);
    }
}