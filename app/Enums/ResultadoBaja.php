<?php

namespace App\Enums;

enum ResultadoBaja: string
{
    case PENDIENTE = 'pendiente';
    case ACEPTADA = 'aceptada';
    case RECHAZADA = 'rechazada';
}