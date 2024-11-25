<?php
// Testando a conexão com o banco de dados
$conn = new mysqli("localhost", "root", "", "restaurante_db");

if ($conn->connect_error) {
    die("Erro de conexão: " . $conn->connect_error);
} else {
    echo "Conexão com o banco de dados realizada com sucesso!";
}

$conn->close();
?>