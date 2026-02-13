<?php

declare(strict_types=1);

namespace App\Notification\Application\Query;

/**
 * Query object for retrieving notification count for a specific order.
 *
 * This is a simple data transfer object (DTO) used in the CQRS pattern
 * to encapsulate the query parameters. It represents a request to fetch
 * notification statistics for an order identified by its UUID.
 *
 * @final
 */
final readonly class GetNotificationCountByOrder
{
    /**
     * @param string $orderId The unique order identifier (UUID format)
     */
    public function __construct(private string $orderId) {}

    public function getOrderId(): string
    {
        return $this->orderId;
    }
}

