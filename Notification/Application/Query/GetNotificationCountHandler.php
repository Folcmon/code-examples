<?php

declare(strict_types=1);

namespace App\Notification\Application\Query;

use App\Notification\Domain\Provider\MailNotificationStatsProviderInterface;
use App\Notification\Infrastructure\Provider\NotificationStatsProvider\MailNotificationStatsProvider;
use Exception;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Query handler for GetNotificationCountByOrder.
 *
 * Retrieves notification statistics for a given order using the MailNotificationStatsProvider.
 * This handler implements the command query responsibility segregation (CQRS) pattern.
 * It acts as an invokable handler that processes the query and returns the result.
 *
 * @final
 */
final readonly class GetNotificationCountHandler
{
    public function __construct(
        #[Autowire(service: MailNotificationStatsProvider::class)]
        private MailNotificationStatsProviderInterface $statsProvider
    ) {}

    /**
     * Execute the query to get notification count by order.
     *
     * @param GetNotificationCountByOrder $query The query object containing orderId
     * @return NotificationInfoView The notification statistics view model
     * @throws Exception When stats provider fails to retrieve data
     */
    public function __invoke(GetNotificationCountByOrder $query): NotificationInfoView
    {
        return $this->statsProvider->getByOrder($query->getOrderId());
    }
}

