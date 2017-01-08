<?php
//print "<pre>";print_r($argv);print "</pre>";
require_once __DIR__ . '/PhotoSorter.php';

$source_dir = $argv[1];
$dest_dir = $argv[2];
$message = '';


$p = new \PhotoSorter\PhotoSorter();
$p->dir_reader($source_dir, 'photo_copier', $dest_dir, '~.+~i', $message,
    ['~.+\.int~i', '~.+\.bnp~i', '~.+\.bin~i', '~.+\.inp~i', '~IndexerVolumeGuid~', '~WPSettings.dat~', '~SONYCARD.IND~']);
print "succsecc message:\n$message";
?>
