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

namespace Clansuite\ServerQuery\ServerProtocols;

/**
 * SCP: Secret Laboratory protocol implementation.
 *
 * SCP: SL uses the Steam A2S query protocol.
 */
class ScpSecretLaboratory extends Steam
{
    /**
     * Protocol name.
     */
    public string $name = 'SCP: Secret Laboratory';

    /**
     * List of supported games.
     *
     * @var array<string>
     */
    public array $supportedGames = ['SCP: Secret Laboratory'];

    /**
     * Protocol identifier.
     */
    public string $protocol = 'A2S';

    /**
     * Game series.
     *
     * @var array<string>
     */
    public array $game_series_list = ['SCP'];

    /**
     * Port adjustment if needed (default 0).
     */
    protected int $port_diff = 0;
}
