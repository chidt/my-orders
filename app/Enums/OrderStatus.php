<?php

namespace App\Enums;

enum OrderStatus: int
{
    case New = 1;
    case Processing = 2;
    case ClosingOrder = 3;
    case AddToCart = 4;
    case Ordered = 5;
    case PreOrder = 6;
    case WaitingForStock = 7;
    case Arrived = 8;
    case Invoiced = 9;
    case Delivering = 10;
    case Completed = 11;
    case Cancelled = 12;

    public function label(): string
    {
        return match ($this) {
            self::New => 'Tạo mới',
            self::Processing => 'Đang xử lý',
            self::ClosingOrder => 'Chốt đơn',
            self::AddToCart => 'Thêm giỏ hàng',
            self::Ordered => 'Đã order',
            self::PreOrder => 'Pre-order',
            self::WaitingForStock => 'Chờ nhập kho',
            self::Arrived => 'Hàng về',
            self::Invoiced => 'Đã báo đơn',
            self::Delivering => 'Đang giao hàng',
            self::Completed => 'Hoàn thành',
            self::Cancelled => 'Hủy',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::New => 'blue',
            self::Processing => 'yellow',
            self::ClosingOrder => 'orange',
            self::AddToCart => 'purple',
            self::Ordered => 'cyan',
            self::PreOrder => 'teal',
            self::WaitingForStock => 'gray',
            self::Arrived => 'indigo',
            self::Invoiced => 'pink',
            self::Delivering => 'lime',
            self::Completed => 'green',
            self::Cancelled => 'red',
        };
    }

    public function transitions(): array
    {
        return match ($this) {
            self::New => [self::Processing, self::ClosingOrder, self::Cancelled],
            self::Processing => [self::ClosingOrder, self::AddToCart, self::Cancelled],
            self::ClosingOrder => [self::AddToCart, self::Cancelled],
            self::AddToCart => [self::Ordered, self::Cancelled],
            self::Ordered => [self::PreOrder, self::WaitingForStock, self::Cancelled],
            self::PreOrder => [self::WaitingForStock, self::Cancelled],
            self::WaitingForStock => [self::Arrived, self::Cancelled],
            self::Arrived => [self::Invoiced, self::Cancelled],
            self::Invoiced => [self::Delivering, self::Cancelled],
            self::Delivering => [self::Completed, self::Cancelled],
            self::Completed, self::Cancelled => [],
        };
    }

    public function isFinal(): bool
    {
        return $this === self::Completed || $this === self::Cancelled;
    }

    /** Reservation is held from chốt đơn until giao (trừ kho). */
    public function hasActiveStockReservation(): bool
    {
        return match ($this) {
            self::Ordered, self::WaitingForStock, self::Arrived, self::Invoiced => true,
            default => false,
        };
    }

    /** Đã trừ tồn khi đang giao hoặc đã hoàn thành (sau bước giao). */
    public function hasStockDeductedForDelivery(): bool
    {
        return $this === self::Delivering || $this === self::Completed;
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
