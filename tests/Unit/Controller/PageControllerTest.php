<?php

namespace OCA\KeepOrSweep\Tests\Unit\Controller;

use PHPUnit\Framework\TestCase;

use OCP\AppFramework\Http\TemplateResponse;

use OCA\KeepOrSweep\Controller\PageController;


class PageControllerTest extends TestCase {
	private $controller;

	public function setUp(): void {
		parent::setUp();
		$request = $this->getMockBuilder('OCP\IRequest')->getMock();

		$this->controller = new PageController(
			'keeporsweep', $request
		);
	}

	public function testIndex() {
		$result = $this->controller->index();

		$this->assertEquals('index', $result->getTemplateName());
		$this->assertTrue($result instanceof TemplateResponse);
	}

}
