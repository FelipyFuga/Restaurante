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

$idUsuario = $_SESSION['id_usuario'];

// Busca os itens do pedido do usuário
$sqlPedido = "SELECT p.id, i.nome, i.preco, p.quantidade, (p.quantidade * i.preco) AS preco_total 
              FROM tb_itens_pedido p 
              JOIN tb_itens i ON p.idItem = i.id 
              WHERE p.idUsuario = ? AND p.finalizado = 0";
$stmt = $conn->prepare($sqlPedido);
$stmt->bind_param("i", $idUsuario);
$stmt->execute();
$resultPedido = $stmt->get_result();

// Calcula o valor total do pedido
$valorTotal = 0;
$itensPedido = [];
while ($row = $resultPedido->fetch_assoc())
{
    $itensPedido[] = $row;
    $valorTotal += $row['preco_total'];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resumo do Pedido</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="resumo-container">
        <header>
            <h1>Resumo do Pedido</h1>
            <p><a href="cardapio.php">Voltar ao Cardápio</a></p>
        </header>

        <!-- Exibição da Mensagem -->
        <?php if (isset($_SESSION['mensagem'])): ?>
            <p class="mensagem"><?= $_SESSION['mensagem']; ?></p>
            <?php unset($_SESSION['mensagem']); ?>
        <?php endif; ?>

        <?php if (empty($itensPedido)) : ?>
            <p>Seu pedido está vazio!</p>
        <?php else : ?>
            <table class="resumo-tabela">
                <thead>
                    <tr>
                        <th>Nome do Item</th>
                        <th>Quantidade</th>
                        <th>Preço Unitário</th>
                        <th>Preço Total</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($itensPedido as $item) : ?>
                        <tr>
                            <td><?= htmlspecialchars($item['nome']); ?></td>
                            <td><?= $item['quantidade']; ?></td>
                            <td>R$<?= number_format($item['preco'], 2, ',', '.'); ?></td>
                            <td>R$<?= number_format($item['preco_total'], 2, ',', '.'); ?></td>
                            <td>
                                <form method="POST" action="alterar_pedido.php" style="display: inline;">
                                    <input type="hidden" name="id_pedido" value="<?= $item['id']; ?>">
                                    <input type="number" name="nova_quantidade" value="<?= $item['quantidade']; ?>" min="1" required>
                                    <button type="submit">Alterar</button>
                                </form>
                                <form method="POST" action="remover_pedido.php" style="display: inline;">
                                    <input type="hidden" name="id_pedido" value="<?= $item['id']; ?>">
                                    <button type="submit">Remover</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="resumo-total">
                <p><strong>Total Geral:</strong> R$<?= number_format($valorTotal, 2, ',', '.'); ?></p>
            </div>
            <!-- Se houver itens, a opção de confirmar o pedido será exibida -->
            <form method="POST" action="confirmar_pedido.php">
                <button type="submit">Confirmar Pedido</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>

<?php
$conn->close();
?>