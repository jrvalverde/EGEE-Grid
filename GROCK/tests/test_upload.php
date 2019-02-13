<?php

// we want to check if we can cd to a tmp dir and upload a
// file to cwd
// btw, we also check the works of link()

mkdir('/data/www/EMBnet/tmp/scratch');
chdir('/data/www/EMBnet/tmp/scratch');
$cwd = getcwd();
echo "<h1>test upload</h1>\n";
echo "<p>We are in $cwd</p>\n";
$uploadfile=getcwd() .'/'. basename($_FILES['upload']['name'][0]);
echo "<p>uploading to $uploadfile</p>\n";
move_uploaded_file($_FILES['upload']['tmp_name'][0], $uploadfile);
link($uploadfile, './tst');
echo '<pre>';
passthru('ls -l');
echo '</pre>';
