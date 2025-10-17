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

require __DIR__ . '/../vendor/autoload.php';

use Clansuite\Capture\ServerAddress;
use Clansuite\ServerQuery\ServerProtocols\Mumble;

// Simple CLI: examples/Mumble.php [address] [port] [--fixture]
$args = $argv;
\array_shift($args);

$fixtureMode = false;
$address     = '95.130.64.232';
$port        = 64738;

foreach ($args as $a) {
    if ($a === '--fixture' || $a === '-f') {
        $fixtureMode = true;

        continue;
    }

    if (\filter_var($a, \FILTER_VALIDATE_IP) !== false) {
        $address = $a;

        continue;
    }

    if (\is_numeric($a)) {
        $port = (int) $a;

        continue;
    }
}

if ($fixtureMode) {
    $fixture = __DIR__ . '/../tests/fixtures/mumble/capture_95_130_64_232_64738.json';
    print "Using fixture: {$fixture}\n";

    if (!\is_file($fixture) || !\is_readable($fixture)) {
        print "Fixture not found or unreadable: {$fixture}\n";

        exit(2);
    }

    $json = \file_get_contents($fixture);

    if ($json === false) {
        print "Failed to read fixture: {$fixture}\n";

        exit(2);
    }

    $data = \json_decode($json, true);

    if (!\is_array($data)) {
        // invalid or empty fixture
        $data = [];
    }

    $key = "{$address}:{$port}";

    $entry = [];

    if (isset($data[$key]) && \is_array($data[$key])) {
        $entry = $data[$key];
    } else {
        // take first array entry if available
        $first = null;

        foreach ($data as $v) {
            if (\is_array($v)) {
                $first = $v;

                break;
            }
        }

        if (\is_array($first)) {
            $entry = $first;
        }
    }

    // Safely derive server name, clients and max players with runtime type checks
    $name = $address;

    if (isset($entry['name']) && \is_scalar($entry['name'])) {
        $name = (string) $entry['name'];
    } elseif (isset($entry['gq_name']) && \is_scalar($entry['gq_name'])) {
        $name = (string) $entry['gq_name'];
    }

    $clients = 0;

    if (isset($entry['numplayers']) && \is_numeric($entry['numplayers'])) {
        $clients = (int) $entry['numplayers'];
    } elseif (isset($entry['players']) && \is_array($entry['players'])) {
        $clients = \count($entry['players']);
    }

    $max = 0;

    if (isset($entry['maxplayers']) && \is_numeric($entry['maxplayers'])) {
        $max = (int) $entry['maxplayers'];
    }

    print 'Server: ' . $name . "\n";
    print 'Players: ' . (string) $clients . '/' . (string) $max . "\n";

    // If channels are present in the fixture, build a channel tree and print players under their channels
    if (isset($entry['channels']) && \is_array($entry['channels']) && $entry['channels'] !== []) {
        // build channel map (defensively: ensure each channel entry is an array and fields are validated)
        $channels = [];

        foreach ($entry['channels'] as $ch) {
            if (!\is_array($ch)) {
                continue;
            }

            $id     = (isset($ch['id']) && \is_numeric($ch['id'])) ? (int) $ch['id'] : 0;
            $pname  = (isset($ch['name']) && \is_scalar($ch['name'])) ? (string) $ch['name'] : 'unknown';
            $parent = null;

            // array_key_exists allows detecting keys that exist and are null
            if (\array_key_exists('parent', $ch) && ($ch['parent'] === null || \is_numeric($ch['parent']))) {
                $parent = $ch['parent'] === null ? null : (int) $ch['parent'];
            }

            $channels[$id] = [
                'id'       => $id,
                'name'     => $pname,
                'parent'   => $parent,
                'children' => [],
                'players'  => [],
            ];
        }

        // attach children
        foreach ($channels as &$ch) {
            $parent = $ch['parent'];

            if ($parent !== null && isset($channels[$parent])) {
                $channels[$parent]['children'][] = &$ch;
            }
        }
        unset($ch);

        // assign players to channels (defensive checks)
        if (isset($entry['players']) && \is_array($entry['players'])) {
            foreach ($entry['players'] as $p) {
                if (!\is_array($p)) {
                    continue;
                }

                $cid = isset($p['channel']) && \is_numeric($p['channel']) ? (int) $p['channel'] : 0;

                if (!isset($channels[$cid])) {
                    // create a placeholder channel
                    $channels[$cid] = [
                        'id'       => $cid,
                        'name'     => 'unknown',
                        'parent'   => null,
                        'children' => [],
                        'players'  => [],
                    ];
                }

                $pname                       = isset($p['name']) && \is_scalar($p['name']) ? (string) $p['name'] : 'unknown';
                $channels[$cid]['players'][] = $pname;
            }
        }

        // find roots (those without parent or parent missing)
        $roots = [];

        foreach ($channels as $ch) {
            if ($ch['parent'] === null || !isset($channels[$ch['parent']])) {
                $roots[] = $ch;
            }
        }

        print "--- CHANNELS ---\n";

        $printChannel = static function (mixed $c, int $indent = 0) use (&$printChannel): void
        {
            if (!\is_array($c)) {
                return;
            }

            $name = isset($c['name']) && \is_scalar($c['name']) ? (string) $c['name'] : 'unknown';
            print \str_repeat('  ', $indent) . '- ' . $name . "\n";

            $players = $c['players'] ?? [];

            if (\is_array($players) && $players !== []) {
                foreach ($players as $pname) {
                    $display = \is_scalar($pname) ? (string) $pname : 'unknown';
                    print \str_repeat('  ', $indent + 1) . '* ' . $display . "\n";
                }
            }

            $children = $c['children'] ?? [];

            if (\is_array($children) && $children !== []) {
                foreach ($children as $child) {
                    $printChannel($child, $indent + 1);
                }
            }
        };

        foreach ($roots as $r) {
            $printChannel($r, 0);
        }
    } else {
        print "--- PLAYERS ---\n";

        if (isset($entry['players']) && \is_array($entry['players'])) {
            foreach ($entry['players'] as $p) {
                $pname = 'unknown';

                if (\is_array($p) && isset($p['name']) && \is_scalar($p['name'])) {
                    $pname = (string) $p['name'];
                } elseif (\is_object($p) && \property_exists($p, 'name') && \is_scalar($p->name)) {
                    $pname = (string) $p->name;
                }
                print ' - ' . $pname . "\n";
            }
        }
    }

    exit(0);
}

print "Querying Mumble server {$address}:{$port}...\n";

$proto = new Mumble($address, $port);
$info  = $proto->query(new ServerAddress($address, $port));

if ($info->online) {
    print 'Server: ' . ($info->servertitle ?? $address) . "\n";
    print 'Players: ' . ($info->numplayers ?? 0) . '/' . ($info->maxplayers ?? 0) . "\n";

    // If channel data is present, build and print as a tree (defensive checks)
    if (isset($info->channels) && \is_iterable($info->channels) && $info->channels !== []) {
        $channels = [];

        foreach ($info->channels as $ch) {
            if (!\is_object($ch) && !\is_array($ch)) {
                continue;
            }

            $id = null;

            if (\is_object($ch) && \property_exists($ch, 'id') && \is_numeric($ch->id)) {
                $id = (int) $ch->id;
            } elseif (\is_array($ch) && isset($ch['id']) && \is_numeric($ch['id'])) {
                $id = (int) $ch['id'];
            }

            if ($id === null) {
                continue;
            }

            $pname = 'unknown';

            if (\is_object($ch) && \property_exists($ch, 'name') && \is_scalar($ch->name)) {
                $pname = (string) $ch->name;
            } elseif (\is_array($ch) && isset($ch['name']) && \is_scalar($ch['name'])) {
                $pname = (string) $ch['name'];
            }

            $parent = null;

            if (\is_object($ch) && \property_exists($ch, 'parent')) {
                $parent = $ch->parent === null ? null : (\is_numeric($ch->parent) ? (int) $ch->parent : null);
            } elseif (\is_array($ch) && \array_key_exists('parent', $ch)) {
                $parent = $ch['parent'] === null ? null : (\is_numeric($ch['parent']) ? (int) $ch['parent'] : null);
            }

            $channels[$id] = [
                'id'       => $id,
                'name'     => $pname,
                'parent'   => $parent,
                'children' => [],
                'players'  => [],
            ];
        }

        foreach ($channels as &$ch) {
            $parent = $ch['parent'];

            if ($parent !== null && isset($channels[$parent])) {
                $channels[$parent]['children'][] = &$ch;
            }
        }
        unset($ch);

        if (isset($info->players) && \is_iterable($info->players)) {
            foreach ($info->players as $p) {
                if (!\is_object($p) && !\is_array($p)) {
                    continue;
                }

                $cid = 0;

                if (\is_object($p) && \property_exists($p, 'channel') && \is_numeric($p->channel)) {
                    $cid = (int) $p->channel;
                } elseif (\is_array($p) && isset($p['channel']) && \is_numeric($p['channel'])) {
                    $cid = (int) $p['channel'];
                }

                if (!isset($channels[$cid])) {
                    $channels[$cid] = [
                        'id'       => $cid,
                        'name'     => 'unknown',
                        'parent'   => null,
                        'children' => [],
                        'players'  => [],
                    ];
                }

                $pname = 'unknown';

                if (\is_object($p) && \property_exists($p, 'name') && \is_scalar($p->name)) {
                    $pname = (string) $p->name;
                } elseif (\is_array($p) && isset($p['name']) && \is_scalar($p['name'])) {
                    $pname = (string) $p['name'];
                }

                $channels[$cid]['players'][] = $pname;
            }
        }

        $roots = [];

        foreach ($channels as $ch) {
            if ($ch['parent'] === null || !isset($channels[$ch['parent']])) {
                $roots[] = $ch;
            }
        }

        print "--- CHANNELS ---\n";

        $printChannel = static function (mixed $c, int $indent = 0) use (&$printChannel): void
        {
            if (!\is_array($c)) {
                return;
            }

            $name = isset($c['name']) && \is_scalar($c['name']) ? (string) $c['name'] : 'unknown';
            print \str_repeat('  ', $indent) . '- ' . $name . "\n";

            $players = $c['players'] ?? [];

            if (\is_array($players) && $players !== []) {
                foreach ($players as $pname) {
                    $display = \is_scalar($pname) ? (string) $pname : 'unknown';
                    print \str_repeat('  ', $indent + 1) . '* ' . $display . "\n";
                }
            }

            $children = $c['children'] ?? [];

            if (\is_array($children) && $children !== []) {
                foreach ($children as $child) {
                    $printChannel($child, $indent + 1);
                }
            }
        };

        foreach ($roots as $r) {
            $printChannel($r, 0);
        }
    } else {
        print "--- PLAYERS ---\n";

        if (isset($info->players) && \is_iterable($info->players)) {
            foreach ($info->players as $p) {
                $pname = 'unknown';

                if (\is_array($p) && isset($p['name']) && \is_scalar($p['name'])) {
                    $pname = (string) $p['name'];
                } elseif (\is_object($p) && \property_exists($p, 'name') && \is_scalar($p->name)) {
                    $pname = (string) $p->name;
                }
                print ' - ' . $pname . "\n";
            }
        }
    }
} else {
    print 'Server appears offline: ' . ($info->errstr ?? 'unknown error') . "\n";
}
