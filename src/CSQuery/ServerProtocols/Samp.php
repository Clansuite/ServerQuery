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

use function is_array;
use function ord;
use function pack;
use function str_starts_with;
use function strlen;
use function substr;
use function unpack;
use Clansuite\Capture\Protocol\ProtocolInterface;
use Clansuite\Capture\ServerAddress;
use Clansuite\Capture\ServerInfo;
use Clansuite\ServerQuery\CSQuery;
use Override;

/**
 * GTA: San Andreas Multiplayer (SAMP) server protocol implementation.
 *
 * SAMP uses a custom UDP query protocol.
 *
 * @see https://sampwiki.blast.hk/wiki/Query_Mechanism
 */
class Samp extends CSQuery implements ProtocolInterface
{
    /**
     * Protocol name.
     */
    public string $name = 'SAMP';

    /**
     * List of supported games.
     *
     * @var array<string>
     */
    public array $supportedGames = ['GTA: San Andreas Multiplayer'];

    /**
     * Protocol identifier.
     */
    public string $protocol = 'samp';

    /**
     * Constructor.
     */
    public function __construct(?string $address = null, ?int $queryport = null)
    {
        parent::__construct();
        $this->address   = $address;
        $this->queryport = $queryport;
    }

    /**
     * Query server information.
     */
    #[Override]
    public function query_server(bool $getPlayers = true, bool $getRules = true): bool
    {
        if ($this->online) {
            $this->reset();
        }

        // SAMP query packet: 'SAMP' + 4 bytes IP + 2 bytes port + 'i'
        $packet = 'SAMP' . pack('C*', 127, 0, 0, 1) . pack('n', 7777) . 'i';

        $address = (string) $this->address;
        $port    = (int) $this->queryport;

        $response = $this->sendCommand($address, $port, $packet);

        if ($response === '' || $response === '0' || $response === false) {
            $this->errstr = 'No reply received';

            return false;
        }

        if (strlen($response) < 11 || !str_starts_with($response, 'SAMP')) {
            $this->errstr = 'Invalid response';

            return false;
        }

        $offset           = 4;
        $this->password   = ord($response[$offset++]);
        $tmp              = @unpack('n', substr($response, $offset, 2));
        $this->numplayers = is_array($tmp) && isset($tmp[1]) ? (int) $tmp[1] : 0;
        $offset += 2;
        $tmp              = @unpack('n', substr($response, $offset, 2));
        $this->maxplayers = is_array($tmp) && isset($tmp[1]) ? (int) $tmp[1] : 0;
        $offset += 2;

        $tmp         = @unpack('N', substr($response, $offset, 4));
        $hostnameLen = is_array($tmp) && isset($tmp[1]) ? (int) $tmp[1] : 0;
        $offset += 4;
        $this->servertitle = substr($response, $offset, $hostnameLen);
        $offset += $hostnameLen;

        $tmp         = @unpack('N', substr($response, $offset, 4));
        $gamemodeLen = is_array($tmp) && isset($tmp[1]) ? (int) $tmp[1] : 0;
        $offset += 4;
        $gamemode = substr($response, $offset, $gamemodeLen);
        $offset += $gamemodeLen;

        $tmp         = @unpack('N', substr($response, $offset, 4));
        $languageLen = is_array($tmp) && isset($tmp[1]) ? (int) $tmp[1] : 0;
        $offset += 4;
        $language = substr($response, $offset, $languageLen);

        $this->mapname = $gamemode; // Use gamemode as map
        $this->rules   = [
            'gamemode' => $gamemode,
            'language' => $language,
        ];

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
            address: $addr->ip,
            queryport: $addr->port,
            online: $this->online,
            gamename: $this->name,
            gameversion: $this->getVersion(new ServerInfo(
                address: $addr->ip,
                queryport: $addr->port,
                online: $this->online,
                gamename: $this->name,
                gameversion: '',
                servertitle: $this->servertitle,
                mapname: $this->mapname,
                gametype: '',
                numplayers: $this->numplayers,
                maxplayers: $this->maxplayers,
                rules: $this->rules,
                players: $this->players,
                errstr: $this->errstr,
            )),
            servertitle: $this->servertitle,
            mapname: $this->mapname,
            gametype: '',
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
        return '';
    }
}
