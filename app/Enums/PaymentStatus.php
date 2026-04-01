<?php

namespace App\Enums;

enum PaymentStatus: int
{
    case Unpaid = 1;
    case PaymentRequested = 2;
    case Paid = 3;
    case Processing = 4;
    case PendingConfirmation = 5;
    case Cancelled = 6;

    public function label(): string
    {
        return match ($this) {
            self::Unpaid => 'Chưa thanh toán',
            self::PaymentRequested => 'Yêu cầu thanh toán',
            self::Paid => 'Đã thanh toán',
            self::Processing => 'Đang xử lý',
            self::PendingConfirmation => 'Chờ xác nhận',
            self::Cancelled => 'Đã hủy',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Unpaid => 'gray',
            self::PaymentRequested => 'blue',
            self::Paid => 'green',
            self::Processing => 'yellow',
            self::PendingConfirmation => 'orange',
            self::Cancelled => 'red',
        };
    }

    public function transitions(): array
    {
        return match ($this) {
            self::Unpaid, self::Cancelled => [self::PaymentRequested],
            self::PaymentRequested => [self::Paid, self::Processing, self::PendingConfirmation, self::Cancelled],
            self::Processing => [self::Paid, self::PendingConfirmation, self::Cancelled],
            self::PendingConfirmation => [self::Paid, self::Processing, self::Cancelled],
            self::Paid => [],
        };
    }

    public function isFinal(): bool
    {
        return $this === self::Paid;
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->map(fn (self $status) => [
                'value' => (string) $status->value,
                'label' => $status->label(),
                'color' => $status->color(),
            ])
            ->all();
    }
}
