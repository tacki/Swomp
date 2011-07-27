<?php
// Swomp include
require_once("../lib/Swomp.php");

// Swomp
$swomp = new Swomp\Main();

// Caches
//$swomp->setCacheManager(new Swomp\Caches\ApcCache);
$swomp->setCacheManager(new Swomp\Caches\ApcCache);

$swomp->addSourceDirectory("css");
$swomp->addSourceDirectory("js");

$allCss   = $swomp->getCombinedStorePath('css');
$allJs    = $swomp->getCombinedStorePath('js');
?>
<!DOCTYPE HTML>
<html>
<head>
    <title>Swomp Test</title>
    <meta charset='UTF-8' />
    <link rel='stylesheet' href='<?php echo $allCss ?>'>
    <script type='text/javascript' src='<?php echo $allJs ?>'></script>
</head>

<body>
<div id='important'><a href='https://github.com/tacki/Swomp'>Swomp Github</a></div>
<br/>
Before Json encoding: <div id='before'></div><br/>
During Json encoding: <div id='json'></div><br/>
After Json decoding: <div id='after'></div><br/>
<script>
    var array  = new Array('string', 1234, {foo: 'bar'});
    var json   = json_encode(array);
    var result = json_decode(json);

    document.getElementById('before').innerHTML = array;
    document.getElementById('json').innerHTML = json;
    document.getElementById('after').innerHTML = result;
</script>
</body>

</html>
