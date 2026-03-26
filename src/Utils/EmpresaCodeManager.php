<?php

namespace App\Utils;

use App\Models\Cliente;
use App\Models\Empresa;

class EmpresaCodeManager
{
    /**
     * Generar código único para una empresa
     * Ejemplo: BARBER123, SALON456
     */
    public static function generarCodigo(string $nombreEmpresa): string
    {
        // Tomar primeras 6 letras del nombre (sin espacios, mayúsculas)
        $prefijo = strtoupper(preg_replace('/[^a-zA-Z]/', '', $nombreEmpresa));
        $prefijo = substr($prefijo, 0, 6);
        
        // Agregar 3 números aleatorios
        $numero = rand(100, 999);
        
        return $prefijo . $numero;
    }

    /**
     * Verificar si un mensaje contiene un código de empresa
     * Retorna el idEmpresa si encuentra el código, null si no
     */
    public static function extraerCodigoDeMensaje(string $mensaje): ?array
    {
        // Buscar patrón: 6 letras seguidas de 3 números al inicio del mensaje
        // Ejemplos: "BARBER123" o "BARBER123 hola quiero cita"
        if (preg_match('/^([A-Z]{6}\d{3})(\s+|$)/i', $mensaje, $matches)) {
            $codigo = strtoupper($matches[1]);
            
            // Buscar empresa por código
            $empresaModel = new Empresa();
            $empresa = $empresaModel->obtenerPorCodigo($codigo);
            
            if ($empresa) {
                // Limpiar mensaje: si hay texto después del código, usarlo; si no, mensaje vacío
                $mensajeLimpio = isset($matches[2]) && trim($matches[2]) ? trim(substr($mensaje, strlen($matches[0]))) : '';
                
                return [
                    'codigo' => $codigo,
                    'idEmpresa' => $empresa['id'],
                    'nombreEmpresa' => $empresa['nombre_empresa'],
                    'mensajeLimpio' => $mensajeLimpio
                ];
            }
        }
        
        return null;
    }

    /**
     * Registrar automáticamente un cliente con código
     */
    public static function registrarClienteConCodigo(string $telefono, string $mensaje): ?int
    {
        $datoCodigo = self::extraerCodigoDeMensaje($mensaje);
        
        if (!$datoCodigo) {
            return null;
        }
        
        $clienteModel = new Cliente();
        
        // Registrar cliente con la empresa
        $registrado = $clienteModel->registrar(
            $datoCodigo['idEmpresa'],
            $telefono,
            '' // nombre vacío, se puede pedir después
        );
        
        if ($registrado) {
            return $datoCodigo['idEmpresa'];
        }
        
        return null;
    }

    /**
     * Obtener mensaje de bienvenida con código
     */
    public static function getMensajeBienvenida(string $nombreEmpresa, string $codigo): string
    {
        return "🎉 *¡Bienvenido a {$nombreEmpresa}!*\n\n"
             . "Tu código de acceso es: *{$codigo}*\n\n"
             . "📱 *¿Cómo usar WhatsApp?*\n"
             . "1️⃣ Guarda este número: +1 (415) 523-8886\n"
             . "2️⃣ Envía tu código seguido de tu mensaje:\n"
             . "   Ejemplo: \"{$codigo} Hola, quiero una cita\"\n\n"
             . "3️⃣ Después del primer mensaje, ya no necesitas el código ✨\n\n"
             . "¿Listo para agendar tu cita? 😊";
    }

    /**
     * Obtener mensaje cuando falta el código
     */
    public static function getMensajeSinCodigo(): string
    {
        return "🚫 *No pudimos identificar tu empresa*\n\n"
             . "Para usar este servicio, necesitas el código de tu empresa.\n\n"
             . "📋 *¿Cómo obtener tu código?*\n"
             . "Contacta a la empresa donde deseas agendar tu cita.\n"
             . "Ellos te proporcionarán un código único.\n\n"
             . "💬 *Formato del mensaje:*\n"
             . "CODIGO123 tu mensaje aquí\n\n"
             . "Ejemplo: BARBER123 Quiero una cita";
    }
}
