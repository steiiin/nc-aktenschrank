<?php

declare(strict_types=1);
// SPDX-FileCopyrightText: Gordian Steinmann <dev@steiiin.de>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\Aktenschrank\Service;

use OCP\IUserSession;
use OCP\IUser;

class ApiService
{

  private IUserSession $userSession;

  public function __construct(
    IUserSession $userSession
  ) {
    $this->userSession = $userSession;
  }

  #region Settings

  #region Userinfo



  #endregion

  #endregion

}
