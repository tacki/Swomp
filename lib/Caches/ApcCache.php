<?php
/**
 * APC Cache
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
 * APC Cache
 */
class ApcCache implements CacheInterface
{
    /**
     * @see Swomp\Caches.CacheInterface::contains()
     */
    public function contains($id)
    {
        return apc_exists($id);
    }

    /**
     * @see Swomp\Caches.CacheInterface::fetch()
     */
    public function fetch($id)
    {
        return apc_fetch($id);
    }

    /**
     * @see Swomp\Caches.CacheInterface::save()
     */
    public function save($id, $data, $lifetime=0)
    {
        return apc_store($id, $data, $lifetime);
    }

    /**
     * @see Swomp\Caches.CacheInterface::delete()
     */
    public function delete($id)
    {
        return apc_delete($id);
    }
}