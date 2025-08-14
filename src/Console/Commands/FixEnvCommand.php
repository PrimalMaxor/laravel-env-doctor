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
                            {--dry-run : Show what would be changed without making changes}
                            {--format : Fix formatting issues (spacing, quotes)}
                            {--add-missing : Add missing variables from example file}
                            {--remove-unused : Remove variables not in example file}';

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
        $format = $this->option('format');
        $addMissing = $this->option('add-missing');
        $removeUnused = $this->option('remove-unused');

        if (!$format && !$addMissing && !$removeUnused) {
            $format = true;
            $addMissing = true;
        }

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

        $fixedContent = $originalContent;
        $changes = [];

        if ($addMissing) {
            $missingVars = array_diff_key($exampleVars, $envVars);
            
            if (!empty($missingVars)) {
                $this->info("Found " . count($missingVars) . " missing variables:");

                foreach ($missingVars as $key => $value) {
                    $this->line("  - {$key}");
                }

                $this->line('');

                $fixedContent = $this->addMissingVariables($fixedContent, $missingVars, $interactive, $dryRun, $changes);
            } else {
                $this->info("No missing variables found.");
            }
        }

        if ($removeUnused) {
            $unusedVars = array_diff_key($envVars, $exampleVars);
            
            if (!empty($unusedVars)) {
                $this->info("Found " . count($unusedVars) . " unused variables:");

                foreach ($unusedVars as $key => $value) {
                    $this->line("  - {$key}");
                }

                $this->line('');

                $fixedContent = $this->removeUnusedVariables($fixedContent, $unusedVars, $interactive, $dryRun, $changes);
            } else {
                $this->info("No unused variables found.");
            }
        }

        if ($format) {
            $this->info("Checking formatting issues...");
            $fixedContent = $this->fixFormattingIssues($fixedContent, $dryRun, $changes);
        }

        if (!empty($changes)) {
            $this->line('');
            $this->info("Changes Summary:");

            foreach ($changes as $change) {
                $this->line("  - {$change}");
            }
        } else {
            $this->info("No changes needed.");
        }

        if (!$dryRun && $fixedContent !== $originalContent) {
            File::put($envPath, $fixedContent);
            $this->info("Environment file updated successfully!");
        } elseif ($dryRun) {
            $this->info("Dry run completed. No changes were made.");
        }

        return 0;
    }

    /**
     * Parse an environment file and return key-value pairs.
     */
    private function parseEnvFile(string $filePath): array
    {
        if (!File::exists($filePath)) {
            return [];
        }

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
                if (!empty($key)) {
                    $vars[$key] = $value;
                }
            }
        }

        return $vars;
    }

    /**
     * Add missing variables to the content.
     */
    private function addMissingVariables(string $content, array $missingVars, bool $interactive, bool $dryRun, array &$changes): string
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
                $changes[] = "Added missing variable: {$key}";
            }
        }

        return implode("\n", $newLines);
    }

    /**
     * Remove unused variables from the content.
     */
    private function removeUnusedVariables(string $content, array $unusedVars, bool $interactive, bool $dryRun, array &$changes): string
    {
        $lines = explode("\n", $content);
        $newLines = [];

        foreach ($lines as $line) {
            $trimmedLine = trim($line);
            
            if (empty($trimmedLine) || str_starts_with($trimmedLine, '#')) {
                $newLines[] = $line;
                continue;
            }

            if (str_contains($trimmedLine, '=')) {
                $key = trim(explode('=', $trimmedLine, 2)[0]);
                
                if (isset($unusedVars[$key])) {
                    $shouldRemove = true;

                    if ($interactive) {
                        $shouldRemove = $this->confirm("Remove unused variable: {$key}?");
                    }

                    if ($shouldRemove) {
                        if (!$dryRun) {
                            $this->line("Removed: {$key}");
                        }
                        $changes[] = "Removed unused variable: {$key}";
                        continue;
                    }
                }
            }
            
            $newLines[] = $line;
        }

        return implode("\n", $newLines);
    }

    /**
     * Fix common formatting issues.
     */
    private function fixFormattingIssues(string $content, bool $dryRun, array &$changes): string
    {
        $lines = explode("\n", $content);
        $fixedLines = [];

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

            if (preg_match('/^([^=]+)=$/', $fixedLine, $matches)) {
                $key = $matches[1];
                $fixedLine = "{$key}=";
                $changes[] = "Fixed empty value: {$key}";
            }

            $fixedLines[] = $fixedLine;
        }

        return implode("\n", $fixedLines);
    }
} 