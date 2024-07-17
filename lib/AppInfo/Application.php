<?php

declare(strict_types=1);
// SPDX-FileCopyrightText: Avangenio <orelvys.gg@avangenio.net>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\PdfSignature\AppInfo;



use OCP\AppFramework\App;
use OCP\Util;

use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;


class Application extends App implements IBootstrap{
	public const APP_ID = 'pdfsignature';


	public function __construct() {
		parent::__construct(self::APP_ID);
	}

	public function register(IRegistrationContext $context): void {
		require_once __DIR__ . '/../../vendor/autoload.php';
	}

	public function boot(IBootContext $context): void {
		Util::addscript(self::APP_ID, self::APP_ID . '-filesplugin', 'files');
	}
}
