<?php

declare(strict_types=1);
// SPDX-FileCopyrightText: Gordian Steinmann <dev@steiiin.de>
// SPDX-License-Identifier: AGPL-3.0-or-later

/**
 * Create your routes in here. The name is the lowercase name of the controller
 * without the controller part, the stuff after the hash is the method.
 * e.g. page#index -> OCA\Aktenschrank\Controller\PageController->index()
 *
 * The controller class has to be registered in the application.php file since
 * it's instantiated in there
 */
return [
	'routes' => [

		#region Pages

		['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
		['name' => 'page#index', 'url' => '/inbox', 'verb' => 'GET', 'postfix' => 'inbox'],
		['name' => 'page#index', 'url' => '/timeline', 'verb' => 'GET', 'postfix' => 'timeline'],
		['name' => 'page#index', 'url' => '/archive', 'verb' => 'GET', 'postfix' => 'archive'],
		
		#endregion

		#region Api

		#region Settings

		['name' => 'settings#getSettings', 'url' => '/api/settings', 'verb' => 'GET' ],

		#endregion

		#endregion



		// SettingsController

		// ConfigController #############################################################
		['name' => 'config#loadSetup', 'url' => '/config/setup', 'verb' => 'GET' ],
		['name' => 'config#saveSetup', 'url' => '/config/setup', 'verb' => 'POST' ],
		['name' => 'config#loadUserConfig', 'url' => '/config/user', 'verb' => 'GET' ],

		['name' => 'config#pickFolder', 'url' => '/config/pick-folder', 'verb' => 'POST'],
		['name' => 'config#pickFile', 'url' => '/config/pick-file', 'verb' => 'POST'],

		['name' => 'config#getPathTemplate', 'url' => '/config/path-template', 'verb' => 'GET' ],
		['name' => 'config#setPathTemplate', 'url' => '/config/path-template', 'verb' => 'POST' ],

		// DocumentController > Props ###################################################
		['name' => 'config#getProps', 'url' => '/config/props', 'verb' => 'GET'],
		
		['name' => 'config#getRecipients', 'url' => '/config/recipients', 'verb' => 'GET'],
		['name' => 'config#setRecipients', 'url' => '/config/recipients', 'verb' => 'POST'],

		['name' => 'config#setOrigin', 'url' => '/config/origins', 'verb' => 'POST'],
		['name' => 'config#deleteOrigin', 'url' => '/config/origins', 'verb' => 'DELETE'],

		['name' => 'config#getFormerValues', 'url' => '/config/formervalues', 'verb' => 'POST'],

		// DocumentController > Mainmenu ################################################
		['name' => 'document#loadTimeline', 'url' => '/document/timeline', 'verb' => 'GET' ],
		['name' => 'document#loadArchive', 'url' => '/document/archive', 'verb' => 'POST' ],
		['name' => 'document#loadInbox', 'url' => '/document/inbox', 'verb' => 'GET' ],
		
		// DocumentController > Documents ###############################################
		['name' => 'document#loadDocument', 'url' => '/document/load', 'verb' => 'POST' ],
		['name' => 'document#saveDocument', 'url' => '/document/save', 'verb' => 'POST' ],
		['name' => 'document#deleteDocument', 'url' => '/document/save', 'verb' => 'DELETE' ],
		
		['name' => 'document#addFile', 'url' => '/document/file', 'verb' => 'POST'],
		['name' => 'document#deleteFile', 'url' => '/document/file', 'verb' => 'DELETE'],

		// DocumentController > Tasks ###################################################
		['name' => 'document#saveTask', 'url' => '/document/task', 'verb' => 'POST' ],
		['name' => 'document#deleteTask', 'url' => '/document/task', 'verb' => 'DELETE' ],

	]
];
