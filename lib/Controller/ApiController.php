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
		$scope = trim((string)$this->request->getParam('folder', ''));

		try {
			$rootFolder = \OC::$server->getRootFolder();
			$userFolder = $rootFolder->getUserFolder($user->getUID());
			$targetFolder = $this->resolveScopeFolder($userFolder, $scope);
			if ($targetFolder === null) {
				return new DataResponse([
					'message' => 'Invalid folder scope.',
				], Http::STATUS_BAD_REQUEST);
			}

			$files = $this->collectFiles($targetFolder, $limit);
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
		$queue = [$folder];
		$seenIds = [];

		while ($queue !== [] && count($results) < $limit) {
			/** @var Folder $current */
			$current = array_shift($queue);
			$entries = $current->getDirectoryListing();
			shuffle($entries);

			foreach ($entries as $node) {
				if (count($results) >= $limit) {
					break 2;
				}

				if ($node instanceof Folder) {
					$queue[] = $node;
					continue;
				}

				if ($node instanceof File) {
					$fileId = $node->getId();
					if ($fileId > 0 && isset($seenIds[$fileId])) {
						continue;
					}

					if ($fileId > 0) {
						$seenIds[$fileId] = true;
					}

					$results[] = $node;
				}
			}
		}

		return $results;
	}

	private function resolveScopeFolder(Folder $userRoot, string $scope): ?Folder {
		$scope = trim($scope, " \t\n\r\0\x0B/");

		if ($scope === '') {
			return $userRoot;
		}

		if (strpos($scope, '..') !== false) {
			return null;
		}

		try {
			$node = $userRoot->get($scope);
			if ($node instanceof Folder) {
				return $node;
			}
		} catch (\Throwable $exception) {
			return null;
		}

		return null;
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