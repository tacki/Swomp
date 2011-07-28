<?php
// Swomp include
require_once("../lib/Swomp.php");

$request = basename($_SERVER['REQUEST_URI']);

if (isset($_GET['type'])) {
    $type = $_GET['type'];
} elseif (isset($_GET['file'])) {
    $file = $_GET['file'];
} elseif (strstr(basename(__FILE__), $request) === false) {
    if ($request === 'css' || $request === 'js') {
        $type = $request;
    } else {
        $file = $request;
    }
}

// Swomp Initialization
$swomp = new Swomp\Main();

// Caches
// ArrayCache is nearly useless because its cleared after each Pagecall (default)
$swomp->setCacheManager(new Swomp\Caches\ArrayCache);
// ApcCache is pretty fast and perfect for small to mid Applications
//$swomp->setCacheManager(new Swomp\Caches\ApcCache);
// Memcache is the choice for big to huge Applications
//$swomp->setCacheManager(new Swomp\Caches\MemcacheCache());

// Add Source Directory where our static files are
$swomp->addSourceDirectory("css");
$swomp->addSourceDirectory("js");

// Get Path of an combined css or js file (includes source of the
// Files in all Source Directories)
//$allCss   = $swomp->getCombinedStorePath('css');
//$allJs    = $swomp->getCombinedStorePath('js');
// Other Possibilities:
//$noBtcode = $swomp->getCombinedStorePath('css', null, array('btcode.css'));
//$myAppCSS = $swomp->getCombinedStorePath('css', array('layout.css', 'index.css', 'main.css'));

if (isset($type)) {
    $swomp->outputCombined($type);
} elseif (isset($file)) {
    $swomp->output($file);
}