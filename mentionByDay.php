<?php
require_once './inc/setup.inc.php';
require_once './classes/myPDOConn.Class.php';
require_once './classes/MentionAnalysis.Class.php';

try {
    $pdoConn = \ninthday\myPDOConn::getInstance('myPDOConnConfig.inc.php');
    $objMentionAny = new \ninthday\floodfire\TwitterProcess\MentionAnalysis($pdoConn);
    $aryMentionByDay = $objMentionAny->getTopNCountByDay(20, '318cleaned', '2014-03-11', 50, 'zh-cn');
    $thead = '<th></th>';
    $tbody = '';
    $ary_mention = array_keys($aryMentionByDay);
    $ary_date = array_keys($aryMentionByDay[$ary_mention[0]]);

    foreach ($ary_date as $dateSeries) {
        $tbody .= '<tr><td>' . $dateSeries . '</td>';
        foreach ($ary_mention as $mention) {
            $tbody .= '<td>' . $aryMentionByDay[$mention][$dateSeries] . '</td>';
        }
        $tbody .= '</tr>';
    }

    foreach ($ary_mention as $mention) {
        $thead .= '<th>' . $mention . '</th>';
    }
} catch (\Exception $exc) {
    echo $exc->getMessage();
}
?>
<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <meta name="author" content="Ninthday (bee.me@ninthday.info)">
        <link rel="stylesheet" href="http://maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css">
        <title><?php echo _WEB_NAME ?></title>
    </head>
    <body>
        <table class="table" style="text-align: right;">
            <thead>
                <tr>
                    <?php echo $thead ?>
                </tr>
            </thead>
            <tbody>
                <?php echo $tbody ?>
            </tbody>
        </table>
    </body>
</html>


