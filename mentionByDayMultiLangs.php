<?php
require_once './inc/setup.inc.php';
require_once './classes/myPDOConn.Class.php';
require_once './classes/MentionAnalysis.Class.php';

try {
    $pdoConn = \ninthday\myPDOConn::getInstance('myPDOConnConfig.inc.php');
    $objMentionAny = new \ninthday\floodfire\TwitterProcess\MentionAnalysis($pdoConn);
    $langs = array('zh-cn', 'zh-tw');
    $aryMentionByDay = $objMentionAny->getTopNCountByDayMultiLangs(20, '318cleaned', '2014-03-11', 50, $langs);
    $thead = '<th></th>';
    $tbody = '';
    $ary_mention = array_keys($aryMentionByDay[$langs[0]]);
    $ary_date = array_keys($aryMentionByDay[$langs[0]][$ary_mention[0]]);

    foreach ($ary_date as $dateSeries) {
        $tbody .= '<tr><td>' . $dateSeries . '</td>';
        foreach ($ary_mention as $mention) {
            $subtotal = 0;
            $subbody = '';
            foreach ($langs as $lang) {
                $subbody .= '<td>' . $aryMentionByDay[$lang][$mention][$dateSeries] . '</td>';
                $subtotal += $aryMentionByDay[$lang][$mention][$dateSeries];
            }
            $tbody .= '<td>' . $subtotal . '</td>' . $subbody;
        }
        $tbody .= '</tr>';
    }

    foreach ($ary_mention as $mention) {
        $thead .= '<th colspan="' . (count($langs) + 1) . '">' . $mention . '</th>';
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


