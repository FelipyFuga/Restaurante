<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Conexão com o banco de dados
$conn = new mysqli("localhost", "root", "", "restaurante_db");

if ($conn->connect_error)
{
    die("Erro de conexão: " . $conn->connect_error);
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST')
{
    // Coleta os dados do formulário
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $senha = $_POST['senha'];
    $confirmacaoSenha = $_POST['confirmacao_senha'];
    $telefone = $_POST['telefone'];
    $dataNascimento = $_POST['data_nascimento'];
    $cep = $_POST['cep'];
    $rua = $_POST['rua'];
    $numero = $_POST['numero'];
    $bairro = $_POST['bairro'];
    $complemento = $_POST['complemento'];
    $cidade = $_POST['cidade'];
    $estado = $_POST['estado'];

    // Verifica se as senhas coincidem
    if ($senha !== $confirmacaoSenha)
    {
        $erro = "As senhas não coincidem.";
    }
    else
    {
        // Criptografa a senha
        $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

        // Verifica se o e-mail já existe
        $sql = "SELECT id FROM tb_usuarios WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0)
        {
            $erro = "E-mail já cadastrado.";
        }
        else
        {
            // Insere os dados no banco de dados
            $sqlInsert = "INSERT INTO tb_usuarios (nome, email, senha, telefone, data_nascimento, cep, rua, numero, bairro, complemento, cidade, estado)
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmtInsert = $conn->prepare($sqlInsert);
            // Corrigido: 'sssssssssssss' para refletir os 12 campos de dados
            $stmtInsert->bind_param("ssssssssssss", $nome, $email, $senhaHash, $telefone, $dataNascimento, $cep, $rua, $numero, $bairro, $complemento, $cidade, $estado);
            if ($stmtInsert->execute())
            {
                // Redireciona para a página de login após o cadastro
                header("Location: index.php");
                exit();
            }
            else
            {
                $erro = "Erro ao cadastrar. Tente novamente.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - Restaurante</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <div class="cadastro-container">
        <h1>Cadastro de Usuário</h1>
        
        <!-- Exibe o erro caso exista -->
        <?php if (!empty($erro)) : ?>
            <p class="erro"><?= htmlspecialchars($erro); ?></p>
        <?php endif; ?>

        <form method="POST" action="" id="cadastroForm">
            <label for="nome">Nome</label>
            <input type="text" id="nome" name="nome" required>

            <label for="email">E-mail</label>
            <input type="email" id="email" name="email" required>

            <label for="data_nascimento">Data de Nascimento</label>
            <input type="date" id="data_nascimento" name="data_nascimento" required>

            <label for="telefone">Telefone</label>
            <input type="text" id="telefone" name="telefone" required>

            <label for="senha">Senha</label>
            <input type="password" id="senha" name="senha" required>

            <label for="confirmacao_senha">Confirme a Senha</label>
            <input type="password" id="confirmacao_senha" name="confirmacao_senha" required>

            <label for="cep">CEP</label>
            <input type="text" id="cep" name="cep" required>

            <label for="rua">Rua</label>
            <input type="text" id="rua" name="rua" required>

            <label for="numero">Número</label>
            <input type="text" id="numero" name="numero" required>

            <label for="bairro">Bairro</label>
            <input type="text" id="bairro" name="bairro" required>

            <label for="complemento">Complemento</label>
            <input type="text" id="complemento" name="complemento">

            <label for="cidade">Cidade</label>
            <input type="text" id="cidade" name="cidade" required>

            <label for="estado">Estado</label>
            <input type="text" id="estado" name="estado" required maxlength="2">

            <button type="submit">Cadastrar</button>
        </form>
        <p>Já tem uma conta? <a href="index.php">Faça login</a></p>
    </div>

    <script>
        document.getElementById('cadastroForm').addEventListener('submit', function(event)
        {
            const nome = document.getElementById('nome').value.trim();
            const email = document.getElementById('email').value.trim();
            const senha = document.getElementById('senha').value;
            const confirmacaoSenha = document.getElementById('confirmacao_senha').value;
            const telefone = document.getElementById('telefone').value.trim();
            const cep = document.getElementById('cep').value.trim();
            const estado = document.getElementById('estado').value.trim();

            // Validação de nome
            if (nome.length < 3)
            {
                alert("O nome deve ter pelo menos 3 caracteres.");
                event.preventDefault();
                return;
            }

            // Validação de e-mail
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email))
            {
                alert("E-mail inválido.");
                event.preventDefault();
                return;
            }

            // Validação de senha
            if (senha.length < 6)
            {
                alert("A senha deve ter pelo menos 6 caracteres.");
                event.preventDefault();
                return;
            }

            if (senha !== confirmacaoSenha)
            {
                alert("As senhas não coincidem.");
                event.preventDefault();
                return;
            }

            // Validação de telefone
            if (telefone.length < 10)
            {
                alert("Telefone deve ter pelo menos 10 dígitos.");
                event.preventDefault();
                return;
            }

            // Validação de CEP
            if (cep.length !== 8 || isNaN(cep))
            {
                alert("CEP deve ter 8 números.");
                event.preventDefault();
                return;
            }

            // Validação de estado
            if (estado.length !== 2)
            {
                alert("Estado deve ter 2 letras.");
                event.preventDefault();
                return;
            }
        });
    </script>
</body>
</html>