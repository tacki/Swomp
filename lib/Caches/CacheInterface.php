<?php

namespace Swomp\Caches;

interface CacheInterface
{
    /**
     * Check if the Cache contains a specific Cache ID
     * @param string $id Cache ID
     * @return bool true if the Cache ID is found, else false
     */
    public function contains($id);

    /**
     * Fetch the content of a given Cache ID
     * @param string $id Cache ID
     * @return mixed Cache Data, false if not found
     */
    public function fetch($id);

    /**
     * Save Data to the Cache
     * @param string $id Cache ID
     * @param mixed $data Cache Data
     * @param int $lifetime Lifetime in seconds (0=infinite)
     * @return bool true if successful, else false
     */
    public function save($id, $data, $lifetime=0);

    /**
     * Delete Entry from the Cache
     * @param string $id Cache ID
     * @return bool true if the Cache ID was successfully deleted, else false
     */
    public function delete($id);
}