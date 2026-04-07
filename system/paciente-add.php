<?php
/**
 * Adicionar Novo Paciente
 */
require_once __DIR__ . '/auth.php';
requireLogin();

$db   = getDB();
$user = currentUser();
$erro = '';
$ok   = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $nome  = trim($_POST['nome'] ?? '');
    $wpp   = trim($_POST['whatsapp'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $nasc  = $_POST['data_nascimento'] ?? '';
    $ocup  = trim($_POST['ocupacao'] ?? '');
    $bloco = max(1, min(3, (int)($_POST['bloco_atual'] ?? 1)));

    if (!$nome || !$wpp) {
        $erro = 'Nome e WhatsApp são obrigatórios.';
    } else {
        $st = $db->prepare('INSERT INTO pacientes (nome, whatsapp, email, data_nascimento, ocupacao, bloco_atual) VALUES (?,?,?,?,?,?)');
        $st->execute([$nome, $wpp, $email ?: null, $nasc ?: null, $ocup ?: null, $bloco]);
        $newId = $db->lastInsertId();
        header("Location: paciente.php?id={$newId}");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Novo Paciente — Seres das Estrelas OS</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="style-system.css" />
</head>
<body>
  <?php include __DIR__ . '/partials/sidebar.php'; ?>

  <main class="main-content">
    <div class="topbar mb-4">
      <button class="btn btn-sm btn-outline-light d-lg-none me-2" id="sidebarToggle"><i class="bi bi-list"></i></button>
      <a href="pacientes.php" class="text-muted-ice me-2"><i class="bi bi-arrow-left"></i></a>
      <h4 class="d-inline fw-bold mb-0">Novo Paciente</h4>
    </div>

    <div class="row justify-content-center">
      <div class="col-lg-7">
        <div class="glass-card p-4">
          <?php if ($erro): ?>
            <div class="alert alert-danger py-2"><?= e($erro) ?></div>
          <?php endif; ?>

          <form method="post">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>" />
            <div class="row g-3">
              <div class="col-12">
                <label class="form-label small">Nome completo *</label>
                <input type="text" name="nome" class="form-control input-dark" required value="<?= e($_POST['nome'] ?? '') ?>" />
              </div>
              <div class="col-md-6">
                <label class="form-label small">WhatsApp *</label>
                <input type="text" name="whatsapp" class="form-control input-dark" placeholder="41991285254" required value="<?= e($_POST['whatsapp'] ?? '') ?>" />
              </div>
              <div class="col-md-6">
                <label class="form-label small">E-mail</label>
                <input type="email" name="email" class="form-control input-dark" value="<?= e($_POST['email'] ?? '') ?>" />
              </div>
              <div class="col-md-6">
                <label class="form-label small">Data de Nascimento</label>
                <input type="date" name="data_nascimento" class="form-control input-dark" value="<?= e($_POST['data_nascimento'] ?? '') ?>" />
              </div>
              <div class="col-md-6">
                <label class="form-label small">Ocupação</label>
                <input type="text" name="ocupacao" class="form-control input-dark" value="<?= e($_POST['ocupacao'] ?? '') ?>" />
              </div>
              <div class="col-12">
                <label class="form-label small">Bloco Inicial</label>
                <select name="bloco_atual" class="form-select input-dark">
                  <option value="1">Bloco 1 — Limpeza e Desintoxicação</option>
                  <option value="2">Bloco 2 — Reequilíbrio e Cura</option>
                  <option value="3">Bloco 3 — Expansão e Propósito</option>
                </select>
              </div>
              <div class="col-12">
                <button type="submit" class="btn btn-gold"><i class="bi bi-person-plus me-1"></i>Cadastrar Paciente</button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="app.js"></script>
</body>
</html>
