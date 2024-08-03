<?php

declare(strict_types=1);
// SPDX-FileCopyrightText: Gordian Steinmann <dev@steiiin.de>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\Aktenschrank\Service;

use OCP\IConfig;
use OCP\IUser;
use OCP\IUserSession;

class ApiService
{

  private IConfig $appConfig;
  private IUserSession $userSession;
  

  public function __construct(
    IConfig $appConfig,
    IUserSession $userSession
  ) {
    $this->appConfig = $appConfig;
    $this->userSession = $userSession;
  }

  #region Settings

  #region Userinfo

  public function getLocalization(): Array 
  {

    // get current user, if no user active resume with default values
    $userUID = $this->userSession->getUser();
		if ($userUID !== null) { $userUID = $userUID->getUID(); }
		
    // get timezone
		$defaultTimeZone = date_default_timezone_get();
		$userTimezone = $this->appConfig->getUserValue($userUID, 'core', 'timezone', $defaultTimeZone);

    // get language preference
		$userLanguage = $this->appConfig->getUserValue($userUID, 'core', 'lang', null);

    // return
    return [ 'localization' => [
      'timezone' => $userTimezone,
      'language' => $userLanguage
    ]];

  }

  #endregion

  #endregion

}
