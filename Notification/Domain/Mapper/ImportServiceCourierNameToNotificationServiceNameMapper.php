<?php

namespace App\Notification\Domain\Mapper;

use App\Notification\Domain\Enum\OrderServiceCourierNames;
use App\Notification\Domain\Enum\NotificationServiceCourierNames;

final class ImportServiceCourierNameToNotificationServiceNameMapper
{
    private static array $courierMap = [
        OrderServiceCourierNames::INPOST->name => NotificationServiceCourierNames::InPost->value,
        OrderServiceCourierNames::POCZTA->name => NotificationServiceCourierNames::PocztaPolska->value,
        OrderServiceCourierNames::PEKAES->name => NotificationServiceCourierNames::Pallex->value,
        OrderServiceCourierNames::ALLEGRO->name => NotificationServiceCourierNames::AllegroShipping->value,
        OrderServiceCourierNames::DPD->name => NotificationServiceCourierNames::DPD->value,
        OrderServiceCourierNames::UPS->name => NotificationServiceCourierNames::UPS->value,
        OrderServiceCourierNames::DHL->name => NotificationServiceCourierNames::DHL->value,
        OrderServiceCourierNames::GLS->name => NotificationServiceCourierNames::GLS->value,
        OrderServiceCourierNames::FEDEX->name => NotificationServiceCourierNames::FedEx->value,
        OrderServiceCourierNames::PWR->name => NotificationServiceCourierNames::CourierManager->value,
        OrderServiceCourierNames::HELLMANN->name => NotificationServiceCourierNames::Hellmann->value,
        OrderServiceCourierNames::RABEN->name => NotificationServiceCourierNames::Raben->value,
        OrderServiceCourierNames::KEX->name => NotificationServiceCourierNames::CourierCenter->value,
        OrderServiceCourierNames::AMBRO->name => NotificationServiceCourierNames::CourierCenter->value,
        OrderServiceCourierNames::RHENUS->name => NotificationServiceCourierNames::Rhenus->value,
        OrderServiceCourierNames::WAWAKURIER->name => NotificationServiceCourierNames::WeDo->value,
        OrderServiceCourierNames::DHL_PARCEL->name => NotificationServiceCourierNames::DHL_Parcel->value,
        OrderServiceCourierNames::ITAXI->name => NotificationServiceCourierNames::CourierCenter->value,
        OrderServiceCourierNames::TNT->name => NotificationServiceCourierNames::TNT->value,
        OrderServiceCourierNames::GEIS->name => NotificationServiceCourierNames::GEIS->value,
        OrderServiceCourierNames::LINEHAUL->name => NotificationServiceCourierNames::Log4World->value,
        OrderServiceCourierNames::SUUS->name => NotificationServiceCourierNames::RohligSUUS->value,
        OrderServiceCourierNames::MATERIALY->name => NotificationServiceCourierNames::Packageez->value,
        OrderServiceCourierNames::PALLEX->name => NotificationServiceCourierNames::Pallex->value,
        OrderServiceCourierNames::ZASILKOVNA->name => NotificationServiceCourierNames::Zadbano->value,
        OrderServiceCourierNames::MEEST->name => NotificationServiceCourierNames::Meest->value,
        OrderServiceCourierNames::KUEHNE->name => NotificationServiceCourierNames::KuehneNagel->value,
        OrderServiceCourierNames::ONE_KURIER->name => NotificationServiceCourierNames::CourierCenter->value,
    ];

    public static function mapFromString(string $getCourierCode): string
    {
        $key = strtoupper($getCourierCode);
        if (isset(self::$courierMap[$key])) {
            return self::$courierMap[$key];
        }

        foreach (NotificationServiceCourierNames::cases() as $case) {
            if (strcasecmp($case->value, $getCourierCode) === 0 || strcasecmp($case->name, $getCourierCode) === 0) {
                return $case->value;
            }
        }

        return $getCourierCode;
    }


    public function mapSupplier(string|OrderServiceCourierNames|null $supplier): ?string
    {
        if ($supplier === null || $supplier === '') {
            return null;
        }

        $key = $supplier instanceof OrderServiceCourierNames ? $supplier->name : strtoupper((string)$supplier);

        if (isset(self::$courierMap[$key])) {
            return self::$courierMap[$key];
        }

        foreach (NotificationServiceCourierNames::cases() as $case) {
            if (strcasecmp($case->value, (string)$supplier) === 0 || strcasecmp($case->name, (string)$supplier) === 0) {
                return $case->value;
            }
        }

        return (string)$supplier;
    }

    public function mapOrder(array $order): array
    {
        $supplier = $order['supplier'] ?? null;
        $order['notification_supplier'] = $this->mapSupplier($supplier);

        return $order;
    }
}

