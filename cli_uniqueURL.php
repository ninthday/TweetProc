<?php

require './inc/setup.inc.php';
require './classes/myPDOConn.Class.php';
require './classes/UnshortrenURL.Class.php';
//$aryDB = array(1,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20);
try {
    $pdoConn = \Floodfire\myPDOConn::getInstance('myPDOConnConfig.inc.php');
    $objUnshorten = new \Floodfire\TwitterProcess\UnshortrenURL($pdoConn);
    $objUnshorten->initUniqueURLTable();
    $objUnshorten->uniqueShortURL();
    
} catch (Exception $ex) {
    echo $ex->getMessage();
}
