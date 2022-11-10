<?php

function conectar()
{
    //Se prepara la conexión
    $dsn = 'mysql:host=localhost;dbname=discografia';
    $opciones = array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8');

    try {
        $conexion = new PDO($dsn, 'vetustamorla', '15151', $opciones);
    } catch (PDOException $e) {
        $conexion = null;
    }
    return $conexion;
}



