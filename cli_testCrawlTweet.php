<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require './inc/setup.inc.php';
require_once './classes/crawlTweetWeb.Class.php';
$obj_crawler = new \ninthday\floodfire\TwitterProcess\crawlTweetWeb('HKmoviefan', '448322339596296192');
$tweet = $obj_crawler->getData();
print_r($tweet);