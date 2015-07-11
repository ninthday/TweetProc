<?php

require_once './inc/setup.inc.php';
require_once './classes/myPDOConn.Class.php';
require_once './classes/URLAnalysis.Class.php';

try {
    $pdoConn = \ninthday\myPDOConn::getInstance('myPDOConnConfig.inc.php');
    $objURLAny = new \ninthday\floodfire\TwitterProcess\URLAnalysis($pdoConn);
    $aryDomainByDay = $objURLAny->getTopNCountByDay(20, '318cleaned', '2014-03-11', 50, 'zh-cn');
    $thead = '<th></th>';
    $tbody = '';
    $ary_domain = array_keys($aryDomainByDay);
    $ary_date = array_keys($aryDomainByDay[$ary_domain[0]]);
    
    foreach ($ary_date as $dateSeries) {
        $tbody .= '<tr><td>' . $dateSeries . '</td>';
        foreach ($ary_domain as $domain) {
            $tbody .= '<td>' . $aryDomainByDay[$domain][$dateSeries] . '</td>';
        }
        $tbody .= '</tr>';
    }
    
    foreach ($ary_domain as $domain) {
        $thead .= '<th>' . $domain . '</th>';
    }
} catch (\Exception $exc) {
    echo $exc->getMessage();
}
?>
<table style="text-align: right;">
    <thead>
        <tr>
            <?php echo $thead?>
        </tr>
    </thead>
    <tbody>
        <?php echo $tbody?>
    </tbody>
</table>

