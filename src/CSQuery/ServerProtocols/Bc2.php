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

use function count;
use function fclose;
use function fread;
use function fsockopen;
use function fwrite;
use function is_float;
use function is_int;
use function is_numeric;
use function is_string;
use function stream_set_blocking;
use function stream_set_timeout;
use function strlen;
use function substr;
use function time;
use function unpack;
use Clansuite\Capture\Protocol\ProtocolInterface;
use Clansuite\Capture\ServerAddress;
use Clansuite\Capture\ServerInfo;
use Clansuite\ServerQuery\CSQuery;
use LogicException;
use Override;

/**
 * Implements the query protocol for Battlefield: Bad Company 2 servers.
 * Handles server queries, player information retrieval, and game-specific data parsing.
 */
class Bc2 extends CSQuery implements ProtocolInterface
{
    /**
     * Real game host (may differ from query host).
     */
    public ?string $gameHost = null;

    /**
     * Real game port (may differ from query port).
     */
    public ?int $gamePort = null;

    /**
     * Protocol name.
     */
    public string $name = 'Battlefield Bad Company 2';

    /**
     * List of supported games.
     *
     * @var array<string>
     */
    public array $supportedGames = ['Battlefield Bad Company 2'];

    /**
     * Protocol identifier.
     */
    public string $protocol  = 'bc2';
    protected int $port_diff = 29321;

    /**
     * Constructor.
     */
    public function __construct(?string $address = null, ?int $queryport = null)
    {
        parent::__construct($address, $queryport);
    }

    /**
     * Returns a native join URI for BC2 or false if not available.
     */
    #[Override]
    public function getNativeJoinURI(): false|string
    {
        return false; // BC2 doesn't have native join URI like BF4
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

        $queryPort = ($this->queryport ?? 0) + $this->port_diff;

        // Attempt TCP query to client_port + 29321 (BC2 convention)
        $errno  = 0;
        $errstr = '';
        $address = $this->address ?? '';
        $fp = @fsockopen($address, $queryPort, $errno, $errstr, 5);

        if ($fp === false) {
            $this->errstr = 'Unable to open TCP socket to BC2 query port';

            return false;
        }
        stream_set_blocking($fp, true);
        stream_set_timeout($fp, 5);

        // serverInfo
        $info = $this->tcpQuery($fp, 'serverInfo');

        if ($info === false) {
            fclose($fp);
            $this->errstr = 'No BC2 serverInfo response';

            return false;
        }

        // parse serverInfo
        $this->parseServerInfo($info);
        $players = $this->tcpQuery($fp, 'listPlayers');

        if ($players !== false) {
            $this->parsePlayers($players);
        }

        // version
        $ver = $this->tcpQuery($fp, 'version');

        if ($ver !== false) {
            $this->parseVersion($ver);
        }

        fclose($fp);
        $this->online = true;

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
        return 'bc2';
    }

    /**
     * getVersion method.
     */
    #[Override]
    public function getVersion(ServerInfo $info): string
    {
        return $info->gameversion ?? 'unknown';
    }

    /**
     * @param resource $fp
     *
     * @return array<mixed>|false
     */
    private function tcpQuery(mixed $fp, string $command): array|false
    {
        $packet  = $this->getPacket($command);
        $written = fwrite($fp, $packet);

        if ($written === false) {
            return false;
        }

        $buf   = '';
        $start = time();

        while (true) {
            $chunk = fread($fp, 8192);

            if ($chunk === false) {
                break;
            }

            if ($chunk !== '') {
                $buf .= $chunk;
            }

            $decoded = $this->decodePacket($buf);

            if ($decoded !== false) {
                return $decoded;
            }

            // timeout 2s
            if ((time() - $start) > 2) {
                break;
            }
        }

        // If we get here, decoding hasn't produced a useful result yet.
        // Returning false signals failure to caller consistent with the phpdoc.
        return false;
    }

    private function getPacket(string $command): string
    {
        return match ($command) {
            'version'     => "\x00\x00\x00\x00\x18\x00\x00\x00\x01\x00\x00\x00\x07\x00\x00\x00version\x00",
            'serverInfo'  => "\x00\x00\x00\x00\x1b\x00\x00\x00\x01\x00\x00\x00\x0a\x00\x00\x00serverInfo\x00",
            'listPlayers' => "\x00\x00\x00\x00\x24\x00\x00\x00\x02\x00\x00\x00\x0b\x00\x00\x00listPlayers\x00\x03\x00\x00\x00\x61ll\x00",
            default       => '',
        };
    }

    /**
     * Decode buffer into associative array or return false when incomplete/invalid.
     *
     * @return array<mixed>|false
     */
    private function decodePacket(string $buffer): array|false
    {
        if (strlen($buffer) < 4) {
            return false;
        }

        $unpacked = unpack('V', $buffer);

        if ($unpacked === false) {
            return false;
        }

        /** @var array{1: int} $unpacked */
        $itemCount = isset($unpacked[1]) ? (int) $unpacked[1] : 0;
        $ptr       = 4;
        $items     = [];

        for ($i = 0; $i < $itemCount; $i++) {
            if ($ptr + 4 > strlen($buffer)) {
                return false;
            }
            $unpacked = unpack('V', substr($buffer, $ptr, 4));

            if ($unpacked === false) {
                return false;
            }

            /** @var array{1: int} $unpacked */
            $len = $unpacked[1];
            $ptr += 4;

            if ($ptr + $len > strlen($buffer)) {
                return false;
            }
            $items[] = substr($buffer, $ptr, $len);
            $ptr += $len;
        }

        return $items;
    }

    /**
     * @param array<mixed> $info
     */
    private function parseServerInfo(array $info): void
    {
        if (count($info) < 9) {
            return;
        }

        $this->playerteams = [];

        $st                = $info[1] ?? null;
        $this->servertitle = is_string($st) ? $st : '';

        $np               = $info[2] ?? null;
        $this->numplayers = is_int($np) ? $np : (is_numeric($np) ? (int) $np : 0);

        $mp               = $info[3] ?? null;
        $this->maxplayers = is_int($mp) ? $mp : (is_numeric($mp) ? (int) $mp : 0);

        $gt             = $info[4] ?? null;
        $this->gametype = is_string($gt) ? $gt : '';

        $mn            = $info[5] ?? null;
        $this->mapname = is_string($mn) ? $mn : '';

        $idx       = 9;
        $tc        = $info[8] ?? null;
        $teamCount = is_int($tc) ? $tc : (is_numeric($tc) ? (int) $tc : 0);

        for ($i = 0; $i < $teamCount; $i++) {
            $tval                = $info[$idx++] ?? null;
            $tickets             = is_float($tval) ? $tval : (is_numeric($tval) ? (float) $tval : 0.0);
            $this->playerteams[] = ['tickets' => $tickets];
        }

        $tsVal                      = $info[$idx++] ?? null;
        $this->rules['targetscore'] = is_int($tsVal) ? $tsVal : (is_numeric($tsVal) ? (int) $tsVal : 0);

        $this->rules['ranked']     = (($info[$idx + 1] ?? '') === 'true');
        $this->rules['punkbuster'] = (($info[$idx + 2] ?? '') === 'true');
        $this->rules['password']   = (($info[$idx + 3] ?? '') === 'true');

        $uptVal                = $info[$idx + 4] ?? null;
        $this->rules['uptime'] = is_int($uptVal) ? $uptVal : (is_numeric($uptVal) ? (int) $uptVal : 0);
    }

    /**
     * @param array<mixed> $players
     */
    private function parsePlayers(array $players): void
    {
        // TODO: implement player parsing
        throw new LogicException('Not implemented yet.');
    }

    /**
     * @param array<mixed> $version
     */
    private function parseVersion(array $version): void
    {
        // TODO: implement version parsing
        throw new LogicException('Not implemented yet.');
    }
}
