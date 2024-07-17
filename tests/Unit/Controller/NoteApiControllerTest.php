<?php

declare(strict_types=1);
// SPDX-FileCopyrightText: Avangenio <email@avangenio.net>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\PdfSignature\Tests\Unit\Controller;

use OCA\PdfSignature\Controller\NoteApiController;

class NoteApiControllerTest extends NoteControllerTest {
	public function setUp(): void {
		parent::setUp();
		$this->controller = new NoteApiController($this->request, $this->service, $this->userId);
	}
}
