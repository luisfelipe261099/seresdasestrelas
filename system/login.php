<?php
require_once __DIR__ . '/auth.php';

// Se já logado, redireciona
if (_loadUserFromToken()) {
    header('Location: index.php');
    exit;
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if ($email && $senha) {
        try {
            $db = getDB();
            $stmt = $db->prepare('SELECT id, nome, email, senha_hash, nivel FROM usuarios WHERE email = ?');
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($senha, $user['senha_hash'])) {
                createAuthToken($user['id']);
                header('Location: index.php');
                exit;
            } else {
                $erro = 'E-mail ou senha incorretos.';
            }
        } catch (PDOException $ex) {
            $erro = 'Erro de conexão com o banco de dados.';
        }
    } else {
        $erro = 'Preencha todos os campos.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login — Seres das Estrelas OS</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="style-system.css" />
</head>
<body class="d-flex align-items-center justify-content-center" style="min-height:100vh;">

  <div class="login-card glass-card p-5 text-center">
    <img src="../logo.jpeg" alt="Logo" class="login-logo mb-3" />
    <h4 class="fw-bold mb-1">Seres das Estrelas</h4>
    <p class="text-muted-ice mb-4">Acesso ao Sistema</p>

    <?php if ($erro): ?>
      <div class="alert alert-danger py-2" role="alert"><?= e($erro) ?></div>
    <?php endif; ?>

    <form method="post" autocomplete="on">
      <div class="mb-3 text-start">
        <label for="email" class="form-label small">E-mail</label>
        <input type="email" name="email" id="email" class="form-control input-dark" required autofocus
               value="<?= e($email ?? '') ?>" />
      </div>
      <div class="mb-4 text-start">
        <label for="senha" class="form-label small">Senha</label>
        <input type="password" name="senha" id="senha" class="form-control input-dark" required />
      </div>
      <button type="submit" class="btn btn-gold w-100">
        <i class="bi bi-box-arrow-in-right me-2"></i>Entrar
      </button>
    </form>

    <a href="../index.html" class="d-block mt-4 small text-muted-ice">&larr; Voltar ao site</a>
  </div>

</body>
</html>
