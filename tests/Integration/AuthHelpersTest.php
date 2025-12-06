<?php

declare(strict_types=1);

use function PestWP\createUser;
use function PestWP\currentUser;
use function PestWP\isUserLoggedIn;
use function PestWP\loginAs;
use function PestWP\logout;

describe('Auth Helpers', function () {
    describe('loginAs()', function () {
        it('can log in with user ID', function () {
            $user = createUser('administrator');

            loginAs($user->ID);

            expect(currentUser()->ID)->toBe($user->ID)
                ->and(isUserLoggedIn())->toBeTrue();
        });

        it('can log in with WP_User object', function () {
            $user = createUser('editor');

            loginAs($user);

            expect(currentUser()->ID)->toBe($user->ID)
                ->and(isUserLoggedIn())->toBeTrue();
        });

        it('sets correct user capabilities', function () {
            $admin = createUser('administrator');

            loginAs($admin);

            expect(current_user_can('manage_options'))->toBeTrue()
                ->and(current_user_can('edit_posts'))->toBeTrue();
        });

        it('allows checking user roles after login', function () {
            $editor = createUser('editor');

            loginAs($editor);

            $current = currentUser();
            expect($current->roles)->toContain('editor')
                ->and(current_user_can('edit_posts'))->toBeTrue()
                ->and(current_user_can('manage_options'))->toBeFalse();
        });

        it('throws exception for non-existent user', function () {
            loginAs(999999);
        })->throws(RuntimeException::class, 'User with ID 999999 does not exist');

        it('returns the logged-in user object', function () {
            $user = createUser('author');

            $loggedInUser = loginAs($user);

            expect($loggedInUser)->toBeInstanceOf(WP_User::class)
                ->and($loggedInUser->ID)->toBe($user->ID)
                ->and($loggedInUser->user_login)->toBe($user->user_login);
        });
    });

    describe('logout()', function () {
        it('logs out the current user', function () {
            $user = createUser('subscriber');
            loginAs($user);

            expect(isUserLoggedIn())->toBeTrue();

            logout();

            expect(isUserLoggedIn())->toBeFalse()
                ->and(currentUser()->ID)->toBe(0);
        });

        it('can be called when no user is logged in', function () {
            logout();

            expect(isUserLoggedIn())->toBeFalse();
        });

        it('clears user capabilities', function () {
            $admin = createUser('administrator');
            loginAs($admin);

            expect(current_user_can('manage_options'))->toBeTrue();

            logout();

            expect(current_user_can('manage_options'))->toBeFalse();
        });
    });

    describe('currentUser()', function () {
        it('returns the logged-in user', function () {
            $user = createUser('contributor');
            loginAs($user);

            $current = currentUser();

            expect($current)->toBeInstanceOf(WP_User::class)
                ->and($current->ID)->toBe($user->ID);
        });

        it('returns user with ID 0 when not logged in', function () {
            logout();

            $current = currentUser();

            expect($current)->toBeInstanceOf(WP_User::class)
                ->and($current->ID)->toBe(0)
                ->and($current->exists())->toBeFalse();
        });
    });

    describe('isUserLoggedIn()', function () {
        it('returns true when user is logged in', function () {
            $user = createUser();
            loginAs($user);

            expect(isUserLoggedIn())->toBeTrue();
        });

        it('returns false when no user is logged in', function () {
            logout();

            expect(isUserLoggedIn())->toBeFalse();
        });
    });

    describe('Login Flow', function () {
        it('can switch between users', function () {
            $user1 = createUser('editor');
            $user2 = createUser('author');

            loginAs($user1);
            expect(currentUser()->ID)->toBe($user1->ID)
                ->and(current_user_can('edit_others_posts'))->toBeTrue();

            loginAs($user2);
            expect(currentUser()->ID)->toBe($user2->ID)
                ->and(current_user_can('edit_others_posts'))->toBeFalse()
                ->and(current_user_can('edit_posts'))->toBeTrue();
        });

        it('maintains user state until logout', function () {
            $user = createUser('administrator');
            loginAs($user);

            // Multiple checks should all pass
            expect(isUserLoggedIn())->toBeTrue();
            expect(currentUser()->ID)->toBe($user->ID);
            expect(current_user_can('manage_options'))->toBeTrue();

            logout();

            // All should fail after logout
            expect(isUserLoggedIn())->toBeFalse();
            expect(currentUser()->ID)->toBe(0);
            expect(current_user_can('manage_options'))->toBeFalse();
        });
    });

    describe('Permissions Testing', function () {
        it('can test subscriber permissions', function () {
            $subscriber = createUser('subscriber');
            loginAs($subscriber);

            expect(current_user_can('read'))->toBeTrue()
                ->and(current_user_can('edit_posts'))->toBeFalse()
                ->and(current_user_can('manage_options'))->toBeFalse();
        });

        it('can test contributor permissions', function () {
            $contributor = createUser('contributor');
            loginAs($contributor);

            expect(current_user_can('read'))->toBeTrue()
                ->and(current_user_can('edit_posts'))->toBeTrue()
                ->and(current_user_can('publish_posts'))->toBeFalse()
                ->and(current_user_can('edit_others_posts'))->toBeFalse();
        });

        it('can test author permissions', function () {
            $author = createUser('author');
            loginAs($author);

            expect(current_user_can('read'))->toBeTrue()
                ->and(current_user_can('edit_posts'))->toBeTrue()
                ->and(current_user_can('publish_posts'))->toBeTrue()
                ->and(current_user_can('edit_others_posts'))->toBeFalse();
        });

        it('can test editor permissions', function () {
            $editor = createUser('editor');
            loginAs($editor);

            expect(current_user_can('read'))->toBeTrue()
                ->and(current_user_can('edit_posts'))->toBeTrue()
                ->and(current_user_can('publish_posts'))->toBeTrue()
                ->and(current_user_can('edit_others_posts'))->toBeTrue()
                ->and(current_user_can('manage_options'))->toBeFalse();
        });

        it('can test administrator permissions', function () {
            $admin = createUser('administrator');
            loginAs($admin);

            expect(current_user_can('read'))->toBeTrue()
                ->and(current_user_can('edit_posts'))->toBeTrue()
                ->and(current_user_can('publish_posts'))->toBeTrue()
                ->and(current_user_can('edit_others_posts'))->toBeTrue()
                ->and(current_user_can('manage_options'))->toBeTrue()
                ->and(current_user_can('delete_users'))->toBeTrue();
        });
    });
});
