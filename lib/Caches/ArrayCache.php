<?php
/**
 * Array Cache
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

/**
 * Array Cache
 */
class ArrayCache implements CacheInterface
{
    private $data = array();

    /**
     * @see Swomp\Caches.CacheInterface::contains()
     */
    public function contains($id)
    {
        return isset($this->data[$id]);
    }

    /**
     * @see Swomp\Caches.CacheInterface::fetch()
     */
    public function fetch($id)
    {
        if ($this->contains($id)) {
            return $this->data[$id];
        }

        return false;
    }

    /**
     * @see Swomp\Caches.CacheInterface::save()
     */
    public function save($id, $data, $lifetime=0)
    {
        $this->data[$id] = $data;

        return true;
    }

    /**
     * @see Swomp\Caches.CacheInterface::delete()
     */
    public function delete($id)
    {
        unset($this->data[$id]);

        return true;
    }
}