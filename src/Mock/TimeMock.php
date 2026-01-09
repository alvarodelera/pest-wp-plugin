<?php

declare(strict_types=1);

namespace PestWP\Mock;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use RuntimeException;

/**
 * Time mocking utility for WordPress tests.
 *
 * Allows freezing time during tests to make time-dependent code deterministic.
 * Works by providing mock values that can be used instead of time(), date(), etc.
 *
 * @example
 * ```php
 * // Freeze time to a specific date
 * $mock = mockTime()->freeze('2024-01-15 10:30:00');
 *
 * // Get mocked timestamp
 * $timestamp = $mock->getTimestamp(); // 1705315800
 *
 * // Advance time
 * $mock->advance('+1 hour');
 * $mock->advanceSeconds(3600);
 * $mock->advanceDays(1);
 *
 * // Travel to another time
 * $mock->travelTo('2024-12-25 00:00:00');
 *
 * // Unfreeze
 * $mock->unfreeze();
 * ```
 */
final class TimeMock
{
    /**
     * Singleton instance
     */
    private static ?self $instance = null;

    /**
     * Whether time is frozen
     */
    private bool $frozen = false;

    /**
     * The frozen timestamp
     */
    private ?int $frozenTimestamp = null;

    /**
     * The frozen DateTime
     */
    private ?DateTimeImmutable $frozenDateTime = null;

    /**
     * Timezone for date operations
     */
    private DateTimeZone $timezone;

    /**
     * Time when freezing started (real time)
     */
    private ?int $freezeStarted = null;

    /**
     * Whether to tick (advance time naturally while frozen)
     */
    private bool $ticking = false;

    private function __construct()
    {
        $this->timezone = new DateTimeZone('UTC');
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
            self::$instance->unfreeze();
        }
        self::$instance = null;
    }

    /**
     * Freeze time at the given moment
     *
     * @param string|int|DateTimeInterface|null $time The time to freeze at (null = now)
     * @return $this
     */
    public function freeze(string|int|DateTimeInterface|null $time = null): self
    {
        $this->frozen = true;
        $this->freezeStarted = time();
        $this->ticking = false;

        if ($time === null) {
            $this->frozenTimestamp = $this->freezeStarted;
            $this->frozenDateTime = new DateTimeImmutable('now', $this->timezone);
        } elseif (is_int($time)) {
            $this->frozenTimestamp = $time;
            $this->frozenDateTime = (new DateTimeImmutable('now', $this->timezone))->setTimestamp($time);
        } elseif ($time instanceof DateTimeInterface) {
            $this->frozenTimestamp = $time->getTimestamp();
            $this->frozenDateTime = DateTimeImmutable::createFromInterface($time)->setTimezone($this->timezone);
        } else {
            $this->frozenDateTime = new DateTimeImmutable($time, $this->timezone);
            $this->frozenTimestamp = $this->frozenDateTime->getTimestamp();
        }

        return $this;
    }

    /**
     * Freeze at the current moment
     *
     * @return $this
     */
    public function now(): self
    {
        return $this->freeze();
    }

    /**
     * Freeze at the start of today
     *
     * @return $this
     */
    public function today(): self
    {
        return $this->freeze((new DateTimeImmutable('today', $this->timezone))->format('Y-m-d 00:00:00'));
    }

    /**
     * Freeze at the start of yesterday
     *
     * @return $this
     */
    public function yesterday(): self
    {
        return $this->freeze((new DateTimeImmutable('yesterday', $this->timezone))->format('Y-m-d 00:00:00'));
    }

    /**
     * Freeze at the start of tomorrow
     *
     * @return $this
     */
    public function tomorrow(): self
    {
        return $this->freeze((new DateTimeImmutable('tomorrow', $this->timezone))->format('Y-m-d 00:00:00'));
    }

    /**
     * Travel to a specific time (alias for freeze)
     *
     * @return $this
     */
    public function travelTo(string|int|DateTimeInterface $time): self
    {
        return $this->freeze($time);
    }

    /**
     * Enable ticking - time advances naturally while frozen base point stays
     *
     * @return $this
     */
    public function tick(): self
    {
        $this->ticking = true;

        return $this;
    }

    /**
     * Disable ticking
     *
     * @return $this
     */
    public function stopTicking(): self
    {
        $this->ticking = false;

        return $this;
    }

    /**
     * Advance time by a relative time string
     *
     * @param string $modifier e.g., '+1 hour', '+30 minutes', '-1 day'
     * @return $this
     */
    public function advance(string $modifier): self
    {
        $this->ensureFrozen();

        /** @var DateTimeImmutable $frozenDateTime - ensureFrozen guarantees non-null */
        $frozenDateTime = $this->frozenDateTime;
        $this->frozenDateTime = $frozenDateTime->modify($modifier) ?: $frozenDateTime;
        $this->frozenTimestamp = $this->frozenDateTime->getTimestamp();

        return $this;
    }

    /**
     * Advance time by seconds
     *
     * @return $this
     */
    public function advanceSeconds(int $seconds): self
    {
        return $this->advance("+{$seconds} seconds");
    }

    /**
     * Advance time by minutes
     *
     * @return $this
     */
    public function advanceMinutes(int $minutes): self
    {
        return $this->advance("+{$minutes} minutes");
    }

    /**
     * Advance time by hours
     *
     * @return $this
     */
    public function advanceHours(int $hours): self
    {
        return $this->advance("+{$hours} hours");
    }

    /**
     * Advance time by days
     *
     * @return $this
     */
    public function advanceDays(int $days): self
    {
        return $this->advance("+{$days} days");
    }

    /**
     * Advance time by weeks
     *
     * @return $this
     */
    public function advanceWeeks(int $weeks): self
    {
        return $this->advance("+{$weeks} weeks");
    }

    /**
     * Advance time by months
     *
     * @return $this
     */
    public function advanceMonths(int $months): self
    {
        return $this->advance("+{$months} months");
    }

    /**
     * Go back in time by a relative time string
     *
     * @return $this
     */
    public function rewind(string $modifier): self
    {
        // Ensure modifier starts with - for going back
        if (! str_starts_with($modifier, '-')) {
            $modifier = '-' . ltrim($modifier, '+');
        }

        return $this->advance($modifier);
    }

    /**
     * Unfreeze time
     *
     * @return $this
     */
    public function unfreeze(): self
    {
        $this->frozen = false;
        $this->frozenTimestamp = null;
        $this->frozenDateTime = null;
        $this->freezeStarted = null;
        $this->ticking = false;

        return $this;
    }

    /**
     * Set the timezone
     *
     * @return $this
     */
    public function setTimezone(string|DateTimeZone $timezone): self
    {
        $this->timezone = $timezone instanceof DateTimeZone
            ? $timezone
            : new DateTimeZone($timezone);

        if ($this->frozenDateTime !== null) {
            $this->frozenDateTime = $this->frozenDateTime->setTimezone($this->timezone);
        }

        return $this;
    }

    /**
     * Get the timezone
     */
    public function getTimezone(): DateTimeZone
    {
        return $this->timezone;
    }

    /**
     * Check if time is frozen
     */
    public function isFrozen(): bool
    {
        return $this->frozen;
    }

    /**
     * Get the current (mocked) timestamp
     */
    public function getTimestamp(): int
    {
        if (! $this->frozen || $this->frozenTimestamp === null) {
            return time();
        }

        if ($this->ticking && $this->freezeStarted !== null) {
            return $this->frozenTimestamp + (time() - $this->freezeStarted);
        }

        return $this->frozenTimestamp;
    }

    /**
     * Get the current (mocked) DateTime
     */
    public function getDateTime(): DateTimeImmutable
    {
        if (! $this->frozen || $this->frozenDateTime === null) {
            return new DateTimeImmutable('now', $this->timezone);
        }

        if ($this->ticking && $this->freezeStarted !== null) {
            $elapsed = time() - $this->freezeStarted;

            return $this->frozenDateTime->modify("+{$elapsed} seconds");
        }

        return $this->frozenDateTime;
    }

    /**
     * Get formatted date string
     */
    public function format(string $format): string
    {
        return $this->getDateTime()->format($format);
    }

    /**
     * Get MySQL datetime string
     */
    public function toMySql(): string
    {
        return $this->format('Y-m-d H:i:s');
    }

    /**
     * Get ISO 8601 string
     */
    public function toIso8601(): string
    {
        return $this->format(DateTimeInterface::ATOM);
    }

    /**
     * Get WordPress-compatible date string (for current_time('mysql'))
     */
    public function toWordPress(): string
    {
        return $this->toMySql();
    }

    /**
     * Get date formatted for WordPress (Y-m-d)
     */
    public function toDate(): string
    {
        return $this->format('Y-m-d');
    }

    /**
     * Get time formatted for WordPress (H:i:s)
     */
    public function toTime(): string
    {
        return $this->format('H:i:s');
    }

    /**
     * Get the year
     */
    public function getYear(): int
    {
        return (int) $this->format('Y');
    }

    /**
     * Get the month
     */
    public function getMonth(): int
    {
        return (int) $this->format('n');
    }

    /**
     * Get the day
     */
    public function getDay(): int
    {
        return (int) $this->format('j');
    }

    /**
     * Get the hour
     */
    public function getHour(): int
    {
        return (int) $this->format('G');
    }

    /**
     * Get the minute
     */
    public function getMinute(): int
    {
        return (int) $this->format('i');
    }

    /**
     * Get the second
     */
    public function getSecond(): int
    {
        return (int) $this->format('s');
    }

    /**
     * Get day of week (0 = Sunday, 6 = Saturday)
     */
    public function getDayOfWeek(): int
    {
        return (int) $this->format('w');
    }

    /**
     * Check if the frozen time is in the past
     */
    public function isPast(): bool
    {
        return $this->getTimestamp() < time();
    }

    /**
     * Check if the frozen time is in the future
     */
    public function isFuture(): bool
    {
        return $this->getTimestamp() > time();
    }

    /**
     * Check if the frozen time is today
     */
    public function isToday(): bool
    {
        return $this->toDate() === date('Y-m-d');
    }

    /**
     * Check if the frozen time is a weekend
     */
    public function isWeekend(): bool
    {
        $dayOfWeek = $this->getDayOfWeek();

        return $dayOfWeek === 0 || $dayOfWeek === 6;
    }

    /**
     * Check if the frozen time is a weekday
     */
    public function isWeekday(): bool
    {
        return ! $this->isWeekend();
    }

    /**
     * Execute callback with frozen time, then restore
     *
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    public function withFrozenTime(callable $callback): mixed
    {
        $wasFrozen = $this->frozen;
        $previousTimestamp = $this->frozenTimestamp;
        $previousDateTime = $this->frozenDateTime;

        try {
            return $callback();
        } finally {
            if (! $wasFrozen) {
                $this->unfreeze();
            } else {
                $this->frozenTimestamp = $previousTimestamp;
                $this->frozenDateTime = $previousDateTime;
            }
        }
    }

    /**
     * Ensure time is frozen before modifying
     */
    private function ensureFrozen(): void
    {
        if (! $this->frozen || $this->frozenDateTime === null) {
            throw new RuntimeException('Time is not frozen. Call freeze() first.');
        }
    }
}
