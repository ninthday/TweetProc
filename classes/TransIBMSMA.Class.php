<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of TransIBMSMA
 *
 * @author jeffy
 */
class TransIBMSMA {
    private $pdoDB = NULL;
    private $dbh = NULL;

    public function __construct(myPDOConn $pdoConn) {
        $this->pdoDB = $pdoConn;
        $this->dbh = $this->pdoDB->dbh;
    }


    public function saveTransJsonByLang($strLang){
//        header('Content-Type: text/html; charset=utf-8');
//        mb_internal_encoding('UTF-8');
        $eol = array("\n","\r\n"); 
        $fileOut = 'output/PE_' . $strLang . '.json';
        $sql_get = 'SELECT `data_id`, `data_text`, `TWTime`, `data_from_user_name`, `data_from_user`  FROM `PresidentialElection` WHERE `lang_detection` = \'' . $strLang . '\'';
        $stmt = $this->dbh->prepare($sql_get);
        $stmt->execute();
        $aryDoclist = array();
        if($strLang == 'zh'){
            $ibmLang = 'Chinese - Simplified';
        }elseif ($strLang == 'zh-TW') {
            $ibmLang = 'Chinese - Traditional';
        }
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
            $aryDoc = array(
                "Id" => $row['data_id'],
                "TextHtml" => urlencode(str_replace("\"", "", str_replace($eol, ' ', $row['data_text']))),
                "SubjectHtml" => '',
                "DocumentType" => 'Twitter',
                "Url" => 'https://twitter.com/' . $row['data_from_user'] . '/status/' . $row['data_id'],
                "Published" => $row['TWTime'],
                "SiteUrl" => 'https://twitter.com/',
                "SiteName" => 'Twitter',
                "Language" => $ibmLang
            );
            array_push($aryDoclist, $aryDoc);
        }
        $aryOutJSON = array(
            "documents" => $aryDoclist
        );
        
        $resultJSON = json_encode($aryOutJSON);
        file_put_contents($fileOut, stripslashes(urldecode($resultJSON)));
    }
    
    public function __destruct() {
        $this->pdoDB = NULL;
    }
}
