<?php

namespace App\Models;

use App\Database\Database;
use PDO;
use Exception;

class Empresa
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Crear una nueva empresa
     *
     * @param string $nombreEmpresa Nombre de la empresa
     * @param string $codigoEmpresa Código único de la empresa
     * @param string $telefonoContacto Teléfono de contacto
     * @param string $telefonoTwilio Número de WhatsApp de Twilio
     * @param string $direccion Dirección (opcional)
     * @param string $usuario Nombre de usuario
     * @param string $passwordEncriptado Contraseña ya encriptada con password_hash()
     * @return bool True si se creó exitosamente, false en caso contrario
     */
    public function crear($nombreEmpresa, $codigoEmpresa, $telefonoContacto, $telefonoTwilio, $direccion, $usuario, $passwordEncriptado)
    {
        try {
            $sql = "INSERT INTO empresas 
                    (nombre_empresa, codigo_empresa, telefono_contacto, telefono_twilio, direccion, usuario, password) 
                    VALUES 
                    (:nombre_empresa, :codigo_empresa, :telefono_contacto, :telefono_twilio, :direccion, :usuario, :password)";

            $stmt = $this->db->prepare($sql);
            
            $stmt->bindParam(':nombre_empresa', $nombreEmpresa, PDO::PARAM_STR);
            $stmt->bindParam(':codigo_empresa', $codigoEmpresa, PDO::PARAM_STR);
            $stmt->bindParam(':telefono_contacto', $telefonoContacto, PDO::PARAM_STR);
            $stmt->bindParam(':telefono_twilio', $telefonoTwilio, PDO::PARAM_STR);
            $stmt->bindParam(':direccion', $direccion, PDO::PARAM_STR);
            $stmt->bindParam(':usuario', $usuario, PDO::PARAM_STR);
            $stmt->bindParam(':password', $passwordEncriptado, PDO::PARAM_STR);

            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error al crear empresa: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verificar si un nombre de usuario ya existe
     *
     * @param string $usuario Nombre de usuario a verificar
     * @return bool True si el usuario existe, false en caso contrario
     */
    public function existeUsuario($usuario)
    {
        try {
            $sql = "SELECT COUNT(*) as total FROM empresas WHERE usuario = :usuario";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':usuario', $usuario, PDO::PARAM_STR);
            $stmt->execute();
            
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            return $resultado['total'] > 0;
        } catch (Exception $e) {
            error_log("Error al verificar usuario: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener empresa por ID
     *
     * @param int $id ID de la empresa
     * @return array|null Datos de la empresa o null si no existe
     */
    public function obtenerPorId($id)
    {
        try {
            $sql = "SELECT id, nombre_empresa, codigo_empresa, telefono_contacto, telefono_twilio, direccion, usuario 
                    FROM empresas 
                    WHERE id = :id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error al obtener empresa: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener empresa por nombre de usuario
     *
     * @param string $usuario Nombre de usuario
     * @return array|null Datos de la empresa o null si no existe
     */
    public function obtenerPorUsuario($usuario)
    {
        try {
            $sql = "SELECT id, nombre_empresa, codigo_empresa, telefono_contacto, telefono_twilio, direccion, usuario, password 
                    FROM empresas 
                    WHERE usuario = :usuario";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':usuario', $usuario, PDO::PARAM_STR);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error al obtener empresa por usuario: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener empresa por código
     *
     * @param string $codigoEmpresa Código único de la empresa
     * @return array|null Datos de la empresa o null si no existe
     */
    public function obtenerPorCodigo($codigoEmpresa)
    {
        try {
            $sql = "SELECT id, nombre_empresa, codigo_empresa, telefono_contacto, telefono_twilio, direccion, usuario 
                    FROM empresas 
                    WHERE codigo_empresa = :codigo_empresa";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':codigo_empresa', $codigoEmpresa, PDO::PARAM_STR);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error al obtener empresa por código: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Verificar credenciales de login
     *
     * @param string $usuario Nombre de usuario
     * @param string $password Contraseña sin encriptar
     * @return array|false Datos de la empresa si las credenciales son correctas, false en caso contrario
     */
    public function verificarCredenciales($usuario, $password)
    {
        try {
            $empresa = $this->obtenerPorUsuario($usuario);
            
            if (!$empresa) {
                return false;
            }

            // Verificar contraseña usando password_verify
            if (password_verify($password, $empresa['password'])) {
                // Remover la contraseña del array antes de devolverlo
                unset($empresa['password']);
                return $empresa;
            }

            return false;
        } catch (Exception $e) {
            error_log("Error al verificar credenciales: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener todas las empresas
     *
     * @return array Lista de empresas
     */
    public function obtenerTodas()
    {
        try {
            $sql = "SELECT id, nombre_empresa, codigo_empresa, telefono_contacto, telefono_twilio, direccion, usuario 
                    FROM empresas 
                    ORDER BY nombre_empresa ASC";
            
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error al obtener empresas: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Actualizar datos de una empresa
     *
     * @param int $id ID de la empresa
     * @param string $nombreEmpresa Nombre de la empresa
     * @param string $telefonoContacto Teléfono de contacto
     * @param string $direccion Dirección
     * @return bool True si se actualizó exitosamente, false en caso contrario
     */
    public function actualizar($id, $nombreEmpresa, $telefonoContacto, $direccion)
    {
        try {
            $sql = "UPDATE empresas 
                    SET nombre_empresa = :nombre_empresa, 
                        telefono_contacto = :telefono_contacto, 
                        direccion = :direccion 
                    WHERE id = :id";

            $stmt = $this->db->prepare($sql);
            
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':nombre_empresa', $nombreEmpresa, PDO::PARAM_STR);
            $stmt->bindParam(':telefono_contacto', $telefonoContacto, PDO::PARAM_STR);
            $stmt->bindParam(':direccion', $direccion, PDO::PARAM_STR);

            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error al actualizar empresa: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualizar contraseña de una empresa
     *
     * @param int $id ID de la empresa
     * @param string $passwordEncriptado Nueva contraseña encriptada
     * @return bool True si se actualizó exitosamente, false en caso contrario
     */
    public function actualizarPassword($id, $passwordEncriptado)
    {
        try {
            $sql = "UPDATE empresas SET password = :password WHERE id = :id";

            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':password', $passwordEncriptado, PDO::PARAM_STR);

            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error al actualizar contraseña: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Eliminar una empresa
     *
     * @param int $id ID de la empresa
     * @return bool True si se eliminó exitosamente, false en caso contrario
     */
    public function eliminar($id)
    {
        try {
            $sql = "DELETE FROM empresas WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error al eliminar empresa: " . $e->getMessage());
            return false;
        }
    }
}
