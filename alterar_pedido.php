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
$novaQuantidade = isset($_POST['nova_quantidade']) ? intval($_POST['nova_quantidade']) : 0;

if ($idPedido <= 0 || $novaQuantidade <= 0)
{
    $_SESSION['mensagem'] = "Quantidade inválida ou pedido inexistente.";
    header("Location: resumo_pedido.php");
    exit();
}

// Verifica se o pedido pertence ao usuário e está ativo
$sqlPedido = "SELECT p.id, i.preco 
              FROM tb_itens_pedido p 
              JOIN tb_itens i ON p.idItem = i.id 
              WHERE p.id = ? AND p.idUsuario = ? AND p.finalizado = 0";
$stmt = $conn->prepare($sqlPedido);
$stmt->bind_param("ii", $idPedido, $idUsuario);
$stmt->execute();
$resultPedido = $stmt->get_result();

if ($resultPedido->num_rows === 0)
{
    $_SESSION['mensagem'] = "Pedido não encontrado.";
    header("Location: resumo_pedido.php");
    exit();
}

$pedido = $resultPedido->fetch_assoc();
$precoUnitario = $pedido['preco'];
$novoPrecoTotal = $precoUnitario * $novaQuantidade;

// Atualiza a quantidade e recalcula o preço no banco
$sqlAtualizar = "UPDATE tb_itens_pedido SET quantidade = ?, preco = ? WHERE id = ?";
$stmtAtualizar = $conn->prepare($sqlAtualizar);
$stmtAtualizar->bind_param("idi", $novaQuantidade, $novoPrecoTotal, $idPedido);

if ($stmtAtualizar->execute())
{
    $_SESSION['mensagem'] = "Quantidade atualizada com sucesso!";
}
else
{
    $_SESSION['mensagem'] = "Erro ao atualizar o pedido. Tente novamente.";
}

// Redireciona para o resumo do pedido
header("Location: resumo_pedido.php");

// Fecha a conexão
$conn->close();
?>