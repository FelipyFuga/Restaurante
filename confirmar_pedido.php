<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

// Verifica se o usuário está logado
if (!isset($_SESSION['id_usuario']))
{
    $_SESSION['mensagem'] = "Você precisa estar logado para confirmar o pedido.";
    header("Location: index.php");
    exit();
}

// Conexão com o banco de dados
$conn = new mysqli("localhost", "root", "", "restaurante_db");

if ($conn->connect_error)
{
    die("Erro de conexão: " . $conn->connect_error);
}

// Obtém o ID do usuário
$idUsuario = $_SESSION['id_usuario'];

// Verifica se há itens no pedido com `finalizado = 0`
$sqlVerificarPedido = "SELECT id, preco FROM tb_itens_pedido WHERE idUsuario = ? AND finalizado = 0";
$stmt = $conn->prepare($sqlVerificarPedido);

if (!$stmt)
{
    $_SESSION['mensagem'] = "Erro na consulta: " . $conn->error;
    header("Location: resumo_pedido.php");
    exit();
}

$stmt->bind_param("i", $idUsuario);
$stmt->execute();
$resultPedido = $stmt->get_result();

// Verifica se há itens pendentes no pedido
if ($resultPedido->num_rows === 0)
{
    $_SESSION['mensagem'] = "Não há itens no pedido para confirmar.";
    header("Location: resumo_pedido.php");
    exit();
}

// Calcula o valor total do pedido
$totalPedido = 0;
while ($item = $resultPedido->fetch_assoc())
{
    $totalPedido += $item['preco'];
}

// Inicia uma transação para garantir consistência
$conn->begin_transaction();

try
{
    // Atualiza o status dos itens do pedido para `finalizado = 1`
    $sqlFinalizarPedido = "UPDATE tb_itens_pedido SET finalizado = 1 WHERE idUsuario = ? AND finalizado = 0";
    $stmtFinalizar = $conn->prepare($sqlFinalizarPedido);

    if (!$stmtFinalizar)
    {
        throw new Exception("Erro ao preparar o SQL para finalizar o pedido: " . $conn->error);
    }

    $stmtFinalizar->bind_param("i", $idUsuario);

    if (!$stmtFinalizar->execute())
    {
        throw new Exception("Erro ao atualizar os itens do pedido: " . $stmtFinalizar->error);
    }

    $sqlRegistrarPedido = "INSERT INTO tb_pedidos (idUsuario, valor_total) VALUES (?, ?)";
    $stmtRegistrar = $conn->prepare($sqlRegistrarPedido);

    if (!$stmtRegistrar)
    {
        throw new Exception("Erro ao preparar o SQL para registrar o pedido: " . $conn->error);
    }

    $stmtRegistrar->bind_param("id", $idUsuario, $totalPedido);

    if (!$stmtRegistrar->execute())
    {
        throw new Exception("Erro ao registrar o pedido: " . $stmtRegistrar->error);
    }

    // Confirma a transação
    $conn->commit();
    $_SESSION['mensagem'] = "Pedido confirmado com sucesso! Obrigado pela sua compra.";
}
catch (Exception $e)
{
    // Faz rollback se ocorrer algum erro
    $conn->rollback();
    $_SESSION['mensagem'] = "Erro ao confirmar o pedido: " . $e->getMessage();
}

// Redireciona para a página de resumo do pedido
header("Location: resumo_pedido.php");

// Fecha os recursos
$stmt->close();
$stmtFinalizar->close();
$stmtRegistrar->close();
$conn->close();
?>