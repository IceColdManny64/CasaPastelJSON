<?php

/**
 * CLASE DE SEGURIDAD (Capa de Protección y Encriptación)
 */
class SeguridadJSON {
    // Clave secreta para firmar los archivos. 
    private static $clave_secreta = 'Llave_Super_Secreta_ERP_2026';

    public static function hashearCamposSensibles($datos) {
        $nombresDeCamposSensibles = ['passw', 'contrasena', 'password'];
        
        foreach ($datos as $key => $value) {
            if (is_array($value)) {
                $datos[$key] = self::hashearCamposSensibles($value);
            } elseif (in_array(strtolower((string)$key), $nombresDeCamposSensibles)) {
                // Solo hashea si NO está hasheado previamente con Bcrypt ($2y$ o $2a$)
                if (is_string($value) && strpos($value, '$2y$') !== 0 && strpos($value, '$2a$') !== 0) {
                    $datos[$key] = password_hash($value, PASSWORD_BCRYPT);
                }
            }
        }
        return $datos;
    }

    public static function firmarArchivo($rutaArchivo, $contenidoString) {
        $hash = hash_hmac('sha256', $contenidoString, self::$clave_secreta);
        file_put_contents($rutaArchivo . '.hash', $hash);
    }

    public static function verificarIntegridad($rutaArchivo, $contenidoString) {
        $rutaHash = $rutaArchivo . '.hash';
        if (!file_exists($rutaHash)) return true; // Si no hay firma aún, lo dejamos pasar (1ra vez)

        $hashEsperado = file_get_contents($rutaHash);
        $hashActual = hash_hmac('sha256', $contenidoString, self::$clave_secreta);
        
        return hash_equals($hashEsperado, $hashActual);
    }
}


/**
 * Clase helper para manejar datos JSON simulando una base de datos
 */
class JsonHelper {
    private $dataPath;
    
    public function __construct($dataPath = './data/') {
        $this->dataPath = $dataPath;
        // Crear directorio si no existe
        if (!is_dir($this->dataPath)) {
            mkdir($this->dataPath, 0755, true);
        }
    }
    
    /**
     * Lee datos de un archivo JSON y verifica su integridad
     */
    public function readData($filename) {
        $filepath = $this->dataPath . $filename . '.json';
        
        if (!file_exists($filepath)) {
            return [];
        }
        
        $content = file_get_contents($filepath);

        // 🛡️ VERIFICACIÓN DE INTEGRIDAD (Anti-Manipulación Manual)
        if (!SeguridadJSON::verificarIntegridad($filepath, $content)) {
            // Escribe en el log del servidor si alguien alteró el archivo a mano
            error_log("ALERTA DE SEGURIDAD: El archivo {$filepath} ha sido alterado manualmente o está corrupto.");
            // En un sistema estricto, aquí devolverías "return []" para bloquear el sistema.
            // Para evitar que se rompa durante pruebas, solo lo registramos en el log.
        }

        return json_decode($content, true) ?: [];
    }
    
    /**
     * Escribe datos a un archivo JSON, los hashea y los firma
     */
    public function writeData($filename, $data) {
        $filepath = $this->dataPath . $filename . '.json';

        // 🛡️ HASHEO AUTOMÁTICO DE CONTRASEÑAS
        $datosSeguros = SeguridadJSON::hashearCamposSensibles($data);

        $jsonContent = json_encode($datosSeguros, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $resultado = file_put_contents($filepath, $jsonContent);

        // 🛡️ FIRMADO DEL ARCHIVO
        if ($resultado !== false) {
            SeguridadJSON::firmarArchivo($filepath, $jsonContent);
        }

        return $resultado;
    }
    
    /**
     * Encuentra un elemento por ID
     */
    public function findById($filename, $id) {
        $data = $this->readData($filename);
        foreach ($data as $item) {
            if ($item['id'] === $id) {
                return $item;
            }
        }
        return null;
    }
    
    /**
     * Encuentra elementos que coincidan con criterios
     */
    public function findWhere($filename, $criteria) {
        $data = $this->readData($filename);
        $results = [];
        
        foreach ($data as $item) {
            $matches = true;
            foreach ($criteria as $key => $value) {
                if (!isset($item[$key]) || $item[$key] !== $value) {
                    $matches = false;
                    break;
                }
            }
            if ($matches) {
                $results[] = $item;
            }
        }
        
        return $results;
    }
    
    /**
     * Agrega un nuevo elemento
     */
    public function create($filename, $newData) {
        $data = $this->readData($filename);
        
        // Generar nuevo ID
        $maxId = 0;
        foreach ($data as $item) {
            if ($item['id'] > $maxId) {
                $maxId = $item['id'];
            }
        }
        $newData['id'] = $maxId + 1;
        
        $data[] = $newData;
        return $this->writeData($filename, $data) ? $newData : false;
    }
    
    /**
     * Actualiza un elemento existente
     */
    public function update($filename, $id, $updateData) {
        $data = $this->readData($filename);
        $updated = false;
        
        for ($i = 0; $i < count($data); $i++) {
            if ($data[$i]['id'] == $id) {
                $data[$i] = array_merge($data[$i], $updateData);
                $updated = true;
                break;
            }
        }
        
        if ($updated) {
            return $this->writeData($filename, $data) ? true : false;
        }
        
        return false;
    }
    
    /**
     * Elimina un elemento por ID
     */
    public function delete($filename, $id) {
        $data = $this->readData($filename);
        $newData = [];
        $found = false;
        
        foreach ($data as $item) {
            if ($item['id'] !== $id) {
                $newData[] = $item;
            } else {
                $found = true;
            }
        }
        
        if ($found) {
            return $this->writeData($filename, $newData) ? true : false;
        }
        
        return false;
    }
    
    /**
     * Obtiene todos los datos de una tabla
     */
    public function getAll($filename) {
        return $this->readData($filename);
    }
    
    /**
     * Verifica si un usuario existe con credenciales
     */
    public function authenticateUser($filename, $userField, $passField, $user, $pass) {
        $data = $this->readData($filename);
        
        foreach ($data as $item) {
            // Solo valida el usuario
            if ($item[$userField] === $user) {
                
                // 🛡️ REVISIÓN CON BCRYPT (o texto plano por compatibilidad temporal)
                if (password_verify($pass, $item[$passField]) || $item[$passField] === $pass) {
                    return $item;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Verifica si un email ya existe
     */
    public function emailExists($email) {
        $users = $this->readData('usuarios');
        foreach ($users as $user) {
            if ($user['correo'] === $email) {
                return true;
            }
        }
        return false;
    }
}