<?php

declare(strict_types=1);

namespace App\Notification\Application\Query;

/**
 * DTO for representing notification history entry in API responses.
 *
 * This is an immutable data transfer object containing information about a single
 * notification event in the order's history. It's designed to be serialized
 * to JSON for API responses.
 *
 * @final
 */
final readonly class NotificationHistoryView
{
    /**
     * @param string $template The email template name used for this notification
     * @param string $sentDate The notification send date in ISO 8601 format
     * @param string $statusName The order status at the time of notification
     * @param int $count The number of times this notification was sent (retry count)
     */
    public function __construct(
        private string $template,
        private string $sentDate,
        private string $statusName,
        private int $count,
    ) {}

    public function toArray(): array
    {
        return [
            'template' => $this->template,
            'sentDate' => $this->sentDate,
            'statusName' => $this->statusName,
            'count' => $this->count,
        ];
    }
}
