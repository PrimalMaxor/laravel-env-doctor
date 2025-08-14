<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Primalmaxor\LaravelEnvDoctor\Console\Commands\CompareEnvCommand;

echo "Laravel Env Doctor Demo\n";
echo "=======================\n\n";

echo "This package provides the following commands:\n";
echo "php artisan env:compare\n";
echo "php artisan env:audit\n";
echo "php artisan env:fix\n";
echo "php artisan env:lint\n";
echo "php artisan env:security\n\n";

echo "Command options:\n";
echo "env:compare:\n";
echo "- --example: Path to example env file (default: .env.example)\n";
echo "- --env: Path to main env file (default: .env)\n";
echo "- --all: Compare all env files in the project\n\n";

echo "env:audit:\n";
echo "- --example: Path to example env file (default: .env.example)\n";
echo "- --env: Path to main env file (default: .env)\n";
echo "- --config: Also check config files for environment usage\n";
echo "- --detailed: Show detailed file locations for each usage\n\n";

echo "env:fix:\n";
echo "- --example: Path to example env file (default: .env.example)\n";
echo "- --env: Path to main env file (default: .env)\n";
echo "- --backup: Create backup before making changes\n";
echo "- --interactive: Ask for confirmation before each change\n";
echo "- --dry-run: Show what would be changed without making changes\n\n";

echo "env:lint:\n";
echo "- --file: Path to env file to lint (default: .env)\n";
echo "- --strict: Enable strict mode for additional checks\n";
echo "- --format: Output format (text, json, xml)\n\n";

echo "env:security:\n";
echo "- --file: Path to env file to scan (default: .env)\n";
echo "- --strict: Enable strict security checks\n";
echo "- --check-git: Check if sensitive files are committed to git\n";
echo "- --format: Output format (text, json, xml)\n\n";

echo "Example usage:\n";
echo "php artisan env:compare --example=.env.example --env=.env.production\n";
echo "php artisan env:compare --all\n";
echo "php artisan env:audit --config --detailed\n";
echo "php artisan env:fix --backup --interactive\n";
echo "php artisan env:lint --strict --format=json\n";
echo "php artisan env:security --strict --check-git\n\n";

echo "The commands will:\n";
echo "env:compare:\n";
echo "1. Parse both environment files\n";
echo "2. Find missing variables in each file\n";
echo "3. Find extra variables in each file\n";
echo "4. Show different values for the same keys\n";
echo "5. Provide a summary of all differences\n\n";

echo "env:audit:\n";
echo "1. Scan entire project for env() and config() calls\n";
echo "2. Cross-reference with environment files\n";
echo "3. Find unused environment variables\n";
echo "4. Find missing environment variables\n";
echo "5. Provide comprehensive usage analysis\n\n";

echo "env:fix:\n";
echo "1. Find missing environment variables\n";
echo "2. Auto-add missing variables from .env.example\n";
echo "3. Fix formatting issues (spacing, quotes)\n";
echo "4. Create backups before making changes\n";
echo "5. Interactive mode for confirmation\n\n";

echo "env:lint:\n";
echo "1. Check syntax errors and formatting\n";
echo "2. Validate best practices\n";
echo "3. Detect duplicate keys\n";
echo "4. Multiple output formats (text, json, xml)\n";
echo "5. Strict mode for additional checks\n\n";

echo "env:security:\n";
echo "1. Scan for sensitive data exposure\n";
echo "2. Detect weak passwords and API keys\n";
echo "3. Check Git tracking status\n";
echo "4. Provide security recommendations\n";
echo "5. Risk level assessment and reporting\n"; 