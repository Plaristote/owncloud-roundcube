<?php
namespace OCA\RoundCube;

use OCA\RoundCube\BackLogin;
use OCP\Util;

class MailboxService {
  const API_KEY       = "";
  const API_PATH      = "https://api.gandi.net/v5/email/mailboxes";
  const CLIENT_DOMAIN = "planed.es";

  public static function mailboxesUri() {
    return self::API_PATH . "/" . self::CLIENT_DOMAIN;
  }

  public static function listMailAccounts()
  {
    $helper = new MailboxService();
    return $helper->sendRequest("", 'GET');
  }

  public static function createMailAccount($user, $password) {
    $helper = new MailboxService();
    return $helper->sendRequest("", 'POST', array(
      'login'        => $user->getUID(),
      'mailbox_type' => 'standard',
      'password'     => $password
    ));
  }

  public static function updateMailAccount($mailboxId, $user, $password) {
    $helper = new MailboxService();
    return $helper->sendRequest($mailboxId, 'PATCH', array(
      'login'        => $user->getUID(),
      'password'     => $password
    ));
  }

  public static function deleteMailAccount($mailboxId) {
    $helper = new MailAccountHelper();
    return $helper->sendRequest($mailboxId, 'DELETE');
  }

  public function sendRequest($gandiQuery, $method, $data = null) {
    $response = false;
    $gandiQuery = $this->mailboxesUri() . "$gandiQuery";
    Util::writeLog('roundcube', __METHOD__ . ":URL '$gandiQuery'.", Util::DEBUG);
    try {
      $curl = curl_init();
      $curlOpts = array(
        CURLOPT_URL            => $gandiQuery,
        CURLOPT_HEADER         => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FRESH_CONNECT  => true
      );
      switch ($method) {
      case 'POST':
        $curlOpts[CURLOPT_POST] = true;
        $this->prepareDataQuery($curlOpts, $data);
        break ;
      case 'PATCH':
        $curlOpts[CURLOPT_CUSTOMREQUEST] = $method;
        $this->prepareDataQuery($curlOpts, $data);
        break ;
      case 'DELETE':
        $curlOpts[CURLOPT_CUSTOMREQUEST] = $method;
        break ;
      default:
        $curlOpts[CURLOPT_HTTPGET] = true;
      }
      curl_setopt_array($curl, $curlOpts);
      $response = $this->onResponseReceived(curl_exec($curl));
    } catch (Exception $e) {
      Util::writeLog('roundcube', __METHOD__ . ": '$method' '$gandiQuery' failed.", Util::WARN);
    } finally {
      curl_close($curl);
    }
    return $response;
  }

  private function onResponseReceived($curl, $rawResponse) {
    $curlErrorNum   = curl_errno($curl);
    $curlError      = curl_error($curl);
    $headerSize     = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
    $respHttpCode   = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    Util::writeLog('roundcube', __METHOD__ . ": Got the following HTTP Status Code: ($respHttpCode) $curlErrorNum: $curlError", Util::DEBUG);
    if ($curlErrorNum === CURLE_OK && $respHttpCode < 400) {
      $response = json_decode(self::splitResponse($rawResponse, $headerSize));

    } else {
       Util::writeLog('roundcube', __METHOD__ . ": Opening url '$rcQuery' failed with '$curlError'", Util::WARN);
    }
    return $response;
  }

  private function prepareDataQuery($curlOpts, $data) {
    if ($data) {
      $postData = json_encode($data);
      $curlOpts[CURLOPT_POSTFIELDS] = $postData;
      $curlOpts[CURLOPT_TIMEOUT] = 60;
      $curlOpts[CURLOPT_HTTPHEADER] = array(
       'Authorization: Apikey' . self::API_KEY,
       'Accept: application/json',
       'Content-Type: application/json',
       'Cache-Control: no-cache',
       'Pragma: no-cache'
      );
    }
  }

  /**
   * Splits a curl response into headers and json.
   * @param string $response
   * @param int    $headerSize
   * @return array ['headers' => [headers], 'json' => json]
   */
  private static function splitResponse($response, $headerSize) {
    $headers = $json = "";
    if ($headerSize) {
      $headers = substr($response, 0, $headerSize);
      $json    = substr($response, $headerSize);
    } else {
      $hh = explode("\r\n\r\n", $response, 2);
      $headers = $hh[0];
      $json    = $hh[1];
    }
    return array(
      'headers' => BackLogin::parseResponseHeaders($headers),
      'json'    => json_decode($json)
    );
  }
}
