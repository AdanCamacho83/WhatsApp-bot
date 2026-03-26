<?php

namespace App\Helpers;

use DateTime;

/**
 * Helper para manejar fechas y formateo
 */
class DateHelper
{
    /**
     * Formatea una fecha en formato largo en español o inglés
     */
    public static function formatearFechaLarga(string $fechaInput, string $idioma = 'ES'): string
    {
        // Convertir el formato dd/mm/aaaa a un objeto DateTime
        $fechaObj = DateTime::createFromFormat('d/m/Y', $fechaInput);

        if (!$fechaObj) {
            return "Formato de fecha inválido";
        }

        // Diccionario de traducciones
        $traducciones = [
            'ES' => [
                'dias' => ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'],
                'meses' => [
                    1 => 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
                    'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'
                ],
                'formato' => "%s %s de %s de %s" // jueves 21 de mayo de 2026
            ],
            'EN' => [
                'dias' => ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'],
                'meses' => [
                    1 => 'january', 'february', 'march', 'april', 'may', 'june',
                    'july', 'august', 'september', 'october', 'november', 'december'
                ],
                'formato' => "%s, %s %s, %s" // thursday, may 21, 2026
            ]
        ];

        // Extraer las partes numéricas de la fecha
        $numDiaSemana = (int)$fechaObj->format('w'); // 0 (domingo) a 6 (sábado)
        $diaMes = (int)$fechaObj->format('j'); // 1 a 31
        $numMes = (int)$fechaObj->format('n'); // 1 a 12
        $anio = $fechaObj->format('Y'); // 2026

        // Construir la cadena según el idioma
        $lang = ($idioma === 'EN') ? 'EN' : 'ES';
        $d = $traducciones[$lang]['dias'][$numDiaSemana];
        $m = $traducciones[$lang]['meses'][$numMes];

        if ($lang === 'ES') {
            return sprintf($traducciones['ES']['formato'], $d, $diaMes, $m, $anio);
        } else {
            return sprintf($traducciones['EN']['formato'], $d, $m, $diaMes, $anio);
        }
    }

    /**
     * Formatea fecha y hora para mostrar al usuario
     */
    public static function formatearFechaHora(string $fechaHora): string
    {
        $soloFecha = date('d/m/Y', strtotime($fechaHora));
        $soloHora = date('g:i A', strtotime($fechaHora));
        return date('d/m/Y g:i A', strtotime($fechaHora));
    }

    /**
     * Obtiene la fecha actual en la zona horaria del sistema
     */
    public static function obtenerFechaActual(string $timezone = 'America/Chicago'): DateTime
    {
        return new DateTime('now', new \DateTimeZone($timezone));
    }
}
