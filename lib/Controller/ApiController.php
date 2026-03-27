<?php
namespace OCA\KeepOrSweep\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\Files\IRootFolder;
use OCP\IRequest;
use OCP\Files\Node;
use OC\Files\Filesystem;

class ApiController extends Controller {

	private IRootFolder $rootFolder;
	private ?string $userId;

	public function __construct(
		string $appName,
		IRequest $request,
		IRootFolder $rootFolder,
		?string $userId = null
	) {
		parent::__construct($appName, $request);
		$this->rootFolder = $rootFolder;
		$this->userId = $userId;
	}

	/**
	 * @NoAdminRequired
	 */
	public function files(): DataResponse {
		$userFolder = $this->rootFolder->getUserFolder($this->userId);
		$files = $this->getFilesRecursive($userFolder);

		$results = array_map(function (Node $file) {
			return [
				'id' => $file->getId(),
				'name' => $file->getName(),
				'size' => $file->getSize(),
				'mimetype' => $file->getMimeType(),
				'mtime' => $file->getMTime(),
				'path' => $this->getRelativePath($file->getPath()),
			];
		}, $files);

		return new DataResponse($results);
	}

	/**
	 * Recursively collect all files
	 */
	private function getFilesRecursive(Node $folder, array &$results = []): array {
		foreach ($folder->getDirectoryListing() as $node) {
			if ($node->getType() === Node::TYPE_FOLDER) {
				$this->getFilesRecursive($node, $results);
			} else {
				$results[] = $node;
			}
		}

		return $results;
	}

	/**
	 * Convert absolute path to user-relative directory path
	 */
	private function getRelativePath(string $path): string {
		$relative = Filesystem::getView()->getRelativePath($path);

		if ($relative === null) {
			return '';
		}

		// Define a maximum path length
		$maxPathLength = 100; // Adjust this value as needed

		if (strlen($relative) > $maxPathLength) {
			$relative = substr($relative, -($maxPathLength - 3)) . '...'; // Truncate and add ellipsis
		}

		return dirname($relative) . '/';
	}
}