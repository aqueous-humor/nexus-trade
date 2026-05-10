<?php

namespace App\Values;

use InvalidArgumentException;

class Money
{
    public function __construct(
        public readonly int $cents,
        public readonly string $currency = 'USD'
    ) {}

    public static function fromCents(int $cents, string $currency = 'USD'): self
    {
        return new self($cents, $currency);
    }

    public static function fromDecimal(string|float $amount, string $currency = 'USD'): self
    {
        // Multiply by 100 and round to avoid floating-point issues
        $cents = (int) round((float) $amount * 100);

        return new self($cents, $currency);
    }

    public function add(Money $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->cents + $other->cents, $this->currency);
    }

    public function subtract(Money $other): self
    {
        $this->assertSameCurrency($other);

        $result = $this->cents - $other->cents;

        if ($result < 0) {
            throw new InvalidArgumentException(
                "Subtraction would result in a negative amount: {$this->cents} - {$other->cents}"
            );
        }

        return new self($result, $this->currency);
    }

    public function isGreaterThan(Money $other): bool
    {
        $this->assertSameCurrency($other);

        return $this->cents > $other->cents;
    }

    public function isLessThan(Money $other): bool
    {
        $this->assertSameCurrency($other);

        return $this->cents < $other->cents;
    }

    public function isZero(): bool
    {
        return $this->cents === 0;
    }

    public function toDecimal(): string
    {
        return number_format($this->cents / 100, 2, '.', '');
    }

    public function toFloat(): float
    {
        return $this->cents / 100;
    }

    public function format(): string
    {
        return '$' . $this->toDecimal();
    }

    private function assertSameCurrency(Money $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException(
                "Currency mismatch: {$this->currency} vs {$other->currency}"
            );
        }
    }
}
