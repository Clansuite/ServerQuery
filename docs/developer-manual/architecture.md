# Architecture

## High-Level Overview

Clansuite Server Query is structured as a modular PHP library with clear separation of concerns. The core components are:

- **CSQuery Factory**: Central entry point for creating server instances.
- **Server Protocols**: Protocol-specific implementations for querying different game servers.
- **Capture System**: Tools for recording and replaying server responses.
- **Utilities**: Helper classes for data processing and testing.

## Architecture

This document gives a concise, up-to-date overview of the repository layout and
 the main runtime components. It is intended for contributors who need to
 understand where to add protocols, how capture fixtures are produced/consumed,
 and where helpers and examples live.

### High-level

The project is a PHP library and tools collection for querying game servers,
producing replayable JSON fixtures and providing protocol implementations.

Key top-level areas:

- `src/` — library code (main functional areas live under `src/CSQuery` and `src/Capture`).
- `examples/` — runnable example scripts for protocol usage.
- `bin/` — command-line tools (notably `bin/capture.php`).
- `tests/` — unit and integration tests, plus captured fixtures used for replay.
- `z_protos/` — bundled/third-party protocol implementations and helpers (GameQ, SteamQueryProxy, etc.).
- `docs/` — user and developer documentation (this file, protocols, game list, and more).

See also: `docs/protocols.md` and `docs/game_list.md` for supported protocols and game mappings.

### Main Components (what's actually in `src/`)

- `src/CSQuery/`
  - `CSQuery.php` — the factory/entry point used by examples and callers to create server instances and perform queries.
  - `ServerProtocols.php` — registry/mapping of supported protocols used by the factory.
  - `DocumentProtocols.php` — helper for documentation-oriented protocol listings.
   - `ServerProtocols/` — individual protocol classes live here (e.g. `Arma3.php`, `ArkSurvivalEvolved.php`, `Ase.php`, etc.).
  - `Util/` — utility classes (e.g. `MockUdpClient.php`, `UdpClient.php`, `PacketReader.php`, `HtmlRenderer.php`, `HuffmanDecoder.php`, `SteamProxyHelper.php`).

- `src/Capture/`
  - `ServerInfo.php`, `ServerAddress.php`, and related classes for representing captured data.
  - Protocol resolution and capture utilities are under `src/Capture/*` (there are helpers and exception classes such as `UnknownProtocolException`).
  - Storage and extractor implementations (JSON fixture helpers and server info extractors) are used by the capture CLI and tests. (Files and namespaces may be split across several files — search `src/Capture` for the exact classes.)

- `bin/capture.php`
  - Command-line capture tool that uses the capture components to query a server and store a JSON fixture under `tests/fixtures/`.

### Examples, Tests, and Protos

- `examples/` contains many short PHP scripts demonstrating protocol usage (e.g. `Cs2.php`, `Csgo.php`, `Arma.php`, etc.). Use these as quick-start examples when adding or debugging a protocol.
- `tests/` contains unit and integration tests; fixtures are kept under `tests/fixtures/` and used for replay testing.
- `z_protos/` holds bundled protocol libraries (GameQ and others). These are not necessarily authored here but are distributed with the repository for convenience.

### High-level Data Flow

1. Caller instantiates `CSQuery` (factory) and requests a protocol-specific server object (via `ServerProtocols` registry).
2. The protocol implementation prepares one or more UDP/TCP queries using `UdpClient` or other helpers and sends them to the target server.
3. Raw packets are parsed by protocol classes and translated into structured server info (fields such as `numplayers`, `mapname`, `players`, `rules`).
4. For capture runs, the CLI / capture logic wraps the parsed output together with raw packets and metadata and writes a JSON fixture under `tests/fixtures/{protocol}/{version}/`.

### Configuration & Running

- Configuration for capture tools is located at `config/capture_config.php`.
- Install dependencies with Composer and run tests via `composer tests-fast` (see `docs/developer-manual/setup.md`).

### Design Principles (practical)

- Keep protocol parsing isolated to `src/CSQuery/ServerProtocols/*` so new protocols are easy to add.
- Use utilities under `src/CSQuery/Util` to avoid duplicating networking and parsing helpers.
- Fixtures stored in `tests/fixtures/` allow unit tests to replay previously captured server sessions without network access.

### Where to change things

- Add or update protocol implementation: `src/CSQuery/ServerProtocols/{YourProtocol}.php` and register it in `ServerProtocols.php`.
- Add capture-related helpers or new storage backends under `src/Capture/`.
- Add examples under `examples/` and tests under `tests/` (include fixtures in `tests/fixtures/` where appropriate).

### Small ASCII Diagram

Caller/CLI
   -> src/CSQuery/CSQuery (factory)
       -> src/CSQuery/ServerProtocols/{Protocol} (network/query/parsing)
            -> src/CSQuery/Util (UdpClient, PacketReader)
       -> src/Capture (capture orchestration, storage)
   -> tests/fixtures (JSON output)

### Links and next steps

- For supported protocols and game mappings, consult `docs/protocols.md` and `docs/game_list.md`.
- If you find mismatches between protocol names in `docs/` and `src/CSQuery/ServerProtocols/`,
  open an issue or send a PR updating `docs/protocols.md`.
