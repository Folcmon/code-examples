<?php

declare(strict_types=1);

namespace App\Notification\Infrastructure\Provider\OrderProvider;

use Exception;
use \OrderProvider\SDK\ImportService\Provider\ExternalOrder\ExternalOrderProviderInterface;
use \OrderProvider\SDK\ImportService\ValueObject\Response\ExternalOrder\Get\ExternalOrderItemExtendedResponse;
use \OrderProvider\SDK\ImportService\ValueObject\Response\Error\ErrorResponse;
use App\Notification\Domain\Entity\Order;
use App\Notification\Domain\Exception\OrderProviderException;
use App\Notification\Domain\Provider\OrderProviderInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Order provider implementation for fetching orders from external OrderProvider SDK.
 *
 * Integrates with the OrderProvider SDK to retrieve order information and map it
 * to the internal Order domain entity. Includes comprehensive error handling and logging.
 */
readonly final class OrderProvider implements OrderProviderInterface
{
    public function __construct(
        private ExternalOrderProviderInterface $provider,
        private Security $security,
        private LoggerInterface $logger,
    ) {}

    /**
     * Retrieve an order by its ID from the external provider.
     *
     * @param string $orderId The unique order identifier (UUID)
     * @return Order|null The Order entity if found, null if not available
     * @throws OrderProviderException When authentication fails, provider errors occur, or data is invalid
     */
    public function getById(string $orderId): ?Order
    {
        // Validate user is authenticated
        $customerUser = $this->security->getUser();
        if (null === $customerUser) {
            $this->logger->error('Unauthorized access attempt to order', ['orderId' => $orderId]);
            throw new OrderProviderException('notification.user.not_authenticated', 401);
        }

        try {
            /**
             * @var $response ExternalOrderItemExtendedResponse | ErrorResponse
             */
            $response = $this->provider->getOne($orderId, $customerUser->getCustomerId());
        } catch (Exception $e) {
            $this->logger->error('Order provider API error', [
                'orderId' => $orderId,
                'error' => $e->getMessage(),
            ]);
            throw new OrderProviderException('notification.order.error.provider', 503, $e);
        }

        if ($response instanceof ErrorResponse) {
            $this->logger->warning('Order not found in provider', ['orderId' => $orderId]);
            throw new OrderProviderException('notification.order.error.not_found', 404);
        }

        if ($response instanceof ExternalOrderItemExtendedResponse) {
            // Validate required order ID
            if (empty($response->id)) {
                $this->logger->error('Invalid order data - missing ID', ['response' => $response]);
                throw new OrderProviderException('notification.order.error.invalid_data', 500);
            }

            // Validate shipment data
            $shipment = $response->order_shipment;
            if (null === $shipment || empty($shipment->foreign_shipment_id)) {
                $this->logger->error('Invalid shipment data for order', ['orderId' => $orderId]);
                throw new OrderProviderException('notification.order.error.invalid_shipment', 500);
            }

            $this->logger->info('Order retrieved successfully', [
                'orderId' => $orderId,
                'trackingNumber' => $shipment->foreign_shipment_id,
            ]);

            return new Order(
                $response->id,
                $shipment->foreign_shipment_id,
                $shipment->supplier ?? 'UNKNOWN'
            );
        }

        $this->logger->warning('Unexpected order provider response type', [
            'orderId' => $orderId,
            'responseType' => get_class($response),
        ]);

        return null;
    }
}
