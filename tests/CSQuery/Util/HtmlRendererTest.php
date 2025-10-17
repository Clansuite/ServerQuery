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

use Clansuite\ServerQuery\Util\HtmlRenderer;
use PHPUnit\Framework\TestCase;

final class HtmlRendererTest extends TestCase
{
    public function testRenderGeneratesCompleteHtmlWithAllSections(): void
    {
        $renderer = new HtmlRenderer;

        $data = [
            'address'     => '127.0.0.1',
            'queryport'   => 27015,
            'online'      => true,
            'servertitle' => 'Test Server',
            'gamename'    => 'Counter-Strike',
            'gameversion' => '1.2.3',
            'mapname'     => 'de_dust2',
            'gametype'    => 'Classic',
            'numplayers'  => 5,
            'maxplayers'  => 10,
            'password'    => '0',
            'players'     => [
                ['name' => 'Player1', 'score' => 100, 'ping' => 50],
                ['name' => 'Player2', 'score' => 80, 'ping' => 40],
            ],
            'playerkeys' => [
                'name'  => true,
                'score' => true,
                'ping'  => true,
            ],
            'rules' => [
                'sv_cheats'    => '0',
                'mp_timelimit' => '30',
            ],
        ];

        $html = $renderer->render($data);

        $this->assertStringContainsString('<!DOCTYPE html>', $html);
        $this->assertStringContainsString('<title>Game Server Query Results</title>', $html);
        $this->assertStringContainsString('Test Server', $html);
        $this->assertStringContainsString('status-online', $html);
        $this->assertStringContainsString('Counter-Strike', $html);
        $this->assertStringContainsString('de_dust2', $html);
        $this->assertStringContainsString('5/10', $html);
        $this->assertStringContainsString('Player1', $html);
        $this->assertStringContainsString('Player2', $html);
        $this->assertStringContainsString('sv_cheats', $html);
        $this->assertStringContainsString('mp_timelimit', $html);
        $this->assertStringContainsString('</html>', $html);
    }

    public function testRenderHandlesOfflineServer(): void
    {
        $renderer = new HtmlRenderer;

        $data = [
            'address'     => '127.0.0.1',
            'queryport'   => 27015,
            'online'      => false,
            'servertitle' => 'Offline Server',
            'gamename'    => 'Unknown',
            'gameversion' => '',
            'mapname'     => '',
            'gametype'    => '',
            'numplayers'  => 0,
            'maxplayers'  => 0,
            'password'    => '0',
            'players'     => [],
            'rules'       => [],
        ];

        $html = $renderer->render($data);

        $this->assertStringContainsString('Offline Server', $html);
        $this->assertStringContainsString('status-offline', $html);
        $this->assertStringContainsString('No players currently online', $html);
        $this->assertStringContainsString('No server rules available', $html);
    }

    public function testRenderHandlesPasswordProtectedServer(): void
    {
        $renderer = new HtmlRenderer;

        $data = [
            'address'   => '127.0.0.1',
            'queryport' => 27015,
            'online'    => true,
            'password'  => '1',
            'players'   => [],
            'rules'     => [],
        ];

        $html = $renderer->render($data);

        $this->assertStringContainsString('Password Protected:</strong> Yes', $html);
    }

    public function testRenderHandlesEmptyPlayersArray(): void
    {
        $renderer = new HtmlRenderer;

        $data = [
            'address'   => '127.0.0.1',
            'queryport' => 27015,
            'online'    => true,
            'players'   => [],
            'rules'     => [],
        ];

        $html = $renderer->render($data);

        $this->assertStringContainsString('No players currently online', $html);
    }

    public function testRenderHandlesEmptyRulesArray(): void
    {
        $renderer = new HtmlRenderer;

        $data = [
            'address'   => '127.0.0.1',
            'queryport' => 27015,
            'online'    => true,
            'players'   => [],
            'rules'     => [],
        ];

        $html = $renderer->render($data);

        $this->assertStringContainsString('No server rules available', $html);
    }

    public function testRenderSortsPlayersByScoreDescending(): void
    {
        $renderer = new HtmlRenderer;

        $data = [
            'address'   => '127.0.0.1',
            'queryport' => 27015,
            'online'    => true,
            'players'   => [
                ['name' => 'LowScore', 'score' => 10],
                ['name' => 'HighScore', 'score' => 100],
                ['name' => 'MidScore', 'score' => 50],
            ],
            'playerkeys' => [
                'name'  => true,
                'score' => true,
            ],
            'rules' => [],
        ];

        $html = $renderer->render($data);

        // Check that all players are present
        $this->assertStringContainsString('HighScore', $html);
        $this->assertStringContainsString('MidScore', $html);
        $this->assertStringContainsString('LowScore', $html);
    }

    public function testRenderHandlesMissingPlayerKeys(): void
    {
        $renderer = new HtmlRenderer;

        $data = [
            'address'   => '127.0.0.1',
            'queryport' => 27015,
            'online'    => true,
            'players'   => [
                ['name' => 'Player1', 'score' => 100, 'ping' => 50],
            ],
            'rules' => [],
        ];

        $html = $renderer->render($data);

        $this->assertStringContainsString('Player1', $html);
        $this->assertStringContainsString('100', $html);
        $this->assertStringContainsString('50', $html);
    }

    public function testRenderHandlesHtmlEntities(): void
    {
        $renderer = new HtmlRenderer;

        $data = [
            'address'     => '127.0.0.1',
            'queryport'   => 27015,
            'online'      => true,
            'servertitle' => 'Server with <script> tags',
            'players'     => [
                ['name' => 'Player<script>', 'score' => 100],
            ],
            'rules' => [
                'rule<script>' => 'value<script>',
            ],
        ];

        $html = $renderer->render($data);

        $this->assertStringContainsString('Server with &lt;script&gt; tags', $html);
        $this->assertStringContainsString('Player&lt;script&gt;', $html);
        $this->assertStringContainsString('rule&lt;script&gt;', $html);
        $this->assertStringContainsString('value&lt;script&gt;', $html);
    }
}
