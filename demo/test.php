<?php
(@include '../vendor/autoload.php') or die('Please use composer to install required packages.');

$engine		= "JsonFile";
$resource	= "cache.json";
$context	= NULL;

$factory	= new \CeusMedia\Cache\Factory();
$cache		= $factory->newStorage($engine, $resource, $context);


print "Current timestamp: " . time() . PHP_EOL;

if($cache->has("lastTest")){
	print "Reading 'lastTest' from cache..." . PHP_EOL;
	print "lastTest: ".$cache->get("lastTest") . PHP_EOL;
}
print "Writing 'lastTest' to cache..." . PHP_EOL;
$cache->set("lastTest", time());
