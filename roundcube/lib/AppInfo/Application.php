<?php
namespace OCA\RoundCube\AppInfo;

use \OCP\AppFramework\App;
use \OCP\RoundCube\UserHooks;

class Application extends App {
  public function __construct(array $urlParams = array()) {
    parent::__construct('roundcube', $urlParams);
    $container = $this->getContainer();
    $container->registerService('UserHooks', function ($c) {
      return new UserHooks($c->query('ServerContainer')->getUserManager());
    });
  }
}
