<?php

declare(strict_types=1);

namespace App\Notification\Presentation\Controller;

use App\Notification\Application\Query\GetNotificationCountByOrder;
use App\Notification\Domain\Exception\OrderProviderException;
use App\Shared\Infrastructure\Symfony\Messenger\QueryBus;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Controller for managing order notification information.
 *
 * Provides API endpoints for retrieving notification statistics and history
 * for specific orders. Implements proper error handling and logging.
 */
final readonly class NotificationController extends AbstractController
{
    public function __construct(
        private QueryBus            $queryBus,
        private TranslatorInterface $translator,
        private LoggerInterface     $logger,
    ) {}

    /**
     * Get notification information for a specific order.
     *
     * @param string $orderId The order identifier (UUID format)
     * @return JsonResponse JSON response containing notification statistics or error
     */
    #[Route(
        path: "/api/orders/{orderId}/notifications-info",
        name: "get_notifications_info",
        requirements: ['orderId' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'],
        methods: ["GET"]
    )]
    public function getNotificationsInfo(string $orderId): JsonResponse
    {
        try {
            $query = new GetNotificationCountByOrder($orderId);
            $view = $this->queryBus->query($query);

            $this->logger->info('Notifications fetched successfully', ['orderId' => $orderId]);

            return new JsonResponse($view->toArray(), Response::HTTP_OK);

        } catch (OrderProviderException $e) {
            $this->logger->warning('Order provider exception', [
                'orderId' => $orderId,
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);

            // Map exception codes to HTTP status codes
            $statusCode = match($e->getCode()) {
                401 => Response::HTTP_UNAUTHORIZED,
                403 => Response::HTTP_FORBIDDEN,
                404 => Response::HTTP_NOT_FOUND,
                503 => Response::HTTP_SERVICE_UNAVAILABLE,
                default => Response::HTTP_INTERNAL_SERVER_ERROR,
            };

            return new JsonResponse(
                ['error' => $this->translator->trans($e->getMessage())],
                $statusCode
            );

        } catch (Exception $e) {
            $this->logger->error('Unexpected error fetching notifications', [
                'orderId' => $orderId,
                'exception' => $e,
            ]);

            return new JsonResponse(
                ['error' => $this->translator->trans('notification.error.internal')],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
