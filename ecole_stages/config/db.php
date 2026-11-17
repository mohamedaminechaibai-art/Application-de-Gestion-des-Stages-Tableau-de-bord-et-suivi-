<?php
    $host = 'localhost';
    $username = 'root';
    $password = '';
    $dbname = 'ecole_stages';

    try{
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch(PDOException $e){
        die("Error connecting to the database: " . $e->getMessage());
    }

?>