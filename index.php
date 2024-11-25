<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

// Conexão com o banco de dados
$conn = new mysqli("localhost", "root", "", "restaurante_db");

if ($conn->connect_error)
{
    die("Erro de conexão: " . $conn->connect_error);
}

// Inicializa a variável de erro
$erro = '';

// Verifica se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] == 'POST')
{
    $email = $_POST['email'];
    $senha = $_POST['senha'];

    // Consulta no banco de dados para verificar se o e-mail existe
    $sql = "SELECT id, senha FROM tb_usuarios WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0)
    {
        $user = $result->fetch_assoc();

        // Verifica a senha
        if (password_verify($senha, $user['senha']))
        {
            // Armazena o ID do usuário na sessão
            $_SESSION['id_usuario'] = $user['id'];

            // Redireciona para o cardápio ou página principal após o login bem-sucedido
            header("Location: cardapio.php"); // Ou qualquer outra página de sua escolha
            exit();
        }
        else
        {
            $erro = "E-mail ou senha incorretos.";
        }
    }
    else
    {
        $erro = "E-mail ou senha incorretos.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Restaurante</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="login-container">
        <h1>Login</h1>

        <!-- Exibe o erro caso exista -->
        <?php if (!empty($erro)) : ?>
            <p class="erro"><?= htmlspecialchars($erro); ?></p>
        <?php endif; ?>

        <!-- Formulário de login -->
        <form method="POST" action="">
            <label for="email">E-mail</label>
            <input type="email" id="email" name="email" required>

            <label for="senha">Senha</label>
            <input type="password" id="senha" name="senha" required>

            <button type="submit">Entrar</button>
        </form>

        <!-- Link para cadastro caso o usuário não tenha uma conta -->
        <p>Não tem uma conta? <a href="cadastro.php">Cadastre-se</a></p>
    </div>
</body>
</html>