<?php
/**
 * Punto de entrada único a la capa de datos.
 *
 * Incluye la clase Database (Singleton) y expone la variable global $pdo,
 * que es la conexión PDO compartida por controladores, modelos y APIs.
 */
require_once __DIR__ . '/../app/bootstrap.php';
$pdo = \App\Core\DatabaseConnection::getSharedInstance();
?>
