<?php

namespace Tests\Pages;

use Tests\Support\AcceptanceTester;

class AdminPage
{
  public function __construct()
  {
    $this->url = '/admin';
  }

  public function login(AcceptanceTester $I) {
    $I->amOnPage('/admin');
  }
}
