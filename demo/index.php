<?php
$start = microtime(true);
require_once("../lib/Swomp.php");
$swomp = new Swomp\Swomp();

$swomp->addSourceDirectory("css");
$swomp->addSourceDirectory("js");
$indexCss = $swomp->getStorePath("index.css");
$allCss   = $swomp->getCombinedCssStorePath();
?>
<!DOCTYPE HTML>
<html>
<head>
    <title>Swomp Test</title>
    <meta charset='UTF-8' />
    <link rel='stylesheet' href='<?php echo $indexCss ?>'>
    <link rel='stylesheet' href='<?php echo $allCss ?>'>
</head>

<body>
<div id='important'><a href="https://github.com/tacki/Swomp">Swomp Github</a></div>


<?php
$time = (microtime(true) - $start)*1000;
echo "Page build in " . $time . " miliseconds";
?>

</body>

</html>
