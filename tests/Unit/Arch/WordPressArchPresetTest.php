<?php

declare(strict_types=1);

use PestWP\Arch\WordPressArchPreset;

describe('WordPress Arch Preset', function () {
    describe('Constants', function () {
        it('has debug functions defined', function () {
            expect(WordPressArchPreset::DEBUG_FUNCTIONS)->toBeArray();
            expect(WordPressArchPreset::DEBUG_FUNCTIONS)->toContain('dd');
            expect(WordPressArchPreset::DEBUG_FUNCTIONS)->toContain('dump');
            expect(WordPressArchPreset::DEBUG_FUNCTIONS)->toContain('var_dump');
            expect(WordPressArchPreset::DEBUG_FUNCTIONS)->toContain('print_r');
        });

        it('has deprecated MySQL functions defined', function () {
            expect(WordPressArchPreset::DEPRECATED_MYSQL_FUNCTIONS)->toBeArray();
            expect(WordPressArchPreset::DEPRECATED_MYSQL_FUNCTIONS)->toContain('mysql_connect');
            expect(WordPressArchPreset::DEPRECATED_MYSQL_FUNCTIONS)->toContain('mysql_query');
            expect(WordPressArchPreset::DEPRECATED_MYSQL_FUNCTIONS)->toContain('mysql_real_escape_string');
        });

        it('has security sensitive functions defined', function () {
            expect(WordPressArchPreset::SECURITY_SENSITIVE_FUNCTIONS)->toBeArray();
            expect(WordPressArchPreset::SECURITY_SENSITIVE_FUNCTIONS)->toContain('eval');
            expect(WordPressArchPreset::SECURITY_SENSITIVE_FUNCTIONS)->toContain('exec');
            expect(WordPressArchPreset::SECURITY_SENSITIVE_FUNCTIONS)->toContain('shell_exec');
            expect(WordPressArchPreset::SECURITY_SENSITIVE_FUNCTIONS)->toContain('extract');
        });

        it('has direct file functions defined', function () {
            expect(WordPressArchPreset::DIRECT_FILE_FUNCTIONS)->toBeArray();
            expect(WordPressArchPreset::DIRECT_FILE_FUNCTIONS)->toContain('file_put_contents');
            expect(WordPressArchPreset::DIRECT_FILE_FUNCTIONS)->toContain('file_get_contents');
            expect(WordPressArchPreset::DIRECT_FILE_FUNCTIONS)->toContain('fopen');
            expect(WordPressArchPreset::DIRECT_FILE_FUNCTIONS)->toContain('mkdir');
        });

        it('has escaping functions defined', function () {
            expect(WordPressArchPreset::ESCAPING_FUNCTIONS)->toBeArray();
            expect(WordPressArchPreset::ESCAPING_FUNCTIONS)->toContain('esc_html');
            expect(WordPressArchPreset::ESCAPING_FUNCTIONS)->toContain('esc_attr');
            expect(WordPressArchPreset::ESCAPING_FUNCTIONS)->toContain('esc_url');
            expect(WordPressArchPreset::ESCAPING_FUNCTIONS)->toContain('wp_kses');
        });

        it('has sanitization functions defined', function () {
            expect(WordPressArchPreset::SANITIZATION_FUNCTIONS)->toBeArray();
            expect(WordPressArchPreset::SANITIZATION_FUNCTIONS)->toContain('sanitize_text_field');
            expect(WordPressArchPreset::SANITIZATION_FUNCTIONS)->toContain('sanitize_email');
            expect(WordPressArchPreset::SANITIZATION_FUNCTIONS)->toContain('wp_kses');
            expect(WordPressArchPreset::SANITIZATION_FUNCTIONS)->toContain('absint');
        });

        it('has deprecated WordPress functions defined', function () {
            expect(WordPressArchPreset::DEPRECATED_WP_FUNCTIONS)->toBeArray();
            expect(array_keys(WordPressArchPreset::DEPRECATED_WP_FUNCTIONS))->toContain('get_currentuserinfo');
            expect(array_keys(WordPressArchPreset::DEPRECATED_WP_FUNCTIONS))->toContain('user_pass_ok');
        });
    });

    describe('Helper Methods', function () {
        it('can get all debug functions', function () {
            $functions = WordPressArchPreset::getDebugFunctions();

            expect($functions)->toBeArray();
            expect($functions)->toBe(WordPressArchPreset::DEBUG_FUNCTIONS);
        });

        it('can get all security sensitive functions', function () {
            $functions = WordPressArchPreset::getSecuritySensitiveFunctions();

            expect($functions)->toBeArray();
            expect($functions)->toBe(WordPressArchPreset::SECURITY_SENSITIVE_FUNCTIONS);
        });

        it('can get all deprecated MySQL functions', function () {
            $functions = WordPressArchPreset::getDeprecatedMySQLFunctions();

            expect($functions)->toBeArray();
            expect($functions)->toBe(WordPressArchPreset::DEPRECATED_MYSQL_FUNCTIONS);
        });

        it('can get all direct file functions', function () {
            $functions = WordPressArchPreset::getDirectFileFunctions();

            expect($functions)->toBeArray();
            expect($functions)->toBe(WordPressArchPreset::DIRECT_FILE_FUNCTIONS);
        });

        it('can get all sanitization functions', function () {
            $functions = WordPressArchPreset::getSanitizationFunctions();

            expect($functions)->toBeArray();
            expect($functions)->toBe(WordPressArchPreset::SANITIZATION_FUNCTIONS);
        });

        it('can get all escaping functions', function () {
            $functions = WordPressArchPreset::getEscapingFunctions();

            expect($functions)->toBeArray();
            expect($functions)->toBe(WordPressArchPreset::ESCAPING_FUNCTIONS);
        });

        it('can get all forbidden functions', function () {
            $functions = WordPressArchPreset::getAllForbiddenFunctions();

            expect($functions)->toBeArray();
            expect($functions)->toContain('dd');
            expect($functions)->toContain('mysql_connect');
            expect($functions)->toContain('eval');
        });

        it('can check if function is debug function', function () {
            expect(WordPressArchPreset::isDebugFunction('dd'))->toBeTrue();
            expect(WordPressArchPreset::isDebugFunction('dump'))->toBeTrue();
            expect(WordPressArchPreset::isDebugFunction('echo'))->toBeFalse();
            expect(WordPressArchPreset::isDebugFunction('sanitize_text_field'))->toBeFalse();
        });

        it('can check if function is security sensitive', function () {
            expect(WordPressArchPreset::isSecuritySensitive('eval'))->toBeTrue();
            expect(WordPressArchPreset::isSecuritySensitive('exec'))->toBeTrue();
            expect(WordPressArchPreset::isSecuritySensitive('echo'))->toBeFalse();
        });

        it('can check if function is direct file function', function () {
            expect(WordPressArchPreset::isDirectFileFunction('file_put_contents'))->toBeTrue();
            expect(WordPressArchPreset::isDirectFileFunction('fopen'))->toBeTrue();
            expect(WordPressArchPreset::isDirectFileFunction('get_option'))->toBeFalse();
        });

        it('can check if function is deprecated WordPress function', function () {
            expect(WordPressArchPreset::isDeprecatedWordPressFunction('get_currentuserinfo'))->toBeTrue();
            expect(WordPressArchPreset::isDeprecatedWordPressFunction('user_pass_ok'))->toBeTrue();
            expect(WordPressArchPreset::isDeprecatedWordPressFunction('wp_get_current_user'))->toBeFalse();
        });

        it('can get deprecation message for WordPress function', function () {
            $message = WordPressArchPreset::getDeprecationMessage('get_currentuserinfo');

            expect($message)->toBeString();
            expect($message)->toContain('deprecated');
            expect($message)->toContain('wp_get_current_user');
        });

        it('returns null for non-deprecated function message', function () {
            $message = WordPressArchPreset::getDeprecationMessage('wp_get_current_user');

            expect($message)->toBeNull();
        });
    });
});
