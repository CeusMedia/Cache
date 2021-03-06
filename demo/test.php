<?php
(@include '../vendor/autoload.php') or die('Please use composer to install required packages.' . PHP_EOL);

$engine		= "JsonFile";
$resource	= "cache.json";
$context	= NULL;

$factory	= new \CeusMedia\Cache\Factory();
$cache		= $factory->newStorage($engine, $resource, $context, 10);

//$cache->flush();

print "Current timestamp: " . time() . PHP_EOL;

print "Index:" . PHP_EOL;
foreach($cache->index() as $key)
	print '- ' . $key . PHP_EOL;
print PHP_EOL;

if($cache->has("lastTest")){
	print "Reading 'lastTest' from cache..." . PHP_EOL;
	print "lastTest: ".$cache->get("lastTest") . PHP_EOL;
}
else{
	print "Writing 'lastTest' to cache..." . PHP_EOL;
	$cache->set("lastTest", time());
}
