<?php

declare(strict_types=1);

/**
 * Clansuite Server Query
 *
 * SPDX-FileCopyrightText: 2003-2025 Jens A. Koch
 * SPDX-License-Identifier: MIT
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Clansuite\ServerQuery\ServerProtocols;

use function is_string;
use function preg_match_all;
use Override;

/**
 * Rust protocol implementation.
 *
 * Based on Steam/Source protocol with Rust-specific parsing for player counts from keywords.
 */
class Rust extends Steam
{
    /**
     * Protocol name.
     */
    public string $name = 'Rust';

    /**
     * List of supported games.
     *
     * @var array<string>
     */
    public array $supportedGames = ['Rust'];

    /**
     * Protocol identifier.
     */
    public string $protocol = 'A2S';

    /**
     * Query server - override to handle Rust specific player count parsing.
     */
    #[Override]
    public function query_server(bool $getPlayers = true, bool $getRules = true): bool
    {
        // Call parent query_server
        $result = parent::query_server($getPlayers, $getRules);

        if (!$result) {
            return false;
        }

        // Parse Rust-specific player counts from keywords
        if (isset($this->rules['keywords']) && is_string($this->rules['keywords'])) {
            $this->parse_rust_player_counts($this->rules['keywords']);
        }

        return true;
    }

    /**
     * Parse player counts from Rust keywords
     * mp{maxplayers} and cp{currentplayers} format.
     */
    private function parse_rust_player_counts(string $keywords): void
    {
        // Match mp{number} and cp{number} patterns
        if (preg_match_all('/(mp|cp)(\d+)/', $keywords, $matches) !== false) {
            foreach ($matches[1] as $index => $type) {
                if (!isset($matches[2][$index])) {
                    continue;
                }
                $value = (int) $matches[2][$index];

                if ($type === 'mp') {
                    $this->maxplayers = $value;
                } else {
                    $this->numplayers = $value;
                }
            }
        }
    }
}
