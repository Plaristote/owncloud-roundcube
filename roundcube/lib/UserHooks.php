<?php
namespace OCA\RoundCube;

use \OCP\RoundCube\AccountHelper;

class UserHooks {
  public function __construct($userManager) {
    $this->userManager = $userManager;
  }

  public function register() {
    $updateCallback = function($user, $password, $recoverPassword) {
      AccountHelper::onPostSetPassword($user, $password);
    };
    $deleteCallback = function($user) {
      AccountHelper::onPostDelete($user);
    };
    $this->userManager->listen('\OC\User', 'postSetPassword', $updateCallback);
    $this->userManager->listen('\OC\User', 'postDelete', $deleteCallback);
  }
}
