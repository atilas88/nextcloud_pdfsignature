<?php

declare(strict_types=1);
// SPDX-FileCopyrightText: Avangenio <email@avangenio.net>
// SPDX-License-Identifier: AGPL-3.0-or-later

/**
 * Create your routes in here. The name is the lowercase name of the controller
 * without the controller part, the stuff after the hash is the method.
 * e.g. page#index -> OCA\PdfSignature\Controller\PageController->index()
 *
 * The controller class has to be registered in the application.php file since
 * it's instantiated in there
 */

return [
  	'routes' => [
  		// this route is used by the admin settings page to save the option values
  		['name' => 'config#setAdminConfig', 'url' => '/admin-config', 'verb' => 'PUT'],
		['name' => 'config#getAdminConfig', 'url' => '/admin-config', 'verb' => 'GET'],
		['name' => 'signer#requestSignature', 'url' => '/request-sign', 'verb' => 'POST'],
		['name' => 'signer#requestValidateSignature', 'url' => '/request-validate', 'verb' => 'POST'],
		['name' => 'signer#requestPdfPage', 'url' => '/request-pdfpage', 'verb' => 'POST'],
  	],
  ];