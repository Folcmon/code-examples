<?php

declare(strict_types=1);

namespace App\Notification\Application\Query;

/**
 * DTO for representing aggregated notification information in API responses.
 *
 * Contains the total notification count and complete history of notifications
 * for a specific order. This is the main response object returned to API clients
 * containing all notification-related information they requested.
 *
 * @final
 */
final readonly class NotificationInfoView
{
    /**
     * @param int $totalNotifications Total number of notifications sent for the order
     * @param array<NotificationHistoryView> $history Array of individual notification history entries
     */
    public function __construct(
        private int $totalNotifications,
        private array $history,
    ) {}

    public function toArray(): array
    {
        return [
            'totalNotifications' => $this->totalNotifications,
            'history' => array_map(
                static fn(NotificationHistoryView $view) => $view->toArray(),
                $this->history
            ),
        ];
    }
}
