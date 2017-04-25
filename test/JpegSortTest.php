<?php

use dovbysh\PhotoSorter\PhotoSorter;
use PHPUnit\Framework\TestCase;

class JpegSortTest extends TestCase
{
    private $rootPath;
    private $sourceDir;

    private $destinationDir;


    private $exiftool = '/usr/bin/exiftool';

    private $sA = '';
    private $sDate1 = '';

    private $now = 0;
    private $date1 = 0;

    /**
     * set up test environmemt
     */
    public function setUp()
    {
        $this->rootPath = sys_get_temp_dir() . '/' . __CLASS__ . mt_rand();
        $this->sourceDir = $this->rootPath . '/source';
        $this->destinationDir = $this->rootPath . '/destinationDir';
        mkdir($this->sourceDir, 0777, true);
        mkdir($this->destinationDir, 0777, true);

        $this->now = time();
        $this->date1 = mktime(mt_rand(0, 24), mt_rand(0, 60), mt_rand(0, 60), mt_rand(1, 12), mt_rand(0, 32), mt_rand(2000, 2017));
        $this->sDate1 = $this->sourceDir . '/' . mt_rand() . '/' . mt_rand() . '/' . mt_rand();
        mkdir($this->sDate1, 0777, true);
        $this->sDate1 .= mt_rand() . '.jpg';

        $this->sA = $this->sourceDir . '/a.jpg';
        $im = imagecreatetruecolor(20, 20);
        imagefill($im, 0, 0, 255);
        imagejpeg($im, $this->sA);
        $dt = date('Y:m:d H:i:s', $this->now);
        `{$this->exiftool} -tagsfromfile /home/dovbysh/photo_sorter_test/IMG_0081.JPG -exif {$this->sA}`;
        `{$this->exiftool} -alldates="$dt" {$this->sA}`;
        `rm -f {$this->sA}_original`;

        copy($this->sA, $this->sDate1);
        $dt = date('Y:m:d H:i:s', $this->date1);
        `{$this->exiftool} -alldates="$dt" {$this->sDate1}`;
        `rm -f {$this->sDate1}_original`;


    }

    public function tearDown()
    {
        `rm -rf {$this->rootPath}`;
        parent::tearDown();
    }

    public function testMoveA()
    {
        $p = new PhotoSorter();
        $p->simulate = false;
        $p->verbose = 0;
        $message = '';
        $p->dir_reader($this->sourceDir, $this->destinationDir, '~.+~i', $message,
            ['~.+\.int~i', '~.+\.bnp~i', '~.+\.bin~i', '~.+\.inp~i', '~IndexerVolumeGuid~', '~WPSettings.dat~', '~SONYCARD.IND~']);
        $this->assertFileExists($this->destinationDir . '/' . date('Y-m-d', $this->now) . '/a.jpg');
        $this->assertFileExists($this->destinationDir . '/' . date('Y-m-d', $this->date1) . '/' . basename($this->sDate1));
    }
}