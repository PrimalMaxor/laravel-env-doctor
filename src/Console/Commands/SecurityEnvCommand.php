<?php

namespace Primalmaxor\LaravelEnvDoctor\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class SecurityEnvCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'env:security 
                            {--file= : Path to the env file to scan (default: .env)}
                            {--strict : Enable strict security checks}
                            {--check-git : Check if sensitive files are committed to git}
                            {--format=text : Output format (text, json, xml)}
                            {--risk-level= : Minimum risk level to report (critical,high,medium,low)}
                            {--export : Export findings to a file}
                            {--fix : Auto-fix simple security issues where possible}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scan environment files for security issues and sensitive data exposure';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $filePath = $this->option('file') ?: '.env';
        $strict = $this->option('strict');
        $checkGit = $this->option('check-git');
        $format = $this->option('format');
        $riskLevel = $this->option('risk-level');
        $export = $this->option('export');
        $fix = $this->option('fix');

        if (!File::exists($filePath)) {
            $this->error("File not found: {$filePath}");

            return 1;
        }

        $this->info("🔒 Security scanning environment file: {$filePath}");
        $this->line('');

        $content = File::get($filePath);
        $lines = explode("\n", $content);
        
        $securityIssues = [];
        $lineNumber = 0;

        foreach ($lines as $line) {
            $lineNumber++;
            $lineIssues = $this->scanLine($line, $lineNumber, $strict);
            $securityIssues = array_merge($securityIssues, $lineIssues);
        }

        $fileIssues = $this->scanFile($content, $strict);
        $securityIssues = array_merge($securityIssues, $fileIssues);

        if ($checkGit) {
            $gitIssues = $this->checkGitSecurity($filePath);
            $securityIssues = array_merge($securityIssues, $gitIssues);
        }

        if ($riskLevel) {
            $securityIssues = $this->filterByRiskLevel($securityIssues, $riskLevel);
        }

        if ($fix && !empty($securityIssues)) {
            $fixedContent = $this->autoFixSecurity($content, $securityIssues);

            if ($fixedContent !== $content) {
                File::put($filePath, $fixedContent);
                $this->info("Auto-fixed security issues and updated file.");
            }
        }

        if ($export && !empty($securityIssues)) {
            $this->exportFindings($securityIssues, $filePath);
        }

        $this->displaySecurityResults($securityIssues, $format);

        return empty($securityIssues) ? 0 : 1;
    }

    /**
     * Scan a single line for security issues.
     */
    private function scanLine(string $line, int $lineNumber, bool $strict): array
    {
        $issues = [];
        $trimmedLine = trim($line);

        if (empty($trimmedLine) || str_starts_with($trimmedLine, '#')) {
            return $issues;
        }

        if (!str_contains($trimmedLine, '=')) {
            return $issues;
        }

        $parts = explode('=', $trimmedLine, 2);
        if (count($parts) !== 2) {
            return $issues;
        }

        $key = trim($parts[0]);
        $value = trim($parts[1]);

        $sensitiveKeys = $this->getSensitiveKeys();
        foreach ($sensitiveKeys as $sensitiveKey => $severity) {
            if (stripos($key, $sensitiveKey) !== false) {
                $issues[] = [
                    'type' => $severity,
                    'line' => $lineNumber,
                    'message' => "Sensitive key detected: {$key}",
                    'line_content' => $line,
                    'risk' => $this->getRiskLevel($severity),
                    'recommendation' => $this->getRecommendation($key, $value),
                    'fixable' => false
                ];
            }
        }

        $weakValueIssues = $this->checkWeakValues($key, $value, $lineNumber, $line);
        $issues = array_merge($issues, $weakValueIssues);

        if ($strict) {
            if (in_array(strtolower($value), ['secret', 'password', 'key', 'token', 'example', 'test'])) {
                $issues[] = [
                    'type' => 'warning',
                    'line' => $lineNumber,
                    'message' => "Default/example value detected: {$value}",
                    'line_content' => $line,
                    'risk' => 'medium',
                    'recommendation' => 'Replace with actual secure value',
                    'fixable' => false
                ];
            }

            if (empty($value) && $this->isSensitiveKey($key)) {
                $issues[] = [
                    'type' => 'warning',
                    'line' => $lineNumber,
                    'message' => "Empty sensitive key: {$key}",
                    'line_content' => $line,
                    'risk' => 'medium',
                    'recommendation' => 'Set a secure value or remove if not needed',
                    'fixable' => false
                ];
            }

            if (in_array(strtolower($value), ['localhost', '127.0.0.1', '0.0.0.0']) && 
                (stripos($key, 'host') !== false || stripos($key, 'url') !== false)) {
                $issues[] = [
                    'type' => 'warning',
                    'line' => $lineNumber,
                    'message' => "Local development value in production: {$value}",
                    'line_content' => $line,
                    'risk' => 'medium',
                    'recommendation' => 'Use production host/URL values',
                    'fixable' => false
                ];
            }
        }

        return $issues;
    }

    /**
     * Get list of sensitive keys with their severity levels.
     */
    private function getSensitiveKeys(): array
    {
        return [
            'password' => 'error',
            'secret' => 'error',
            'private_key' => 'error',
            'api_secret' => 'error',
            'jwt_secret' => 'error',
            'encryption_key' => 'error',
            'master_key' => 'error',
            'app_key' => 'error',
            
            'key' => 'warning',
            'token' => 'warning',
            'api_key' => 'warning',
            'access_token' => 'warning',
            'refresh_token' => 'warning',
            'session_secret' => 'warning',
            'cipher_key' => 'warning',
            'database_password' => 'warning',
            
            'username' => 'info',
            'email' => 'info',
            'host' => 'info',
            'port' => 'info',
            'database' => 'info'
        ];
    }

    /**
     * Check if a key is sensitive.
     */
    private function isSensitiveKey(string $key): bool
    {
        $sensitiveKeys = array_keys($this->getSensitiveKeys());
        foreach ($sensitiveKeys as $sensitiveKey) {
            if (stripos($key, $sensitiveKey) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check for weak values.
     */
    private function checkWeakValues(string $key, string $value, int $lineNumber, string $line): array
    {
        $issues = [];

        $weakPasswords = ['password', '123456', 'admin', 'root', 'test', 'secret', 'changeme', 'default'];
        if (in_array(strtolower($value), $weakPasswords)) {
            $issues[] = [
                'type' => 'error',
                'line' => $lineNumber,
                'message' => "Weak password detected: {$value}",
                'line_content' => $line,
                'risk' => 'high',
                'recommendation' => 'Use a strong, unique password',
                'fixable' => false
            ];
        }

        if (stripos($key, 'api_key') !== false && strlen($value) < 32) {
            $issues[] = [
                'type' => 'warning',
                'line' => $lineNumber,
                'message' => "Short API key detected (length: " . strlen($value) . ")",
                'line_content' => $line,
                'risk' => 'medium',
                'recommendation' => 'Use API keys with at least 32 characters',
                'fixable' => false
            ];
        }

        if (preg_match('/^(admin|user|test|demo|guest)$/i', $value)) {
            $issues[] = [
                'type' => 'warning',
                'line' => $lineNumber,
                'message' => "Predictable username detected: {$value}",
                'line_content' => $line,
                'risk' => 'medium',
                'recommendation' => 'Use unique, non-predictable usernames',
                'fixable' => false
            ];
        }

        return $issues;
    }

    /**
     * Scan the entire file for security issues.
     */
    private function scanFile(string $content, bool $strict): array
    {
        $issues = [];

        if ($strict) {
            $issues[] = [
                'type' => 'info',
                'line' => 'file',
                'message' => 'Ensure .env file has restricted permissions (600 or 400)',
                'line_content' => 'File permission check',
                'risk' => 'low',
                'recommendation' => 'Set file permissions to 600 (owner read/write only)',
                'fixable' => false
            ];
        }

        if (preg_match('/#\s*(password|secret|key|token)\s*=\s*\S+/i', $content)) {
            $issues[] = [
                'type' => 'warning',
                'line' => 'file',
                'message' => 'Commented sensitive data detected',
                'line_content' => 'Commented sensitive values',
                'risk' => 'medium',
                'recommendation' => 'Remove commented sensitive data',
                'fixable' => true
            ];
        }

        if (preg_match('/#.*(sk-|pk_|ghp_|gho_|ghu_|ghs_|ghr_)/i', $content)) {
            $issues[] = [
                'type' => 'error',
                'line' => 'file',
                'message' => 'Potential secret key pattern detected in comments',
                'line_content' => 'Secret key in comment',
                'risk' => 'high',
                'recommendation' => 'Remove any secret keys from comments',
                'fixable' => true
            ];
        }

        return $issues;
    }

    /**
     * Check Git security status.
     */
    private function checkGitSecurity(string $filePath): array
    {
        $issues = [];

        if (is_dir('.git')) {
            $gitOutput = shell_exec("git ls-files {$filePath} 2>/dev/null");
            
            if (!empty($gitOutput)) {
                $issues[] = [
                    'type' => 'error',
                    'line' => 'git',
                    'message' => "Environment file is tracked by Git: {$filePath}",
                    'line_content' => 'File is committed to version control',
                    'risk' => 'critical',
                    'recommendation' => 'Remove from Git tracking: git rm --cached {$filePath}',
                    'fixable' => false
                ];
            } else {
                $issues[] = [
                    'type' => 'info',
                    'line' => 'git',
                    'message' => "Environment file is not tracked by Git (good)",
                    'line_content' => 'File is properly ignored',
                    'risk' => 'none',
                    'recommendation' => 'Continue to keep this file out of version control',
                    'fixable' => false
                ];
            }

            if (File::exists('.gitignore')) {
                $gitignore = File::get('.gitignore');
                if (!str_contains($gitignore, $filePath) && !str_contains($gitignore, '*.env')) {
                    $issues[] = [
                        'type' => 'warning',
                        'line' => 'git',
                        'message' => "Environment file not in .gitignore",
                        'line_content' => 'File may be accidentally committed',
                        'risk' => 'medium',
                        'recommendation' => 'Add {$filePath} to .gitignore',
                        'fixable' => true
                    ];
                }
            }
        }

        return $issues;
    }

    /**
     * Filter issues by risk level.
     */
    private function filterByRiskLevel(array $issues, string $riskLevel): array
    {
        $riskLevels = ['critical' => 4, 'high' => 3, 'medium' => 2, 'low' => 1, 'none' => 0];
        $minRisk = $riskLevels[strtolower($riskLevel)] ?? 0;

        return array_filter($issues, function($issue) use ($riskLevels, $minRisk) {
            $issueRisk = $riskLevels[$issue['risk']] ?? 0;

            return $issueRisk >= $minRisk;
        });
    }

    /**
     * Auto-fix simple security issues.
     */
    private function autoFixSecurity(string $content, array $issues): string
    {
        $lines = explode("\n", $content);
        $fixedLines = [];

        foreach ($lines as $lineNumber => $line) {
            $fixedLine = $line;

            $lineIssues = array_filter($issues, fn($i) => $i['line'] === $lineNumber + 1 && $i['fixable']);

            foreach ($lineIssues as $issue) {
                switch ($issue['message']) {
                    case 'Commented sensitive data detected':
                        if (preg_match('/^#\s*(password|secret|key|token)\s*=/i', $line)) {
                            $fixedLine = "# REMOVED: " . $line;
                        }
                        break;
                }
            }

            $fixedLines[] = $fixedLine;
        }

        return implode("\n", $fixedLines);
    }

    /**
     * Export findings to a file.
     */
    private function exportFindings(array $issues, string $filePath): void
    {
        $exportPath = 'security-scan-' . date('Y-m-d-H-i-s') . '.json';
        $exportData = [
            'scan_date' => date('c'),
            'file_scanned' => $filePath,
            'total_issues' => count($issues),
            'issues' => $issues
        ];

        File::put($exportPath, json_encode($exportData, JSON_PRETTY_PRINT));
        $this->info("Security findings exported to: {$exportPath}");
    }

    /**
     * Get risk level description.
     */
    private function getRiskLevel(string $severity): string
    {
        return match($severity) {
            'error' => 'high',
            'warning' => 'medium',
            'info' => 'low',
            default => 'unknown'
        };
    }

    /**
     * Get security recommendation.
     */
    private function getRecommendation(string $key, string $value): string
    {
        if (stripos($key, 'password') !== false) {
            return 'Use a strong, unique password with at least 12 characters';
        }
        
        if (stripos($key, 'api_key') !== false) {
            return 'Use a long, random API key (32+ characters)';
        }
        
        if (stripos($key, 'secret') !== false) {
            return 'Use a cryptographically secure random value';
        }
        
        return 'Ensure this value is secure and not committed to version control';
    }

    /**
     * Display security scan results.
     */
    private function displaySecurityResults(array $issues, string $format): void
    {
        if (empty($issues)) {
            $this->info("✅ No security issues found! Your environment file is secure.");

            return;
        }

        $criticalCount = count(array_filter($issues, fn($i) => $i['risk'] === 'critical'));
        $highCount = count(array_filter($issues, fn($i) => $i['risk'] === 'high'));
        $mediumCount = count(array_filter($issues, fn($i) => $i['risk'] === 'medium'));
        $lowCount = count(array_filter($issues, fn($i) => $i['risk'] === 'low'));

        $this->line("🔍 Security Scan Results:");
        $this->line("  Critical: {$criticalCount}");
        $this->line("  High: {$highCount}");
        $this->line("  Medium: {$mediumCount}");
        $this->line("  Low: {$lowCount}");
        $this->line('');

        if ($format === 'json') {
            $this->output->write(json_encode($issues, JSON_PRETTY_PRINT));
            return;
        }

        if ($format === 'xml') {
            $this->output->write($this->generateSecurityXml($issues));
            return;
        }

        foreach ($issues as $issue) {
            $risk = strtoupper($issue['risk']);
            $type = strtoupper($issue['type']);
            $line = $issue['line'] === 'file' ? 'FILE' : ($issue['line'] === 'git' ? 'GIT' : "Line {$issue['line']}");
            $fixable = $issue['fixable'] ? ' [FIXABLE]' : '';
            
            $this->line("[{$risk}]{$fixable} {$type} - {$line}: {$issue['message']}");
            if ($issue['line'] !== 'file' && $issue['line'] !== 'git') {
                $this->line("  {$issue['line_content']}");
            }
            $this->line("  💡 Recommendation: {$issue['recommendation']}");
            $this->line('');
        }
    }

    /**
     * Generate XML output for security results.
     */
    private function generateSecurityXml(array $issues): string
    {
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        $xml .= "<security-scan-results>\n";
        
        foreach ($issues as $issue) {
            $xml .= "  <issue type=\"{$issue['type']}\" risk=\"{$issue['risk']}\" line=\"{$issue['line']}\" fixable=\"" . ($issue['fixable'] ? 'true' : 'false') . "\">\n";
            $xml .= "    <message>" . htmlspecialchars($issue['message']) . "</message>\n";
            $xml .= "    <content>" . htmlspecialchars($issue['line_content']) . "</content>\n";
            $xml .= "    <recommendation>" . htmlspecialchars($issue['recommendation']) . "</recommendation>\n";
            $xml .= "  </issue>\n";
        }
        
        $xml .= "</security-scan-results>";
        
        return $xml;
    }
} 