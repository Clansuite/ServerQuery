<?php declare(strict_types=1);

/**
 * Clansuite Server Query
 *
 * SPDX-FileCopyrightText: 2003-2025 Jens A. Koch
 * SPDX-License-Identifier: MIT
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use Clansuite\ServerQuery\Util\SteamProxyHelper;
use PHPUnit\Framework\TestCase;

final class SteamProxyHelperTest extends TestCase
{
    public function testChallengeLifecycle(): void
    {
        $h = new SteamProxyHelper(4);

        // get a mutate value and compute challenge
        $mutate = 0x12345678;
        $ch     = $h->challengeGet($mutate);

        $this->assertIsInt($ch);
        $this->assertTrue($h->challengeValidate($ch, $mutate));

        // invalid challenge
        $this->assertFalse($h->challengeValidate(0, $mutate));

        // new challenge rotated should still accept a valid one after calling challengeNew
        $h->challengeNew();
        $this->assertIsInt($h->challengeGet($mutate));
    }

    public function testJenkinsHash(): void
    {
        // Test known hash values (calculated from actual implementation)
        $this->assertEquals(1800198439, SteamProxyHelper::jenkinsHash(0));
        $this->assertEquals(3028648374, SteamProxyHelper::jenkinsHash(1));
        $this->assertEquals(41833609, SteamProxyHelper::jenkinsHash(0x12345678));

        // Test that hash is deterministic
        $value = 0xABCDEF12;
        $hash1 = SteamProxyHelper::jenkinsHash($value);
        $hash2 = SteamProxyHelper::jenkinsHash($value);
        $this->assertEquals($hash1, $hash2);
    }

    public function testConstructorWithDefaultSize(): void
    {
        $helper = new SteamProxyHelper;
        $this->assertInstanceOf(SteamProxyHelper::class, $helper);
    }

    public function testConstructorWithCustomSize(): void
    {
        $helper = new SteamProxyHelper(10);
        $this->assertInstanceOf(SteamProxyHelper::class, $helper);
    }

    public function testConstructorWithInvalidSize(): void
    {
        $helper = new SteamProxyHelper(0);
        $this->assertInstanceOf(SteamProxyHelper::class, $helper);

        $helper = new SteamProxyHelper(-5);
        $this->assertInstanceOf(SteamProxyHelper::class, $helper);
    }

    public function testChallengeNewGeneratesValidChallenges(): void
    {
        $helper = new SteamProxyHelper(4);

        // Get initial challenge
        $mutate     = 0x11111111;
        $challenge1 = $helper->challengeGet($mutate);

        // Generate new challenge
        $helper->challengeNew();
        $challenge2 = $helper->challengeGet($mutate);

        // Both should be valid with their respective states
        $this->assertTrue($helper->challengeValidate($challenge2, $mutate));
    }

    public function testChallengeGetReturnsConsistentValues(): void
    {
        $helper = new SteamProxyHelper(4);
        $mutate = 0x22222222;

        $challenge1 = $helper->challengeGet($mutate);
        $challenge2 = $helper->challengeGet($mutate);

        // Same mutate should give same challenge (until challengeNew is called)
        $this->assertEquals($challenge1, $challenge2);
    }

    public function testChallengeValidateWithInvalidChallenges(): void
    {
        $helper = new SteamProxyHelper(4);
        $mutate = 0x33333333;

        // Test invalid challenge values
        $this->assertFalse($helper->challengeValidate(0, $mutate));
        $this->assertFalse($helper->challengeValidate(0xFFFFFFFF, $mutate));
        $this->assertFalse($helper->challengeValidate(-1, $mutate));
    }

    public function testChallengeValidateWithWrongMutate(): void
    {
        $helper  = new SteamProxyHelper(4);
        $mutate1 = 0x44444444;
        $mutate2 = 0x55555555;

        $challenge = $helper->challengeGet($mutate1);

        // Should not validate with different mutate
        $this->assertFalse($helper->challengeValidate($challenge, $mutate2));
    }

    public function testChallengeRotation(): void
    {
        $helper = new SteamProxyHelper(3);
        $mutate = 0x66666666;

        // Get a challenge
        $challenge = $helper->challengeGet($mutate);
        $this->assertTrue($helper->challengeValidate($challenge, $mutate));

        // Generate some new challenges
        for ($i = 0; $i < 5; $i++) {
            $helper->challengeNew();
        }

        // Current challenge should be valid
        $currentChallenge = $helper->challengeGet($mutate);
        $this->assertTrue($helper->challengeValidate($currentChallenge, $mutate));
    }

    public function testChallengeValidateAfterMultipleNew(): void
    {
        $helper = new SteamProxyHelper(4);
        $mutate = 0x77777777;

        // Get current challenge
        $challenge = $helper->challengeGet($mutate);
        $this->assertTrue($helper->challengeValidate($challenge, $mutate));

        // Call challengeNew multiple times
        for ($i = 0; $i < 10; $i++) {
            $helper->challengeNew();
        }

        // Current challenge should still be valid
        $currentChallenge = $helper->challengeGet($mutate);
        $this->assertTrue($helper->challengeValidate($currentChallenge, $mutate));
    }

    public function testJenkinsHashWithLargeValues(): void
    {
        // Test with values that might cause overflow in 32-bit operations
        $largeValue = 0xFFFFFFFF;
        $hash       = SteamProxyHelper::jenkinsHash($largeValue);
        $this->assertIsInt($hash);
        $this->assertGreaterThanOrEqual(0, $hash);
        $this->assertLessThanOrEqual(0xFFFFFFFF, $hash);
    }

    public function testJenkinsHashWithNegativeValues(): void
    {
        // Test with negative values (should be handled as unsigned in the algorithm)
        $negativeValue = -12345;
        $hash          = SteamProxyHelper::jenkinsHash($negativeValue);
        $this->assertIsInt($hash);
        $this->assertGreaterThanOrEqual(0, $hash);
        $this->assertLessThanOrEqual(0xFFFFFFFF, $hash);
    }

    public function testChallengeGetWithDifferentMutates(): void
    {
        $helper = new SteamProxyHelper(4);

        $mutate1 = 0xAAAAAAAA;
        $mutate2 = 0xBBBBBBBB;

        $challenge1 = $helper->challengeGet($mutate1);
        $challenge2 = $helper->challengeGet($mutate2);

        // Different mutates should give different challenges
        $this->assertNotEquals($challenge1, $challenge2);

        // Each should validate with its own mutate
        $this->assertTrue($helper->challengeValidate($challenge1, $mutate1));
        $this->assertTrue($helper->challengeValidate($challenge2, $mutate2));
    }

    public function testChallengeNewAvoidsInvalidValues(): void
    {
        $helper = new SteamProxyHelper(4);

        // Generate many challenges and ensure none are 0 or 0xFFFFFFFF
        for ($i = 0; $i < 50; $i++) {
            $challenge = $helper->challengeGet(0xCCCCCCCC);
            $this->assertNotEquals(0, $challenge);
            $this->assertNotEquals(0xFFFFFFFF, $challenge);
            $helper->challengeNew();
        }
    }

    public function testLargeSizeConstructor(): void
    {
        $helper = new SteamProxyHelper(100);
        $this->assertInstanceOf(SteamProxyHelper::class, $helper);

        $mutate    = 0xDDDDDDDD;
        $challenge = $helper->challengeGet($mutate);
        $this->assertTrue($helper->challengeValidate($challenge, $mutate));
    }
}
