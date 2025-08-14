# Laravel Env Doctor

A Laravel package to diagnose and fix common environment configuration issues by comparing environment files and identifying differences.

## Features

- 🔍 Compare `.env` files with `.env.example`
- 📊 Find missing environment variables
- 🚨 Detect extra environment variables
- ⚠️ Identify different values for the same keys
- 🌐 Compare all environment files in your project
- 🔍 Audit entire project for environment variable usage
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