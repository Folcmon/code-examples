<?php

declare(strict_types=1);

namespace App\Tests\Unit\Notifications\Application\Query;

use App\Notification\Application\Query\GetNotificationCountByOrder;
use App\Notification\Application\Query\GetNotificationCountHandler;
use App\Notification\Application\Query\NotificationHistoryView;
use App\Notification\Application\Query\NotificationInfoView;
use App\Notification\Domain\Provider\MailNotificationStatsProviderInterface;
use PHPUnit\Framework\TestCase;

/**
 * Extended test suite for GetNotificationCountHandler.
 *
 * Covers happy path, edge cases, and error scenarios to ensure
 * the handler correctly processes queries and returns expected results.
 */
final class GetNotificationCountHandlerTest extends TestCase
{
    private MailNotificationStatsProviderInterface $statsProvider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->statsProvider = $this->createMock(MailNotificationStatsProviderInterface::class);
    }

    /**
     * @test
     */
    public function testInvokeReturnsNotificationInfoViewWithSingleNotification(): void
    {
        // Arrange
        $handler = new GetNotificationCountHandler($this->statsProvider);
        $orderId = 'addfd1c2-3e4b-5678-9abc-1234567890ab';
        $query = new GetNotificationCountByOrder($orderId);

        $expectedView = new NotificationInfoView(1, [
            new NotificationHistoryView('template', '2026-01-28T12:53:43.903Z', 'status', 1)
        ]);

        $this->statsProvider
            ->expects($this->once())
            ->method('getByOrder')
            ->with($orderId)
            ->willReturn($expectedView);

        // Act
        $result = $handler($query);

        // Assert
        $this->assertEquals($expectedView, $result);
        $this->assertSame(1, $result->toArray()['totalNotifications']);
        $this->assertCount(1, $result->toArray()['history']);
    }

    /**
     * @test
     */
    public function testInvokeReturnsEmptyNotificationsWhenNoneExist(): void
    {
        // Arrange
        $handler = new GetNotificationCountHandler($this->statsProvider);
        $orderId = 'addfd1c2-3e4b-5678-9abc-1234567890ab';
        $query = new GetNotificationCountByOrder($orderId);

        $expectedView = new NotificationInfoView(0, []);

        $this->statsProvider
            ->expects($this->once())
            ->method('getByOrder')
            ->with($orderId)
            ->willReturn($expectedView);

        // Act
        $result = $handler($query);

        // Assert
        $this->assertEquals($expectedView, $result);
        $this->assertSame(0, $result->toArray()['totalNotifications']);
        $this->assertEmpty($result->toArray()['history']);
    }

    /**
     * @test
     */
    public function testInvokeReturnsMultipleNotificationsWithCorrectTotalCount(): void
    {
        // Arrange
        $handler = new GetNotificationCountHandler($this->statsProvider);
        $orderId = 'addfd1c2-3e4b-5678-9abc-1234567890ab';
        $query = new GetNotificationCountByOrder($orderId);

        $expectedView = new NotificationInfoView(5, [
            new NotificationHistoryView('confirmation', '2026-01-28T12:00:00Z', 'pending', 1),
            new NotificationHistoryView('shipping', '2026-01-29T12:00:00Z', 'shipped', 2),
            new NotificationHistoryView('delivered', '2026-01-30T12:00:00Z', 'delivered', 2),
        ]);

        $this->statsProvider
            ->expects($this->once())
            ->method('getByOrder')
            ->with($orderId)
            ->willReturn($expectedView);

        // Act
        $result = $handler($query);

        // Assert
        $this->assertSame(5, $result->toArray()['totalNotifications']);
        $this->assertCount(3, $result->toArray()['history']);
    }

    /**
     * @test
     */
    public function testInvokePassesOrderIdCorrectlyToProvider(): void
    {
        // Arrange
        $handler = new GetNotificationCountHandler($this->statsProvider);
        $orderId = 'different-order-id-1234-5678-90ab-cdef-1234567890ab';
        $query = new GetNotificationCountByOrder($orderId);

        $this->statsProvider
            ->expects($this->once())
            ->method('getByOrder')
            ->with($orderId)  // Verify correct order ID is passed
            ->willReturn(new NotificationInfoView(0, []));

        // Act
        $handler($query);

        // Assert - Mock verifies correct parameter was passed
    }

    /**
     * @test
     */
    public function testInvokeHandlesProviderException(): void
    {
        // Arrange
        $handler = new GetNotificationCountHandler($this->statsProvider);
        $orderId = 'addfd1c2-3e4b-5678-9abc-1234567890ab';
        $query = new GetNotificationCountByOrder($orderId);

        $this->statsProvider
            ->expects($this->once())
            ->method('getByOrder')
            ->with($orderId)
            ->willThrowException(new \Exception('Provider error'));

        // Assert & Act
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Provider error');

        $handler($query);
    }

    /**
     * @test
     */
    public function testInvokeReturnsCorrectlyFormattedHistoryArray(): void
    {
        // Arrange
        $handler = new GetNotificationCountHandler($this->statsProvider);
        $orderId = 'addfd1c2-3e4b-5678-9abc-1234567890ab';
        $query = new GetNotificationCountByOrder($orderId);

        $history = [
            new NotificationHistoryView('order_confirmation', '2026-01-28T10:00:00Z', 'pending', 1),
            new NotificationHistoryView('payment_reminder', '2026-01-29T14:30:00Z', 'processing', 2),
        ];
        $expectedView = new NotificationInfoView(3, $history);

        $this->statsProvider
            ->expects($this->once())
            ->method('getByOrder')
            ->willReturn($expectedView);

        // Act
        $result = $handler($query);
        $resultArray = $result->toArray();

        // Assert
        $this->assertIsArray($resultArray);
        $this->assertArrayHasKey('totalNotifications', $resultArray);
        $this->assertArrayHasKey('history', $resultArray);
        $this->assertIsArray($resultArray['history']);

        foreach ($resultArray['history'] as $item) {
            $this->assertArrayHasKey('template', $item);
            $this->assertArrayHasKey('sentDate', $item);
            $this->assertArrayHasKey('statusName', $item);
            $this->assertArrayHasKey('count', $item);
        }
    }

    /**
     * @test
     */
    public function testInvokeWithVeryHighNotificationCount(): void
    {
        // Arrange
        $handler = new GetNotificationCountHandler($this->statsProvider);
        $orderId = 'addfd1c2-3e4b-5678-9abc-1234567890ab';
        $query = new GetNotificationCountByOrder($orderId);

        $history = [];
        $totalCount = 0;

        // Create 50 notification entries
        for ($i = 1; $i <= 50; $i++) {
            $history[] = new NotificationHistoryView(
                "template_$i",
                "2026-01-28T" . str_pad((string)$i, 2, '0', STR_PAD_LEFT) . ":00:00Z",
                "status_$i",
                $i
            );
            $totalCount += $i;
        }

        $expectedView = new NotificationInfoView($totalCount, $history);

        $this->statsProvider
            ->expects($this->once())
            ->method('getByOrder')
            ->willReturn($expectedView);

        // Act
        $result = $handler($query);

        // Assert
        $this->assertSame($totalCount, $result->toArray()['totalNotifications']);
        $this->assertCount(50, $result->toArray()['history']);
    }

    /**
     * @test
     */
    public function testInvokeWithUuidValidation(): void
    {
        // Arrange
        $handler = new GetNotificationCountHandler($this->statsProvider);
        $validUuid = '12345678-1234-5678-1234-567812345678';
        $query = new GetNotificationCountByOrder($validUuid);

        $this->statsProvider
            ->expects($this->once())
            ->method('getByOrder')
            ->with($validUuid)
            ->willReturn(new NotificationInfoView(0, []));

        // Act
        $result = $handler($query);

        // Assert
        $this->assertInstanceOf(NotificationInfoView::class, $result);
    }
}

