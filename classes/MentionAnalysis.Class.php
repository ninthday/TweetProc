<?php

/**
 * Description of URLAnalysis
 * 2015-07-07
 * 整理 tcat mention 資料表的內容
 *
 * @author ninthday <bee.me@ninthday.info>
 * @version 1.0
 * @copyright (c) 2015, Jeffy Shih
 */

namespace ninthday\floodfire\TwitterProcess;

class MentionAnalysis
{

    private $dbh = NULL;

    function __construct(\ninthday\myPDOConn $pdoConn)
    {
        $this->dbh = $pdoConn->dbh;
    }

    /**
     * 逐日計算某 twitter 帳號被引用的數量
     * 
     * @param string $bin_name tcat的bin名稱
     * @param string $mention_user 被引用的人名
     * @param string $startdate 起始時間
     * @param int $long 持續天數
     * @param string $lang 哪一種語言
     * @return array 逐日排序的數量
     * @throws \InvalidArgumentException
     * @since version 1.0
     * @access public
     */
    public function countMentionByDay($bin_name, $mention_user, $startdate, $long, $lang = '')
    {
        $rtn = array();
        if (!is_string($mention_user)) {
            throw new \InvalidArgumentException('Mention User 名稱參數一定需為字串！');
        }
        if ($lang == '') {
            $sql = 'SELECT `a`.`datesSeries`, IFNULL(`b`.`cnt`,0) FROM
            (SELECT DATE(DATE_ADD(\'' . $startdate . '\', INTERVAL @i:=@i+1 DAY) ) AS `datesSeries`
            FROM `' . $bin_name . '_mentions`, (SELECT @i:=-1) r
            WHERE @i < :long) `a`
            LEFT JOIN
            (SELECT DATE_FORMAT(`created_at`, \'%Y-%m-%d\') AS `onlyDay`, COUNT(*) AS `cnt` FROM `' . $bin_name . '_mentions` 
            WHERE `to_user` =:mention_user GROUP BY `onlyDay`) `b` 
            ON `b`.`onlyDay`= `a`.`datesSeries`
            ORDER BY `datesSeries`';
        } else {
            $sql = 'SELECT `a`.`datesSeries`, IFNULL(`b`.`cnt`,0) FROM
            (SELECT DATE(DATE_ADD(\'' . $startdate . '\', INTERVAL @i:=@i+1 DAY) ) AS `datesSeries`
            FROM `' . $bin_name . '_mentions`, (SELECT @i:=-1) r
            WHERE @i < :long) `a`
            LEFT JOIN
            (SELECT DATE_FORMAT(`' . $bin_name . '_mentions`.`created_at`, \'%Y-%m-%d\') AS `onlyDay`, COUNT(*) AS `cnt` FROM `' . $bin_name . '_mentions` 
            INNER JOIN `' . $bin_name . '_tweets` ON `' . $bin_name . '_tweets`.`id` = `' . $bin_name . '_mentions`.`tweet_id`  
            WHERE `' . $bin_name . '_tweets`.`lang` =:lang AND `to_user` =:mention_user 
            GROUP BY `onlyDay`) `b` 
            ON `b`.`onlyDay`= `a`.`datesSeries`
            ORDER BY `datesSeries`';
        }

        $long = $long - 1;
        try {
            $stmt = $this->dbh->prepare($sql);
            $stmt->bindParam(':long', $long, \PDO::PARAM_INT);
            $stmt->bindParam(':mention_user', $mention_user, \PDO::PARAM_STR);
            if ($lang != '') {
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
     * 指定語言逐日計算前N筆Twitter帳號被引用排名的數量，沒有指定語言時為全部語言
     * 
     * @param type $top_n 排名前N筆資料
     * @param type $bin_name tcat的bin名稱
     * @param type $startdate 起始時間
     * @param type $long 持續天數
     * @param type $lang 哪一種語言
     * @return array 二維陣列，1: 帳號名稱，2: 逐日日期，3: 數量
     * @since version 1.0
     * @access public
     */
    public function getTopNCountByDay($top_n, $bin_name, $startdate, $long, $lang = '')
    {
        $rtn = array();
        $ary_topn = $this->getTopNMention($top_n, $bin_name, $lang);
        foreach ($ary_topn as $row) {
            $mention = $row;
            $rtn[$mention] = $this->countMentionByDay($bin_name, $mention, $startdate, $long, $lang);
        }
        return $rtn;
    }

    /**
     * 指定多語言逐日計算前N筆Twitter帳號被引用排名的數量
     * 
     * @param int $top_n 排名前N筆資料
     * @param string $bin_name tcat的bin名稱
     * @param string $startdate 起始時間
     * @param int $long 持續天數
     * @param array $langs 哪些語言 
     * @return array 三維陣列，1:語言，2:帳號名稱，3:逐日日期，4:數量
     * @since version 1.0
     * @access public
     */
    public function getTopNCountByDayMultiLangs($top_n, $bin_name, $startdate, $long, array $langs)
    {
        $rtn = array();
        $ary_topn = $this->getTopNMentionMultiLangs($top_n, $bin_name, $langs);
        foreach ($langs as $lang) {
            foreach ($ary_topn as $row) {
                $mention = $row;
                $rtn[$lang][$mention] = $this->countMentionByDay($bin_name, $mention, $startdate, $long, $lang);
            }
        }
        return $rtn;
    }

    /**
     * 取得指定語言的前N筆的帳號名稱資料
     * 
     * @param int $top_n 排名前N筆資料
     * @param string $bin_name tcat的bin名稱
     * @param string $lang 哪一種語言
     * @return array 前N筆的帳號名稱
     * @since version 1.0
     * @access privste
     */
    private function getTopNMention($top_n, $bin_name, $lang)
    {
        $rtn = array();
        if ($lang == '') {
            $sql = 'SELECT `to_user`, COUNT(*) AS `CNT` FROM `' . $bin_name . '_mentions` '
                    . 'WHERE `to_user` != \'\' GROUP BY `to_user` '
                    . 'ORDER BY `CNT` DESC LIMIT 0, :topn';
        } else {
            $sql = 'SELECT `to_user`, COUNT(*) AS `CNT` FROM `' . $bin_name . '_mentions` '
                    . 'INNER JOIN `' . $bin_name . '_tweets` ON `' . $bin_name . '_tweets`.`id` = `' . $bin_name . '_mentions`.`tweet_id`'
                    . 'WHERE `' . $bin_name . '_tweets`.`lang` =:lang AND `to_user` != \'\' GROUP BY `to_user` '
                    . 'ORDER BY `CNT` DESC LIMIT 0, :topn';
        }
        try {
            $stmt = $this->dbh->prepare($sql);
            $stmt->bindParam(':topn', $top_n, \PDO::PARAM_INT);
            if ($lang != '') {
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
     * 指定多語言取得前N筆的帳號名稱資料
     * 
     * @param int $top_n 排名前N筆資料
     * @param string $bin_name tcat的bin名稱
     * @param array $langs 哪些語言
     * @return array 前N筆的帳號名稱
     * @since version 1.0
     * @access private
     */
    private function getTopNMentionMultiLangs($top_n, $bin_name, array $langs)
    {
        $rtn = array();

        $sql = 'SELECT `to_user`, COUNT(*) AS `CNT` FROM `' . $bin_name . '_mentions` '
                . 'INNER JOIN `' . $bin_name . '_tweets` ON `' . $bin_name . '_tweets`.`id` = `' . $bin_name . '_mentions`.`tweet_id` '
                . 'WHERE `' . $bin_name . '_tweets`.`lang` IN (\'' . implode('\', \'', $langs) . '\') AND `to_user` != \'\' GROUP BY `to_user` '
                . 'ORDER BY `CNT` DESC LIMIT 0, :topn';
        try {
            $stmt = $this->dbh->prepare($sql);
            $stmt->bindParam(':topn', $top_n, \PDO::PARAM_INT);
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
