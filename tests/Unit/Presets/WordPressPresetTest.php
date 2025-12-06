<?php

declare(strict_types=1);

use PestWP\Presets\CodeAnalyzer;
use PestWP\Presets\WordPressPreset;

describe('WordPress Preset', function () {
    describe('Constants', function () {
        it('has forbidden functions defined', function () {
            expect(WordPressPreset::FORBIDDEN_FUNCTIONS)
                ->toBeArray()
                ->toHaveKey('dd')
                ->toHaveKey('dump')
                ->toHaveKey('var_dump')
                ->toHaveKey('print_r');
        });

        it('has deprecated MySQL functions defined', function () {
            expect(WordPressPreset::DEPRECATED_MYSQL_FUNCTIONS)
                ->toBeArray()
                ->toHaveKey('mysql_connect')
                ->toHaveKey('mysql_query')
                ->toHaveKey('mysql_fetch_array');
        });

        it('has discouraged patterns defined', function () {
            expect(WordPressPreset::DISCOURAGED_PATTERNS)
                ->toBeArray()
                ->toHaveKey('global $wpdb')
                ->toHaveKey('global $post');
        });

        it('has security sensitive functions defined', function () {
            expect(WordPressPreset::SECURITY_SENSITIVE)
                ->toBeArray()
                ->toHaveKey('eval')
                ->toHaveKey('exec')
                ->toHaveKey('extract');
        });

        it('has sanitization warnings defined', function () {
            expect(WordPressPreset::USE_SANITIZED_FUNCTIONS)
                ->toBeArray()
                ->toHaveKey('$_GET')
                ->toHaveKey('$_POST')
                ->toHaveKey('$_REQUEST');
        });
    });

    describe('Helper Methods', function () {
        it('can get all forbidden functions', function () {
            $forbidden = WordPressPreset::getForbiddenFunctions();

            expect($forbidden)
                ->toBeArray()
                ->toHaveKey('dd')
                ->toHaveKey('mysql_connect');
        });

        it('can get all patterns', function () {
            $patterns = WordPressPreset::getAllPatterns();

            expect($patterns)->toBeArray();
            expect(count($patterns))->toBeGreaterThan(20);
        });

        it('can check if function is forbidden', function () {
            expect(WordPressPreset::isForbidden('dd'))->toBeTrue();
            expect(WordPressPreset::isForbidden('dump'))->toBeTrue();
            expect(WordPressPreset::isForbidden('mysql_query'))->toBeTrue();
            expect(WordPressPreset::isForbidden('wp_insert_post'))->toBeFalse();
        });

        it('can get message for pattern', function () {
            expect(WordPressPreset::getMessage('dd'))
                ->toBeString()
                ->toContain('logging');

            expect(WordPressPreset::getMessage('nonexistent'))->toBeNull();
        });
    });
});

describe('Code Analyzer', function () {
    beforeEach(function () {
        $this->analyzer = new CodeAnalyzer();
    });

    describe('Forbidden Functions Detection', function () {
        it('detects dd() function', function () {
            $code = '<?php dd($variable);';
            $issues = $this->analyzer->analyzeCode($code);

            expect($issues)->toHaveCount(1);
            expect($issues[0]['pattern'])->toBe('dd');
            expect($issues[0]['severity'])->toBe('error');
        });

        it('detects dump() function', function () {
            $code = '<?php dump($variable);';
            $issues = $this->analyzer->analyzeCode($code);

            expect($issues)->toHaveCount(1);
            expect($issues[0]['pattern'])->toBe('dump');
        });

        it('detects var_dump() function', function () {
            $code = '<?php var_dump($array);';
            $issues = $this->analyzer->analyzeCode($code);

            expect($issues)->toHaveCount(1);
            expect($issues[0]['pattern'])->toBe('var_dump');
        });

        it('detects print_r() function', function () {
            $code = '<?php print_r($array);';
            $issues = $this->analyzer->analyzeCode($code);

            expect($issues)->toHaveCount(1);
            expect($issues[0]['pattern'])->toBe('print_r');
        });

        it('does not flag function names in strings', function () {
            $code = '<?php $message = "Do not use dd() in production";';
            $issues = $this->analyzer->analyzeCode($code);

            expect($issues)->toBeEmpty();
        });

        it('does not flag function names in comments', function () {
            $code = <<<'CODE'
            <?php
            // Never use dd() or dump() in production
            * @deprecated Use proper logging instead of var_dump()
            CODE;
            $issues = $this->analyzer->analyzeCode($code);

            expect($issues)->toBeEmpty();
        });
    });

    describe('Deprecated MySQL Functions Detection', function () {
        it('detects mysql_connect()', function () {
            $code = '<?php mysql_connect($host, $user, $pass);';
            $issues = $this->analyzer->analyzeCode($code);

            expect($issues)->toHaveCount(1);
            expect($issues[0]['pattern'])->toBe('mysql_connect');
            expect($issues[0]['severity'])->toBe('error');
        });

        it('detects mysql_query()', function () {
            $code = '<?php mysql_query("SELECT * FROM users");';
            $issues = $this->analyzer->analyzeCode($code);

            expect($issues)->toHaveCount(1);
            expect($issues[0]['pattern'])->toBe('mysql_query');
        });

        it('detects mysql_real_escape_string()', function () {
            $code = '<?php $safe = mysql_real_escape_string($input);';
            $issues = $this->analyzer->analyzeCode($code);

            expect($issues)->toHaveCount(1);
            expect($issues[0]['message'])->toContain('wpdb->prepare');
        });
    });

    describe('Discouraged Patterns Detection', function () {
        it('detects global $wpdb', function () {
            $code = '<?php global $wpdb;';
            $issues = $this->analyzer->analyzeCode($code);

            expect($issues)->toHaveCount(1);
            expect($issues[0]['pattern'])->toBe('global $wpdb');
            expect($issues[0]['severity'])->toBe('warning');
        });

        it('detects global $post', function () {
            $code = '<?php global $post;';
            $issues = $this->analyzer->analyzeCode($code);

            expect($issues)->toHaveCount(1);
            expect($issues[0]['pattern'])->toBe('global $post');
        });

        it('detects $GLOBALS usage', function () {
            $code = '<?php $value = $GLOBALS["my_var"];';
            $issues = $this->analyzer->analyzeCode($code);

            expect($issues)->toHaveCount(1);
            expect($issues[0]['pattern'])->toBe('$GLOBALS[');
        });
    });

    describe('Security Sensitive Functions Detection', function () {
        it('detects eval()', function () {
            $code = '<?php eval($code);';
            $issues = $this->analyzer->analyzeCode($code);

            expect($issues)->toHaveCount(1);
            expect($issues[0]['pattern'])->toBe('eval');
            expect($issues[0]['severity'])->toBe('warning');
        });

        it('detects exec()', function () {
            $code = '<?php exec($command);';
            $issues = $this->analyzer->analyzeCode($code);

            expect($issues)->toHaveCount(1);
            expect($issues[0]['pattern'])->toBe('exec');
        });

        it('detects extract()', function () {
            $code = '<?php extract($array);';
            $issues = $this->analyzer->analyzeCode($code);

            expect($issues)->toHaveCount(1);
            expect($issues[0]['pattern'])->toBe('extract');
        });
    });

    describe('Unsanitized Input Detection', function () {
        it('detects unsanitized $_GET', function () {
            $code = '<?php $value = $_GET["param"];';
            $issues = $this->analyzer->analyzeCode($code);

            expect($issues)->toHaveCount(1);
            expect($issues[0]['pattern'])->toBe('$_GET');
        });

        it('detects unsanitized $_POST', function () {
            $code = '<?php $data = $_POST["field"];';
            $issues = $this->analyzer->analyzeCode($code);

            expect($issues)->toHaveCount(1);
            expect($issues[0]['pattern'])->toBe('$_POST');
        });

        it('does not flag sanitized $_GET', function () {
            $code = '<?php $value = sanitize_text_field($_GET["param"]);';
            $issues = $this->analyzer->analyzeCode($code);

            expect($issues)->toBeEmpty();
        });

        it('does not flag $_GET with wp_unslash', function () {
            $code = '<?php $value = wp_unslash($_GET["param"]);';
            $issues = $this->analyzer->analyzeCode($code);

            expect($issues)->toBeEmpty();
        });

        it('does not flag $_GET in isset check', function () {
            $code = '<?php if (isset($_GET["param"])) { }';
            $issues = $this->analyzer->analyzeCode($code);

            expect($issues)->toBeEmpty();
        });

        it('does not flag $_POST with absint', function () {
            $code = '<?php $id = absint($_POST["id"]);';
            $issues = $this->analyzer->analyzeCode($code);

            expect($issues)->toBeEmpty();
        });
    });

    describe('Multiple Issues Detection', function () {
        it('detects multiple issues in same file', function () {
            $code = <<<'CODE'
            <?php
            dd($debug);
            global $wpdb;
            var_dump($data);
            CODE;
            $issues = $this->analyzer->analyzeCode($code);

            expect(count($issues))->toBeGreaterThanOrEqual(3);
        });

        it('detects issue on correct line number', function () {
            $code = <<<'CODE'
            <?php
            $a = 1;
            $b = 2;
            dd($debug);
            $c = 3;
            CODE;
            $issues = $this->analyzer->analyzeCode($code);

            expect($issues)->toHaveCount(1);
            expect($issues[0]['line'])->toBe(4);
        });
    });

    describe('Summary and Reporting', function () {
        it('can get summary of issues', function () {
            $code = <<<'CODE'
            <?php
            dd($debug);
            global $wpdb;
            eval($code);
            CODE;
            $this->analyzer->analyzeCode($code);
            $summary = $this->analyzer->getSummary();

            expect($summary)->toBeArray();
            expect($summary)->toHaveKey('errors');
            expect($summary)->toHaveKey('warnings');
            expect($summary)->toHaveKey('total');
        });

        it('can check for errors', function () {
            $code = '<?php dd($debug);';
            $this->analyzer->analyzeCode($code);

            expect($this->analyzer->hasErrors())->toBeTrue();
        });

        it('can check for warnings', function () {
            $code = '<?php global $wpdb;';
            $this->analyzer->analyzeCode($code);

            expect($this->analyzer->hasWarnings())->toBeTrue();
        });

        it('returns no issues for clean code', function () {
            $code = <<<'CODE'
            <?php
            function get_posts_count(): int {
                return wp_count_posts()->publish;
            }
            CODE;
            $issues = $this->analyzer->analyzeCode($code);

            expect($issues)->toBeEmpty();
            expect($this->analyzer->hasErrors())->toBeFalse();
            expect($this->analyzer->hasWarnings())->toBeFalse();
        });

        it('can format report', function () {
            $code = '<?php dd($debug);';
            $this->analyzer->analyzeCode($code);
            $report = $this->analyzer->formatReport('test.php');

            expect($report)
                ->toContain('test.php')
                ->toContain('[ERROR]')
                ->toContain('Line 1');
        });

        it('returns clean message for no issues', function () {
            $code = '<?php $a = 1;';
            $this->analyzer->analyzeCode($code);
            $report = $this->analyzer->formatReport();

            expect($report)->toContain('No issues found');
        });
    });

    describe('File Analysis', function () {
        it('throws exception for non-existent file', function () {
            expect(fn () => $this->analyzer->analyzeFile('/nonexistent/file.php'))
                ->toThrow(InvalidArgumentException::class);
        });
    });
});
