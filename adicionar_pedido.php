<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

// Verifica se o usuário está logado
if (!isset($_SESSION['id_usuario']))
{
    $_SESSION['mensagem'] = "Você precisa estar logado para adicionar itens ao pedido.";
    header("Location: index.php");
    exit();
}

// Conexão com o banco de dados
$conn = new mysqli("localhost", "root", "", "restaurante_db");

if ($conn->connect_error)
{
    die("Erro de conexão: " . $conn->connect_error);
}

// Validação dos dados recebidos do formulário
$idUsuario = $_SESSION['id_usuario'];
$idItem = isset($_POST['id_item']) ? intval($_POST['id_item']) : 0;
$quantidade = isset($_POST['quantidade']) ? intval($_POST['quantidade']) : 1;

// Verifica se os dados são válidos
if ($idItem <= 0 || $quantidade <= 0)
{
    $_SESSION['mensagem'] = "Por favor, forneça um item válido e uma quantidade positiva.";
    header("Location: cardapio.php");
    exit();
}

// Verifica se o item existe no banco e recupera seu preço
$sqlItem = "SELECT preco FROM tb_itens WHERE id = ?";
$stmt = $conn->prepare($sqlItem);
if (!$stmt)
{
    $_SESSION['mensagem'] = "Erro no banco de dados: " . $conn->error;
    header("Location: cardapio.php");
    exit();
}
$stmt->bind_param("i", $idItem);
$stmt->execute();
$resultItem = $stmt->get_result();

if ($resultItem->num_rows === 0)
{
    $_SESSION['mensagem'] = "O item selecionado não existe. Por favor, tente novamente.";
    header("Location: cardapio.php");
    exit();
}

$item = $resultItem->fetch_assoc();
$precoUnitario = $item['preco'];

// Calcula o preço total do item
$precoTotal = $quantidade * $precoUnitario;

// Inicia uma transação para garantir consistência
$conn->begin_transaction();

try
{
    // Adiciona o item ao pedido na tabela `tb_itens_pedido`
    $sqlAdicionar = "INSERT INTO tb_itens_pedido (idUsuario, idItem, quantidade, preco, finalizado) VALUES (?, ?, ?, ?, ?)";
    $stmtAdicionar = $conn->prepare($sqlAdicionar);

    if (!$stmtAdicionar)
    {
        throw new Exception("Erro ao preparar a query: " . $conn->error);
    }

    $finalizado = 0;
    $stmtAdicionar->bind_param("iiidi", $idUsuario, $idItem, $quantidade, $precoTotal, $finalizado);

    if ($stmtAdicionar->execute())
    {
        // Confirma a transação
        $conn->commit();
        $_SESSION['mensagem'] = "Item adicionado ao pedido com sucesso!";
    }
    else
    {
        throw new Exception("Erro ao executar a query.");
    }
}
catch (Exception $e)
{
    // Faz o rollback em caso de erro
    $conn->rollback();
    $_SESSION['mensagem'] = "Erro ao adicionar o item ao pedido: " . $e->getMessage();
}

// Redireciona de volta para o cardápio com uma mensagem
header("Location: cardapio.php");

// Fecha os recursos abertos
$stmt->close();
$stmtAdicionar->close();
$conn->close();
?>