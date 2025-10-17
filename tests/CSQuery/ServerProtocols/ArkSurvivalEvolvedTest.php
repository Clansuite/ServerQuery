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

namespace Tests\CSQuery\ServerProtocols;

use function class_exists;
use Clansuite\ServerQuery\ServerProtocols\ArkSurvivalEvolved;
use Clansuite\ServerQuery\ServerProtocols\Steam;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class ArkSurvivalEvolvedTest extends TestCase
{
    public function testClassExistsAndExtendsSteam(): void
    {
        $this->assertTrue(class_exists(ArkSurvivalEvolved::class));

        $reflection = new ReflectionClass(ArkSurvivalEvolved::class);
        $this->assertTrue($reflection->isSubclassOf(Steam::class));
    }

    public function testHasCorrectMetadata(): void
    {
        $server = new ArkSurvivalEvolved('127.0.0.1', 27015, true);
        $this->assertEquals('Ark: Survival Evolved', $server->name);
        $this->assertEquals(['Ark: Survival Evolved'], $server->supportedGames);
        $this->assertEquals('A2S', $server->protocol);
    }

    public function testPortCalculationInitialValue(): void
    {
        $server = new ArkSurvivalEvolved('127.0.0.1', 7020, true);
        // initial queryport property should be the provided port until query_server adjusts it
        $this->assertEquals(7020, $server->queryport);
    }

    public function testGetNativeJoinURI(): void
    {
        $inst           = new ArkSurvivalEvolved('example.com', 27015);
        $inst->hostport = 27015;

        $this->assertSame('steam://connect/example.com:27015', $inst->getNativeJoinURI());
    }

    public function testQueryServerAdjustsAndRestoresQueryPortWhenAutoCalculateTrue(): void
    {
        $originalPort = 27015;

        $inst            = new ArkSurvivalEvolved('127.0.0.1', $originalPort, true);
        $inst->queryport = $originalPort;

        $result = $inst->query_server(true, true);

        $this->assertIsBool($result);

        // Ensure the object's queryport is restored to original even if parent query fails
        $this->assertSame($originalPort, $inst->queryport);
    }

    public function testQueryServerUsesProvidedPortWhenAutoCalculateFalse(): void
    {
        $originalPort = 28000;

        $inst            = new ArkSurvivalEvolved('127.0.0.1', $originalPort, false);
        $inst->queryport = $originalPort;

        $result = $inst->query_server(true, true);

        $this->assertIsBool($result);
        $this->assertSame($originalPort, $inst->queryport);
    }
}
