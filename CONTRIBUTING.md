# Contributing to Protect.php

Thank you for your interest in contributing to Protect.php! This document provides guidelines and information for contributors.

## Reporting Issues

### Bug Reports and Feature Requests

Please use the GitHub issue tracker to report bugs, suggest features, or documentation improvements.

[When filing an issue](https://github.com/cipherstash/protectphp/issues/new/choose), please check [existing open](https://github.com/cipherstash/protectphp/issues?q=is%3Aissue+is%3Aopen+sort%3Aupdated-desc), or [recently closed](https://github.com/cipherstash/protectphp/issues?q=is%3Aissue+sort%3Aupdated-desc+is%3Aclosed), issues to make sure somebody else hasn't already reported the issue.

When reporting issues, please include:

- PHP version and platform information
- Steps to reproduce the issue
- Expected vs actual behavior
- Any relevant error messages or logs
- Minimal code example demonstrating the problem

### Security Issues

If you discover a potential security issue in this project, we ask that you contact us at security@cipherstash.com.

Please do not create a public GitHub issue for security vulnerabilities.

## Development Setup

### Requirements

- [PHP 8.1+](https://www.php.net/downloads)
- [Composer](https://getcomposer.org/download/)

### Initial Setup

Clone the repository and install dependencies:

```bash
git clone https://github.com/cipherstash/protectphp.git

cd protectphp

composer install
```

## Development Workflow

### Code Quality Standards

Ensure your code passes all quality checks before committing changes:

```bash
# Format code
composer format

# Run static analysis
composer stan
```

### Testing

Run the test suite to verify your changes:

```bash
# Unit tests
composer test:unit

# Integration tests (requires CipherStash credentials in .env file)
composer test:integration
```

### Commit Message Guidelines

This project follows [Conventional Commits](https://www.conventionalcommits.org) format, for example:

```
feat(protect): add `createSearchTerms()` method for searchable encryption
```

```
feat(exceptions): add `failedToCreateSearchTerms()` exception factory method
```

```
fix(protect): normalize empty string handling in `encrypt()` method
```

```
fix(data-converter): prevent type coercion errors in `convertToStorageFormat()`
```

```
perf(protect): optimize configuration validation in `buildEncryptConfig()`
```

```
refactor(data-converter): consolidate type validation in `validateDataType()` method
```

```
docs(readme): add searchable encryption examples
```

```
docs(protect): document encryption configuration array structure
```

```
test(integration): add `createSearchTerms()` validation
```

```
test(unit): verify `DataConverter` handles all PHP data types correctly
```

```
chore(composer): bump minimum PHP version requirement from 8.1 to 8.2
```

```
ci(workflows): add PHP 8.3 to compatibility test matrix
```

```
style(phpstan): fix `array<string, mixed>` type annotations in `Protect` class
```

## Pull Request Process

1. **Fork the repository** and create your feature branch from `main`:
   ```bash
   git checkout -b feat/my-new-feature
   ```
2. **Make your changes** to implement your feature or fix
3. **Add tests** for any new functionality or bug fixes
4. **Update documentation** as needed (README, code comments, etc.)
5. **Run quality checks and tests** to validate your changes
6. **Submit a pull request** with conventional commit messages and a clear description

### Pull Request Requirements

- Code must be properly formatted and pass static analysis
- New features must include appropriate tests
- All tests must pass
- Breaking changes must be clearly documented
- Commit messages should follow [Conventional Commits](https://www.conventionalcommits.org/) format

## Continuous Integration

Pull requests are automatically tested across all [supported PHP versions](README.md#requirements) to ensure compatibility before merging.

The CI/CD pipeline:

1. **Detects changes** to determine which components need testing
2. **Performs code quality checks** (formatting, static analysis)
3. **Runs comprehensive PHP test suites** across all PHP version combinations

Only pull requests that pass all checks will be merged.

## Project Information

### Versioning and Releases

This project follows [Semantic Versioning](https://semver.org/spec/v2.0.0.html). Version numbers are structured as `MAJOR.MINOR.PATCH`:

- **MAJOR**: Incompatible API changes
- **MINOR**: New functionality in a backwards compatible manner
- **PATCH**: Backwards compatible bug fixes

The [CHANGELOG](CHANGELOG.md) file will soon be automatically generated based on [Conventional Commits](https://www.conventionalcommits.org/). Please avoid editing it manually.

### Code of Conduct

This project has adopted the [Contributor Covenant](https://www.contributor-covenant.org/). For more information see the [Code of Conduct FAQ](CODE_OF_CONDUCT.md) or contact support@cipherstash.com with any questions or comments.

### License

By contributing to Protect.php, you agree that your contributions will be licensed under the same license as the project. See the [LICENSE](LICENSE.md) file for our project's licensing.
