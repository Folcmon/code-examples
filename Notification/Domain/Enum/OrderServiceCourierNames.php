<?php

namespace App\Notification\Domain\Enum;

enum OrderServiceCourierNames: string
{
    case INPOST = 'INPOST';
    case POCZTA = 'POCZTA';
    case PEKAES = 'PEKAES';
    case ALLEGRO = 'ALLEGRO';
    case DPD = 'DPD';
    case UPS = 'UPS';
    case DHL = 'DHL';
    case GLS = 'GLS';
    case FEDEX = 'FEDEX';
    case PWR = 'PWR';
    case HELLMANN = 'HELLMANN';
    case RABEN = 'RABEN';
    case KEX = 'KEX';
    case AMBRO = 'AMBRO';
    case RHENUS = 'RHENUS';
    case WAWAKURIER = 'WAWAKURIER';
    case DHL_PARCEL = 'DHL_PARCEL';
    case ITAXI = 'ITAXI';
    case TNT = 'TNT';
    case GEIS = 'GEIS';
    case LINEHAUL = 'LINEHAUL';
    case SUUS = 'SUUS';
    case MATERIALY = 'MATERIALY';
    case PALLEX = 'PALLEX';
    case ZASILKOVNA = 'ZASILKOVNA';
    case MEEST = 'MEEST';
    case KUEHNE = 'KUEHNE';
    case ONE_KURIER = 'ONE_KURIER';

}