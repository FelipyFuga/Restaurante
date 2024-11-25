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

// Consulta para obter as categorias
$sqlCategorias = "SELECT * FROM tb_categoria";
$resultCategorias = $conn->query($sqlCategorias);

// Consulta para obter os itens
$sqlItens = "SELECT i.id, i.nome AS item_nome, i.descricao, i.preco, i.foto, c.nome AS categoria_nome 
             FROM tb_itens i 
             JOIN tb_categoria c ON i.idCategoria = c.id
             ORDER BY c.nome, i.nome";
$resultItens = $conn->query($sqlItens);

// Organizando os itens por categoria
$itensPorCategoria = [];
if ($resultItens->num_rows > 0)
{
    while ($item = $resultItens->fetch_assoc())
    {
        $itensPorCategoria[$item['categoria_nome']][] = $item;
    }
}

// Mapeamento de imagens por nome do item
$imagens = [
    'Tiramisu' => 'images/tiramisu.jpg',
    'Spaghetti Carbonara' => 'images/spaghetti.jpg',
    'Coca-Cola' => 'images/cocacola.jpg',
];

// Verifica se há itens no pedido do usuário
$idUsuario = $_SESSION['id_usuario'];
$sqlVerificarPedido = "SELECT id 
                       FROM tb_itens_pedido 
                       WHERE idUsuario = ? AND finalizado = 0";
$stmt = $conn->prepare($sqlVerificarPedido);
$stmt->bind_param("i", $idUsuario);
$stmt->execute();
$resultPedido = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cardápio - Restaurante</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<div class="container">
    <header>
        <h1>Bem-vindo ao Cardápio</h1>
        <p><a href="index.php">Logout</a></p>
        <!-- Botão para acessar o Resumo do Pedido -->
        <p><a href="resumo_pedido.php" class="resumo-pedido-link">Ver Resumo do Pedido</a></p>
    </header>

    <section class="cardapio">
        <?php if ($resultCategorias->num_rows > 0) : ?>
            <?php while ($categoria = $resultCategorias->fetch_assoc()) : ?>
                <div class="categoria">
                    <h2><?= htmlspecialchars($categoria['nome']); ?></h2>
                    <div class="itens">
                        <?php
                        // Verifica se existem itens para esta categoria
                        if (isset($itensPorCategoria[$categoria['nome']])) :
                            foreach ($itensPorCategoria[$categoria['nome']] as $item) :
                                // Mapeia a imagem do item com base no nome do item
                                $nomeItem = htmlspecialchars($item['item_nome']);
                                $caminhoImagem = isset($imagens[$nomeItem]) ? $imagens[$nomeItem] : 'images/default.jpg';
                        ?>
                            <div class="item">
                                <!-- Exibe a imagem do item -->
                                <img src="<?= $caminhoImagem; ?>" alt="<?= $nomeItem; ?>" class="produto-imagem">
                                
                                <h3><?= $nomeItem; ?></h3>
                                <p><?= htmlspecialchars($item['descricao']); ?></p>
                                <p><strong>Preço:</strong> R$<?= number_format($item['preco'], 2, ',', '.'); ?></p>
                                <a href="detalhes_item.php?id=<?= $item['id']; ?>" class="detalhes">Ver detalhes</a>

                                <!-- Formulário de adicionar ao pedido -->
                                <form action="adicionar_pedido.php" method="POST">
                                    <input type="hidden" name="id_item" value="<?= $item['id']; ?>">
                                    <button type="button" 
                                        class="adicionar" 
                                        data_item_id="<?= htmlspecialchars($item['id']); ?>" 
                                        data_item_name="<?= $nomeItem; ?>" 
                                        data_item_preco="<?= number_format($item['preco'], 2, ',', '.'); ?>">
                                        Adicionar ao Pedido
                                    </button>
                                </form>

                                <!-- Botão de alterar pedido -->
                                <form action="alterar_pedido.php" method="POST">
                                    <input type="hidden" name="id_pedido" value="<?= $item['id']; ?>">
                                    <label for="nova_quantidade">Alterar quantidade:</label>
                                    <input type="number" name="nova_quantidade" id="nova_quantidade" min="1" required>
                                    <button type="submit" class="alterar">Alterar Pedido</button>
                                </form>

                                <!-- Botão de remover do pedido -->
                                <form action="remover_pedido.php" method="POST">
                                    <input type="hidden" name="id_pedido" value="<?= $item['id']; ?>">
                                    <button type="submit" class="remover">
                                        Remover do Pedido
                                    </button>
                                </form>
                            </div>

                        <?php
                            endforeach;
                        endif;
                        ?>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else : ?>
            <p>Nenhuma categoria encontrada.</p>
        <?php endif; ?>

        <!-- Exibição do botão de confirmação do pedido, se houver itens no pedido -->
        <?php if ($resultPedido->num_rows > 0) : ?>
            <div class="confirmar-pedido">
                <form action="confirmar_pedido.php" method="POST">
                    <button type="submit" class="confirmar-pedido-button">Confirmar Pedido</button>
                </form>
            </div>
        <?php else : ?>
            <p>Não há itens no seu pedido para confirmar.</p>
        <?php endif; ?>
    </section>
</div>

<script>
    document.addEventListener("DOMContentLoaded", () => {
        // Seleciona todos os botões com a classe "adicionar"
        const botoesAdicionar = document.querySelectorAll(".adicionar");

        // Adiciona um event listener para cada botão
        botoesAdicionar.forEach((botao) => {
            botao.addEventListener("click", (event) => {
                event.preventDefault(); // Impede o comportamento padrão do botão

                // Obtém os atributos data-* do botão clicado
                const itemId = botao.getAttribute("data_item_id");
                const itemName = botao.getAttribute("data_item_name");
                const itemPreco = botao.getAttribute("data_item_preco");

                // Obtém a quantidade do produto (campo de entrada de quantidade)
                const quantidadeInput = botao.closest('.item').querySelector('input[name="nova_quantidade"]');
                const quantidade = quantidadeInput ? parseInt(quantidadeInput.value, 10) : 1; // Se não houver, define como 1

                // Verifica se a quantidade é válida (número positivo)
                if (quantidade < 1 || isNaN(quantidade))
                {
                    alert('Quantidade inválida! Por favor, insira um número válido.');
                    return;
                }

                // Exibe os valores no console
                console.log(`ID do Item: ${itemId}`);
                console.log(`Nome do Item: ${itemName}`);
                console.log(`Preço do Item: ${itemPreco}`);
                console.log(`Quantidade: ${quantidade}`);

                // Envia requisição via fetch para adicionar ao pedido
                fetch('adicionar_pedido.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `id_item=${itemId}&quantidade=1`
                })
                .then(response => response.text())
                .then(data => {
                    console.log(data);
                    alert('Item adicionado ao pedido com sucesso!');
                })
                .catch((error) => {
                    console.error('Erro:', error);
                    alert('Erro ao adicionar o item. Tente novamente.');
                });
            });
        });
    });
</script>

</body>
</html>

<?php
$conn->close();
?>