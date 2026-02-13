<?php

declare(strict_types=1);

namespace App\Notification\Domain\Provider;

use App\Notification\Domain\Entity\Order;

interface OrderProviderInterface
{
    public function getById(string $orderId): ?Order;
}

