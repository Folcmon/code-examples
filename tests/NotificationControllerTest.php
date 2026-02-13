<?php

declare(strict_types=1);

namespace App\Tests\Unit\Notifications\Application\Query;

use App\Notification\Application\Query\NotificationHistoryView;
use App\Notification\Application\Query\NotificationInfoView;
use App\Notification\Domain\Exception\OrderProviderException;
use App\Notification\Presentation\Controller\NotificationController;
use App\Shared\Infrastructure\Symfony\Messenger\QueryBus;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Extended test suite for NotificationController.
 *
 * Tests all HTTP endpoints with various scenarios including happy paths,
 * error handling, and edge cases.
 */
class NotificationControllerTest extends TestCase
{
    private QueryBus $queryBus;
    private TranslatorInterface $translator;
    private LoggerInterface $logger;
    private NotificationController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->queryBus = $this->createMock(QueryBus::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->controller = new NotificationController($this->queryBus, $this->translator, $this->logger);

        // Setup container mock for AbstractController
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->with('serializer')->willReturn(false);

        $ref = new \ReflectionObject($this->controller);
        $prop = $ref->getProperty('container');
        $prop->setAccessible(true);
        $prop->setValue($this->controller, $container);
    }

    /**
     * @test
     */
    public function testGetNotificationsInfoReturnsSuccessResponse(): void
    {
        // Arrange
        $orderId = '00000000-0000-0000-0000-000000000000';
        $expectedView = new NotificationInfoView(2, [
            new NotificationHistoryView('confirmation', '2026-01-28T12:00:00Z', 'pending', 1),
            new NotificationHistoryView('shipped', '2026-01-29T12:00:00Z', 'shipped', 1),
        ]);

        $this->queryBus
            ->expects($this->once())
            ->method('query')
            ->willReturn($expectedView);

        $this->logger->expects($this->once())->method('info');

        // Act
        $response = $this->controller->getNotificationsInfo($orderId);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertSame(2, $content['totalNotifications']);
        $this->assertCount(2, $content['history']);
    }

    /**
     * @test
     */
    public function testGetNotificationsInfoHandlesOrderNotFound(): void
    {
        // Arrange
        $orderId = '00000000-0000-0000-0000-000000000000';
        $exception = new OrderProviderException('notification.order.error.not_found', 404);

        $this->queryBus
            ->expects($this->once())
            ->method('query')
            ->willThrowException($exception);

        $this->translator
            ->expects($this->once())
            ->method('trans')
            ->with('notification.order.error.not_found')
            ->willReturn('Order not found.');

        $this->logger->expects($this->once())->method('warning');

        // Act
        $response = $this->controller->getNotificationsInfo($orderId);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertSame('Order not found.', $content['error']);
    }

    /**
     * @test
     */
    public function testGetNotificationsInfoHandlesUnauthorizedAccess(): void
    {
        // Arrange
        $orderId = '00000000-0000-0000-0000-000000000000';
        $exception = new OrderProviderException('notification.user.not_authenticated', 401);

        $this->queryBus
            ->expects($this->once())
            ->method('query')
            ->willThrowException($exception);

        $this->translator
            ->expects($this->once())
            ->method('trans')
            ->with('notification.user.not_authenticated')
            ->willReturn('User not authenticated.');

        $this->logger->expects($this->once())->method('warning');

        // Act
        $response = $this->controller->getNotificationsInfo($orderId);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertSame('User not authenticated.', $content['error']);
    }

    /**
     * @test
     */
    public function testGetNotificationsInfoHandlesForbiddenAccess(): void
    {
        // Arrange
        $orderId = '00000000-0000-0000-0000-000000000000';
        $exception = new OrderProviderException('notification.order.error.access_denied', 403);

        $this->queryBus
            ->expects($this->once())
            ->method('query')
            ->willThrowException($exception);

        $this->translator
            ->expects($this->once())
            ->method('trans')
            ->with('notification.order.error.access_denied')
            ->willReturn('Access denied to this order.');

        $this->logger->expects($this->once())->method('warning');

        // Act
        $response = $this->controller->getNotificationsInfo($orderId);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function testGetNotificationsInfoHandlesProviderUnavailable(): void
    {
        // Arrange
        $orderId = '00000000-0000-0000-0000-000000000000';
        $exception = new OrderProviderException('notification.order.error.provider', 503);

        $this->queryBus
            ->expects($this->once())
            ->method('query')
            ->willThrowException($exception);

        $this->translator
            ->expects($this->once())
            ->method('trans')
            ->willReturn('Service temporarily unavailable.');

        $this->logger->expects($this->once())->method('warning');

        // Act
        $response = $this->controller->getNotificationsInfo($orderId);

        // Assert
        $this->assertSame(Response::HTTP_SERVICE_UNAVAILABLE, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function testGetNotificationsInfoHandlesUnexpectedException(): void
    {
        // Arrange
        $orderId = '00000000-0000-0000-0000-000000000000';
        $exception = new \RuntimeException('Unexpected error', 0);

        $this->queryBus
            ->expects($this->once())
            ->method('query')
            ->willThrowException($exception);

        $this->translator
            ->expects($this->once())
            ->method('trans')
            ->with('notification.error.internal')
            ->willReturn('Internal server error.');

        $this->logger->expects($this->once())->method('error');

        // Act
        $response = $this->controller->getNotificationsInfo($orderId);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertSame('Internal server error.', $content['error']);
    }

    /**
     * @test
     */
    public function testGetNotificationsInfoWithEmptyHistoryReturnsZeroNotifications(): void
    {
        // Arrange
        $orderId = '00000000-0000-0000-0000-000000000000';
        $expectedView = new NotificationInfoView(0, []);

        $this->queryBus
            ->expects($this->once())
            ->method('query')
            ->willReturn($expectedView);

        $this->logger->expects($this->once())->method('info');

        // Act
        $response = $this->controller->getNotificationsInfo($orderId);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertSame(0, $content['totalNotifications']);
        $this->assertEmpty($content['history']);
    }

    /**
     * @test
     */
    public function testGetNotificationsInfoWithLargeHistoryList(): void
    {
        // Arrange
        $orderId = '00000000-0000-0000-0000-000000000000';

        $history = [];
        for ($i = 1; $i <= 100; $i++) {
            $history[] = new NotificationHistoryView(
                "template_$i",
                "2026-01-28T" . str_pad((string)$i, 2, '0', STR_PAD_LEFT) . ":00:00Z",
                "status_$i",
                1
            );
        }

        $expectedView = new NotificationInfoView(100, $history);

        $this->queryBus
            ->expects($this->once())
            ->method('query')
            ->willReturn($expectedView);

        $this->logger->expects($this->once())->method('info');

        // Act
        $response = $this->controller->getNotificationsInfo($orderId);

        // Assert
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertSame(100, $content['totalNotifications']);
        $this->assertCount(100, $content['history']);
    }

    /**
     * @test
     */
    public function testGetNotificationsInfoResponseStructure(): void
    {
        // Arrange
        $orderId = '00000000-0000-0000-0000-000000000000';
        $expectedView = new NotificationInfoView(1, [
            new NotificationHistoryView('order_confirmation', '2026-01-28T12:00:00Z', 'pending', 1),
        ]);

        $this->queryBus
            ->expects($this->once())
            ->method('query')
            ->willReturn($expectedView);

        $this->logger->expects($this->once())->method('info');

        // Act
        $response = $this->controller->getNotificationsInfo($orderId);
        $content = json_decode($response->getContent(), true);

        // Assert - Verify response structure
        $this->assertIsArray($content);
        $this->assertArrayHasKey('totalNotifications', $content);
        $this->assertArrayHasKey('history', $content);

        if (!empty($content['history'])) {
            foreach ($content['history'] as $item) {
                $this->assertArrayHasKey('template', $item);
                $this->assertArrayHasKey('sentDate', $item);
                $this->assertArrayHasKey('statusName', $item);
                $this->assertArrayHasKey('count', $item);
            }
        }
    }
}

