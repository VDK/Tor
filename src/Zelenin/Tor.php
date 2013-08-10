<?php

/**
 * The Old Reader API (PHP implementation)
 *
 * @package Tor
 * @author  Aleksandr Zelenin <aleksandr@zelenin.me>
 * @link    https://github.com/zelenin/Curl
 * @version 0.0.1
 * @license http://opensource.org/licenses/gpl-3.0.html GPL-3.0
 */

namespace Zelenin;

class Tor
{
	const VERSION = '0.0.1';
	private $_token;
	private $_request;

	public function __construct( $token = null )
	{
		$this->_token = $token;
	}

	public function getToken( $email, $password )
	{
		$args = array(
			'client' => 'YourAppName',
			'accountType' => 'HOSTED_OR_GOOGLE',
			'service' => 'reader',
			'Email' => $email,
			'Passwd' => $password,
		);
		return $this->_post( 'https://theoldreader.com/reader/api/0/accounts/ClientLogin', $args );
	}

	public function getStatus()
	{
		return $this->_get( 'https://theoldreader.com/reader/api/0/status' );
	}

	public function getUserInfo()
	{
		return $this->_get( 'https://theoldreader.com/reader/api/0/user-info' );
	}

	public function getPreferences()
	{
		return $this->_get( 'https://theoldreader.com/reader/api/0/preference/list' );
	}

	public function getFriendList()
	{
		return $this->_get( 'https://theoldreader.com/reader/api/0/friend/list' );
	}

	public function getTagList()
	{
		return $this->_get( 'https://theoldreader.com/reader/api/0/tag/list' );
	}

	public function getStreamPreferencesList()
	{
		return $this->_get( 'https://theoldreader.com/reader/api/0/preference/stream/list' );
	}

	public function updateStreamPreferencesList()
	{
		return $this->_post( 'https://theoldreader.com/reader/api/0/preference/stream/set' );
	}

	public function renameFolder( $s, $dest )
	{
		$args = array(
			's' => $this->_regexpLabel( $s ),
			'dest' => $this->_regexpLabel( $dest ),
		);
		return $this->_post( 'https://theoldreader.com/reader/api/0/rename-tag', $args );
	}

	public function removeFolder( $s )
	{
		$args = array(
			's' => $this->_regexpLabel( $s ),
		);
		return $this->_post( 'https://theoldreader.com/reader/api/0/disable-tag', $args );
	}

	public function getUnreadCount()
	{
		return $this->_get( 'https://theoldreader.com/reader/api/0/unread-count' );
	}

	public function getSubscriptionsList()
	{
		return $this->_get( 'https://theoldreader.com/reader/api/0/subscription/list' );
	}

	public function getSubscriptionsOpml()
	{
		header( 'Content-Type: application/xml; charset=utf-8' );
		$args = array(
			'output' => 'xml'
		);
		return $this->_get( 'https://theoldreader.com/reader/subscriptions/export', $args );
	}

	public function addSubscription( $quickadd )
	{
		$args = array(
			'quickadd' => $quickadd
		);
		return $this->_post( 'https://theoldreader.com/reader/api/0/subscription/quickadd', $args );
	}

	public function updateSubscription( $s, $t = null, $a = null, $r = false )
	{
		$args = array(
			'ac' => 'edit',
			's' => $this->_regexpFeed( $s )
		);
		if ( $t ) $args['t'] = $t;
		if ( $r ) {
			$args['r'] = $this->_regexpLabel( 'Folder' );
		} elseif ( $a ) {
			$args['a'] = $this->_regexpLabel( $a );
		}
		return $this->_post( 'https://theoldreader.com/reader/api/0/subscription/edit', $args );
	}

	public function removeSubscription( $s )
	{
		$args = array(
			'ac' => 'unsubscribe',
			's' => $this->_regexpFeed( $s )
		);
		return $this->_post( 'https://theoldreader.com/reader/api/0/subscription/edit', $args );
	}

	public function getItemIds( $s, $xt = null, $n = 1000, $r = false, $c = null, $t = null )
	{
		$args = array(
			's' => $s,
			'xt' => $xt,
			'n' => $n,
			'c' => $c
		);
		if ( $r ) {
			$args['r'] = 'o';
			$args['ot'] = $t;
		} else {
			$args['nt'] = $t;
		}
		return $this->_get( 'https://theoldreader.com/reader/api/0/stream/items/ids', $args );
	}

	public function getItemContents( $i, $output = 'json' )
	{
		$args = array(
			'i' => $i,
			'output' => $output
		);
		return $this->_post( 'https://theoldreader.com/reader/api/0/stream/items/contents', $args );
	}

	public function getStreamContents( $i, $output = 'json' )
	{
		$args = array(
			'i' => $this->_regexpLabel( $i ),
			'output' => $output
		);
		return $this->_get( 'https://theoldreader.com/reader/api/0/stream/contents', $args );
	}

	public function markAllAsRead( $s, $ts = null )
	{
		$args = array(
			's' => $s,
			'ts' => $ts
		);
		return $this->_post( 'https://theoldreader.com/reader/api/0/mark-all-as-read', $args );
	}

	public function updateItems( $i, $a = true, $r = false )
	{
		$args = array(
			'i' => $i
		);
		if ( $a ) {
			$args['a'] = 'user/-/state/com.google/read';
		} else {
			$args['r'] = 'user/-/state/com.google/read';
		}
		return $this->_post( 'https://theoldreader.com/reader/api/0/edit-tag', $args );
	}

	private function _regexpLabel( $string )
	{
		return 'user/-/label/' . preg_replace( '/user\/-\/label\//', '', $string );
	}

	private function _regexpFeed( $string )
	{
		return 'feed/' . preg_replace( '/feed\//', '', $string );
	}

	private function _get( $url, $data = array() )
	{
		return $this->_request( $url, $data, $method = 'get' );
	}

	private function _post( $url, $data = array() )
	{
		return $this->_request( $url, $data, $method = 'post' );
	}

	private function _request( $url, $data = array(), $method = 'get' )
	{
		$data = array_merge( array( 'output' => 'json' ), $data );
		$headers = array( 'Authorization: GoogleLogin auth=' . $this->_token );

		if ( $method == 'get' && $data ) {
			$url = is_array( $data ) ? trim( $url, '/' ) . '/?' . http_build_query( $data ) : trim( $url, '/' ) . '/?' . $data;
		}
		$this->_request = curl_init( $url );

		$options = array(
			CURLOPT_HEADER => true,
			CURLOPT_NOBODY => false,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_CONNECTTIMEOUT => 30,
			CURLOPT_USERAGENT => 'Tor ' . self::VERSION,
			CURLOPT_SSL_VERIFYPEER => false
		);

		if ( ( $method == 'post' ) && $data ) {
			$options[CURLOPT_POSTFIELDS] = is_array( $data ) ? http_build_query( $data ) : $data;
		}

		if ( $headers ) {
			$options[CURLOPT_HTTPHEADER] = $headers;
		}

		curl_setopt_array( $this->_request, $options );
		$result = curl_exec( $this->_request );

		$response_parts = explode( "\r\n\r\n", $result, 2 );
		curl_close( $this->_request );

		$body = json_decode( $response_parts[1], true );
		return !empty( $body ) ? $body : $response_parts[1];
	}
}