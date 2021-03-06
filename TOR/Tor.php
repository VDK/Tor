<?php

namespace Zelenin;

class Tor
{
    const DOMAIN = 'https://theoldreader.com';
    const API = '/reader/api/0';

    private $token;
    private $request;

    public function __construct($token = null)
    {
        $this->token = $token;
    }

    public function getToken($email, $password)
    {
        $args = array(
            'client' => 'YourAppName',
            'accountType' => 'HOSTED_OR_GOOGLE',
            'service' => 'reader',
            'Email' => $email,
            'Passwd' => $password,
        );
        return $this->post(self::DOMAIN . self::API . '/accounts/ClientLogin', $args);
    }

    public function getStatus()
    {
        return $this->get(self::DOMAIN . self::API . '/status');
    }

    public function getUserInfo()
    {
        return $this->get(self::DOMAIN . self::API . '/user-info');
    }

    public function getPreferences()
    {
        return $this->get(self::DOMAIN . self::API . '/preference/list');
    }

    public function getFriendList()
    {
        return $this->get(self::DOMAIN . self::API . '/friend/list');
    }

    public function getTagList()
    {
        return $this->get(self::DOMAIN . self::API . '/tag/list');
    }

    public function getStreamPreferencesList()
    {
        return $this->get(self::DOMAIN . self::API . '/preference/stream/list');
    }

    public function updateStreamPreferencesList()
    {
        return $this->post(self::DOMAIN . self::API . '/preference/stream/set');
    }

    public function renameFolder($s, $dest)
    {
        $args = array(
            's' => $this->regexpLabel($s),
            'dest' => $this->regexpLabel($dest),
        );
        return $this->post(self::DOMAIN . self::API . '/rename-tag', $args);
    }

    public function removeFolder($s)
    {
        $args = array(
            's' => $this->regexpLabel($s),
        );
        return $this->post(self::DOMAIN . self::API . '/disable-tag', $args);
    }

    public function getUnreadCount()
    {
        return $this->get(self::DOMAIN . self::API . '/unread-count');
    }

    public function getSubscriptionsList()
    {
        return $this->get(self::DOMAIN . self::API . '/subscription/list');
    }

    public function getSubscriptionsOpml()
    {
        header('Content-Type: application/xml; charset=utf-8');
        $args = array('output' => 'xml');
        return $this->get(self::DOMAIN . '/reader/subscriptions/export', $args);
    }

    public function addSubscription($quickadd)
    {
        $args = array('quickadd' => $quickadd);
        return $this->post(self::DOMAIN . self::API . '/subscription/quickadd', $args);
    }

    public function updateSubscription($s, $t = null, $a = null, $r = false)
    {
        $args = array(
            'ac' => 'edit',
            's' => $this->regexpFeed($s)
        );
        if ($t) {
            $args['t'] = $t;
        }
        if ($r) {
            $args['r'] = $this->regexpLabel('Folder');
        } elseif ($a) {
            $args['a'] = $this->regexpLabel($a);
        }
        return $this->post(self::DOMAIN . self::API . '/subscription/edit', $args);
    }

    public function removeSubscription($s)
    {
        $args = array(
            'ac' => 'unsubscribe',
            's' => $this->regexpFeed($s)
        );
        return $this->post(self::DOMAIN . self::API . '/subscription/edit', $args);
    }

    public function getItemIds($s, $xt = null, $n = 1000, $r = false, $c = null, $t = null)
    {
        $args = array(
            's' => $s,
            'xt' => $xt,
            'n' => $n,
            'c' => $c
        );
        if ($r) {
            $args['r'] = 'o';
            $args['ot'] = $t;
        } else {
            $args['nt'] = $t;
        }
        return $this->get(self::DOMAIN . self::API . '/stream/items/ids', $args);
    }

    public function getItemContents($i, $output = 'json')
    {
        $args = array(
            'i' => $i,
            'output' => $output
        );
        return $this->post(self::DOMAIN . self::API . '/stream/items/contents', $args);
    }

    public function getStreamContents($i, $output = 'json')
    {
        $args = array(
            'i' => $this->regexpLabel($i),
            'output' => $output
        );
        return $this->get(self::DOMAIN . self::API . '/stream/contents', $args);
    }

    public function markAllAsRead($s, $ts = null)
    {
        $args = array(
            's' => $s,
            'ts' => $ts
        );
        return $this->post(self::DOMAIN . self::API . '/mark-all-as-read', $args);
    }

    public function markAsRead($i, $mark = true)
    {
        return $this->updateItems($i, $mark, 'read');
    }

    public function markAsStarred($i, $mark = true)
    {
        return $this->updateItems($i, $mark, 'starred');
    }

    private function updateItems($i, $a = true, $state = 'read')
    {
        $args = array(
            'i' => $i
        );
        if ($a) {
            $args['a'] = 'user/-/state/com.google/' . $state;
        } else {
            $args['r'] = 'user/-/state/com.google/' . $state;
        }
        return $this->post(self::DOMAIN . self::API . '/edit-tag', $args);
    }

    private function regexpLabel($string)
    {
        return 'user/-/label/' . preg_replace('/user\/-\/label\//', '', $string);
    }

    private function regexpFeed($string)
    {
        return 'feed/' . preg_replace('/feed\//', '', $string);
    }

    private function get($url, $data = array())
    {
        return $this->request($url, $data, $method = 'get');
    }

    private function post($url, $data = array())
    {
        return $this->request($url, $data, $method = 'post');
    }

    private function request($url, $data = array(), $method = 'get')
    {
        $data = array_merge(array('output' => 'json'), $data);
        $headers = array('Authorization: GoogleLogin auth=' . $this->token);

        if ($method == 'get' && $data) {
            $url = is_array($data) ? trim($url, '/') . '/?' . http_build_query($data) : trim($url, '/') . '/?' . $data;
        }
        $this->request = curl_init($url);

        $options = array(
            CURLOPT_HEADER => true,
            CURLOPT_NOBODY => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_USERAGENT => 'Tor <https://github.com/zelenin/Tor/>',
            CURLOPT_SSL_VERIFYPEER => false
        );

        if (($method == 'post') && $data) {
            $options[CURLOPT_POSTFIELDS] = is_array($data) ? http_build_query($data) : $data;
        }

        if ($headers) {
            $options[CURLOPT_HTTPHEADER] = $headers;
        }

        curl_setopt_array($this->request, $options);
        $result = curl_exec($this->request);

        $response_parts = explode("\r\n\r\n", $result, 2);
        curl_close($this->request);

        $body = json_decode($response_parts[1], true);
        return !empty($body) ? $body : $response_parts[1];
    }
}
