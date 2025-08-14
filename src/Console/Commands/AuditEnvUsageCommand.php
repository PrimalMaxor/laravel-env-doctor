<?php

namespace Primalmaxor\LaravelEnvDoctor\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\Finder;

class AuditEnvUsageCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'env:audit 
                            {--example= : Path to the example env file (default: .env.example)}
                            {--env= : Path to the main env file (default: .env)}
                            {--config : Also check config files for environment usage}
                            {--detailed : Show detailed file locations for each usage}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Audit the entire project for environment variable usage and validate against env files';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $examplePath = $this->option('example') ?: '.env.example';
        $envPath = $this->option('env') ?: '.env';
        $checkConfig = $this->option('config');
        $detailed = $this->option('detailed');

        if (!File::exists($examplePath)) {
            $this->error("Example file not found: {$examplePath}");
            
            return 1;
        }

        $this->info("Auditing environment variable usage in the project...");
        $this->line('');

        $exampleVars = $this->parseEnvFile($examplePath);
        $envVars = File::exists($envPath) ? $this->parseEnvFile($envPath) : [];
        $envUsage = $this->findEnvUsageInProject($checkConfig);
        
        $this->analyzeEnvUsage($envUsage, $exampleVars, $envVars, $detailed, $examplePath, $envPath);

        return 0;
    }

    /**
     * Find all environment variable usage in the project.
     */
    private function findEnvUsageInProject(bool $checkConfig): array
    {
        $this->info("Scanning project files for environment variable usage...");
        
        $envUsage = [];
        $finder = new Finder();
        
        $finder->files()
            ->name('*.php')
            ->in(base_path())
            ->exclude(['vendor', 'node_modules', '.git', 'storage', 'bootstrap/cache']);

        $bar = $this->output->createProgressBar($finder->count());
        $bar->start();

        foreach ($finder as $file) {
            $content = $file->getContents();
            $relativePath = $file->getRelativePathname();
            
            $this->extractEnvCalls($content, $relativePath, $envUsage);
            
            if ($checkConfig) {
                $this->extractConfigCalls($content, $relativePath, $envUsage);
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->line('');

        return $envUsage;
    }

    /**
     * Extract env() calls from file content.
     */
    private function extractEnvCalls(string $content, string $filePath, array &$envUsage): void
    {
        preg_match_all("/env\s*\(\s*['\"]([^'\"]+)['\"]\s*(?:,\s*[^)]*)?\)/", $content, $matches);
        
        if (!empty($matches[1])) {
            foreach ($matches[1] as $envVar) {
                if (!isset($envUsage[$envVar])) {
                    $envUsage[$envVar] = [];
                }

                $envUsage[$envVar][] = [
                    'type' => 'env()',
                    'file' => $filePath,
                    'line' => $this->findLineNumber($content, "env('{$envVar}'")
                ];
            }
        }
    }

    /**
     * Extract config() calls from file content.
     */
    private function extractConfigCalls(string $content, string $filePath, array &$envUsage): void
    {
        preg_match_all("/config\s*\(\s*['\"]([^'\"]+)['\"]\s*\)/", $content, $matches);
        
        if (!empty($matches[1])) {
            foreach ($matches[1] as $configKey) {
                if ($this->isEnvironmentRelatedConfig($configKey)) {
                    $envVar = $this->configKeyToEnvVar($configKey);

                    if ($envVar) {
                        if (!isset($envUsage[$envVar])) {
                            $envUsage[$envVar] = [];
                        }

                        $envUsage[$envVar][] = [
                            'type' => 'config()',
                            'file' => $filePath,
                            'line' => $this->findLineNumber($content, "config('{$configKey}'")
                        ];
                    }
                }
            }
        }
    }

    /**
     * Check if a config key is likely environment-related.
     */
    private function isEnvironmentRelatedConfig(string $configKey): bool
    {
        $envRelatedKeys = [
            'app.name', 'app.env', 'app.debug', 'app.url',
            'database.connections.mysql.host', 'database.connections.mysql.database',
            'database.connections.mysql.username', 'database.connections.mysql.password',
            'mail.mailers.smtp.host', 'mail.mailers.smtp.port',
            'cache.default', 'session.driver', 'queue.default',
            'broadcasting.default', 'filesystems.default'
        ];
        
        return in_array($configKey, $envRelatedKeys) || 
            str_contains($configKey, 'database.') ||
            str_contains($configKey, 'mail.') ||
            str_contains($configKey, 'cache.') ||
            str_contains($configKey, 'session.') ||
            str_contains($configKey, 'queue.') ||
            str_contains($configKey, 'broadcasting.');
    }

    /**
     * Convert config key to potential environment variable name.
     */
    private function configKeyToEnvVar(string $configKey): ?string
    {
        $mappings = [
            'app.name' => 'APP_NAME',
            'app.env' => 'APP_ENV',
            'app.debug' => 'APP_DEBUG',
            'app.url' => 'APP_URL',
            'database.connections.mysql.host' => 'DB_HOST',
            'database.connections.mysql.database' => 'DB_DATABASE',
            'database.connections.mysql.username' => 'DB_USERNAME',
            'database.connections.mysql.password' => 'DB_PASSWORD',
            'mail.mailers.smtp.host' => 'MAIL_HOST',
            'mail.mailers.smtp.port' => 'MAIL_PORT',
            'cache.default' => 'CACHE_DRIVER',
            'session.driver' => 'SESSION_DRIVER',
            'queue.default' => 'QUEUE_CONNECTION',
        ];
        
        return $mappings[$configKey] ?? null;
    }

    /**
     * Find line number for a specific string in content.
     */
    private function findLineNumber(string $content, string $search): int
    {
        $lines = explode("\n", $content);

        foreach ($lines as $lineNum => $line) {
            if (str_contains($line, $search)) {
                return $lineNum + 1;
            }
        }

        return 0;
    }

    /**
     * Parse an environment file and return key-value pairs.
     */
    private function parseEnvFile(string $filePath): array
    {
        $content = File::get($filePath);
        $lines = explode("\n", $content);
        $vars = [];

        foreach ($lines as $line) {
            $line = trim($line);
            
            if (empty($line) || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }

            $parts = explode('=', $line, 2);
            if (count($parts) === 2) {
                $key = trim($parts[0]);
                $value = trim($parts[1]);
                $vars[$key] = $value;
            }
        }

        return $vars;
    }

    /**
     * Analyze environment variable usage and show results.
     */
    private function analyzeEnvUsage(array $envUsage, array $exampleVars, array $envVars, bool $detailed, string $examplePath, string $envPath): void
    {
        $this->line('');
        $this->info("Environment Variable Usage Analysis");
        $this->line(str_repeat('=', 50));

        $usedVars = array_keys($envUsage);
        $definedVars = array_keys($exampleVars);
        $envDefinedVars = array_keys($envVars);

        $unusedInExample = array_diff($definedVars, $usedVars);
        $unusedInEnv = array_diff($envDefinedVars, $usedVars);

        $missingInExample = array_diff($usedVars, $definedVars);
        $missingInEnv = array_diff($usedVars, $envDefinedVars);

        if (!empty($missingInExample)) {
            $this->warn("Environment variables used in code but missing in {$examplePath}:");

            foreach ($missingInExample as $var) {
                $this->line("  - {$var}");
                if ($detailed) {
                    $this->showUsageDetails($envUsage[$var]);
                }
            }

            $this->line('');
        }

        if (!empty($missingInEnv)) {
            $this->warn("Environment variables used in code but missing in {$envPath}:");

            foreach ($missingInEnv as $var) {
                $this->line("  - {$var}");
                if ($detailed) {
                    $this->showUsageDetails($envUsage[$var]);
                }
            }

            $this->line('');
        }

        // Show unused variables
        if (!empty($unusedInExample)) {
            $this->info("Environment variables defined in {$examplePath} but not used in code:");

            foreach ($unusedInExample as $var) {
                $this->line("  - {$var}");
            }

            $this->line('');
        }

        if (!empty($unusedInEnv)) {
            $this->info("Environment variables defined in {$envPath} but not used in code:");

            foreach ($unusedInEnv as $var) {
                $this->line("  - {$var}");
            }

            $this->line('');
        }

        $this->info("Summary:");
        $this->line("  Total environment variables used in code: " . count($usedVars));
        $this->line("  Total variables defined in {$examplePath}: " . count($definedVars));
        $this->line("  Total variables defined in {$envPath}: " . count($envDefinedVars));
        $this->line("  Missing in {$examplePath}: " . count($missingInExample));
        $this->line("  Missing in {$envPath}: " . count($missingInEnv));
        $this->line("  Unused in {$examplePath}: " . count($unusedInExample));
        $this->line("  Unused in {$envPath}: " . count($unusedInEnv));
    }

    /**
     * Show detailed usage information for a variable.
     */
    private function showUsageDetails(array $usages): void
    {
        foreach ($usages as $usage) {
            $this->line("    {$usage['type']} in {$usage['file']}:{$usage['line']}");
        }
    }
} 