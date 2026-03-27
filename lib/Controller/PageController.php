<?php

declare(strict_types=1);

namespace OCA\KeepOrSweep\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;

class PageController extends Controller {
	public function __construct(string $appName, IRequest $request) {
		parent::__construct($appName, $request);
	}

	/**
	 * CAUTION: the @Stuff turns off security checks; for this page no admin is
	 *          required and no CSRF check. If you don't know what CSRF is, read
	 *          it up in the docs or you might create a security hole. This is
	 *          basically the only required method to add this exemption, don't
	 *          add it to any other method if you don't exactly know what it does
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function index(): TemplateResponse {
		$response = new TemplateResponse('keeporsweep', 'index');
		$csp = new ContentSecurityPolicy();
		// Needed for legacy runtime Vue template compilation in this app.
		$csp->allowEvalScript(true);
		$response->setContentSecurityPolicy($csp);

		return $response;
	}

}
