<?php
namespace OCA\PdfSignature\Settings;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IConfig;
use OCP\Settings\ISettings;

use OCA\PdfSignature\AppInfo\Application;


class Admin implements ISettings {

	private IConfig $config;
	private IInitialState $initialStateService;
	private ?string $userId;

	public function __construct(IConfig       $config,
								IInitialState $initialStateService,
								?string       $userId) {
		$this->config = $config;
		$this->initialStateService = $initialStateService;
		$this->userId = $userId;
	}

	/**
	 * @return TemplateResponse
	 */
	public function getForm(): TemplateResponse {
		
        $apiHost = $this->config->getAppValue(Application::APP_ID, 'api_host');
		$apiKey = $this->config->getAppValue(Application::APP_ID, 'api_key');
		$state = [
            'api_host' => $apiHost,
			'api_key' => $apiKey,
		];
		$this->initialStateService->provideInitialState('admin-config', $state);
		return new TemplateResponse(Application::APP_ID, 'adminSettings');
	}

	public function getSection(): string {
		return 'signature-settings';
	}

	public function getPriority(): int {
		return 10;
	}
}
