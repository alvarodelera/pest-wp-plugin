<?php

declare(strict_types=1);

use PestWP\Mock\TimeMock;

beforeEach(function () {
    TimeMock::resetInstance();
});

afterEach(function () {
    TimeMock::resetInstance();
});

describe('TimeMock', function () {
    describe('singleton', function () {
        test('getInstance() returns singleton', function () {
            $instance1 = TimeMock::getInstance();
            $instance2 = TimeMock::getInstance();

            expect($instance1)->toBe($instance2);
        });

        test('resetInstance() clears singleton', function () {
            $instance1 = TimeMock::getInstance();
            TimeMock::resetInstance();
            $instance2 = TimeMock::getInstance();

            expect($instance1)->not->toBe($instance2);
        });
    });

    describe('freezing time', function () {
        test('isFrozen() returns false by default', function () {
            expect(TimeMock::getInstance()->isFrozen())->toBeFalse();
        });

        test('freeze() freezes time', function () {
            $mock = TimeMock::getInstance()->freeze('2024-01-15 10:30:00');

            expect($mock->isFrozen())->toBeTrue();
        });

        test('freeze() with string date sets correct timestamp', function () {
            $mock = TimeMock::getInstance()->freeze('2024-01-15 10:30:00');

            expect($mock->format('Y-m-d H:i:s'))->toBe('2024-01-15 10:30:00');
        });

        test('freeze() with timestamp works', function () {
            $timestamp = strtotime('2024-06-01 12:00:00');
            $mock = TimeMock::getInstance()->freeze($timestamp);

            expect($mock->getTimestamp())->toBe($timestamp);
        });

        test('freeze() with null freezes current time', function () {
            $before = time();
            $mock = TimeMock::getInstance()->freeze();
            $after = time();

            expect($mock->getTimestamp())->toBeGreaterThanOrEqual($before);
            expect($mock->getTimestamp())->toBeLessThanOrEqual($after);
        });

        test('freeze() with DateTime object works', function () {
            $dt = new DateTimeImmutable('2024-03-20 15:45:00');
            $mock = TimeMock::getInstance()->freeze($dt);

            expect($mock->format('Y-m-d H:i:s'))->toBe('2024-03-20 15:45:00');
        });

        test('now() freezes at current time', function () {
            $before = time();
            $mock = TimeMock::getInstance()->now();
            $after = time();

            expect($mock->getTimestamp())->toBeGreaterThanOrEqual($before);
            expect($mock->getTimestamp())->toBeLessThanOrEqual($after);
        });

        test('today() freezes at start of today', function () {
            $mock = TimeMock::getInstance()->today();

            expect($mock->format('H:i:s'))->toBe('00:00:00');
        });

        test('unfreeze() unfreezes time', function () {
            $mock = TimeMock::getInstance()->freeze();
            $mock->unfreeze();

            expect($mock->isFrozen())->toBeFalse();
        });
    });

    describe('time advancement', function () {
        test('advance() modifies frozen time', function () {
            $mock = TimeMock::getInstance()->freeze('2024-01-15 10:00:00');
            $mock->advance('+1 hour');

            expect($mock->format('Y-m-d H:i:s'))->toBe('2024-01-15 11:00:00');
        });

        test('advanceSeconds() advances by seconds', function () {
            $mock = TimeMock::getInstance()->freeze('2024-01-15 10:00:00');
            $mock->advanceSeconds(30);

            expect($mock->format('Y-m-d H:i:s'))->toBe('2024-01-15 10:00:30');
        });

        test('advanceMinutes() advances by minutes', function () {
            $mock = TimeMock::getInstance()->freeze('2024-01-15 10:00:00');
            $mock->advanceMinutes(15);

            expect($mock->format('Y-m-d H:i:s'))->toBe('2024-01-15 10:15:00');
        });

        test('advanceHours() advances by hours', function () {
            $mock = TimeMock::getInstance()->freeze('2024-01-15 10:00:00');
            $mock->advanceHours(5);

            expect($mock->format('Y-m-d H:i:s'))->toBe('2024-01-15 15:00:00');
        });

        test('advanceDays() advances by days', function () {
            $mock = TimeMock::getInstance()->freeze('2024-01-15 10:00:00');
            $mock->advanceDays(3);

            expect($mock->format('Y-m-d H:i:s'))->toBe('2024-01-18 10:00:00');
        });

        test('advanceWeeks() advances by weeks', function () {
            $mock = TimeMock::getInstance()->freeze('2024-01-15 10:00:00');
            $mock->advanceWeeks(2);

            expect($mock->format('Y-m-d'))->toBe('2024-01-29');
        });

        test('advanceMonths() advances by months', function () {
            $mock = TimeMock::getInstance()->freeze('2024-01-15 10:00:00');
            $mock->advanceMonths(2);

            expect($mock->format('Y-m-d'))->toBe('2024-03-15');
        });

        test('rewind() goes back in time', function () {
            $mock = TimeMock::getInstance()->freeze('2024-01-15 10:00:00');
            $mock->rewind('2 hours');

            expect($mock->format('Y-m-d H:i:s'))->toBe('2024-01-15 08:00:00');
        });

        test('advance() throws when not frozen', function () {
            expect(fn () => TimeMock::getInstance()->advance('+1 hour'))
                ->toThrow(\RuntimeException::class);
        });
    });

    describe('formatting', function () {
        test('format() returns formatted date', function () {
            $mock = TimeMock::getInstance()->freeze('2024-01-15 10:30:45');

            expect($mock->format('Y'))->toBe('2024');
            expect($mock->format('m'))->toBe('01');
            expect($mock->format('d'))->toBe('15');
        });

        test('toMySql() returns MySQL datetime format', function () {
            $mock = TimeMock::getInstance()->freeze('2024-01-15 10:30:45');

            expect($mock->toMySql())->toBe('2024-01-15 10:30:45');
        });

        test('toDate() returns date only', function () {
            $mock = TimeMock::getInstance()->freeze('2024-01-15 10:30:45');

            expect($mock->toDate())->toBe('2024-01-15');
        });

        test('toTime() returns time only', function () {
            $mock = TimeMock::getInstance()->freeze('2024-01-15 10:30:45');

            expect($mock->toTime())->toBe('10:30:45');
        });
    });

    describe('component getters', function () {
        test('getYear() returns year', function () {
            $mock = TimeMock::getInstance()->freeze('2024-01-15');
            expect($mock->getYear())->toBe(2024);
        });

        test('getMonth() returns month', function () {
            $mock = TimeMock::getInstance()->freeze('2024-03-15');
            expect($mock->getMonth())->toBe(3);
        });

        test('getDay() returns day', function () {
            $mock = TimeMock::getInstance()->freeze('2024-01-20');
            expect($mock->getDay())->toBe(20);
        });

        test('getHour() returns hour', function () {
            $mock = TimeMock::getInstance()->freeze('2024-01-15 14:30:00');
            expect($mock->getHour())->toBe(14);
        });

        test('getMinute() returns minute', function () {
            $mock = TimeMock::getInstance()->freeze('2024-01-15 10:45:00');
            expect($mock->getMinute())->toBe(45);
        });

        test('getSecond() returns second', function () {
            $mock = TimeMock::getInstance()->freeze('2024-01-15 10:30:25');
            expect($mock->getSecond())->toBe(25);
        });

        test('getDayOfWeek() returns day of week', function () {
            // 2024-01-15 is Monday (1)
            $mock = TimeMock::getInstance()->freeze('2024-01-15');
            expect($mock->getDayOfWeek())->toBe(1);

            // 2024-01-14 is Sunday (0)
            $mock->travelTo('2024-01-14');
            expect($mock->getDayOfWeek())->toBe(0);

            // 2024-01-20 is Saturday (6)
            $mock->travelTo('2024-01-20');
            expect($mock->getDayOfWeek())->toBe(6);
        });
    });

    describe('date checks', function () {
        test('isWeekend() returns true for weekend', function () {
            // Saturday
            $mock = TimeMock::getInstance()->freeze('2024-01-20');
            expect($mock->isWeekend())->toBeTrue();

            // Sunday
            $mock->travelTo('2024-01-21');
            expect($mock->isWeekend())->toBeTrue();
        });

        test('isWeekend() returns false for weekday', function () {
            // Monday
            $mock = TimeMock::getInstance()->freeze('2024-01-15');
            expect($mock->isWeekend())->toBeFalse();
        });

        test('isWeekday() returns true for weekday', function () {
            // Wednesday
            $mock = TimeMock::getInstance()->freeze('2024-01-17');
            expect($mock->isWeekday())->toBeTrue();
        });

        test('isWeekday() returns false for weekend', function () {
            // Saturday
            $mock = TimeMock::getInstance()->freeze('2024-01-20');
            expect($mock->isWeekday())->toBeFalse();
        });
    });

    describe('timezone', function () {
        test('default timezone is UTC', function () {
            $mock = TimeMock::getInstance();
            expect($mock->getTimezone()->getName())->toBe('UTC');
        });

        test('setTimezone() changes timezone', function () {
            $mock = TimeMock::getInstance()->setTimezone('America/New_York');
            expect($mock->getTimezone()->getName())->toBe('America/New_York');
        });
    });

    describe('getDateTime()', function () {
        test('getDateTime() returns DateTimeImmutable when frozen', function () {
            $mock = TimeMock::getInstance()->freeze('2024-01-15 10:00:00');
            $dt = $mock->getDateTime();

            expect($dt)->toBeInstanceOf(DateTimeImmutable::class);
            expect($dt->format('Y-m-d H:i:s'))->toBe('2024-01-15 10:00:00');
        });

        test('getDateTime() returns current time when not frozen', function () {
            $before = new DateTimeImmutable();
            $dt = TimeMock::getInstance()->getDateTime();
            $after = new DateTimeImmutable();

            expect($dt->getTimestamp())->toBeGreaterThanOrEqual($before->getTimestamp());
            expect($dt->getTimestamp())->toBeLessThanOrEqual($after->getTimestamp());
        });
    });
});
