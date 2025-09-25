<?php
class PWClass {
  private $host = "localhost";
  private $user = "root";
  private $pass = "";
  private $db = "postres";
  public $conn;

  public function obtenerConexion() {
    $this->conn = new mysqli($this->host, $this->user, $this->pass, $this->db);
    if ($this->conn->connect_error) {
      die("Conexión fallida: " . $this->conn->connect_error);
    }
    return $this->conn;
  }

  public function cerrar() {
    if ($this->conn) {
      $this->conn->close();
    }
  }
}
?>
