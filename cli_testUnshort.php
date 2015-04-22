<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require './inc/setup.inc.php';
require './inc/myPDOConnConfig.inc.php';
require './classes/myPDOConn.Class.php';
require './classes/ParseContent.Class.php';




try {
    $pdoConn = myPDOConn::getInstance();
    $objPaser = new ParseContent($pdoConn);

    $strURL = $objPaser->expendShortURLBycURL((string)$argv[1]);
    echo $strURL . PHP_EOL;
    echo $objPaser->getDomainByURL($strURL);
    
} catch (Exception $ex) {
    echo $ex->getMessage();
}