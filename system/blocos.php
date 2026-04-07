<?php
/**
 * Seres das Estrelas OS — Gerenciar Blocos (Status Estelar)
 */
require_once __DIR__ . '/auth.php';
requireLogin();

$db   = getDB();
$user = currentUser();

// ── AÇÕES POST ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    csrf_check();

    if ($_POST['action'] === 'criar') {
        $numero    = (int)$_POST['numero'];
        $nome      = trim($_POST['nome'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $cor       = trim($_POST['cor'] ?? '#E0A458');
        if ($nome && $numero > 0) {
            $ordem = $db->query("SELECT COALESCE(MAX(ordem),0)+1 FROM blocos")->fetchColumn();
            $st = $db->prepare("INSERT INTO blocos (numero, nome, descricao, cor, ordem) VALUES (?,?,?,?,?)");
            $st->execute([$numero, $nome, $descricao ?: null, $cor, $ordem]);
        }
        invalidar_cache_blocos();
        header("Location: blocos.php?msg=criado");
        exit;
    }

    if ($_POST['action'] === 'editar') {
        $bid       = (int)$_POST['bloco_id'];
        $numero    = (int)$_POST['numero'];
        $nome      = trim($_POST['nome'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $cor       = trim($_POST['cor'] ?? '#E0A458');
        if ($nome && $numero > 0) {
            $st = $db->prepare("UPDATE blocos SET numero = ?, nome = ?, descricao = ?, cor = ? WHERE id = ?");
            $st->execute([$numero, $nome, $descricao ?: null, $cor, $bid]);
        }
        invalidar_cache_blocos();
        header("Location: blocos.php?msg=atualizado");
        exit;
    }

    if ($_POST['action'] === 'toggle') {
        $bid = (int)$_POST['bloco_id'];
        $db->prepare("UPDATE blocos SET ativo = NOT ativo WHERE id = ?")->execute([$bid]);
        invalidar_cache_blocos();
        header("Location: blocos.php?msg=toggle");
        exit;
    }

    if ($_POST['action'] === 'reordenar') {
        $ids = array_map('intval', $_POST['ordem'] ?? []);
        foreach ($ids as $i => $bid) {
            $db->prepare("UPDATE blocos SET ordem = ? WHERE id = ?")->execute([$i + 1, $bid]);
        }
        invalidar_cache_blocos();
        header("Location: blocos.php?msg=reordenado");
        exit;
    }
}

$blocos = $db->query("SELECT b.*, (SELECT COUNT(*) FROM pacientes p WHERE p.bloco_atual = b.numero AND p.status_ativo = 1) AS total_pacientes FROM blocos b ORDER BY b.ordem ASC, b.numero ASC")->fetchAll();
$msg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Blocos — Seres das Estrelas OS</title>
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
    <div class="topbar d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
      <div>
        <button class="btn btn-sm btn-outline-light d-lg-none me-2" id="sidebarToggle"><i class="bi bi-list"></i></button>
        <h4 class="d-inline fw-bold mb-0"><i class="bi bi-stars me-2 text-gold"></i>Blocos — Status Estelar</h4>
      </div>
    </div>

    <?php if ($msg): ?>
      <div class="alert py-2 small" style="background:rgba(25,135,84,0.15);border:1px solid rgba(25,135,84,0.3);color:#a3cfbb;border-radius:var(--radius);">
        <i class="bi bi-check-circle me-1"></i>
        <?= match($msg) { 'criado' => 'Bloco criado!', 'atualizado' => 'Bloco atualizado!', 'toggle' => 'Status alterado!', 'reordenado' => 'Ordem salva!', default => 'Feito!' } ?>
      </div>
    <?php endif; ?>

    <!-- Novo Bloco -->
    <div class="glass-card p-4 mb-4">
      <h6 class="fw-bold mb-3"><i class="bi bi-plus-circle me-2 text-gold"></i>Criar Novo Bloco</h6>
      <form method="post">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>" />
        <input type="hidden" name="action" value="criar" />
        <div class="row g-3 align-items-end">
          <div class="col-4 col-md-1">
            <label class="form-label small">Nº</label>
            <input type="number" name="numero" class="form-control form-control-sm input-dark" min="1" required placeholder="4" />
          </div>
          <div class="col-8 col-md-3">
            <label class="form-label small">Nome</label>
            <input type="text" name="nome" class="form-control form-control-sm input-dark" required placeholder="Ex: Transcendência" />
          </div>
          <div class="col-8 col-md-3">
            <label class="form-label small">Descrição</label>
            <input type="text" name="descricao" class="form-control form-control-sm input-dark" placeholder="Breve descrição" />
          </div>
          <div class="col-4 col-md-2">
            <label class="form-label small">Cor</label>
            <input type="color" name="cor" class="form-control form-control-sm input-dark p-1" value="#E0A458" style="height:38px;" />
          </div>
          <div class="col-12 col-md-2">
            <button type="submit" class="btn btn-gold btn-sm w-100"><i class="bi bi-plus-lg me-1"></i>Criar</button>
          </div>
        </div>
      </form>
    </div>

    <!-- Lista de Blocos -->
    <div class="glass-card p-4">
      <h6 class="fw-bold mb-3"><i class="bi bi-list-ol me-2 text-gold"></i>Blocos Cadastrados</h6>
      
      <?php if (empty($blocos)): ?>
        <p class="text-muted-ice text-center py-3 mb-0">Nenhum bloco cadastrado.</p>
      <?php else: ?>
        <?php foreach ($blocos as $b): ?>
          <div class="glass-card p-3 mb-3 <?= !$b['ativo'] ? 'opacity-50' : '' ?>" style="border-left:4px solid <?= e($b['cor']) ?>;">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
              <div class="flex-grow-1">
                <div class="d-flex align-items-center gap-2 mb-1">
                  <span class="badge-bloco" style="background:<?= e($b['cor']) ?>20;color:<?= e($b['cor']) ?>;border:1px solid <?= e($b['cor']) ?>40;">Bloco <?= (int)$b['numero'] ?></span>
                  <strong class="small"><?= e($b['nome']) ?></strong>
                  <?php if (!$b['ativo']): ?>
                    <span class="badge" style="background:rgba(108,117,125,0.2);color:#6c757d;font-size:0.65rem;">Inativo</span>
                  <?php endif; ?>
                </div>
                <?php if ($b['descricao']): ?>
                  <p class="text-muted-ice small mb-1"><?= e($b['descricao']) ?></p>
                <?php endif; ?>
                <span class="text-muted-ice" style="font-size:0.72rem;"><?= (int)$b['total_pacientes'] ?> paciente(s) neste bloco</span>
              </div>

              <div class="d-flex gap-1">
                <!-- Editar (modal) -->
                <button class="btn btn-sm btn-outline-gold" data-bs-toggle="modal" data-bs-target="#modalEdit<?= $b['id'] ?>" title="Editar"><i class="bi bi-pencil"></i></button>
                <!-- Toggle ativo -->
                <form method="post" class="d-inline">
                  <input type="hidden" name="csrf" value="<?= csrf_token() ?>" />
                  <input type="hidden" name="action" value="toggle" />
                  <input type="hidden" name="bloco_id" value="<?= $b['id'] ?>" />
                  <button class="btn btn-sm <?= $b['ativo'] ? 'btn-outline-warning' : 'btn-outline-success' ?>" title="<?= $b['ativo'] ? 'Desativar' : 'Ativar' ?>">
                    <i class="bi bi-<?= $b['ativo'] ? 'pause' : 'play' ?>"></i>
                  </button>
                </form>
              </div>
            </div>
          </div>

          <!-- Modal Editar -->
          <div class="modal fade" id="modalEdit<?= $b['id'] ?>" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
              <div class="modal-content" style="background:var(--navy-light);border:1px solid var(--glass-border);border-radius:var(--radius);">
                <form method="post">
                  <div class="modal-header border-0 pb-0">
                    <h6 class="modal-title fw-bold"><i class="bi bi-pencil me-2 text-gold"></i>Editar Bloco <?= (int)$b['numero'] ?></h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                  </div>
                  <div class="modal-body">
                    <input type="hidden" name="csrf" value="<?= csrf_token() ?>" />
                    <input type="hidden" name="action" value="editar" />
                    <input type="hidden" name="bloco_id" value="<?= $b['id'] ?>" />
                    <div class="mb-3">
                      <label class="form-label small">Número</label>
                      <input type="number" name="numero" class="form-control form-control-sm input-dark" value="<?= (int)$b['numero'] ?>" min="1" required />
                    </div>
                    <div class="mb-3">
                      <label class="form-label small">Nome</label>
                      <input type="text" name="nome" class="form-control form-control-sm input-dark" value="<?= e($b['nome']) ?>" required />
                    </div>
                    <div class="mb-3">
                      <label class="form-label small">Descrição</label>
                      <input type="text" name="descricao" class="form-control form-control-sm input-dark" value="<?= e($b['descricao'] ?? '') ?>" />
                    </div>
                    <div class="mb-3">
                      <label class="form-label small">Cor</label>
                      <input type="color" name="cor" class="form-control form-control-sm input-dark p-1" value="<?= e($b['cor']) ?>" style="height:38px;" />
                    </div>
                  </div>
                  <div class="modal-footer border-0 pt-0">
                    <button type="submit" class="btn btn-gold btn-sm w-100"><i class="bi bi-check-circle me-1"></i>Salvar</button>
                  </div>
                </form>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="app.js"></script>
</body>
</html>
