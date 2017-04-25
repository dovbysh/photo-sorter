<?php

namespace dovbysh\PhotoSorter;


class PhotoSorter
{
    const VERBOSE_MAX = 5;
    public $simulate = true;
    public $timeshiftRxp = '~(/TIMESHIFT/\d+/)~';
    public $verbose = 5;
    private $filter = '~.+~i';
    private $excludeFilter = ['~.+\.int~i', '~.+\.bnp~i', '~.+\.bin~i', '~.+\.inp~i', '~IndexerVolumeGuid~', '~WPSettings.dat~', '~SONYCARD.IND~'];
    private $messages;
    private $sourceDirectory;
    private $destinationDirectory;

    public function __construct(string $sourceDirectory = '', string $destinationDirectory = '')
    {
        $this->messages = '';
        $this->setDestinationDirectory($destinationDirectory);
        $this->setSourceDirectory($sourceDirectory);
    }

    /**
     * @param mixed $sourceDirectory
     */
    public function setSourceDirectory(string $sourceDirectory)
    {
        $this->sourceDirectory = $sourceDirectory;
    }

    /**
     * @param string $filter
     */
    public function setFilter(string $filter)
    {
        $this->filter = $filter;
    }

    /**
     * @param array $excludeFilter
     */
    public function setExcludeFilter(array $excludeFilter)
    {
        $this->excludeFilter = $excludeFilter;
    }

    /**
     * @return string
     */
    public function getMessages(): string
    {
        return $this->messages;
    }

    public function run()
    {
        $this->directoryIterate($this->sourceDirectory);
    }

    protected function directoryIterate($source_dir = '')
    {
        if ($handle = opendir($source_dir)) {
            /* This is the correct way to loop over the directory. */
            while (false !== ($file = readdir($handle))) {
                if ($file != '.' && $file != '..' && is_dir($source_dir . '/' . $file)) {
                    $this->directoryIterate($source_dir . '/' . $file);
                }
                if ($this->excludeFilter) {
                    foreach ($this->excludeFilter as $exc) {
                        if (preg_match($exc, $file)) {
                            print "Skipped $file matched $exc\n";
                            continue 2;
                        }
                    }
                }
                if ($file != '.' && $file != '..' && is_file($source_dir . '/' . $file) && preg_match($this->filter, $file)) {
                    $m = $this->photoCopy($source_dir . '/' . $file);
                    if (!$m) {
                        print "^Skipped...\n";
                    } else {
                        if ($m != -1) {
                            $this->messages .= $m;
                        }
                    }
                }
            }

            closedir($handle);
        }
    }

    protected function photoCopy($ffile)
    {
        @$exif = exif_read_data($ffile);
        $date = '';
        if ($exif !== false) {
            if (array_key_exists('DateTime', $exif) && strtotime($exif['DateTime']) > 0) {
                $date = date('Y-m-d', strtotime($exif['DateTime']));
            } else {
                if (array_key_exists('DateTimeOriginal', $exif) && strtotime($exif['DateTimeOriginal']) > 0) {
                    $date = date('Y-m-d', strtotime($exif['DateTimeOriginal']));
                } else {
                    if (array_key_exists('DateTimeDigitized', $exif) && strtotime($exif['DateTimeDigitized']) > 0) {
                        $date = date('Y-m-d', strtotime($exif['DateTimeDigitized']));
                    }
                }
            }
        }
        if (empty($date)) {
            var_dump($exif);
            $mediaDate = $this->getMediaDate($ffile);
            if ($mediaDate !== null) {
                $date = date('Y-m-d', $mediaDate);
            }
        }
        if (empty($date)) {
            $date = date('Y-m-d', filemtime($ffile));
        }
        $resultDestinationDirectory = $this->getDestinationDirectory() . '/' . $date . '/';
        if (preg_match($this->timeshiftRxp, $ffile, $timeshiftDirArr)) {
            $resultDestinationDirectory .= $timeshiftDirArr[1];
            if ($this->verbose >= static::VERBOSE_MAX) {
                print "Timeshift detected! dest_dir: $resultDestinationDirectory\n";
            }
        }
        @mkdir($resultDestinationDirectory, 0777, true);
        clearstatcache();
        $name = basename($ffile);
        if (is_dir($resultDestinationDirectory)) {
            if (file_exists($resultDestinationDirectory . $name) && filesize($resultDestinationDirectory . $name) != filesize($ffile)) {
                print "File $ffile ($resultDestinationDirectory" . "$name) exists and has different size\n";
                return 0;
            }
            if (file_exists($resultDestinationDirectory . $name) && filesize($resultDestinationDirectory . $name) == filesize($ffile)) {
                return -1;
            }
            if (!file_exists($resultDestinationDirectory . $name)) {
                if (!$this->simulate) {
                    copy($ffile, $resultDestinationDirectory . $name);
                }
                if (!$this->simulate) {
                    clearstatcache();
                    $m = file_exists($resultDestinationDirectory . $name);
                } else {
                    $m = true;
                }
                if (!$m) {
                    print "Failed to copy from $ffile to $resultDestinationDirectory" . "$name\n";
                    return 0;
                }
                if (!$this->simulate) {
                    $dt = filemtime($ffile);
                    if ($dt !== false) {
                        touch($resultDestinationDirectory . $name, $dt);
                    }
                }
                $message = ($this->simulate ? '[simulate] '
                        : '') . "File $name succsesfuly copied to " . $resultDestinationDirectory . $name . "\n";
                if ($this->verbose >= static::VERBOSE_MAX) {
                    print $message;
                }
                return $message;
            }
            return 0;
        } else {
            print "Can't create directory $resultDestinationDirectory\n";
            return 0;
        }
    }

    protected function getMediaDate($file, $mediaInfo = '/usr/bin/mediainfo')
    {
        $output = [];
        exec($mediaInfo . ' ' . $file, $output);
        $res = null;
        if ($output) {
            foreach ($output as $o) {
                $pairs = preg_split('~\:~', $o, 2);
                if (!empty($pairs[0]) && !empty($pairs[1]) && preg_match('~Tagged date~i', $pairs[0]) && strtotime($pairs[1])) {
                    $res = strtotime($pairs[1]);
                    break;
                }
            }
        }
        return $res;
    }

    /**
     * @return string
     */
    public function getDestinationDirectory(): string
    {
        return $this->destinationDirectory;
    }

    /**
     * @param mixed $destinationDirectory
     */
    public function setDestinationDirectory(string $destinationDirectory)
    {
        $this->destinationDirectory = $destinationDirectory;
    }
}