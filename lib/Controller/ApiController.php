<?php

declare(strict_types=1);

namespace OCA\KeepOrSweep\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\IRequest;

class ApiController extends Controller {
	private const DEFAULT_LIST_LIMIT = 200;
	private const MAX_LIST_LIMIT = 1000;
	private const MAX_PATH_LENGTH = 100;

	public function __construct(string $appName, IRequest $request) {
		parent::__construct($appName, $request);
	}

	/**
	 * @NoAdminRequired
	 */
	public function files(): DataResponse {
		$userSession = \OC::$server->getUserSession();
		$user = $userSession->getUser();

		if ($user === null) {
			return new DataResponse([
				'message' => 'Authentication required.',
			], Http::STATUS_UNAUTHORIZED);
		}

		$rawLimit = (int)$this->request->getParam('limit', self::DEFAULT_LIST_LIMIT);
		$limit = max(1, min($rawLimit, self::MAX_LIST_LIMIT));

		try {
			$rootFolder = \OC::$server->getRootFolder();
			$userFolder = $rootFolder->getUserFolder($user->getUID());
			$files = $this->collectFiles($userFolder, $limit);
			$userPath = rtrim($userFolder->getPath(), '/');

			$results = array_map(function (File $file) use ($userPath): array {
				return [
					'id' => $file->getId(),
					'name' => $file->getName(),
					'size' => $file->getSize(),
					'mimetype' => $file->getMimeType(),
					'mtime' => $file->getMTime(),
					'path' => $this->getRelativeDirectoryPath($file->getPath(), $userPath),
				];
			}, $files);

			return new DataResponse([
				'files' => $results,
				'meta' => [
					'count' => count($results),
					'limit' => $limit,
					'truncated' => count($results) >= $limit,
				],
			]);
		} catch (\Throwable $exception) {
			return new DataResponse([
				'message' => 'Could not load files.',
			], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * Iterative traversal to avoid deep recursion and cap response sizes.
	 *
	 * @return File[]
	 */
	private function collectFiles(Folder $folder, int $limit): array {
		$results = [];
		$stack = [$folder];

		while ($stack !== [] && count($results) < $limit) {
			/** @var Folder $current */
			$current = array_pop($stack);

			foreach ($current->getDirectoryListing() as $node) {
				if (count($results) >= $limit) {
					break 2;
				}

				if ($node instanceof Folder) {
					$stack[] = $node;
					continue;
				}

				if ($node instanceof File) {
					$results[] = $node;
				}
			}
		}

		return $results;
	}

	private function getRelativeDirectoryPath(string $absolutePath, string $userBasePath): string {
		if (strpos($absolutePath, $userBasePath) !== 0) {
			return '';
		}

		$relativePath = ltrim(substr($absolutePath, strlen($userBasePath)), '/');
		$directory = dirname($relativePath);

		if ($directory === '.' || $directory === DIRECTORY_SEPARATOR) {
			return '';
		}

		if (strlen($directory) > self::MAX_PATH_LENGTH) {
			$directory = '…' . substr($directory, -(self::MAX_PATH_LENGTH - 1));
		}

		return rtrim($directory, '/') . '/';
	}
}