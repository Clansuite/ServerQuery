# Introduction

## Overview

Clansuite Server Query is a robust PHP library designed to query game and voice servers across various protocols. It enables developers and system administrators to retrieve real-time information about game servers, including player counts, server details, and rules, without relying on external services.

This library is a modern rewrite of the deprecated gsQuery project, incorporating best practices for reliability, extensibility, and testing.

## Purpose

The primary purpose of Clansuite Server Query is to provide a programmatic way to:

- Query game servers for status information.
- Capture network packets for offline testing and development.
- Integrate server monitoring into applications, websites, or tools.

## Key Features

- **Multi-Protocol Support**: Queries servers using protocols like Quake, Source, Battlefield, and more.
- **Flexible Output**: Supports JSON, HTML, and raw data formats.
- **Fixture-Based Testing**: Capture and replay server responses for reliable testing.
- **Web Interface**: Built-in web-based query interface.
- **Extensible Architecture**: Easy to add support for new protocols.

## Benefits

- **Reliability**: Use captured fixtures for consistent testing without network dependencies.
- **Performance**: Efficient querying with minimal overhead.
- **Community-Driven**: Open-source and actively maintained.
- **Easy Integration**: Simple API for PHP applications.

## Supported Servers

See the [full list of supported servers](SUPPORTED_SERVERS.md) for details.
