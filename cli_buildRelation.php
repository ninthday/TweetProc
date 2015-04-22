<?php
/**
 * 短網址還原，由 command line 操作
 * 
 * @author ninthday <jeffy@ninthday.info>
 * @version 1.0
 * @copyright (c) 2014, Jeffy Shih
 */
require './inc/setup.inc.php';
require './classes/myPDOConn.Class.php';
require './classes/NetworkAnalysis.Class.php';
// 由內容抽取網址後的儲存資料表前綴詞
$strDBPrefix = 'PE';
// 來源資料表名稱
$strSourceDB = 'PresidentialElection';

try {
    $pdoConn = \Floodfire\myPDOConn::getInstance('myPDOConnConfig.inc.php');
    $objAnalysis = new \Floodfire\TwitterProcess\NetworkAnalysis($pdoConn);
    // 設定資料表前綴詞
    $objAnalysis->setDBPrefixName($strDBPrefix);
    // 初始化資料表
    $objAnalysis->initRelationTable();
    $objAnalysis->buildUserRelation($strSourceDB);
} catch (Exception $ex) {
    echo $ex->getMessage();
}
