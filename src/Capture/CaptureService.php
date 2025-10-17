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
use Clansuite\Capture\Storage\FixtureStorageInterface;
use Clansuite\Capture\Strategy\CaptureStrategyInterface;

/**
 * Service class for capturing game server information using configurable strategies and protocols.
 */
final readonly class CaptureService
{
    /**
     * Initializes the capture service with required dependencies.
     *
     * @param ProtocolResolver         $protocolResolver resolves protocol implementations
     * @param CaptureStrategyInterface $captureStrategy  strategy for performing captures
     * @param FixtureStorageInterface  $storage          storage for captured data
     * @param VersionNormalizer        $normalizer       normalizes version information
     */
    public function __construct(
        private ProtocolResolver $protocolResolver,
        private CaptureStrategyInterface $captureStrategy,
        private FixtureStorageInterface $storage,
        private VersionNormalizer $normalizer
    ) {
    }

    /**
     * Captures server information for the given IP, port, and protocol, and stores the result.
     *
     * @param string       $ip       server IP address
     * @param int          $port     server port
     * @param string       $protocol protocol name or 'auto' for automatic detection
     * @param array<mixed> $options  additional capture options
     *
     * @return string path to the stored capture result
     */
    public function capture(string $ip, int $port, string $protocol = 'auto', array $options = []): string
    {
        $protocolInstance = $this->protocolResolver->resolve($protocol, $ip, $port);
        $addr             = new ServerAddress($ip, $port);

        $result = $this->captureStrategy->capture($protocolInstance, $addr, $options + ['protocol_name' => $protocol]);

        $version = $this->normalizer->normalize(
            $protocolInstance->getVersion($result->serverInfo),
        );

        return $this->storage->save(
            $protocolInstance->getProtocolName(),
            $version,
            $ip,
            $port,
            $result,
        );
    }
}
