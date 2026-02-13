<?php

declare(strict_types=1);

namespace App\Notification\Domain\Provider;

use App\Notification\Application\Query\NotificationInfoView;

interface MailNotificationStatsProviderInterface
{
    public function getByOrder(string $orderId): NotificationInfoView;
}

