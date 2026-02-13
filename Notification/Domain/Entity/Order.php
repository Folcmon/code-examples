<?php

declare(strict_types=1);

namespace App\Notification\Domain\Entity;

/**
 * Order entity representing a shipment order with tracking information.
 *
 * This is an immutable value object that should not be modified after creation.
 * Uses PHP 8.1 readonly properties with constructor property promotion.
 */
final readonly class Order
{
    /**
     * @param string $id The unique order identifier (UUID format)
     * @param string $trackingNumber The shipment tracking number from courier
     * @param string $courierCode The courier service code (e.g., INPOST, DHL)
     */
    public function __construct(
        private string $id,
        private string $trackingNumber,
        private string $courierCode,
    ) {}

    public function getId(): string
    {
        return $this->id;
    }

    public function getTrackingNumber(): string
    {
        return $this->trackingNumber;
    }

    public function getCourierCode(): string
    {
        return $this->courierCode;
    }
}
