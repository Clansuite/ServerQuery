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

namespace Clansuite\ServerQuery\Util;

use function array_key_exists;
use function array_keys;
use function count;
use function htmlentities;
use function is_array;
use function is_numeric;
use function is_object;
use function is_scalar;
use function json_encode;
use function ucfirst;
use function usort;

/**
 * HtmlRenderer for displaying game server query results in HTML format.
 */
class HtmlRenderer
{
    /**
     * Render server data as HTML tables.
     *
     * @param array        $data The decoded JSON data from toJson()
     * @param array<mixed> $data
     *
     * @return string HTML representation of the server data
     */
    public function render(array $data): string
    {
        $html = $this->getHtmlHeader();
        $html .= $this->renderServerInfo($data);
        $html .= '<div class="tables-container">';
        $html .= $this->renderPlayers($data);
        $html .= $this->renderRules($data);
        $html .= '</div>';
        $html .= $this->getHtmlFooter();

        return $html;
    }

    /**
     * Get HTML header with basic styling.
     */
    private function getHtmlHeader(): string
    {
        return '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Game Server Query Results</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f5f5f5; }
        .container { max-width: 1400px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1, h2 { color: #333; border-bottom: 2px solid #007cba; padding-bottom: 5px; }
        .tables-container { display: flex; gap: 20px; margin-bottom: 20px; }
        .table-container { flex: 1; }
        .table-container table { width: 100%; border-collapse: collapse; background: white; }
        .table-container th, .table-container td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        .table-container th { background-color: #007cba; color: white; font-weight: bold; }
        .table-container tr:nth-child(even) { background-color: #f9f9f9; }
        .table-container tr:hover { background-color: #f1f1f1; }
        .status-online { color: #28a745; font-weight: bold; }
        .status-offline { color: #dc3545; font-weight: bold; }
        .server-info { background: #e3f2fd; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .server-info strong { display: inline-block; min-width: 120px; }
        @media (max-width: 768px) {
            .tables-container { flex-direction: column; }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Game Server Query Results</h1>';
    }

    /**
     * Render server basic information.
     *
     * @param array<mixed> $data
     */
    private function renderServerInfo(array $data): string
    {
        $online = isset($data['online']) ? (bool) $data['online'] : false;

        $address     = isset($data['address']) && is_scalar($data['address']) ? (string) $data['address'] : '';
        $queryport   = isset($data['queryport']) && is_scalar($data['queryport']) ? (string) $data['queryport'] : '';
        $servertitle = isset($data['servertitle']) && is_scalar($data['servertitle']) ? (string) $data['servertitle'] : '';
        $gamename    = isset($data['gamename']) && is_scalar($data['gamename']) ? (string) $data['gamename'] : '';
        $gameversion = isset($data['gameversion']) && is_scalar($data['gameversion']) ? (string) $data['gameversion'] : '';
        $mapname     = isset($data['mapname']) && is_scalar($data['mapname']) ? (string) $data['mapname'] : '';
        $gametype    = isset($data['gametype']) && is_scalar($data['gametype']) ? (string) $data['gametype'] : '';
        $numplayers  = isset($data['numplayers']) && is_numeric($data['numplayers']) ? (int) $data['numplayers'] : 0;
        $maxplayers  = isset($data['maxplayers']) && is_numeric($data['maxplayers']) ? (int) $data['maxplayers'] : 0;
        $password    = isset($data['password']) && is_scalar($data['password']) ? (string) $data['password'] : '0';

        $statusClass = $online ? 'status-online' : 'status-offline';
        $statusText  = $online ? 'Online' : 'Offline';

        $html = '<div class="server-info">';
        $html .= '<h2>Server Information</h2>';
        $html .= '<p><strong>Address:</strong> ' . htmlentities($address) . ':' . htmlentities($queryport) . '</p>';
        $html .= '<p><strong>Status:</strong> <span class="' . $statusClass . '">' . $statusText . '</span></p>';
        $html .= '<p><strong>Server Name:</strong> ' . htmlentities($servertitle) . '</p>';
        $html .= '<p><strong>Game:</strong> ' . htmlentities($gamename) . '</p>';
        $html .= '<p><strong>Version:</strong> ' . htmlentities($gameversion) . '</p>';
        $html .= '<p><strong>Map:</strong> ' . htmlentities($mapname) . '</p>';
        $html .= '<p><strong>Gametype:</strong> ' . htmlentities($gametype) . '</p>';
        $html .= '<p><strong>Players:</strong> ' . $numplayers . '/' . $maxplayers . '</p>';
        $html .= '<p><strong>Password Protected:</strong> ' . ($password === '0' ? 'No' : 'Yes') . '</p>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Render players table.
     *
     * @param array<mixed> $data
     */
    private function renderPlayers(array $data): string
    {
        if (!isset($data['players']) || !is_array($data['players']) || count($data['players']) === 0) {
            return '<div class="table-container"><h2>Players</h2><p>No players currently online.</p></div>';
        }

        // Sort players by score (frags) in descending order
        $players = $data['players'];
        usort($players, static function (mixed $a, mixed $b): int
        {
            $scoreA = is_array($a) && isset($a['score']) && is_numeric($a['score']) ? (int) $a['score'] : 0;
            $scoreB = is_array($b) && isset($b['score']) && is_numeric($b['score']) ? (int) $b['score'] : 0;

            return $scoreB <=> $scoreA; // Descending order
        });

        $html = '<div class="table-container">';
        $html .= '<h2>Players (' . count($players) . ')</h2>';
        $html .= '<table>';
        $html .= '<thead><tr>';

        // Determine available columns from playerkeys
        $columns = [];

        if (isset($data['playerkeys']) && is_array($data['playerkeys']) && count($data['playerkeys']) > 0) {
            foreach ($data['playerkeys'] as $key => $available) {
                if ((bool) $available) {
                    $columns[] = (string) $key;
                }
            }
        } else {
            // Fallback: check first player for available keys
            if (isset($players[0]) && is_array($players[0]) && count($players[0]) > 0) {
                $columns = array_keys($players[0]);
            }
        }

        foreach ($columns as $column) {
            $colStr = (string) $column;
            $html .= '<th>' . ucfirst(htmlentities($colStr)) . '</th>';
        }
        $html .= '</tr></thead><tbody>';

        foreach ($players as $player) {
            if (!is_array($player)) {
                continue;
            }
            $html .= '<tr>';

            foreach ($columns as $column) {
                $value = array_key_exists($column, $player) ? $player[$column] : '';
                $cell  = is_scalar($value) ? (string) $value : (is_object($value) || is_array($value) ? (string) json_encode($value) : '');
                $html .= '<td>' . htmlentities($cell) . '</td>';
            }
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Render server rules table.
     *
     * @param array<mixed> $data
     */
    private function renderRules(array $data): string
    {
        if (!isset($data['rules']) || count($data['rules']) === 0) {
            return '<div class="table-container"><h2>Server Rules</h2><p>No server rules available.</p></div>';
        }

        $html = '<div class="table-container">';
        $html .= '<h2>Server Rules</h2>';
        $html .= '<table>';
        $html .= '<thead><tr><th>Rule</th><th>Value</th></tr></thead><tbody>';

        foreach ($data['rules'] as $key => $value) {
            $html .= '<tr>';
            $keyStr   = (string) $key;
            $valueStr = is_scalar($value) ? (string) $value : (is_object($value) || is_array($value) ? (json_encode($value) !== false ? json_encode($value) : '') : '');
            $html .= '<td>' . htmlentities($keyStr) . '</td>';
            $html .= '<td>' . htmlentities($valueStr) . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Get HTML footer.
     */
    private function getHtmlFooter(): string
    {
        return '    </div>
</body>
</html>';
    }
}
