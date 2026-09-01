<?php

declare(strict_types=1);

namespace BikeShare\Repository;

use BikeShare\Db\DbInterface;
use BikeShare\Enum\CouponStatus;

class CouponRepository
{
    public function __construct(private readonly DbInterface $db)
    {
    }

    public function findAllActive(): array
    {
        $coupons = $this->db->query(
            'SELECT coupon, value, status FROM coupons WHERE status=0 ORDER BY status, value, coupon'
        )->fetchAllAssoc();

        return $coupons;
    }

    public function updateStatus(string $coupon, CouponStatus $status): void
    {
        $this->db->query(
            'UPDATE coupons SET status = :status WHERE coupon = :coupon LIMIT 1',
            [
                'status' => $status->value,
                'coupon' => $coupon,
            ]
        );
    }

    public function addItem(string $coupon, float $value): void
    {
        $this->db->query(
            'INSERT INTO coupons (coupon, value, status) VALUES (:coupon, :value, 0)',
            [
                'coupon' => $coupon,
                'value' => $value,
            ]
        );
    }

    public function findActiveItem(string $coupon): ?array
    {
        $result = $this->db->query(
            'SELECT coupon, value, status FROM coupons WHERE coupon = :coupon AND status < 2 LIMIT 1',
            [
                'coupon' => $coupon,
            ]
        );

        return $result->fetchAssoc();
    }
}
