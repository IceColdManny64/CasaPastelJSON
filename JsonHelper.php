<?php
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
     * Lee datos de un archivo JSON
     */
    public function readData($filename) {
        $filepath = $this->dataPath . $filename . '.json';
        
        if (!file_exists($filepath)) {
            return [];
        }
        
        $content = file_get_contents($filepath);
        return json_decode($content, true) ?: [];
    }
    
    /**
     * Escribe datos a un archivo JSON
     */
    public function writeData($filename, $data) {
        $filepath = $this->dataPath . $filename . '.json';
        return file_put_contents($filepath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    
    /**
     * Encuentra un elemento por ID
     */
    public function findById($filename, $id) {
        $data = $this->readData($filename);
        foreach ($data as $item) {
            if ($item['id'] == $id) {
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
                if (!isset($item[$key]) || $item[$key] != $value) {
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
            if ($item['id'] != $id) {
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
            if ($item[$userField] === $user && $item[$passField] === $pass) {
                return $item;
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