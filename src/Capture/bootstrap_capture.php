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

namespace Clansuite\Capture;

use Clansuite\Capture\Extractor\VersionNormalizer;
use Clansuite\Capture\Protocol\ProtocolResolver;
use Clansuite\Capture\Storage\JsonFixtureStorage;
use Clansuite\Capture\Strategy\DirectCaptureStrategy;
use Clansuite\Capture\Strategy\WorkerCaptureStrategy;
use Clansuite\ServerQuery\ServerProtocols;

function createCaptureService(): CaptureService
{
    /**
     * @var array{
     *     fixtures_dir: string,
     *     default_timeout: int,
     *     worker_timeout: int,
     *     use_worker: bool
     * }
     */
    $config = require __DIR__ . '/../../config/capture_config.php';

    $storage  = new JsonFixtureStorage($config['fixtures_dir']);
    $resolver = new ProtocolResolver(ServerProtocols::getProtocolsMap());
    $strategy = $config['use_worker']
        ? new WorkerCaptureStrategy($config['worker_timeout'])
        : new DirectCaptureStrategy;
    $normalizer = new VersionNormalizer;

    return new CaptureService($resolver, $strategy, $storage, $normalizer);
}
