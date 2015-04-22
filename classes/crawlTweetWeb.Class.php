<?php

/**
 * Description of crawlTweetWeb
 * 2015-04-22
 * 由 Twitter 網頁爬回公開的資料內容
 *
 * @author ninthday <jeffy@ninthday.info>
 * @version 1.0
 * @copyright (c) 2015, Jeffy Shih
 */

namespace Floodfire\TwitterProcess;

class crawlTweetWeb
{

    private $tweet_id = '';
    private $user_name = '';
    private $url = 'https://twitter.com/';
    private $tweet = array();

    public function __construct($user_name, $tweet_id)
    {
        $this->setIdentity($user_name, $tweet_id);
    }

    /**
     * 設定要用來抓取的資料
     * @param type $user_name
     * @param type $tweet_id
     */
    private function setIdentity($user_name, $tweet_id)
    {
        $this->user_name = $user_name;
        $this->tweet_id = $tweet_id;
        $this->url .= $this->user_name . '/status/' . $this->tweet_id;
        $this->tweet['tweet_id'] = $this->tweet_id;
        $this->tweet['from_user_name'] = $this->user_name;
    }

    /**
     * 取得資料回傳內容
     * 
     * @return array tweet_id, from_user_name, text, html-text
     */
    public function getData()
    {
        $html = $this->getHTML();
        $this->parseHTML($html);

        return $this->tweet;
    }

    /**
     * 使用 PHP DOMDocument 解析HTML內容，取出部分內容存入陣列中
     * 
     * @param string $html_string HTML的內容
     */
    private function parseHTML($html_string)
    {
        $dom = new \DOMDocument();
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html_string);
        $title = $dom->getElementsByTagName('title');

        foreach ($dom->getElementsByTagName('meta') as $meta_dom) {
            if ($meta_dom->getAttribute('property') == 'og:description') {
                $this->tweet['text'] = $meta_dom->getAttribute('content');
            }
        }

        foreach ($dom->getElementsByTagName('p') as $p_dom) {
            if ($p_dom->getAttribute('class') == 'js-tweet-text tweet-text') {
                $this->tweet['html-text'] = $p_dom->nodeValue;
            }
        }

        $dom = null;
    }

    /**
     * 取得 HTML 字串內容
     * 
     * @return string HTML的內容
     * @throws Exception
     */
    public function getHTML()
    {
        if (empty($this->user_name) or empty($this->tweet_id)) {
            throw new Exception('Tweet Id or User name is empty!');
        }
        $useragent = 'Mozilla/5.0 (Windows NT 6.3; rv:36.0) Gecko/20100101 Firefox/36.0';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);

        $html = curl_exec($ch);
        curl_close($ch);

        return $html;
    }

    /**
     * 
     * @param type $user_name
     * @param type $tweet_id
     */
    public function resetMe($user_name, $tweet_id)
    {
        $this->clearMe();
        $this->setIdentity($user_name, $tweet_id);
    }

    /**
     * 清除目前所有的屬性內容
     */
    public function clearMe()
    {
        $this->tweet_id = '';
        $this->user_name = '';
        $this->url = 'https://twitter.com/';
        $this->tweet = array();
    }

    public function __destruct()
    {
        
    }

}
