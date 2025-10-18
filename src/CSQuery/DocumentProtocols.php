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

namespace Clansuite\ServerQuery;

use function array_keys;
use function array_merge;
use function array_search;
use function array_unique;
use function array_values;
use function basename;
use function class_exists;
use function count;
use function explode;
use function file_put_contents;
use function glob;
use function is_array;
use function sort;
use function strcasecmp;
use function uksort;
use function usort;
use function var_export;
use ReflectionClass;

/**
 * Utility class to generate markdown and HTML documentation of server protocols.
 */
class DocumentProtocols
{
    /** @var array<string, array<string>> */
    private array $protocols = [];

    /** @var array<string> */
    private array $gameList = [];

    /** @var array<string, array<string>> */
    private array $series       = [];
    private int $totalProtocols = 0;
    private int $totalGames     = 0;

    /**
     * Initializes the documentation generator with the protocols directory.
     *
     * @param string $protocolsDir Path to the server protocols directory
     */
    public function __construct(private readonly string $protocolsDir = __DIR__ . '/ServerProtocols')
    {
    }

    /**
     * parseProtocols method.
     */
    public function parseProtocols(): void
    {
        $files = glob($this->protocolsDir . '/*.php');

        if ($files === false) {
            $files = [];
        }

        $this->protocols = [];
        $this->gameList  = [];

        foreach ($files as $file) {
            $className     = basename($file, '.php');
            $fullClassName = 'Clansuite\\ServerQuery\\ServerProtocols\\' . $className;

            if (!class_exists($fullClassName)) {
                continue;
            }

            $reflection = new ReflectionClass($fullClassName);

            if (!$reflection->isInstantiable()) {
                continue;
            }

            /** @var CSQuery $instance */
            $instance = $reflection->newInstanceWithoutConstructor();

            $parent       = $reflection->getParentClass();
            $baseProtocol = $parent !== false ? $parent->getName() : 'CSQuery';

            $name = $instance->name;

            if ($name === 'Unknown') {
                var_export($className);
            }
            $supportedGames   = $instance->supportedGames;
            $protocol         = $instance->protocol;
            $game_series_list = $instance->game_series_list;

            $base = $this->getBaseProtocol($protocol);

            if (!isset($this->protocols[$base])) {
                $this->protocols[$base] = [];
            }
            $this->protocols[$base] = array_merge($this->protocols[$base], $supportedGames);
        }

        // Sort bases in custom order
        $order = ['Steam', 'Half-Life', 'Gamespy', 'Gamespy 2', 'Gamespy 3', 'Quake', 'Quake 3', 'Unreal 2', 'Minecraft', 'Mumble', 'Teamspeak 3', 'SAMP', 'Factorio', 'ASE', 'Assetto Corsa', 'Battlefield', 'Cube Engine', 'DDnet', 'Eco', 'Farming Simulator', 'Palworld', 'SQP', 'Satisfactory', 'Starbound', 'Terraria', 'Tibia', 'Torque', 'Ventrilo', 'Launcher Protocol', 'Tribes 2', 'BeamMP'];
        uksort($this->protocols, static function (string $a, string $b) use ($order): int
        {
            $posA = array_search($a, $order, true);
            $posB = array_search($b, $order, true);

            if ($posA === false) {
                $posA = 999;
            }

            if ($posB === false) {
                $posB = 999;
            }

            return $posA <=> $posB;
        });

        // Deduplicate and sort games under each base
        foreach ($this->protocols as $base => &$games) {
            $games = array_unique($games);
            sort($games);
        }

        // Build gameList
        $this->gameList = [];

        foreach ($this->protocols as $base => $games) {
            foreach ($games as $game) {
                $this->gameList[] = $game . ' - ' . $base;
            }
        }

        // Sort gameList alphabetically
        usort($this->gameList, static function (string $a, string $b): int
        {
            $gameA = explode(' - ', $a)[0];
            $gameB = explode(' - ', $b)[0];

            return strcasecmp($gameA, $gameB);
        });

        // Build series
        $this->series = [];

        foreach ($files as $file) {
            $className     = basename($file, '.php');
            $fullClassName = 'Clansuite\\ServerQuery\\ServerProtocols\\' . $className;

            if (!class_exists($fullClassName)) {
                continue;
            }

            $reflection = new ReflectionClass($fullClassName);

            if (!$reflection->isInstantiable()) {
                continue;
            }

            /** @var CSQuery $instance */ $instance = $reflection->newInstanceWithoutConstructor();
            $game_series_list                       = $instance->game_series_list ?? [$instance->name ?? 'Unknown'];
            $supportedGames                         = $instance->supportedGames ?? [];

            foreach ($game_series_list as $series) {
                if (!isset($this->series[$series])) {
                    $this->series[$series] = [];
                }
                $this->series[$series] = array_merge($this->series[$series], $supportedGames);
            }
        }

        foreach ($this->series as &$games) {
            $games = array_unique($games);
            sort($games);
        }

        // Set totals
        $this->totalProtocols = count($this->protocols);
        $this->totalGames     = count($this->gameList);
    }

    /**
     * renderMarkdown method.
     */
    public function renderMarkdown(): string
    {
        $markdown = "## Clansuite Server Query\n";
        $markdown .= "### Table of Contents\n\n";
        $markdown .= "1. [Supported Server Protocols](#supported-server-protocols)\n";
        $markdown .= "2. [Supported Game and Voice Servers](#supported-game-and-voice-servers)\n";
        $markdown .= "3. [Game Series](#game-series)\n\n";
        $markdown .= "\n";
        $markdown .= "---\n";
        $markdown .= "\n";
        $markdown .= "### Supported Server Protocols\n\n";
        $markdown .= "Total Protocols: {$this->totalProtocols}\n";
        $markdown .= "Total Games: {$this->totalGames}\n\n";
        $markdown .= "This section lists the server protocols supported, organized in a hierarchical tree structure.\n";
        $markdown .= "Each protocol serves as a top-level category, with the games they support listed underneath.\n\n";
        $markdown .= "```\n";
        $markdown .= "Server Query Protocols\n";
        $totalBases = count($this->protocols);
        $i          = 0;

        foreach ($this->protocols as $base => $games) {
            $isLastBase    = $i === $totalBases - 1;
            $baseConnector = $isLastBase ? '└── ' : '├── ';
            $markdown .= $baseConnector . $base . "\n";

            foreach ($games as $gIndex => $game) {
                $isLastGame    = $gIndex === count($games) - 1;
                $gameConnector = $isLastBase ? '    ' : '│   ';
                $markdown .= $gameConnector . $game . "\n";
            }
            $i++;
        }
        $markdown .= "```\n\n";
        $markdown .= "### Supported Game and Voice Servers\n\n";
        $markdown .= "This is a table of all supported games with their corresponding protocols, including voice servers.\n\n";
        $markdown .= "| Number | Game Name | Server Protocol |\n";
        $markdown .= "|--------|-----------|-----------------|\n";

        foreach ($this->gameList as $index => $game) {
            $parts    = explode(' - ', (string) $game, 2);
            $gameName = $parts[0];
            $protocol = $parts[1] ?? '';
            $number   = $index + 1;
            $markdown .= "| {$number} | {$gameName} | {$protocol} |\n";
        }
        $markdown .= "\n";
        $markdown .= "### Game Series\n\n";
        $markdown .= "This section lists games grouped by their series, regardless of protocol differences.\n\n";

        foreach ($this->series as $series => $games) {
            $markdown .= "#### {$series}\n\n";

            if (is_array($games)) {
                foreach ($games as $game) {
                    $markdown .= '- ' . (string) $game . "\n";
                }
            }
            $markdown .= "\n";
        }
        $markdown .= "\n";

        return $markdown;
    }

    /**
     * renderHtml method.
     */
    public function renderHtml(): string
    {
        $html = "<!DOCTYPE html>\n<html>\n";
        $html .= "<head>\n<title>Supported Server Protocols by Clansuite Server Query</title>\n</head>\n";
        $html .= "<body>\n";
        $html .= "<h2>Clansuite Server Query</h2>\n";
        $html .= "Clansuite Server Query currently supports {$this->totalProtocols} server protocols and is compatible with {$this->totalGames} game and voice servers.\n";
        $html .= "<p>This document provides a complete list of supported protocols and games.</p>\n";
        $html .= "<p>If the server you are looking for is not included, you can either contribute by forking the project on <a href='https://github.com/Clansuite/ServerQuery'>GitHub</a> and add it yourself, or <a href='https://github.com/Clansuite/ServerQuery/issues'>submit a request</a> to have it added.</p>\n";
        $html .= "<p>Best regards,<br>Jens A. Koch</p>\n";

        $html .= "<h3>Supported Server Protocols</h3>\n";
        $html .= "Clansuite Server Query currently supports {$this->totalProtocols} server protocols.\n";
        $html .= '<p>The protocols are displayed in a hierarchical tree structure.</p>';
        $html .= "<p>Each protocol appears as a top-level category, with the supported games organized beneath it.</p>\n";
        $html .= "<pre>\n";
        $html .= "Server Query Protocols\n";
        $totalBases = count($this->protocols);
        $i          = 0;

        foreach ($this->protocols as $base => $games) {
            $isLastBase    = $i === $totalBases - 1;
            $baseConnector = $isLastBase ? '└── ' : '├── ';
            $html .= $baseConnector . $base . "\n";

            foreach ($games as $gIndex => $game) {
                $isLastGame    = $gIndex === count($games) - 1;
                $gameConnector = $isLastBase ? '    ' : '│   ';
                $html .= $gameConnector . $game . "\n";
            }
            $i++;
        }
        $html .= "</pre>\n\n";
        $html .= "<h3>Supported Game Servers</h3>\n";
        $html .= "<p>The table below lists all supported games along with their corresponding server protocols.</p>\n";
        $html .= "<p>Each entry includes the game name and the protocol it uses for server communication.</p>\n";
        $html .= "<table>\n";
        $html .= "<thead>\n<tr>\n<th>#</th>\n<th>Game Name</th>\n<th>Server Protocol</th>\n</tr>\n</thead>\n";
        $html .= "<tbody>\n";

        foreach ($this->gameList as $index => $game) {
            $parts    = explode(' - ', (string) $game, 2);
            $gameName = $parts[0] ?? '';
            $protocol = $parts[1] ?? '';
            $number   = $index + 1;
            $html .= "<tr>\n<td>{$number}</td>\n<td>{$gameName}</td>\n<td>{$protocol}</td>\n</tr>\n";
        }
        $html .= "</tbody>\n</table>\n\n";
        $html .= "<h3>Game Series</h3>\n";
        $html .= "<p>This section lists games organized by series, independent of their server protocols.</p>\n";

        foreach ($this->series as $series => $games) {
            /** @var array<string> $games */
            $html .= "<h4>{$series}</h4>\n<ul>\n";

            foreach ($games as $game) {
                $html .= "<li>{$game}</li>\n";
            }
            $html .= "</ul>\n";
        }
        $html .= "</body>\n</html>\n";

        return $html;
    }

    /**
     * writeFiles method.
     */
    public function writeFiles(string $outputDir = __DIR__ . '/../../'): void
    {
        $markdown = $this->renderMarkdown();
        $html     = $this->renderHtml();

        file_put_contents($outputDir . '/protocols.md', $markdown);
        file_put_contents($outputDir . '/protocols.html', $html);

        print "Generated protocols.md and protocols.html\n";
    }

    /**
     * run method.
     */
    public function run(): void
    {
        $this->parseProtocols();
        $this->writeFiles();
    }

    /**
     * getSupportedGames method.
     *
     * @return array<string>
     */
    public function getSupportedGames(): array
    {
        return array_merge(...array_values($this->series));
    }

    /**
     * getGameSeries method.
     *
     * @return array<string>
     */
    public function getGameSeries(): array
    {
        return array_keys($this->series);
    }

    private function getBaseProtocol(string $protocol): string
    {
        $map = [
            'A2S'                 => 'Steam',
            'Halflife'            => 'Half-Life',
            'Halflife2'           => 'Half-Life',
            'Gamespy'             => 'Gamespy',
            'Gamespy2'            => 'Gamespy 2',
            'Gamespy3'            => 'Gamespy 3',
            'Quake'               => 'Quake',
            'Quake3'              => 'Quake 3',
            'Quake4'              => 'Quake',
            'doom3'               => 'Quake 3',
            'etqw'                => 'Quake 3',
            'fear'                => 'Quake 3',
            'Unreal2'             => 'Unreal 2',
            'minecraft'           => 'Minecraft',
            'mumble'              => 'Mumble',
            'teamspeak3'          => 'Teamspeak 3',
            'samp'                => 'SAMP',
            'Satisfactory'        => 'Satisfactory',
            'starbound'           => 'Starbound',
            'terraria'            => 'Terraria',
            'tibia'               => 'Tibia',
            'Torque'              => 'Torque',
            'Tribes2'             => 'Tribes 2',
            'ventrilo'            => 'Ventrilo',
            'Skulltag'            => 'Launcher Protocol',
            'Zandronum'           => 'Launcher Protocol',
            'AgeOfTime'           => 'Tribes 2',
            'Blockland'           => 'Tribes 2',
            'beammp'              => 'BeamMP',
            'CounterStrike16'     => 'Half-Life',
            'assettocorsa'        => 'Assetto Corsa',
            'Battlefield4'        => 'Battlefield',
            'bc2'                 => 'Battlefield',
            'Frostbite'           => 'Battlefield',
            'BF4'                 => 'Battlefield',
            'Cube'                => 'Cube Engine',
            'ddnet'               => 'DDnet',
            'arma'                => 'Gamespy 2',
            'halo'                => 'Gamespy 2',
            'swat4'               => 'Gamespy 2',
            'Bf2'                 => 'Gamespy 3',
            'ut3'                 => 'Gamespy 3',
            'gamespy3'            => 'Gamespy 3',
            'et'                  => 'Quake 3',
            'ql'                  => 'Quake 3',
            'StarWarsJK'          => 'Quake 3',
            'urbanterror'         => 'Quake 3',
            'wolf'                => 'Quake 3',
            'killingfloor'        => 'Unreal 2',
            'ravaged'             => 'Unreal 2',
            'ro2'                 => 'Unreal 2',
            'ror'                 => 'Unreal 2',
            'sniperelite2'        => 'Unreal 2',
            'conan'               => 'Steam',
            'dayz'                => 'Steam',
            'ffow'                => 'Steam',
            'source'              => 'Steam',
            'dods'                => 'Steam',
            'scum'                => 'Steam',
            'ASE'                 => 'ASE',
            'gta-san-andreas-mta' => 'ASE',
            'SQP'                 => 'SQP',
            'Factorio'            => 'Factorio',
            'Eco'                 => 'Eco',
            'FarmingSimulator'    => 'Farming Simulator',
            'Palworld'            => 'Palworld',
        ];

        return $map[$protocol] ?? $protocol;
    }
}

// Run if called directly
if (isset($argv) && __FILE__ === $argv[0]) {
    $doc = new DocumentProtocols;
    $doc->run();
}
