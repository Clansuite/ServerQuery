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

namespace Clansuite\Capture\Protocol;

use function is_a;
use function is_string;
use Clansuite\Capture\UnknownProtocolException;

/**
 * Resolves protocol names to their corresponding class implementations for server querying.
 */
class ProtocolResolver
{
    /**
     * Constructor.
     *
     * @param array<mixed> $protocolMap
     */
    public function __construct(private array $protocolMap)
    {
    }

    /**
     * resolve method.
     */
    public function resolve(string $protocol, string $ip, int $port): ProtocolInterface
    {
        if ($protocol === 'auto') {
            $protocol = $this->detectProtocol();
        }

        $class = $this->protocolMap[$protocol] ?? throw new UnknownProtocolException($protocol);

        if (!is_string($class) || !is_a($class, ProtocolInterface::class, true)) {
            throw new UnknownProtocolException($protocol);
        }

        /** @var class-string<ProtocolInterface> $class */
        return new $class;
    }

    private function detectProtocol(): string
    {
        // Stub: default to source
        return 'source';
    }
}
