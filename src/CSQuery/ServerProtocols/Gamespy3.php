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

use const PREG_SPLIT_NO_EMPTY;
use function count;
use function is_int;
use function is_string;
use function ord;
use function preg_replace;
use function preg_split;
use function sprintf;
use function strlen;
use function substr;
use Clansuite\Capture\Protocol\ProtocolInterface;
use Clansuite\Capture\ServerAddress;
use Clansuite\Capture\ServerInfo;
use Clansuite\ServerQuery\CSQuery;
use Override;

/**
 * GameSpy3 protocol implementation.
 */
class Gamespy3 extends CSQuery implements ProtocolInterface
{
    /**
     * Protocol name.
     */
    public string $name = 'Gamespy3';

    /**
     * List of supported games.
     *
     * @var array<string>
     */
    public array $supportedGames = ['Gamespy3', 'Just Cause 2 Multiplayer'];

    /**
     * Protocol identifier.
     */
    public string $protocol = 'Gamespy3';

    /**
     * Constructor.
     */
    public function __construct(mixed $address = null, mixed $queryport = null)
    {
        parent::__construct(is_string($address) ? $address : null, is_int($queryport) ? $queryport : null);
    }

    /**
     * query_server method.
     */
    #[Override]
    public function query_server(bool $getPlayers = true, bool $getRules = true): bool
    {
        if ($this->online) {
            $this->reset();
        }

        // Send challenge packet
        $challengePacket = "\xFE\xFD\x09\x10\x20\x30\x40";

        if (($challenge = $this->sendCommand($this->address ?? '', $this->queryport ?? 0, $challengePacket)) === '' || ($challenge = $this->sendCommand($this->address ?? '', $this->queryport ?? 0, $challengePacket)) === '0' || ($challenge = $this->sendCommand($this->address ?? '', $this->queryport ?? 0, $challengePacket)) === false) {
            return false;
        }

        // Parse challenge
        $challenge       = (int) substr((string) preg_replace("/[^0-9\-]/si", '', $challenge), 1);
        $challengeResult = '';

        if ($challenge !== 0) {
            $challengeResult = sprintf(
                '%c%c%c%c',
                ($challenge >> 24),
                ($challenge >> 16),
                ($challenge >> 8),
                ($challenge >> 0),
            );
        }

        // Send main query packet
        $queryPacket = "\xFE\xFD\x00\x10\x20\x30\x40" . $challengeResult . "\xFF\xFF\xFF\x01";

        if (($result = $this->sendCommand($this->address ?? '', $this->queryport ?? 0, $queryPacket)) === '' || ($result = $this->sendCommand($this->address ?? '', $this->queryport ?? 0, $queryPacket)) === '0' || ($result = $this->sendCommand($this->address ?? '', $this->queryport ?? 0, $queryPacket)) === false) {
            return false;
        }

        $this->online = true;

        // Process response
        $this->processResponse($result);

        return true;
    }

    /**
     * query method.
     */
    #[Override]
    public function query(ServerAddress $addr): ServerInfo
    {
        $this->address   = $addr->ip;
        $this->queryport = $addr->port;
        $this->query_server(true, true);

        return new ServerInfo(
            address: $this->address,
            queryport: $this->queryport,
            online: $this->online,
            gamename: $this->gamename,
            gameversion: $this->gameversion,
            servertitle: $this->servertitle,
            mapname: $this->mapname,
            gametype: $this->gametype,
            numplayers: $this->numplayers,
            maxplayers: $this->maxplayers,
            rules: $this->rules,
            players: $this->players,
            errstr: $this->errstr,
        );
    }

    /**
     * getProtocolName method.
     */
    #[Override]
    public function getProtocolName(): string
    {
        return $this->protocol;
    }

    /**
     * getVersion method.
     */
    #[Override]
    public function getVersion(ServerInfo $info): string
    {
        return $info->gameversion ?? 'unknown';
    }

    private function processResponse(string $buffer): void
    {
        // Split the response at the player/team delimiter
        $parts = preg_split('/\\x00\\x00\\x01/', $buffer, -1, PREG_SPLIT_NO_EMPTY);

        if ($parts === false) {
            return;
        }

        if (count($parts) >= 1) {
            $this->processDetails($parts[0]);
        }

        if (count($parts) >= 2) {
            $this->processPlayers();
        }
    }

    private function processDetails(string $buffer): void
    {
        $i   = 0;
        $len = strlen($buffer);

        while ($i < $len) {
            $key = '';

            while ($i < $len && ord($buffer[$i]) !== 0) {
                $key .= $buffer[$i];
                $i++;
            }
            $i++; // Skip null

            if ($key === '' || $key === '0') {
                break;
            }

            $value = '';

            while ($i < $len && ord($buffer[$i]) !== 0) {
                $value .= $buffer[$i];
                $i++;
            }
            $i++; // Skip null

            $this->setDetail($key, $value);
        }
    }

    private function setDetail(string $key, string $value): void
    {
        switch ($key) {
            case 'hostname':
                $this->servertitle = $value;

                break;

            case 'mapname':
                $this->mapname = $value;

                break;

            case 'gametype':
                $this->gametype = $value;

                break;

            case 'maxplayers':
                $this->maxplayers = (int) $value;

                break;

            case 'numplayers':
                $this->numplayers = (int) $value;

                break;

            case 'gamever':
                $this->gameversion = $value;

                break;

            default:
                $this->rules[$key] = $value;

                break;
        }
    }

    private function processPlayers(): void
    {
        // This is a simplified implementation
        // In a full implementation, we'd parse the complex GameSpy3 player format
        $this->players = [];
    }
}
