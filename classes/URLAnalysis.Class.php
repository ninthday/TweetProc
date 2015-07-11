<?php

/**
 * Description of URLAnalysis
 * 2015-07-07
 * 整理 tcat 中的 URL 資料表
 *
 * @author ninthday <bee.me@ninthday.info>
 * @version 1.0
 * @copyright (c) 2015, Jeffy Shih
 */

namespace ninthday\floodfire\TwitterProcess;

class URLAnalysis
{

    private $dbh = NULL;

    function __construct(\ninthday\myPDOConn $pdoConn)
    {
        $this->dbh = $pdoConn->dbh;
    }

    /**
     * 逐日計算某網域名稱的數量
     * 
     * @param string $bin_name tcat的bin名稱
     * @param string $domain 網域名稱
     * @param string $startdate 起始時間
     * @param int $long 持續天數
     * @param string $lang 哪一種語言
     * @return array 逐日排序的數量
     * @throws \InvalidArgumentException
     */
    public function countDomainByDay($bin_name, $domain, $startdate, $long, $lang = '')
    {
        $rtn = array();
        if (!is_string($domain)) {
            throw new \InvalidArgumentException('Domain名稱參數一定需為字串！');
        }
        if ($lang == '') {
            $sql = 'SELECT `a`.`datesSeries`, IFNULL(`b`.`cnt`,0) FROM
            (SELECT DATE(DATE_ADD(\'' . $startdate . '\', INTERVAL @i:=@i+1 DAY) ) AS `datesSeries`
            FROM `' . $bin_name . '_urls`, (SELECT @i:=-1) r
            WHERE @i < :long) `a`
            LEFT JOIN
            (SELECT DATE_FORMAT(`created_at`, \'%Y-%m-%d\') AS `onlyDay`, COUNT(*) AS `cnt` FROM `' . $bin_name . '_urls` 
            WHERE `domain` =:domain GROUP BY `onlyDay`) `b` 
            ON `b`.`onlyDay`= `a`.`datesSeries`
            ORDER BY `datesSeries`';
        } else {
            $sql = 'SELECT `a`.`datesSeries`, IFNULL(`b`.`cnt`,0) FROM
            (SELECT DATE(DATE_ADD(\'' . $startdate . '\', INTERVAL @i:=@i+1 DAY) ) AS `datesSeries`
            FROM `' . $bin_name . '_urls`, (SELECT @i:=-1) r
            WHERE @i < :long) `a`
            LEFT JOIN
            (SELECT DATE_FORMAT(`' . $bin_name . '_urls`.`created_at`, \'%Y-%m-%d\') AS `onlyDay`, COUNT(*) AS `cnt` FROM `' . $bin_name . '_urls` 
            INNER JOIN `' . $bin_name . '_tweets` ON `' . $bin_name . '_tweets`.`id` = `' . $bin_name . '_urls`.`tweet_id`  
            WHERE `' . $bin_name . '_tweets`.`lang` =:lang AND `domain` =:domain 
            GROUP BY `onlyDay`) `b` 
            ON `b`.`onlyDay`= `a`.`datesSeries`
            ORDER BY `datesSeries`';
        }

        $long = $long - 1;
        try {
            $stmt = $this->dbh->prepare($sql);
            $stmt->bindParam(':long', $long, \PDO::PARAM_INT);
            $stmt->bindParam(':domain', $domain, \PDO::PARAM_STR);
            if($lang != ''){
                $stmt->bindParam(':lang', $lang, \PDO::PARAM_STR);
            }
            if ($stmt->execute()) {
                $rs = $stmt->fetchAll(\PDO::FETCH_NUM);
                foreach ($rs as $row) {
                    $rtn[$row[0]] = $row[1];
                }
            }
        } catch (\PDOException $exc) {
            echo $exc->getMessage();
        }
        return $rtn;
    }

    /**
     * 逐日計算前N筆網域名稱排名的數量
     * 
     * @param int $top_n 排名前N筆資料
     * @param string $bin_name tcat的bin名稱
     * @param string $startdate 起始時間
     * @param int $long 持續天數
     * @param string $lang 哪一種語言
     * @return array 二維陣列，1: 網域名稱，2: 逐日日期，3: 數量
     */
    public function getTopNCountByDay($top_n, $bin_name, $startdate, $long, $lang = '')
    {
        $rtn = array();
        $ary_topn = $this->getTopNDomain($top_n, $bin_name, $lang);
        foreach ($ary_topn as $row) {
            $domain = $row;
            $rtn[$domain] = $this->countDomainByDay($bin_name, $domain, $startdate, $long, $lang);
        }
        return $rtn;
    }

    /**
     * 取得指定語言的前N筆的網域名稱資料
     * 
     * @param int $top_n 排名前N筆資料
     * @param string $bin_name tcat的bin名稱
     * @param string $lang 哪一種語言
     * @return array 前N筆的網域名稱
     * @since version 1.0
     * @access private
     */
    private function getTopNDomain($top_n, $bin_name, $lang)
    {
        $rtn = array();
        if ($lang == '') {
            $sql = 'SELECT `domain`, COUNT(*) AS `CNT` FROM `' . $bin_name . '_urls` '
                    . 'WHERE `domain` != \'\' GROUP BY `domain` '
                    . 'ORDER BY `CNT` DESC LIMIT 0, :topn';
        } else {
            $sql = 'SELECT `domain`, COUNT(*) AS `CNT` FROM `' . $bin_name . '_urls` '
                    . 'INNER JOIN `' . $bin_name . '_tweets` ON `' . $bin_name . '_tweets`.`id` = `' . $bin_name . '_urls`.`tweet_id`'
                    . 'WHERE `' . $bin_name . '_tweets`.`lang` =:lang AND `domain` != \'\' GROUP BY `domain` '
                    . 'ORDER BY `CNT` DESC LIMIT 0, :topn';
        }

        try {
            $stmt = $this->dbh->prepare($sql);
            $stmt->bindParam(':topn', $top_n, \PDO::PARAM_INT);
            if($lang != ''){
                $stmt->bindParam(':lang', $lang, \PDO::PARAM_STR);
            }
            $stmt->execute();
            $rs = $stmt->fetchAll(\PDO::FETCH_NUM);
            foreach ($rs as $row) {
                array_push($rtn, $row[0]);
            }
        } catch (\PDOException $exc) {
            echo $exc->getMessage();
        }

        return $rtn;
    }

    /**
     * 解構子
     */
    function __destruct()
    {
        $this->dbh = null;
        unset($this->dbh);
    }

}
