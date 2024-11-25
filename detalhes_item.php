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

// Obtém o ID do item pela URL
$idItem = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($idItem <= 0)
{
    header("Location: cardapio.php");
    exit();
}

// Busca informações do item no banco
$sqlItem = "SELECT i.nome AS item_nome, i.descricao, i.preco, i.foto, c.nome AS categoria_nome 
            FROM tb_itens i 
            JOIN tb_categoria c ON i.idCategoria = c.id 
            WHERE i.id = ?";
$stmt = $conn->prepare($sqlItem);
$stmt->bind_param("i", $idItem);
$stmt->execute();
$resultItem = $stmt->get_result();

if ($resultItem->num_rows === 0)
{
    header("Location: cardapio.php");
    exit();
}

$item = $resultItem->fetch_assoc();

// Mapeia as imagens com base no nome do item
$imagens = [
    'Tiramisu' => 'images/tiramisu.jpg',
    'Spaghetti Carbonara' => 'images/spaghetti.jpg',
    'Coca-Cola' => 'images/cocacola.jpg'
];

// Atribui a imagem correta, caso exista no array
$caminhoImagem = isset($imagens[$item['item_nome']]) ? $imagens[$item['item_nome']] : 'images/default.jpg';

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes do Item - <?= htmlspecialchars($item['item_nome']); ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="detalhes-container">
        <header>
            <h1>Detalhes do Item</h1>
            <p><a href="cardapio.php">Voltar ao Cardápio</a></p>
        </header>
        <div class="detalhes-item">
            <!-- Exibe a imagem do item com base no nome do item -->
            <img src="<?= $caminhoImagem; ?>" alt="<?= htmlspecialchars($item['item_nome']); ?>" class="detalhes-imagem">
            <h2><?= htmlspecialchars($item['item_nome']); ?></h2>
            <p><strong>Categoria:</strong> <?= htmlspecialchars($item['categoria_nome']); ?></p>
            <p><strong>Descrição:</strong> <?= nl2br(htmlspecialchars($item['descricao'])); ?></p>
            <p><strong>Preço:</strong> R$<?= number_format($item['preco'], 2, ',', '.'); ?></p>
            <form method="POST" action="adicionar_pedido.php">
                <label for="quantidade">Quantidade:</label>
                <input type="number" id="quantidade" name="quantidade" value="1" min="1" required>
                <input type="hidden" name="id_item" value="<?= $idItem; ?>">
                <button type="submit">Adicionar ao Pedido</button>
            </form>
        </div>
    </div>
</body>
</html>

<?php
$conn->close();
?>