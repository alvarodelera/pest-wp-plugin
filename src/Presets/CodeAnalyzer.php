<?php

declare(strict_types=1);

namespace PestWP\Presets;

/**
 * WordPress Code Analyzer.
 *
 * Analyzes PHP code for WordPress-specific issues and bad practices.
 */
final class CodeAnalyzer
{
    /**
     * Analysis results.
     *
     * @var array<int, array{line: int, message: string, severity: string, pattern: string}>
     */
    private array $issues = [];

    /**
     * Analyze a PHP file for issues.
     *
     * @return array<int, array{line: int, message: string, severity: string, pattern: string}>
     */
    public function analyzeFile(string $filePath): array
    {
        if (! file_exists($filePath)) {
            throw new \InvalidArgumentException("File not found: {$filePath}");
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new \RuntimeException("Could not read file: {$filePath}");
        }

        return $this->analyzeCode($content);
    }

    /**
     * Analyze PHP code string for issues.
     *
     * @return array<int, array{line: int, message: string, severity: string, pattern: string}>
     */
    public function analyzeCode(string $code): array
    {
        $this->issues = [];
        $lines = explode("\n", $code);

        foreach ($lines as $lineNumber => $line) {
            $this->analyzeLine($line, $lineNumber + 1);
        }

        return $this->issues;
    }

    /**
     * Analyze a single line of code.
     */
    private function analyzeLine(string $line, int $lineNumber): void
    {
        // Skip comments
        $trimmedLine = trim($line);
        if (str_starts_with($trimmedLine, '//') || str_starts_with($trimmedLine, '*') || str_starts_with($trimmedLine, '/*')) {
            return;
        }

        // Check forbidden functions
        foreach (WordPressPreset::FORBIDDEN_FUNCTIONS as $function => $message) {
            if ($this->containsFunctionCall($line, $function)) {
                $this->addIssue($lineNumber, $message, 'error', $function);
            }
        }

        // Check deprecated MySQL functions
        foreach (WordPressPreset::DEPRECATED_MYSQL_FUNCTIONS as $function => $message) {
            if ($this->containsFunctionCall($line, $function)) {
                $this->addIssue($lineNumber, $message, 'error', $function);
            }
        }

        // Check discouraged patterns
        foreach (WordPressPreset::DISCOURAGED_PATTERNS as $pattern => $message) {
            if (str_contains($line, $pattern)) {
                $this->addIssue($lineNumber, $message, 'warning', $pattern);
            }
        }

        // Check security sensitive functions
        foreach (WordPressPreset::SECURITY_SENSITIVE as $function => $message) {
            if ($this->containsFunctionCall($line, $function)) {
                $this->addIssue($lineNumber, $message, 'warning', $function);
            }
        }

        // Check unsanitized superglobals
        foreach (WordPressPreset::USE_SANITIZED_FUNCTIONS as $superglobal => $message) {
            if (str_contains($line, $superglobal) && ! $this->isSanitized($line, $superglobal)) {
                $this->addIssue($lineNumber, $message, 'warning', $superglobal);
            }
        }
    }

    /**
     * Check if a line contains a function call (not just the function name in a string).
     */
    private function containsFunctionCall(string $line, string $function): bool
    {
        // First, remove string contents to avoid false positives
        $lineWithoutStrings = $this->removeStringContents($line);

        // Look for function call pattern: function_name(
        $pattern = '/\b' . preg_quote($function, '/') . '\s*\(/';

        return preg_match($pattern, $lineWithoutStrings) === 1;
    }

    /**
     * Remove string contents from a line to avoid false positives.
     *
     * Replaces content inside single and double quotes with placeholders.
     */
    private function removeStringContents(string $line): string
    {
        // Remove double-quoted strings (handling escaped quotes)
        $line = preg_replace('/"(?:[^"\\\\]|\\\\.)*"/', '""', $line) ?? $line;

        // Remove single-quoted strings (handling escaped quotes)
        $line = preg_replace("/\'(?:[^\'\\\\]|\\\\.)*\'/", "''", $line) ?? $line;

        return $line;
    }

    /**
     * Check if a superglobal access is properly sanitized.
     */
    private function isSanitized(string $line, string $superglobal): bool
    {
        $sanitizationFunctions = [
            'sanitize_text_field',
            'sanitize_email',
            'sanitize_url',
            'sanitize_title',
            'sanitize_file_name',
            'sanitize_key',
            'sanitize_mime_type',
            'sanitize_option',
            'sanitize_sql_orderby',
            'sanitize_html_class',
            'wp_unslash',
            'absint',
            'intval',
            'floatval',
            'esc_html',
            'esc_attr',
            'esc_url',
            'esc_url_raw',
            'wp_kses',
            'wp_kses_post',
        ];

        foreach ($sanitizationFunctions as $func) {
            if (str_contains($line, $func)) {
                return true;
            }
        }

        // Check if it's in an isset() or empty() check (common pattern)
        if (preg_match('/\b(isset|empty)\s*\(.*' . preg_quote($superglobal, '/') . '/', $line)) {
            return true;
        }

        return false;
    }

    /**
     * Add an issue to the results.
     */
    private function addIssue(int $line, string $message, string $severity, string $pattern): void
    {
        $this->issues[] = [
            'line' => $line,
            'message' => $message,
            'severity' => $severity,
            'pattern' => $pattern,
        ];
    }

    /**
     * Get issues filtered by severity.
     *
     * @return array<int, array{line: int, message: string, severity: string, pattern: string}>
     */
    public function getIssuesBySeverity(string $severity): array
    {
        return array_filter($this->issues, fn ($issue) => $issue['severity'] === $severity);
    }

    /**
     * Check if there are any errors.
     */
    public function hasErrors(): bool
    {
        return count($this->getIssuesBySeverity('error')) > 0;
    }

    /**
     * Check if there are any warnings.
     */
    public function hasWarnings(): bool
    {
        return count($this->getIssuesBySeverity('warning')) > 0;
    }

    /**
     * Get a summary of issues.
     *
     * @return array{errors: int, warnings: int, total: int}
     */
    public function getSummary(): array
    {
        return [
            'errors' => count($this->getIssuesBySeverity('error')),
            'warnings' => count($this->getIssuesBySeverity('warning')),
            'total' => count($this->issues),
        ];
    }

    /**
     * Format issues as a string report.
     */
    public function formatReport(string $filePath = ''): string
    {
        if (empty($this->issues)) {
            return "No issues found.\n";
        }

        $output = '';
        if ($filePath !== '') {
            $output .= "File: {$filePath}\n";
            $output .= str_repeat('-', strlen("File: {$filePath}")) . "\n";
        }

        foreach ($this->issues as $issue) {
            $severity = strtoupper($issue['severity']);
            $output .= "[{$severity}] Line {$issue['line']}: {$issue['message']}\n";
        }

        $summary = $this->getSummary();
        $output .= "\nSummary: {$summary['errors']} error(s), {$summary['warnings']} warning(s)\n";

        return $output;
    }
}
