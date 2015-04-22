<?php
/**
 * Description of Tweets
 *
 * @author jeffy
 */
namespace Floodfire\TwitterProcess;
class TweetsProc {

    private $pdoDB = NULL;
    private $dbh = NULL;
    private $objParse = NULL;

    public function __construct(myPDOConn $pdoConn) {
        $this->pdoDB = $pdoConn;
        $this->dbh = $this->pdoDB->dbh;

        require _APP_PATH . 'classes/ParseContent.Class.php';
        $this->objParse = new ParseContent($this->pdoDB);
    }

    public function getTweets() {
        $sql_get = 'SELECT `data_text`, `TWTime` FROM `PresidentialElection` LIMIT 0,30';
        $stmt = $this->dbh->prepare($sql_get);
        $stmt->execute();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo $row['data_text'], '<br>';
        }
    }

    /**
     *  初始化建立截取短網址後的儲存資料庫
     * @throws Exception
     */
    public function initURLTable() {
        $sql_init = "CREATE TABLE IF NOT EXISTS `URLinTweets` (
            `URTId` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `FromTb` tinyint(3) unsigned NOT NULL COMMENT 'TableNo',
            `TweetId` bigint(20) unsigned NOT NULL,
            `ShortenURL` varchar(255) NOT NULL COMMENT '截取的短網址',
            `regularURL` text COMMENT '原來的網址',
            `domainURL` text COMMENT 'only domain',
            PRIMARY KEY (`URTId`),
            KEY `TweetId` (`TweetId`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";
        try {
            $stmt = $this->dbh->prepare($sql_init);
            $stmt->execute();
        } catch (PDOException $exc) {
            throw new Exception($exc->getMessage());
        }
    }

    public function extraURLinTweet($intDBNo = 0) {

        $strDBName = 'z_' . (string) $intDBNo;
        $sql_get = 'SELECT `text`, `id` FROM `' . $strDBName . '`';
        try {
            $stmt = $this->dbh->prepare($sql_get);
            $stmt->bindParam(':dbName', $strDBName, PDO::PARAM_STR);
            $stmt->execute();
        } catch (PDOException $exc) {
            throw new Exception($exc->getMessage());
        }

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $aryURLs = $this->objParse->getURLContent($row['text']);
            $this->saveURLs($intDBNo, $row['id'], $aryURLs);
            echo $row['id'] . ' ----> ' . count($aryURLs) . PHP_EOL;
        }
    }
    
    public function extraURLinTweetByDBName($strDBName) {

        $sql_get = 'SELECT `text`, `id` FROM `' . $strDBName . '`';
        try {
            $stmt = $this->dbh->prepare($sql_get);
            $stmt->bindParam(':dbName', $strDBName, PDO::PARAM_STR);
            $stmt->execute();
        } catch (PDOException $exc) {
            throw new Exception($exc->getMessage());
        }

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $aryURLs = $this->objParse->getURLContent($row['text']);
            $this->saveURLsSingleTB($row['id'], $aryURLs);
            echo $row['id'] . ' ----> ' . count($aryURLs) . PHP_EOL;
        }
    }

    public function retoreShortURL($intBegain) {
        $int_seg = 100000;
        $intLimitBegin = ($intBegain - 1) * $int_seg;
        $sql_get = 'SELECT * FROM `URLinTweets` LIMIT ' . $intLimitBegin . ', ' . $int_seg;
        try {
            $stmt = $this->dbh->prepare($sql_get);
            $stmt->execute();
        } catch (PDOException $exc) {
            throw new Exception($exc->getMessage());
        }

        $intCount = $intLimitBegin;
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $intCount += 1;
            sleep(rand(1, 2));
            $strRegularURL = $this->objParse->expendShortURL2($row['ShortenURL']);
            $this->saveRegularURL($row['URTId'], $strRegularURL);
            echo $intCount . '. ' . $row['ShortenURL'] . ' --> ' . $strRegularURL . PHP_EOL;
        }
    }

    public function retoreShortURL2($intBegain) {
        $int_seg = 3000;
        $intLimitBegin = ($intBegain - 1) * $int_seg;
        $sql_get = 'SELECT * FROM `UniqueURL` LIMIT ' . $intLimitBegin . ', ' . $int_seg;
        echo $sql_get;
        try {
            $stmt = $this->dbh->prepare($sql_get);
            $stmt->execute();
        } catch (PDOException $exc) {
            throw new Exception($exc->getMessage());
        }

        $intCount = $intLimitBegin;
        $arySaveRegular = array();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $intCount += 1;
            echo $intCount . '.(' . $row['URTId'] . ')' . PHP_EOL;
            sleep(rand(1, 2));
            $strRegularURL = $this->objParse->expendShortURLBycURL($row['ShortenURL']);
            if ($intCount % 10 == 0) {
                $this->saveRegularURL2($arySaveRegular);
                unset($arySaveRegular);
                $arySaveRegular = array();
                array_push($arySaveRegular, array($row['URTId'], $strRegularURL));
            }else{
                array_push($arySaveRegular, array($row['URTId'], $strRegularURL));
            }
            echo $row['ShortenURL'] . ' --> ' . $strRegularURL . PHP_EOL;
        }
        
        if($intCount % 10 != 0){
            $this->saveRegularURL2($arySaveRegular);
        }
    }

    public function extractDomain($strDBName){
        $sql_get = 'SELECT * FROM `' . $strDBName . '`';
        try {
            $stmt = $this->dbh->prepare($sql_get);
            $stmt->execute();
        } catch (PDOException $exc) {
            throw new Exception($exc->getMessage());
        }
        $intCount = 0;
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $intCount += 1;
            $strDomain = parse_url($row['regularURL'], PHP_URL_HOST);
            $this->saveDomain($row['URTId'], $strDomain);
            if($intCount % 100 == 0){
                echo $intCount . '............. ' . PHP_EOL;
            }
        }
    }
    private function saveURLs($intDBNo, $strTweetID, $aryURLs) {
        $sql_insert = 'INSERT INTO `URLinTweets`(`FromTb`, `TweetId`, `ShortenURL`) '
                . 'VALUES (:FromTb, :TweetId, :ShortenURL)';
        $stmt = $this->dbh->prepare($sql_insert);
        $stmt->bindParam(':FromTb', $intDBNo, PDO::PARAM_INT);
        $stmt->bindParam(':TweetId', $strTweetID, PDO::PARAM_STR);
        foreach ($aryURLs as &$strURL) {
            $stmt->bindParam(':ShortenURL', $strURL, PDO::PARAM_STR);
            $stmt->execute();
        }
    }

    private function saveURLsSingleTB($strTweetID, $aryURLs) {
        $sql_insert = 'INSERT INTO `URLinTweets`(`TweetId`, `ShortenURL`) '
                . 'VALUES (:TweetId, :ShortenURL)';
        $stmt = $this->dbh->prepare($sql_insert);
        $stmt->bindParam(':TweetId', $strTweetID, PDO::PARAM_STR);
        foreach ($aryURLs as &$strURL) {
            $stmt->bindParam(':ShortenURL', $strURL, PDO::PARAM_STR);
            $stmt->execute();
        }
    }
    private function saveRegularURL($intURTId, $strRegularURL) {
        $sql_update = 'UPDATE `URLinTweets` SET `regularURL`=:regularURL WHERE `URTId`=:URTId';
        $stmt = $this->dbh->prepare($sql_update);
        $stmt->bindParam(':regularURL', $strRegularURL, PDO::PARAM_STR);
        $stmt->bindParam(':URTId', $intURTId, PDO::PARAM_INT);
        $stmt->execute();
    }

    private function saveRegularURL2($arySavePair) {
        $sql_update = 'UPDATE `UniqueURL` SET `regularURL`=:regularURL WHERE `URTId`=:URTId';
        $stmt = $this->dbh->prepare($sql_update);
        foreach ($arySavePair as $aryPair) {
            $stmt->bindParam(':regularURL', $aryPair[1], PDO::PARAM_STR);
            $stmt->bindParam(':URTId', $aryPair[0], PDO::PARAM_INT);
            $stmt->execute();
        }
    }
    
    private function saveDomain($intURTId, $strDomain){
        $sql_update = 'UPDATE `UniqueURLNotNull` SET `domainName`=:domainName WHERE `URTId`=:URTId';
        $stmt = $this->dbh->prepare($sql_update);
        $stmt->bindParam(':domainName', $strDomain, PDO::PARAM_STR);
        $stmt->bindParam(':URTId', $intURTId, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function __destruct() {
        $this->pdoDB = NULL;
        $this->objParse = NULL;
    }

}
