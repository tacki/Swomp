<?php
// Swomp include
require_once("../lib/Swomp.php");

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
$allCss   = $swomp->getCombinedStorePath('css');
$allJs    = $swomp->getCombinedStorePath('js');
// Other Possibilities:
//$noBtcode = $swomp->getCombinedStorePath('css', null, array('btcode.css'));
//$myAppCSS = $swomp->getCombinedStorePath('css', array('layout.css', 'index.css', 'main.css'));

?>
<!DOCTYPE HTML>
<html>
<head>
    <title>Swomp Test</title>
    <meta charset='UTF-8' />
    <!-- combined css file -->
    <link rel='stylesheet' href='<?php echo $allCss ?>'>
    <!-- combined js file -->
    <script type='text/javascript' src='<?php echo $allJs ?>'></script>
</head>

<body>
see Source...
</body>

</html>
