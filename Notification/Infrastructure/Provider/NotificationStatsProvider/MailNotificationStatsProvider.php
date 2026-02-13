<?php

declare(strict_types=1);

namespace App\Notification\Infrastructure\Provider\NotificationStatsProvider;

use App\Notification\Application\Query\NotificationHistoryView;
use App\Notification\Application\Query\NotificationInfoView;
use App\Notification\Domain\Mapper\ImportServiceCourierNameToNotificationServiceNameMapper;
use App\Notification\Domain\Provider\MailNotificationStatsProviderInterface;
use App\Notification\Domain\Provider\OrderProviderInterface;
use App\Notification\Infrastructure\Provider\OrderProvider\OrderProvider;
use OrderProvider\SDK\NotificationSdk\NotificationSdkInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Provider for fetching mail notification statistics for orders.
 *
 * Integrates with external NotificationSdk to retrieve email notification history
 * and maps courier information between different service formats.
 */
class MailNotificationStatsProvider implements MailNotificationStatsProviderInterface
{
    public function __construct(
        private NotificationSdkInterface $notificationSdk,
        #[Autowire(service: OrderProvider::class)]
        private OrderProviderInterface $orderProvider,
        private LoggerInterface $logger,
    ) {}

    /**
     * Get notification statistics for a specific order.
     *
     * @param string $orderId The order identifier
     * @return NotificationInfoView View model with aggregated notification statistics
     * @throws \Exception When SDK call fails or order not found
     */
    public function getByOrder(string $orderId): NotificationInfoView
    {
        try {
            $order = $this->orderProvider->getById($orderId);

            if (!$order) {
                $this->logger->info('Order not found, returning empty notifications', ['orderId' => $orderId]);
                return new NotificationInfoView(0, []);
            }

            $trackingNumber = $order->getTrackingNumber();
            $courierCode = ImportServiceCourierNameToNotificationServiceNameMapper::mapFromString(
                $order->getCourierCode()
            );

            $this->logger->debug('Fetching notifications from SDK', [
                'trackingNumber' => $trackingNumber,
                'courierCode' => $courierCode,
            ]);

            $response = $this->notificationSdk->getEmailNotificationHistory($trackingNumber, $courierCode);

            $history = [];
            $totalCount = 0;

            foreach ($response->getNotifications() as $notification) {
                $history[] = new NotificationHistoryView(
                    $notification->getTemplateName(),
                    $notification->getNotificationDate(),
                    $notification->getOrderStatus(),
                    $notification->getCount()
                );
                $totalCount += $notification->getCount();
            }

            $this->logger->info('Notifications fetched successfully', [
                'orderId' => $orderId,
                'count' => $totalCount,
            ]);

            return new NotificationInfoView($totalCount, $history);

        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch notification statistics', [
                'orderId' => $orderId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}

