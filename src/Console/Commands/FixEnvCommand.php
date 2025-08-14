<?php

namespace Primalmaxor\LaravelEnvDoctor\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class FixEnvCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'env:fix 
                            {--example= : Path to the example env file (default: .env.example)}
                            {--env= : Path to the main env file (default: .env)}
                            {--backup : Create backup before making changes}
                            {--interactive : Ask for confirmation before each change}
                            {--dry-run : Show what would be changed without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically fix missing environment variables and common formatting issues';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $examplePath = $this->option('example') ?: '.env.example';
        $envPath = $this->option('env') ?: '.env';
        $backup = $this->option('backup');
        $interactive = $this->option('interactive');
        $dryRun = $this->option('dry-run');

        if (!File::exists($examplePath)) {
            $this->error("Example file not found: {$examplePath}");

            return 1;
        }

        if (!File::exists($envPath)) {
            $this->error("Environment file not found: {$envPath}");

            return 1;
        }

        $this->info("Fixing environment file: {$envPath}");
        $this->line('');

        $exampleVars = $this->parseEnvFile($examplePath);
        $envVars = $this->parseEnvFile($envPath);
        $originalContent = File::get($envPath);

        if ($backup && !$dryRun) {
            $backupPath = $envPath . '.backup.' . date('Y-m-d-H-i-s');
            File::copy($envPath, $backupPath);
            $this->info("Backup created: {$backupPath}");
        }

        $missingVars = array_diff_key($exampleVars, $envVars);
        
        if (empty($missingVars)) {
            $this->info("No missing variables found. Your .env file is up to date!");

            return 0;
        }

        $this->info("Found " . count($missingVars) . " missing variables:");
        foreach ($missingVars as $key => $value) {
            $this->line("  - {$key}");
        }
        $this->line('');

        $fixedContent = $this->fixMissingVariables($originalContent, $missingVars, $interactive, $dryRun);
        $fixedContent = $this->fixFormattingIssues($fixedContent, $dryRun);

        if (!$dryRun) {
            File::put($envPath, $fixedContent);
            $this->info("Environment file updated successfully!");
        } else {
            $this->info("Dry run completed. No changes were made.");
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
     * Fix missing variables in the content.
     */
    private function fixMissingVariables(string $content, array $missingVars, bool $interactive, bool $dryRun): string
    {
        $lines = explode("\n", $content);
        $newLines = [];

        foreach ($lines as $line) {
            $newLines[] = $line;
        }

        foreach ($missingVars as $key => $value) {
            $shouldAdd = true;

            if ($interactive) {
                $shouldAdd = $this->confirm("Add missing variable: {$key} = {$value}?");
            }

            if ($shouldAdd) {
                $newLines[] = "{$key}={$value}";
                if (!$dryRun) {
                    $this->line("Added: {$key}={$value}");
                }
            }
        }

        return implode("\n", $newLines);
    }

    /**
     * Fix common formatting issues.
     */
    private function fixFormattingIssues(string $content, bool $dryRun): string
    {
        $lines = explode("\n", $content);
        $fixedLines = [];
        $changes = [];

        foreach ($lines as $line) {
            $originalLine = $line;
            $fixedLine = $line;

            if (preg_match('/^([^=]+)\s*=\s*(.*)$/', $line, $matches)) {
                $key = trim($matches[1]);
                $value = trim($matches[2]);
                $fixedLine = "{$key}={$value}";
                
                if ($fixedLine !== $originalLine) {
                    $changes[] = "Fixed spacing: {$originalLine} → {$fixedLine}";
                }
            }

            if (preg_match('/^([^=]+)=(.*)$/', $fixedLine, $matches)) {
                $key = $matches[1];
                $value = $matches[2];
                
                if (str_contains($value, ' ') && !preg_match('/^["\'].*["\']$/', $value)) {
                    $fixedLine = "{$key}=\"{$value}\"";
                    $changes[] = "Added quotes: {$key}={$value} → {$fixedLine}";
                }
            }

            $fixedLines[] = $fixedLine;
        }

        if (!empty($changes) && !$dryRun) {
            $this->line('');
            $this->info("Formatting fixes applied:");
            
            foreach ($changes as $change) {
                $this->line("  - {$change}");
            }
        }

        return implode("\n", $fixedLines);
    }
} 