<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Primalmaxor\LaravelEnvDoctor\Console\Commands\CompareEnvCommand;

echo "Laravel Env Doctor Demo\n";
echo "=======================\n\n";

echo "This package provides the following commands:\n";
echo "php artisan env:compare\n";
echo "php artisan env:audit\n\n";

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

echo "Example usage:\n";
echo "php artisan env:compare --example=.env.example --env=.env.production\n";
echo "php artisan env:compare --all\n";
echo "php artisan env:audit --config --detailed\n\n";

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
echo "5. Provide comprehensive usage analysis\n"; 