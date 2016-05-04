<?php

(php_sapi_name() === "cli") OR exit("Script access is only allowed from command line");

require '/var/www/vendor/autoload.php';

/*
 * Function Definition
 */

function get_list_filename() {
    exec("find /root/swrveexternal-alegrium/ -path *app-2618*.log.gz", $output); // find all log.gz
    asort($output);
    $output = array_values($output);
    return $output;
}

/*
 * Configuration, moved to swrve.php
 */

//$db_host = 'mongodb_server';
//$db_user = 'user';
//$db_pass = 'password';
$db_name = 'swrve';

include "/var/www/swrve.php";

/*
 * Main Script
 */

$connection_string = "mongodb://"
        . $db_user . ":"
        . $db_pass . "@"
        . $db_host . "/"
        . $db_name;

$client = new MongoDB\Client($connection_string); // create object client 
$db = $client->$db_name; // select database

$list_filename = get_list_filename();
foreach ($list_filename as $filename) {
    // 1. Cek status namafile di database
    $status = false;
    $document = $db->swrvelog->findOne(['filename' => $filename, 'app' => '2618']);
    if (is_object($document)) {
        $status = true;
    }

    // 2. Import ke mongodb jika belum pernah di import
    if ($status == false) {
        exec("gunzip -k -f --verbose $filename");
        $filelog = str_replace("log.gz", "log", $filename);
        exec("mongoimport --host $db_host --db $db_name --collection swrveexternal_2618 --file $filelog --username $db_user --password $db_pass");
        exec("rm $filelog");
        $db->swrvelog->insertOne(array('filename' => $filename, 'app' => '2618'));
    }
}