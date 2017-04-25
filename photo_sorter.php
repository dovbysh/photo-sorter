<?php
//print "<pre>";print_r($argv);print "</pre>";
require_once __DIR__ . '/vendor/autoload.php';

$source_dir = $argv[1];
$dest_dir = $argv[2];
$message = '';


$p = new dovbysh\PhotoSorter\PhotoSorter();
$p->simulate = false;
$p->dir_reader($source_dir, $dest_dir, '~.+~i', $message,
    ['~.+\.int~i', '~.+\.bnp~i', '~.+\.bin~i', '~.+\.inp~i', '~IndexerVolumeGuid~', '~WPSettings.dat~', '~SONYCARD.IND~']);
print "succsecc message:\n$message";
?>
