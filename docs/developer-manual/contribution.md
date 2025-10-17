# Contribution Guide

## Coding Standards

- Follow PSR-12 for code style.
- Use strict types: `declare(strict_types=1);`
- Namespace: `Clansuite\ServerQuery\` for main, `Clansuite\Capture\` for capture.
- Class names: PascalCase.
- Method names: camelCase.

## Adding a New Protocol

1. Create a new class in `src/CSQuery/ServerProtocols/` extending the base protocol.
2. Implement `query_server()` and data parsing.
3. Update `/docs/protocols.md` using `php bin/generate_docs.php`.
4. Add tests in `tests/CSQuery/ServerProtocols/`.
5. Update capture config if needed.

## Pull Request Process

1. Fork the repository.
2. Create a feature branch.
3. Write tests for new functionality.
4. Ensure all tests pass.
5. Run `composer phpcs-stepwise-fix` for style.
6. Submit PR with description.

## Commit Guidelines

- Use clear, descriptive messages.
- Prefix with type: `feat:`, `fix:`, `docs:`.

## Reporting Issues

- Use GitHub issues.
- Include PHP version, protocol, and steps to reproduce.

## Code of Conduct

- Be respectful and constructive.
- Focus on improving the project.
