<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

// Verifica se o usuário está logado
if (!isset($_SESSION['id_usuario']))
{
    header("Location: index.php");
    exit();
}

// Conexão com o banco de dados
$conn = new mysqli("localhost", "root", "", "restaurante_db");

if ($conn->connect_error)
{
    die("Erro de conexão: " . $conn->connect_error);
}

// Valida os dados recebidos
$idUsuario = $_SESSION['id_usuario'];
$idPedido = isset($_POST['id_pedido']) ? intval($_POST['id_pedido']) : 0;

if ($idPedido <= 0)
{
    $_SESSION['mensagem'] = "Pedido inválido ou inexistente.";
    header("Location: resumo_pedido.php");
    exit();
}

// Verifica se o pedido pertence ao usuário e está ativo
$sqlPedido = "SELECT id 
              FROM tb_itens_pedido 
              WHERE id = ? AND idUsuario = ? AND finalizado = 0";
$stmt = $conn->prepare($sqlPedido);
$stmt->bind_param("ii", $idPedido, $idUsuario);
$stmt->execute();
$resultPedido = $stmt->get_result();

if ($resultPedido->num_rows === 0)
{
    $_SESSION['mensagem'] = "Item do pedido não encontrado.";
    header("Location: resumo_pedido.php");
    exit();
}

// Remove o item do pedido
$sqlRemover = "DELETE FROM tb_itens_pedido WHERE id = ?";
$stmtRemover = $conn->prepare($sqlRemover);
$stmtRemover->bind_param("i", $idPedido);

if ($stmtRemover->execute())
{
    $_SESSION['mensagem'] = "Item removido do pedido com sucesso!";
}
else
{
    $_SESSION['mensagem'] = "Erro ao remover o item. Tente novamente.";
}

// Redireciona para o resumo do pedido
header("Location: resumo_pedido.php");
exit();

// Fecha a conexão
$conn->close();
?>