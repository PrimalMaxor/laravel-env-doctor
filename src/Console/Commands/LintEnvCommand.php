<?php

namespace Primalmaxor\LaravelEnvDoctor\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class LintEnvCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'env:lint 
                            {--file= : Path to the env file to lint (default: .env)}
                            {--strict : Enable strict mode for additional checks}
                            {--format=text : Output format (text, json, xml)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Lint environment files for syntax errors and best practices';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $filePath = $this->option('file') ?: '.env';
        $strict = $this->option('strict');
        $format = $this->option('format');

        if (!File::exists($filePath)) {
            $this->error("File not found: {$filePath}");

            return 1;
        }

        $this->info("Linting environment file: {$filePath}");
        $this->line('');

        $content = File::get($filePath);
        $lines = explode("\n", $content);
        
        $issues = [];
        $lineNumber = 0;

        foreach ($lines as $line) {
            $lineNumber++;
            $lineIssues = $this->lintLine($line, $lineNumber, $strict);
            $issues = array_merge($issues, $lineIssues);
        }

        $fileIssues = $this->lintFile($content, $strict);
        $issues = array_merge($issues, $fileIssues);

        $this->displayResults($issues, $format);

        return empty($issues) ? 0 : 1;
    }

    /**
     * Lint a single line for issues.
     */
    private function lintLine(string $line, int $lineNumber, bool $strict): array
    {
        $issues = [];
        $trimmedLine = trim($line);

        if (empty($trimmedLine) || str_starts_with($trimmedLine, '#')) {
            return $issues;
        }

        if (!str_contains($trimmedLine, '=')) {
            $issues[] = [
                'type' => 'error',
                'line' => $lineNumber,
                'message' => 'Missing equals sign (=)',
                'line_content' => $line
            ];
            return $issues;
        }

        $parts = explode('=', $trimmedLine, 2);
        if (count($parts) !== 2) {
            $issues[] = [
                'type' => 'error',
                'line' => $lineNumber,
                'message' => 'Invalid key-value format',
                'line_content' => $line
            ];
            return $issues;
        }

        $key = trim($parts[0]);
        $value = trim($parts[1]);

        if (empty($key)) {
            $issues[] = [
                'type' => 'error',
                'line' => $lineNumber,
                'message' => 'Empty key',
                'line_content' => $line
            ];
        }

        if (preg_match('/\s+=\s+/', $line)) {
            $issues[] = [
                'type' => 'warning',
                'line' => $lineNumber,
                'message' => 'Spaces around equals sign (recommended: no spaces)',
                'line_content' => $line
            ];
        }

        if (str_contains($value, ' ') && !preg_match('/^["\'].*["\']$/', $value)) {
            $issues[] = [
                'type' => 'warning',
                'line' => $lineNumber,
                'message' => 'Value with spaces should be quoted',
                'line_content' => $line
            ];
        }

        if ($strict) {
            if ($key !== strtoupper($key)) {
                $issues[] = [
                    'type' => 'info',
                    'line' => $lineNumber,
                    'message' => 'Key should be uppercase (Laravel convention)',
                    'line_content' => $line
                ];
            }

            $sensitiveKeys = ['password', 'secret', 'key', 'token', 'api_key'];
            foreach ($sensitiveKeys as $sensitive) {
                if (stripos($key, $sensitive) !== false && $value !== '') {
                    $issues[] = [
                        'type' => 'warning',
                        'line' => $lineNumber,
                        'message' => 'Sensitive key detected - ensure this is not committed to version control',
                        'line_content' => $line
                    ];
                }
            }
        }

        return $issues;
    }

    /**
     * Lint the entire file for issues.
     */
    private function lintFile(string $content, bool $strict): array
    {
        $issues = [];

        $lines = explode("\n", $content);
        $keys = [];
        
        foreach ($lines as $lineNumber => $line) {
            $trimmedLine = trim($line);
            
            if (empty($trimmedLine) || str_starts_with($trimmedLine, '#')) {
                continue;
            }

            if (str_contains($trimmedLine, '=')) {
                $key = trim(explode('=', $trimmedLine, 2)[0]);
                
                if (isset($keys[$key])) {
                    $issues[] = [
                        'type' => 'error',
                        'line' => $lineNumber + 1,
                        'message' => "Duplicate key: {$key}",
                        'line_content' => $line
                    ];
                } else {
                    $keys[$key] = $lineNumber + 1;
                }
            }
        }

        if (preg_match('/[ \t]+$/', $content, $matches)) {
            $issues[] = [
                'type' => 'warning',
                'line' => 'file',
                'message' => 'File contains trailing whitespace',
                'line_content' => 'Trailing whitespace detected'
            ];
        }

        return $issues;
    }

    /**
     * Display linting results.
     */
    private function displayResults(array $issues, string $format): void
    {
        if (empty($issues)) {
            $this->info("✅ No issues found! Your environment file is clean.");
            return;
        }

        $errorCount = count(array_filter($issues, fn($i) => $i['type'] === 'error'));
        $warningCount = count(array_filter($issues, fn($i) => $i['type'] === 'warning'));
        $infoCount = count(array_filter($issues, fn($i) => $i['type'] === 'info'));

        $this->line("Found " . count($issues) . " issues:");
        $this->line("  Errors: {$errorCount}");
        $this->line("  Warnings: {$warningCount}");
        $this->line("  Info: {$infoCount}");
        $this->line('');

        if ($format === 'json') {
            $this->output->write(json_encode($issues, JSON_PRETTY_PRINT));
            return;
        }

        if ($format === 'xml') {
            $this->output->write($this->generateXml($issues));
            return;
        }

        foreach ($issues as $issue) {
            $type = strtoupper($issue['type']);
            $line = $issue['line'] === 'file' ? 'FILE' : "Line {$issue['line']}";
            
            $this->line("[{$type}] {$line}: {$issue['message']}");
            if ($issue['line'] !== 'file') {
                $this->line("  {$issue['line_content']}");
            }
            $this->line('');
        }
    }

    /**
     * Generate XML output.
     */
    private function generateXml(array $issues): string
    {
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        $xml .= "<lint-results>\n";
        
        foreach ($issues as $issue) {
            $xml .= "  <issue type=\"{$issue['type']}\" line=\"{$issue['line']}\">\n";
            $xml .= "    <message>" . htmlspecialchars($issue['message']) . "</message>\n";
            $xml .= "    <content>" . htmlspecialchars($issue['line_content']) . "</content>\n";
            $xml .= "  </issue>\n";
        }
        
        $xml .= "</lint-results>";
        return $xml;
    }
} 