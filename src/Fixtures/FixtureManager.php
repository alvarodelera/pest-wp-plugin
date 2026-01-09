<?php

declare(strict_types=1);

namespace PestWP\Fixtures;

use RuntimeException;

/**
 * Fixture manager for WordPress test data.
 *
 * Provides a simple way to define and load test fixtures from YAML or JSON files.
 * Fixtures are automatically cleaned up after tests.
 *
 * @example
 * ```php
 * // Load fixtures from a file
 * $fixtures = fixtures()->load('users.yaml');
 *
 * // Define fixtures inline
 * $fixtures = fixtures()->define([
 *     'users' => [
 *         ['login' => 'admin', 'role' => 'administrator'],
 *         ['login' => 'editor', 'role' => 'editor'],
 *     ],
 *     'posts' => [
 *         ['title' => 'Hello World', 'status' => 'publish'],
 *     ],
 * ]);
 *
 * // Seed the database
 * $fixtures->seed();
 *
 * // Access created objects
 * $admin = $fixtures->get('users.admin');
 * $post = $fixtures->get('posts.0');
 * ```
 */
final class FixtureManager
{
    /**
     * Singleton instance
     */
    private static ?self $instance = null;

    /**
     * Fixture definitions
     *
     * @var array<string, array<int|string, array<string, mixed>>>
     */
    private array $definitions = [];

    /**
     * Created objects
     *
     * @var array<string, array<int|string, mixed>>
     */
    private array $created = [];

    /**
     * IDs of created objects for cleanup
     *
     * @var array<string, array<int, int>>
     */
    private array $createdIds = [];

    /**
     * Path to fixtures directory
     */
    private string $fixturesPath;

    /**
     * Whether fixtures have been seeded
     */
    private bool $seeded = false;

    /**
     * Custom factory callbacks
     *
     * @var array<string, callable(array<string, mixed>): mixed>
     */
    private array $factories = [];

    public function __construct(?string $fixturesPath = null)
    {
        $this->fixturesPath = $fixturesPath ?? getcwd() . '/tests/fixtures';
    }

    /**
     * Get or create the singleton instance
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Reset the singleton instance
     */
    public static function resetInstance(): void
    {
        if (self::$instance !== null) {
            self::$instance->cleanup();
        }
        self::$instance = null;
    }

    /**
     * Set the fixtures path
     *
     * @return $this
     */
    public function setPath(string $path): self
    {
        $this->fixturesPath = $path;

        return $this;
    }

    /**
     * Get the fixtures path
     */
    public function getPath(): string
    {
        return $this->fixturesPath;
    }

    /**
     * Load fixtures from a file
     *
     * @return $this
     */
    public function load(string $file): self
    {
        $path = $this->resolvePath($file);

        if (! file_exists($path)) {
            throw new RuntimeException("Fixture file not found: {$path}");
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        $content = match ($extension) {
            'json' => $this->loadJson($path),
            'yaml', 'yml' => $this->loadYaml($path),
            'php' => $this->loadPhp($path),
            default => throw new RuntimeException("Unsupported fixture format: {$extension}"),
        };

        $this->definitions = array_merge_recursive($this->definitions, $content);

        return $this;
    }

    /**
     * Define fixtures inline
     *
     * @param array<string, array<int|string, array<string, mixed>>> $fixtures
     * @return $this
     */
    public function define(array $fixtures): self
    {
        $this->definitions = array_merge_recursive($this->definitions, $fixtures);

        return $this;
    }

    /**
     * Register a custom factory for a fixture type
     *
     * @param callable(array<string, mixed>): mixed $factory
     * @return $this
     */
    public function factory(string $type, callable $factory): self
    {
        $this->factories[$type] = $factory;

        return $this;
    }

    /**
     * Seed the database with fixtures
     *
     * @return $this
     */
    public function seed(): self
    {
        if ($this->seeded) {
            return $this;
        }

        foreach ($this->definitions as $type => $items) {
            $this->seedType($type, $items);
        }

        $this->seeded = true;

        return $this;
    }

    /**
     * Seed a specific fixture type
     *
     * @param array<int|string, array<string, mixed>> $items
     */
    private function seedType(string $type, array $items): void
    {
        foreach ($items as $key => $data) {
            $object = $this->createObject($type, $data);

            if (! isset($this->created[$type])) {
                $this->created[$type] = [];
            }

            // Use the key if it's a string (named fixture), otherwise use numeric index
            $this->created[$type][$key] = $object;

            // Track ID for cleanup
            if (is_object($object) && property_exists($object, 'ID')) {
                /** @var object{ID: int} $object */
                if (! isset($this->createdIds[$type])) {
                    $this->createdIds[$type] = [];
                }
                $this->createdIds[$type][] = (int) $object->ID;
            } elseif (is_array($object) && isset($object['ID'])) {
                if (! isset($this->createdIds[$type])) {
                    $this->createdIds[$type] = [];
                }
                /** @var int|string $id */
                $id = $object['ID'];
                $this->createdIds[$type][] = (int) $id;
            } elseif (is_int($object)) {
                if (! isset($this->createdIds[$type])) {
                    $this->createdIds[$type] = [];
                }
                $this->createdIds[$type][] = $object;
            }
        }
    }

    /**
     * Create an object from fixture data
     *
     * @param array<string, mixed> $data
     */
    private function createObject(string $type, array $data): mixed
    {
        // Use custom factory if registered
        if (isset($this->factories[$type])) {
            return ($this->factories[$type])($data);
        }

        // Use built-in WordPress factories
        return match ($type) {
            'users' => $this->createUser($data),
            'posts' => $this->createPost($data),
            'pages' => $this->createPage($data),
            'terms' => $this->createTerm($data),
            'comments' => $this->createComment($data),
            'options' => $this->createOption($data),
            'transients' => $this->createTransient($data),
            default => throw new RuntimeException("Unknown fixture type: {$type}. Register a factory with fixture()->factory('{$type}', fn(\$data) => ...)"),
        };
    }

    /**
     * Create a user
     *
     * @param array<string, mixed> $data
     * @return mixed User object, ID, or mock array
     */
    private function createUser(array $data): mixed
    {
        if (function_exists('wp_insert_user')) {
            $defaults = [
                'user_login' => $data['login'] ?? 'testuser_' . uniqid(),
                'user_pass' => $data['password'] ?? 'password123',
                'user_email' => $data['email'] ?? 'test_' . uniqid() . '@example.com',
                'role' => $data['role'] ?? 'subscriber',
            ];

            /** @var array{user_login: string, user_pass: string, user_email: string, role: string} $userData */
            $userData = array_merge($defaults, $data);
            $userId = wp_insert_user($userData);

            if (is_wp_error($userId)) {
                throw new RuntimeException('Failed to create user: ' . $userId->get_error_message());
            }

            return get_user_by('id', $userId) ?: $userId;
        }

        // Return mock data if WordPress not loaded
        return array_merge(['ID' => random_int(1, 9999)], $data);
    }

    /**
     * Create a post
     *
     * @param array<string, mixed> $data
     * @return mixed Post object, ID, or mock array
     */
    private function createPost(array $data): mixed
    {
        if (function_exists('wp_insert_post')) {
            $defaults = [
                'post_title' => $data['title'] ?? 'Test Post ' . uniqid(),
                'post_content' => $data['content'] ?? 'Test content',
                'post_status' => $data['status'] ?? 'publish',
                'post_type' => $data['type'] ?? 'post',
            ];

            /** @var array{post_title: string, post_content: string, post_status: string, post_type: string} $postData */
            $postData = array_merge($defaults, $data);
            $postId = wp_insert_post($postData, true);

            if (is_wp_error($postId)) {
                throw new RuntimeException('Failed to create post: ' . $postId->get_error_message());
            }

            return get_post($postId) ?: $postId;
        }

        return array_merge(['ID' => random_int(1, 9999)], $data);
    }

    /**
     * Create a page
     *
     * @param array<string, mixed> $data
     * @return mixed Page object, ID, or mock array
     */
    private function createPage(array $data): mixed
    {
        $data['type'] = 'page';

        return $this->createPost($data);
    }

    /**
     * Create a term
     *
     * @param array<string, mixed> $data
     * @return array<string, int|mixed>
     */
    private function createTerm(array $data): array
    {
        if (function_exists('wp_insert_term')) {
            /** @var string $taxonomy */
            $taxonomy = $data['taxonomy'] ?? 'category';
            /** @var string $name */
            $name = $data['name'] ?? 'Test Term ' . uniqid();
            /** @var array{description?: string, parent?: int, slug?: string} $args */
            $args = array_diff_key($data, array_flip(['taxonomy', 'name']));

            $result = wp_insert_term($name, $taxonomy, $args);

            if (is_wp_error($result)) {
                throw new RuntimeException('Failed to create term: ' . $result->get_error_message());
            }

            return $result;
        }

        return array_merge(['term_id' => random_int(1, 9999)], $data);
    }

    /**
     * Create a comment
     *
     * @param array<string, mixed> $data
     * @return mixed Comment object, ID, or mock array
     */
    private function createComment(array $data): mixed
    {
        if (function_exists('wp_insert_comment')) {
            $defaults = [
                'comment_content' => $data['content'] ?? 'Test comment',
                'comment_author' => $data['author'] ?? 'Test Author',
                'comment_author_email' => $data['email'] ?? 'test@example.com',
                'comment_post_ID' => $data['post_id'] ?? 1,
            ];

            /** @var array{comment_content: string, comment_author: string, comment_author_email: string, comment_post_ID: int} $commentData */
            $commentData = array_merge($defaults, $data);
            $commentId = wp_insert_comment($commentData);

            if ($commentId === false || $commentId === 0) {
                throw new RuntimeException('Failed to create comment');
            }

            return get_comment($commentId) ?: $commentId;
        }

        return array_merge(['comment_ID' => random_int(1, 9999)], $data);
    }

    /**
     * Create an option
     *
     * @param array<string, mixed> $data
     * @return bool|array<string, mixed>
     */
    private function createOption(array $data): bool|array
    {
        if (function_exists('update_option')) {
            /** @var string $name */
            $name = $data['name'] ?? throw new RuntimeException('Option name is required');
            $value = $data['value'] ?? '';

            return update_option($name, $value);
        }

        return $data;
    }

    /**
     * Create a transient
     *
     * @param array<string, mixed> $data
     * @return bool|array<string, mixed>
     */
    private function createTransient(array $data): bool|array
    {
        if (function_exists('set_transient')) {
            /** @var string $name */
            $name = $data['name'] ?? throw new RuntimeException('Transient name is required');
            $value = $data['value'] ?? '';
            /** @var int $expiration */
            $expiration = $data['expiration'] ?? 0;

            return set_transient($name, $value, $expiration);
        }

        return $data;
    }

    /**
     * Get a created fixture object by path
     *
     * @param string $path e.g., 'users.admin' or 'posts.0'
     */
    public function get(string $path): mixed
    {
        $parts = explode('.', $path, 2);
        $type = $parts[0];
        $key = $parts[1] ?? null;

        if (! isset($this->created[$type])) {
            return null;
        }

        if ($key === null) {
            return $this->created[$type];
        }

        // Support numeric keys
        if (is_numeric($key)) {
            $key = (int) $key;
        }

        return $this->created[$type][$key] ?? null;
    }

    /**
     * Check if a fixture exists
     */
    public function has(string $path): bool
    {
        return $this->get($path) !== null;
    }

    /**
     * Get all created fixtures
     *
     * @return array<string, array<int|string, mixed>>
     */
    public function all(): array
    {
        return $this->created;
    }

    /**
     * Cleanup created fixtures
     *
     * @return $this
     */
    public function cleanup(): self
    {
        foreach ($this->createdIds as $type => $ids) {
            $this->cleanupType($type, $ids);
        }

        $this->created = [];
        $this->createdIds = [];
        $this->definitions = [];
        $this->seeded = false;

        return $this;
    }

    /**
     * Cleanup a specific fixture type
     *
     * @param array<int, int> $ids
     */
    private function cleanupType(string $type, array $ids): void
    {
        if (! function_exists('wp_delete_post')) {
            return;
        }

        foreach ($ids as $id) {
            match ($type) {
                'users' => function_exists('wp_delete_user') ? wp_delete_user($id) : null,
                'posts', 'pages' => wp_delete_post($id, true),
                'terms' => function_exists('wp_delete_term') ? wp_delete_term($id, $this->getTermTaxonomy($id)) : null,
                'comments' => function_exists('wp_delete_comment') ? wp_delete_comment($id, true) : null,
                default => null,
            };
        }
    }

    /**
     * Get taxonomy for a term (for cleanup)
     */
    private function getTermTaxonomy(int $termId): string
    {
        if (function_exists('get_term')) {
            $term = get_term($termId);
            if ($term instanceof \WP_Term) {
                return $term->taxonomy;
            }
        }

        return 'category';
    }

    /**
     * Resolve fixture file path
     */
    private function resolvePath(string $file): string
    {
        if (file_exists($file)) {
            return $file;
        }

        return $this->fixturesPath . '/' . $file;
    }

    /**
     * Load JSON fixture file
     *
     * @return array<string, array<int|string, array<string, mixed>>>
     */
    private function loadJson(string $path): array
    {
        $content = file_get_contents($path);
        if ($content === false) {
            throw new RuntimeException("Failed to read fixture file: {$path}");
        }

        $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

        if (! is_array($data)) {
            throw new RuntimeException("Invalid fixture format in: {$path}");
        }

        /** @var array<string, array<int|string, array<string, mixed>>> $data */
        return $data;
    }

    /**
     * Load YAML fixture file
     *
     * @return array<string, array<int|string, array<string, mixed>>>
     */
    private function loadYaml(string $path): array
    {
        // Check for native yaml extension
        if (function_exists('\yaml_parse_file')) {
            /** @var array<string, array<int|string, array<string, mixed>>>|false $data */
            $data = \yaml_parse_file($path);

            if ($data === false || ! is_array($data)) {
                throw new RuntimeException("Failed to parse YAML file: {$path}");
            }

            return $data;
        }

        // Try Symfony YAML as fallback
        if (class_exists(\Symfony\Component\Yaml\Yaml::class)) {
            /** @var array<string, array<int|string, array<string, mixed>>> $data */
            $data = \Symfony\Component\Yaml\Yaml::parseFile($path);

            return $data;
        }

        throw new RuntimeException('YAML support requires the yaml extension or symfony/yaml package');
    }

    /**
     * Load PHP fixture file
     *
     * @return array<string, array<int|string, array<string, mixed>>>
     */
    private function loadPhp(string $path): array
    {
        $data = require $path;

        if (! is_array($data)) {
            throw new RuntimeException("PHP fixture must return an array: {$path}");
        }

        /** @var array<string, array<int|string, array<string, mixed>>> $data */
        return $data;
    }

    /**
     * Reset for fresh state
     *
     * @return $this
     */
    public function reset(): self
    {
        $this->definitions = [];
        $this->created = [];
        $this->createdIds = [];
        $this->seeded = false;

        return $this;
    }
}
