<?php
/**
 * @package   panopticon
 * @copyright Copyright (c)2023-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   https://www.gnu.org/licenses/agpl-3.0.txt GNU Affero General Public License, version 3 or later
 */

declare(strict_types=1);

namespace Akeeba\Panopticon\Tests\Unit\Architecture;

defined('AKEEBA') || die;

use Akeeba\Panopticon\Tests\AbstractUnitTestCase;

/**
 * Asserts that every local source in composer.json's `extra.copy-static` exists.
 *
 * `InstallationScript::copyNodeDependencies()` copies these with AWF's filesystem abstraction, which returns FALSE
 * instead of failing when the source is missing. When an NPM package moves a file between versions, the copy silently
 * does nothing, the target under `media/` is never created, and the release archive ships without it. This is exactly
 * how ALTCHA 3.x — which moved `dist/altcha.umd.cjs` to `dist/main/altcha.umd.cjs` — left user registration without a
 * CAPTCHA widget for several releases.
 *
 * @since 2.5.0
 */
class CopyStaticSourcesExistTest extends AbstractUnitTestCase
{
	/**
	 * @return  array<string, array{string, string}>  Local copy-static sources: [type, repository-relative path].
	 */
	public static function sourceProvider(): array
	{
		$composer = json_decode(
			(string) file_get_contents(dirname(__DIR__, 3) . '/composer.json'), true, 512, JSON_THROW_ON_ERROR
		);

		$ret = [];

		foreach ($composer['extra']['copy-static'] ?? [] as $def)
		{
			$from = $def['from'] ?? '';

			if ($from === '' || str_starts_with($from, 'http://') || str_starts_with($from, 'https://'))
			{
				continue;
			}

			$ret[$from] = [$def['type'] ?? 'file', trim($from, '/')];
		}

		return $ret;
	}

	/**
	 * Guard against the provider silently becoming empty, which would make this whole test file pass vacuously.
	 */
	public function testTheProviderFindsTheAltchaWidget(): void
	{
		$sources = array_column(self::sourceProvider(), 1);

		$this->assertGreaterThan(10, count($sources), 'Found implausibly few copy-static entries in composer.json');
		$this->assertNotEmpty(
			array_filter($sources, fn($s) => str_starts_with($s, 'node_modules/altcha/')),
			'composer.json no longer copies the ALTCHA widget; update this guard if that was intentional'
		);
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('sourceProvider')]
	public function testSourceExists(string $type, string $relativePath): void
	{
		$root = dirname(__DIR__, 3);

		if (str_starts_with($relativePath, 'node_modules/') && !is_dir($root . '/node_modules'))
		{
			$this->markTestSkipped('node_modules is not installed; run composer install');
		}

		$path = $root . '/' . $relativePath;

		if ($type === 'file')
		{
			$this->assertFileExists($path, 'A copy-static file source is missing, so its target under media/ is never created');

			return;
		}

		$this->assertDirectoryExists($path, 'A copy-static folder source is missing, so its target under media/ is never created');
	}
}
