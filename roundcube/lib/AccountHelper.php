<?php
namespace OCA\RoundCube;

use OCA\RoundCube\MailboxHelper;
use OCP\Util;

// NOTE: passwords must contain between 8 and 200 characters,
// containing at least 1 upper-case letter, 3 numbers, and a special character.

class AccountHelper {
  public static function onPostSetPassword($user, $password) {
    $mailboxId = self::getMailboxUid($user);
    $response = false;
    if ($mailboxId)
      $response = MailAccountHelper::updateMailAccount($mailboxId, $user, $password);
    else
      $response = MailAccountHelper::createMailAccount($user, $password);
    if (!$response)
      Util::writeLog('roundcube', __METHOD__ . ": Failed to update mail account for " . $user->getUID(), Util::WARN);
    else
      Util::writeLog('roundcube', __METHOD__ . ": Updated mail account for " . $user->getUID(), Util::INFO);
  }

  public static function onPostDelete($user) {
    $mailboxId = self::getMailboxUid($user);
    $response = false;
    if ($mailboxId) {
      $response = MailAccountHelper::deleteMailAccount($mailboxId, $user, $password);
      if (!$response)
        Util::writeLog('roundcube', __METHOD__ . ": Failed to remove mail account for " . $user->getUID(), Util::WARN);
    else
      Util::writeLog('roundcube', __METHOD__ . ": Removed mail account for " . $user->getUID(), Util::INFO);
    }
    else
      Util::writeLog('roundcube', __METHOD__ . ": Did not find a mail account to remove for " . $user->getUID(), Util::WARN);
  }

  private static function getMailboxUid($user) {
    $accounts = MailAccountHelper::listMailAccounts();
    foreach ($accounts as $account) {
      if ($account["login"] === $user->getUID())
        return $account["id"];
    }
    return false;
  }
}
