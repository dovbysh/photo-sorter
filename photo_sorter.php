<?php
//print "<pre>";print_r($argv);print "</pre>";
require_once __DIR__ . '/vendor/autoload.php';

$sourceDirectory = $argv[1];
$destinationDirectory = $argv[2];


$p = new dovbysh\PhotoSorter\PhotoSorter($sourceDirectory, $destinationDirectory);
$p->simulate = false;
$p->run();
print "succsecc message:\n".$p->getMessages();
