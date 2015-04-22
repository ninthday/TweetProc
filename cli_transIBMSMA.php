<?php

require './inc/setup.inc.php';
//require './inc/ffConfig.inc.php';
require './classes/myPDOConn.Class.php';
require './classes/TransIBMSMA.Class.php';

$strDBName = '';

try {
    $pdoConn = myPDOConn::getInstance('ffConfig.inc.php');
    $objTransIBM = new TransIBMSMA($pdoConn);

    $objTransIBM->saveTransJsonByLang((string)$argv[1]);

    
//    $objTransIBM->saveTransJson('PE_zh.json');
    
} catch (Exception $ex) {
    echo $ex->getMessage();
}
