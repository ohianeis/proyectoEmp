<?php

namespace App\Enums;

enum UserEstado: string
{
    case PENDIENTE_VALIDACION = 'pendiente_validacion';
    case ACTIVO = 'activo';
    case PENDIENTE_BAJA = 'pendiente_baja';
    case INACTIVO = 'inactivo';
}