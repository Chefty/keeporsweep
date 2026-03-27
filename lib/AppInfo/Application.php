<?php

declare(strict_types=1);

namespace OCA\KeepOrSweep\AppInfo;

use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;

class Application implements IBootstrap {
	public const APP_ID = 'keeporsweep';

	public function register(IRegistrationContext $context): void {
		// Intentionally empty for now: constructor autowiring is sufficient.
		// Keeping this class enables modern bootstrap extension points.
	}

	public function boot(IBootContext $context): void {
		// No-op.
	}
}