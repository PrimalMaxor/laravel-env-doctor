# Laravel Env Doctor

A Laravel package to diagnose and fix common environment configuration issues by comparing environment files and identifying differences.

## Features

- 🔍 Compare `.env` files with `.env.example`
- 📊 Find missing environment variables
- 🚨 Detect extra environment variables
- ⚠️ Identify different values for the same keys
- 🌐 Compare all environment files in your project
- 🔍 Audit entire project for environment variable usage
- 🛠️ **Auto-fix missing variables and formatting issues**
- ✅ **Lint environment files for syntax and best practices**
- 🔒 **Security scanning for sensitive data exposure**
- 📝 Detailed reporting with summaries

## Installation

1. Install the package via Composer:

```bash
composer require primalmaxor/laravel-env-doctor
```

2. The service provider will be automatically registered. If you need to register it manually, add it to your `config/app.php`:

```php
'providers' => [
    // ...
    Primalmaxor\LaravelEnvDoctor\LaravelEnvDoctorServiceProvider::class,
],
```

3. (Optional) Publish the configuration file:

```bash
php artisan vendor:publish --tag=laravel-env-doctor-config
```

## Usage

### Basic Comparison

Compare your `.env` file with `.env.example`:

```bash
php artisan env:compare
```

### Custom File Paths

Compare specific files:

```bash
php artisan env:compare --example=.env.staging --env=.env.production
```

### Compare All Environment Files

Compare all environment files in your project with the example file:

```bash
php artisan env:compare --all
```

### Environment Usage Audit

Audit your entire project for environment variable usage:

```bash
php artisan env:audit
```

### Advanced Audit Options

Audit with config file checking and detailed output:

```bash
php artisan env:audit --config --detailed
```

### Auto-Fix Environment Issues

Automatically fix missing variables and formatting issues:

```bash
php artisan env:fix
```

With backup and interactive mode:

```bash
php artisan env:fix --backup --interactive
```

### Lint Environment Files

Check for syntax errors and best practices:

```bash
php artisan env:lint
```

With strict mode and different output formats:

```bash
php artisan env:lint --strict --format=json
```

### Security Scanning

Scan for security issues and sensitive data:

```bash
php artisan env:security
```

With Git tracking check and strict mode:

```bash
php artisan env:security --strict --check-git
```

## Command Options

| Option | Description | Default |
|--------|-------------|---------|
| `--example` | Path to the example env file | `.env.example` |
| `--env` | Path to the main env file | `.env` |
| `--all` | Compare all env files in the project | `false` |

### env:audit Command Options

| Option | Description | Default |
|--------|-------------|---------|
| `--example` | Path to the example env file | `.env.example` |
| `--env` | Path to the main env file | `.env` |
| `--config` | Also check config files for environment usage | `false` |
| `--detailed` | Show detailed file locations for each usage | `false` |

### env:fix Command Options

| Option | Description | Default |
|--------|-------------|---------|
| `--example` | Path to the example env file | `.env.example` |
| `--env` | Path to the main env file | `.env` |
| `--backup` | Create backup before making changes | `false` |
| `--interactive` | Ask for confirmation before each change | `false` |
| `--dry-run` | Show what would be changed without making changes | `false` |

### env:lint Command Options

| Option | Description | Default |
|--------|-------------|---------|
| `--file` | Path to the env file to lint | `.env` |
| `--strict` | Enable strict mode for additional checks | `false` |
| `--format` | Output format (text, json, xml) | `text` |

### env:security Command Options

| Option | Description | Default |
|--------|-------------|---------|
| `--file` | Path to the env file to scan | `.env` |
| `--strict` | Enable strict security checks | `false` |
| `--check-git` | Check if sensitive files are committed to git | `false` |
| `--format` | Output format (text, json, xml) | `text` |

## Output Examples

### Missing Variables
```
Missing in .env (present in .env.example):
  - APP_DEBUG
  - CACHE_DRIVER
```

### Extra Variables
```
Extra in .env (not in .env.example):
  - CUSTOM_VARIABLE
  - DEBUG_MODE
```

### Different Values
```
Different values between .env.example and .env:
  APP_ENV:
    .env.example: local
    .env: production
```

### Summary
```
Summary:
  Total keys in .env.example: 25
  Total keys in .env: 23
  Missing keys: 2
  Extra keys: 0
  Different values: 1
```

### Environment Usage Audit Output

```
Environment Variable Usage Analysis
==================================================

Environment variables used in code but missing in .env.example:
  - CUSTOM_API_KEY
  - DEBUG_MODE

Environment variables defined in .env.example but not used in code:
  - AWS_ACCESS_KEY_ID
  - AWS_SECRET_ACCESS_KEY

Summary:
  Total environment variables used in code: 15
  Total variables defined in .env.example: 25
  Total variables defined in .env: 23
  Missing in .env.example: 2
  Missing in .env: 4
  Unused in .env.example: 10
  Unused in .env: 8
```

### Auto-Fix Output

```
Fixing environment file: .env

Found 3 missing variables:
  - APP_DEBUG
  - CACHE_DRIVER
  - SESSION_DRIVER

Added: APP_DEBUG=true
Added: CACHE_DRIVER=file
Added: SESSION_DRIVER=file

Formatting fixes applied:
  - Fixed spacing: APP_NAME = Laravel → APP_NAME=Laravel
  - Added quotes: APP_URL = http://localhost → APP_URL="http://localhost"

Environment file updated successfully!
```

### Linting Output

```
Linting environment file: .env

Found 4 issues:
  Errors: 1
  Warnings: 2
  Info: 1

[ERROR] Line 5: Missing equals sign (=)
  APP_NAME Laravel

[WARNING] Line 8: Spaces around equals sign (recommended: no spaces)
  APP_DEBUG = true

[WARNING] Line 12: Value with spaces should be quoted
  APP_URL = http://localhost

[INFO] Line 15: Key should be uppercase (Laravel convention)
  app_env = local
```

### Security Scan Output

```
🔒 Security scanning environment file: .env

🔍 Security Scan Results:
  Critical: 1
  High: 2
  Medium: 1
  Low: 1

[HIGH] ERROR - Line 8: Sensitive key detected: DB_PASSWORD
  DB_PASSWORD=password
  💡 Recommendation: Use a strong, unique password with at least 12 characters

[HIGH] ERROR - Line 12: Weak password detected: password
  API_SECRET=password
  💡 Recommendation: Use a strong, unique password

[CRITICAL] ERROR - GIT: Environment file is tracked by Git: .env
  File is committed to version control
  💡 Recommendation: Remove from Git tracking: git rm --cached .env
```

## Configuration

You can customize the package behavior by publishing the configuration file:

```bash
php artisan vendor:publish --tag=laravel-env-doctor-config
```

This will create `config/laravel-env-doctor.php` where you can modify:

- Default file paths
- File patterns to include
- Directories to exclude when searching

## Requirements

- PHP 8.1+
- Laravel 10.0+ or 11.0+

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.