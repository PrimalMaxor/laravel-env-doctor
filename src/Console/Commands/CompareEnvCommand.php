<?php

namespace Primalmaxor\LaravelEnvDoctor\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\Finder;

class CompareEnvCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'env:compare 
                            {--example= : Path to the example env file (default: .env.example)}
                            {--env= : Path to the main env file (default: .env)}
                            {--all : Compare all env files in the project}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Compare environment files and show differences';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $examplePath = $this->option('example') ?: '.env.example';
        $envPath = $this->option('env') ?: '.env';
        $compareAll = $this->option('all');

        if ($compareAll) {
            return $this->compareAllEnvFiles($examplePath);
        }

        return $this->compareTwoFiles($examplePath, $envPath);
    }

    /**
     * Compare two specific environment files.
     */
    private function compareTwoFiles(string $examplePath, string $envPath): int
    {
        if (!File::exists($examplePath)) {
            $this->error("Example file not found: {$examplePath}");

            return 1;
        }

        if (!File::exists($envPath)) {
            $this->error("Environment file not found: {$envPath}");

            return 1;
        }

        $this->info("Comparing {$examplePath} with {$envPath}");
        $this->line('');

        $exampleVars = $this->parseEnvFile($examplePath);
        $envVars = $this->parseEnvFile($envPath);

        $this->showDifferences($examplePath, $envPath, $exampleVars, $envVars);

        return 0;
    }

    /**
     * Compare all environment files with the example file.
     */
    private function compareAllEnvFiles(string $examplePath): int
    {
        if (!File::exists($examplePath)) {
            $this->error("Example file not found: {$examplePath}");

            return 1;
        }

        $this->info("Comparing all environment files with {$examplePath}");
        $this->line('');

        $exampleVars = $this->parseEnvFile($examplePath);
        $envFiles = $this->findEnvFiles();

        if (empty($envFiles)) {
            $this->warn('No environment files found to compare.');

            return 0;
        }

        foreach ($envFiles as $envFile) {
            if ($envFile === $examplePath) {
                continue;
            }

            $this->line('');
            $this->line(str_repeat('=', 50));
            $this->info("File: {$envFile}");
            $this->line(str_repeat('=', 50));

            $envVars = $this->parseEnvFile($envFile);
            $this->showDifferences($examplePath, $envFile, $exampleVars, $envVars);
        }

        return 0;
    }

    /**
     * Find all environment files in the project.
     */
    private function findEnvFiles(): array
    {
        $finder = new Finder();
        $finder->files()
            ->name('*.env*')
            ->in(base_path())
            ->exclude(['vendor', 'node_modules', '.git']);

        $files = [];

        foreach ($finder as $file) {
            $files[] = $file->getRelativePathname();
        }

        return $files;
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
     * Show differences between two environment files.
     */
    private function showDifferences(string $examplePath, string $envPath, array $exampleVars, array $envVars): void
    {
        $exampleKeys = array_keys($exampleVars);
        $envKeys = array_keys($envVars);

        $missingInEnv = array_diff($exampleKeys, $envKeys);
        if (!empty($missingInEnv)) {
            $this->warn("Missing in {$envPath} (present in {$examplePath}):");
            foreach ($missingInEnv as $key) {
                $this->line("  - {$key}");
            }
            $this->line('');
        }

        $extraInEnv = array_diff($envKeys, $exampleKeys);
        if (!empty($extraInEnv)) {
            $this->warn("Extra in {$envPath} (not in {$examplePath}):");
            foreach ($extraInEnv as $key) {
                $this->line("  - {$key}");
            }
            $this->line('');
        }

        $commonKeys = array_intersect($exampleKeys, $envKeys);
        $differentValues = [];

        foreach ($commonKeys as $key) {
            if ($exampleVars[$key] !== $envVars[$key]) {
                $differentValues[$key] = [
                    'example' => $exampleVars[$key],
                    'env' => $envVars[$key]
                ];
            }
        }

        if (!empty($differentValues)) {
            $this->warn("Different values between {$examplePath} and {$envPath}:");
            foreach ($differentValues as $key => $values) {
                $this->line("  {$key}:");
                $this->line("    {$examplePath}: {$values['example']}");
                $this->line("    {$envPath}: {$values['env']}");
            }
            $this->line('');
        }

        $this->info("Summary:");
        $this->line("  Total keys in {$examplePath}: " . count($exampleVars));
        $this->line("  Total keys in {$envPath}: " . count($envVars));
        $this->line("  Missing keys: " . count($missingInEnv));
        $this->line("  Extra keys: " . count($extraInEnv));
        $this->line("  Different values: " . count($differentValues));
    }
} 