<?php

/**
 * Description of URLAnalysis
 * 2015-07-07
 * 由 Twitter 網頁爬回公開的資料內容
 *
 * @author ninthday <bee.me@ninthday.info>
 * @version 1.0
 * @copyright (c) 2015, Jeffy Shih
 */

namespace floodfire\TwitterProcess;

class URLAnalysis
{

    private $dbh = NULL;

    function __construct(\ninthday\myPDOConn $pdoConn)
    {
        $this->dbh = $pdoConn->dbh;
    }

    public function countDomainByDay($tablename, $domain, $startdate, $long)
    {
        $rtn = array();
        if (!is_string($domain)) {
            throw new \InvalidArgumentException('Domain名稱參數一定需為字串！');
        }
        $sql = 'SELECT `a`.`datesSeries`, IFNULL(`b`.`cnt`,0) FROM
            (SELECT DATE(DATE_ADD(\'' . $startdate . '\', INTERVAL @i:=@i+1 DAY) ) AS `datesSeries`
            FROM `' . $tablename . '`, (SELECT @i:=-1) r
            WHERE @i < :long) `a`
            LEFT JOIN
            (SELECT DATE_FORMAT(`created_at`, \'%Y-%m-%d\') AS `onlyDay`, COUNT(*) AS `cnt` FROM `' . $tablename . '` 
            WHERE `domain` =:domain GROUP BY `onlyDay`) `b` 
            ON `b`.`onlyDay`= `a`.`datesSeries`
            ORDER BY `datesSeries`';
        $stmt = $this->dbh->prepare($sql);
        try {
            $stmt->bindParam(':long', $long, \PDO::PARAM_INT);
            $stmt->bindParam(':domain', $domain, \PDO::PARAM_STR);
            if ($stmt->execute()) {
                $rs = $stmt->fetchAll(\PDO::FETCH_NUM);
                foreach ($rs as $row) {
                    $rtn[$row[0]] = $row[1];
                }
            }
        } catch (\PDOException $exc) {
            echo $exc->getTraceAsString();
        }
        return $rtn;
    }

    public function getTopNCountByDay()
    {
        
    }

    private function getTopNDomain($top_n, $tablename)
    {
        $rtn = array();
        $sql = '';
        return $rtn;
    }

    function __destruct()
    {
        $this->dbh = null;
        unset($this->dbh);
    }

}
