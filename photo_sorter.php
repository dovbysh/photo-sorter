<?php
//print "<pre>";print_r($argv);print "</pre>";

$source_dir = $argv[1];
$dest_dir = $argv[2];
$message = '';


function getMediaDate($file, $mediaInfo = '/usr/bin/mediainfo')
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

function dir_reader($source_dir, $function, $args, $filter, &$message, $exclude_filter = [])
{
    if ($handle = opendir($source_dir)) {
        /* This is the correct way to loop over the directory. */
        while (false !== ($file = readdir($handle))) {
            if ($file != '.' && $file != '..' && is_dir($source_dir . '/' . $file)) {
                dir_reader($source_dir . '/' . $file, $function, $args, $filter, $message, $exclude_filter);
            }
            if ($exclude_filter) {
                foreach ($exclude_filter as $exc) {
                    if (preg_match($exc, $file)) {
                        print "Skipped $file matched $exc\n";
                        continue 2;
                    }
                }
            }
            if ($file != '.' && $file != '..' && is_file($source_dir . '/' . $file) && preg_match($filter, $file)) {
                $m = $function($source_dir . '/' . $file, $args);
                if (!$m) {
                    print "^Skipped...\n";
                } else {
                    if ($m != -1) {
                        $message .= $m;
                    }
                }
            }
        }

        closedir($handle);
    }
}

function photo_copier()
{
    $ffile = func_get_arg(0);
    $dest_dir = func_get_arg(1);
    $exif = exif_read_data($ffile);
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
        $mediaDate = getMediaDate($ffile);
        if ($mediaDate !== null) {
            $date = date('Y-m-d', $mediaDate);
        }
    }
    if (empty($date)) {
        $date = date('Y-m-d', filemtime($ffile));
    }
    $dest_dir .= '/' . $date . '/';
    @mkdir($dest_dir, 0777, true);
    clearstatcache();
    $name = basename($ffile);
    if (is_dir($dest_dir)) {
        if (file_exists($dest_dir . $name) && filesize($dest_dir . $name) != filesize($ffile)) {
            print "File $ffile ($dest_dir" . "$name) exists and has different size\n";
            return 0;
        }
        if (file_exists($dest_dir . $name) && filesize($dest_dir . $name) == filesize($ffile)) {
            //print "File $dest_dir"."$name exists and has _same_ size\n";
            return -1;
        }
        if (!file_exists($dest_dir . $name)) {
            $m = copy($ffile, $dest_dir . $name);

            //system("copy " . my_windows($ffile) . " " . my_windows($dest_dir));
            clearstatcache();
            $m = file_exists($dest_dir . $name);
            if (!$m) {
                print "Failed to copy from $ffile to $dest_dir" . "$name\n";
                return 0;
            }
            $dt = filemtime($ffile);
            if ($dt !== false) {
                touch($dest_dir . $name, $dt);
            }
            return "File $name succsesfuly copied to " . $dest_dir . $name . "\n";
        }
        return 0;
    } else {
        print "Can't create directory $dest_dir\n";
        return 0;
    }
}

function my_windows($file)
{
    $p = [
        /**/
        '~\\/~',
        /**/
        '~/~',
    ];
    $r = [
        /**/
        '\\',
        /**/
        '\\',
    ];
    return preg_replace($p, $r, $file);
}

dir_reader($source_dir, 'photo_copier', $dest_dir, '~.+~i', $message,
    ['~.+\.int~i', '~.+\.bnp~i', '~.+\.bin~i', '~.+\.inp~i', '~IndexerVolumeGuid~', '~WPSettings.dat~', '~SONYCARD.IND~']);
print "succsecc message:\n$message";
?>
