<?php
/**
 * @package   panopticon
 * @copyright Copyright (c)2023-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   https://www.gnu.org/licenses/agpl-3.0.txt GNU Affero General Public License, version 3 or later
 */

declare(strict_types=1);

namespace Akeeba\Panopticon\Tests\Integration\Library\Captcha;

defined('AKEEBA') || die;

use Akeeba\Panopticon\Library\Captcha\AltchaCaptcha;
use Akeeba\Panopticon\Tests\AbstractIntegrationTestCase;

/**
 * Integration tests for {@see AltchaCaptcha}: the markup it emits for the ALTCHA 3.x widget, and the round trip of a
 * challenge through the payload format the widget submits.
 *
 * @since 2.5.0
 */
class AltchaCaptchaTest extends AbstractIntegrationTestCase
{
	private mixed $originalChallenge;

	protected function setUp(): void
	{
		parent::setUp();

		$this->originalChallenge = $this->container->segment->get('altcha_challenge', '');
	}

	protected function tearDown(): void
	{
		$this->container->segment->set('altcha_challenge', $this->originalChallenge);
		$this->container->input->set('altcha', null);

		parent::tearDown();
	}

	/**
	 * ALTCHA 3.x reads the challenge from the `challenge` attribute only. The 2.x `challengejson` attribute is ignored;
	 * the widget then fetches the current page as if it were a challenge URL, fails, and the form is submitted without a
	 * payload, so every registration is rejected.
	 */
	public function testRendersTheChallengeInTheV3Attribute(): void
	{
		$html = (new AltchaCaptcha($this->container))->renderChallenge();

		$this->assertMatchesRegularExpression('/<altcha-widget\b[^>]*\schallenge="/', $html);
		$this->assertStringNotContainsString('challengejson', $html);

		preg_match('/\schallenge="([^"]*)"/', $html, $matches);
		$challenge = json_decode(html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8'), true);

		$this->assertIsArray($challenge, 'The challenge attribute does not hold JSON');
		$this->assertSame('SHA-256', $challenge['algorithm'] ?? null);
		$this->assertArrayHasKey('salt', $challenge);
		$this->assertArrayHasKey('signature', $challenge);
	}

	public function testLoadsTheWidgetScriptCopiedFromNodeModules(): void
	{
		$html = (new AltchaCaptcha($this->container))->renderChallenge();

		$this->assertStringContainsString('<script src="media/altcha/altcha.js"', $html);
	}

	/**
	 * Solve a rendered challenge the way the widget does and submit it in the widget's (v1-compatible) payload format.
	 */
	public function testSolvedChallengeValidatesOnce(): void
	{
		$captcha = new AltchaCaptcha($this->container);
		$payload = $this->solve($captcha->renderChallenge());

		$this->container->input->set('altcha', $payload);

		$this->assertTrue($captcha->validateResponse(), 'A correctly solved challenge was rejected');
		$this->assertFalse($captcha->validateResponse(), 'A solved challenge was accepted twice');
	}

	public function testWrongNumberIsRejected(): void
	{
		$captcha = new AltchaCaptcha($this->container);
		$payload = json_decode(base64_decode($this->solve($captcha->renderChallenge())), true);

		$payload['number'] = ($payload['number'] + 1) % 100001;

		$this->container->input->set('altcha', base64_encode(json_encode($payload)));

		$this->assertFalse($captcha->validateResponse());
	}

	public function testMissingPayloadIsRejected(): void
	{
		$captcha = new AltchaCaptcha($this->container);
		$captcha->renderChallenge();

		$this->assertFalse($captcha->validateResponse());
	}

	/**
	 * Brute-force the rendered challenge and return the base64-encoded payload ALTCHA 3.x submits for a v1 challenge.
	 */
	private function solve(string $html): string
	{
		preg_match('/\schallenge="([^"]*)"/', $html, $matches);
		$this->assertNotEmpty($matches, 'No challenge attribute in the rendered markup');

		$challenge = json_decode(html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8'), true);

		for ($number = 0; $number <= $challenge['maxnumber']; $number++)
		{
			if (hash('sha256', $challenge['salt'] . $number) === $challenge['challenge'])
			{
				return base64_encode(
					json_encode(
						[
							'algorithm' => $challenge['algorithm'],
							'challenge' => $challenge['challenge'],
							'number'    => $number,
							'salt'      => $challenge['salt'],
							'signature' => $challenge['signature'],
							'took'      => 1,
						]
					)
				);
			}
		}

		$this->fail('The rendered challenge has no solution within maxnumber');
	}
}
