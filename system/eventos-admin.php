<?php
/**
 * Gestão de Eventos — Admin (CRUD)
 */
require_once __DIR__ . '/auth.php';
requireLogin();

$db   = getDB();
$user = currentUser();
$msg  = '';
$msgType = '';

// ---- AÇÕES ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';

    if ($action === 'criar') {
        $titulo     = trim($_POST['titulo'] ?? '');
        $descricao  = trim($_POST['descricao'] ?? '');
        $data       = $_POST['data_evento'] ?? '';
        $horario    = trim($_POST['horario'] ?? '19:30');
        $localTipo  = $_POST['local_tipo'] ?? 'presencial';
        $link       = trim($_POST['link_online'] ?? '');
        $palestr    = trim($_POST['palestrantes'] ?? '');

        if ($titulo && $data) {
            $st = $db->prepare('INSERT INTO eventos (titulo, descricao, data_evento, horario, local_tipo, link_online, palestrantes) VALUES (?,?,?,?,?,?,?)');
            $st->execute([$titulo, $descricao ?: null, $data, $horario, $localTipo, $link ?: null, $palestr ?: null]);
            $msg = 'Evento criado com sucesso!';
            $msgType = 'success';
        } else {
            $msg = 'Título e data são obrigatórios.';
            $msgType = 'danger';
        }
    }

    if ($action === 'editar') {
        $evId       = (int)$_POST['evento_id'];
        $titulo     = trim($_POST['titulo'] ?? '');
        $descricao  = trim($_POST['descricao'] ?? '');
        $data       = $_POST['data_evento'] ?? '';
        $horario    = trim($_POST['horario'] ?? '19:30');
        $localTipo  = $_POST['local_tipo'] ?? 'presencial';
        $link       = trim($_POST['link_online'] ?? '');
        $palestr    = trim($_POST['palestrantes'] ?? '');

        if ($titulo && $data && $evId) {
            $st = $db->prepare('UPDATE eventos SET titulo=?, descricao=?, data_evento=?, horario=?, local_tipo=?, link_online=?, palestrantes=? WHERE id=?');
            $st->execute([$titulo, $descricao ?: null, $data, $horario, $localTipo, $link ?: null, $palestr ?: null, $evId]);
            $msg = 'Evento atualizado!';
            $msgType = 'success';
        }
    }

    if ($action === 'excluir') {
        $evId = (int)$_POST['evento_id'];
        if ($evId) {
            $st = $db->prepare('DELETE FROM eventos WHERE id = ?');
            $st->execute([$evId]);
            $msg = 'Evento excluído.';
            $msgType = 'warning';
        }
    }

    if ($action === 'toggle') {
        $evId = (int)$_POST['evento_id'];
        if ($evId) {
            $db->prepare('UPDATE eventos SET ativo = NOT ativo WHERE id = ?')->execute([$evId]);
            $msg = 'Status alterado.';
            $msgType = 'success';
        }
    }
}

// ---- LISTAGEM ----
$eventos = $db->query('SELECT * FROM eventos ORDER BY data_evento DESC, horario ASC')->fetchAll();

// Verificar se está editando
$editando = null;
if (isset($_GET['editar'])) {
    $edId = (int)$_GET['editar'];
    $stEd = $db->prepare('SELECT * FROM eventos WHERE id = ?');
    $stEd->execute([$edId]);
    $editando = $stEd->fetch();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Eventos — Seres das Estrelas OS</title>
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
        <h4 class="d-inline fw-bold mb-0">Eventos</h4>
        <span class="text-muted-ice ms-2 small">(<?= count($eventos) ?>)</span>
      </div>
      <a href="/eventos.php" target="_blank" class="btn btn-outline-gold btn-sm"><i class="bi bi-eye me-1"></i>Ver Página Pública</a>
    </div>

    <?php if ($msg): ?>
      <div class="alert alert-<?= $msgType === 'success' ? 'success' : ($msgType === 'warning' ? 'warning' : 'danger') ?> py-2 small" style="background:rgba(<?= $msgType==='success'?'40,167,69':($msgType==='warning'?'255,193,7':'220,53,69') ?>,0.15);border:1px solid rgba(<?= $msgType==='success'?'40,167,69':($msgType==='warning'?'255,193,7':'220,53,69') ?>,0.3);color:var(--ice);border-radius:var(--radius);">
        <?= e($msg) ?>
      </div>
    <?php endif; ?>

    <div class="row g-4">
      <!-- Formulário Criar / Editar -->
      <div class="col-lg-5">
        <div class="glass-card p-4">
          <h6 class="fw-bold mb-3">
            <i class="bi bi-<?= $editando ? 'pencil' : 'plus-circle' ?> me-2 text-gold"></i>
            <?= $editando ? 'Editar Evento' : 'Novo Evento' ?>
          </h6>
          <form method="post">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>" />
            <input type="hidden" name="action" value="<?= $editando ? 'editar' : 'criar' ?>" />
            <?php if ($editando): ?>
              <input type="hidden" name="evento_id" value="<?= (int)$editando['id'] ?>" />
            <?php endif; ?>

            <div class="mb-3">
              <label class="form-label small">Título *</label>
              <input type="text" name="titulo" class="form-control input-dark" required
                     value="<?= e($editando['titulo'] ?? '') ?>"
                     placeholder="Ex: Psicoterapia Sistêmica" />
            </div>

            <div class="mb-3">
              <label class="form-label small">Descrição</label>
              <textarea name="descricao" class="form-control input-dark" rows="3"
                        placeholder="Descrição do evento..."><?= e($editando['descricao'] ?? '') ?></textarea>
            </div>

            <div class="row g-3 mb-3">
              <div class="col-6">
                <label class="form-label small">Data *</label>
                <input type="date" name="data_evento" class="form-control input-dark" required
                       value="<?= e($editando['data_evento'] ?? '') ?>" />
              </div>
              <div class="col-6">
                <label class="form-label small">Horário</label>
                <input type="text" name="horario" class="form-control input-dark" placeholder="19:30"
                       value="<?= e($editando['horario'] ?? '19:30') ?>" />
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label small">Tipo</label>
              <select name="local_tipo" class="form-select input-dark">
                <option value="presencial" <?= ($editando['local_tipo'] ?? '') === 'presencial' ? 'selected' : '' ?>>Presencial</option>
                <option value="online" <?= ($editando['local_tipo'] ?? '') === 'online' ? 'selected' : '' ?>>Online</option>
                <option value="hibrido" <?= ($editando['local_tipo'] ?? '') === 'hibrido' ? 'selected' : '' ?>>Híbrido</option>
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label small">Link da Reunião (Meet / Zoom / outro)</label>
              <input type="url" name="link_online" class="form-control input-dark"
                     placeholder="https://meet.google.com/..."
                     value="<?= e($editando['link_online'] ?? '') ?>" />
            </div>

            <div class="mb-3">
              <label class="form-label small">Palestrantes</label>
              <input type="text" name="palestrantes" class="form-control input-dark"
                     placeholder="Joyce Myllena, Barbara Sbravatti"
                     value="<?= e($editando['palestrantes'] ?? '') ?>" />
            </div>

            <div class="d-flex gap-2">
              <button type="submit" class="btn btn-gold btn-sm">
                <i class="bi bi-<?= $editando ? 'save' : 'plus-lg' ?> me-1"></i>
                <?= $editando ? 'Salvar Alterações' : 'Criar Evento' ?>
              </button>
              <?php if ($editando): ?>
                <a href="eventos-admin.php" class="btn btn-outline-gold btn-sm">Cancelar</a>
              <?php endif; ?>
            </div>
          </form>
        </div>
      </div>

      <!-- Lista de Eventos -->
      <div class="col-lg-7">
        <div class="glass-card p-4">
          <h6 class="fw-bold mb-3"><i class="bi bi-calendar3 me-2 text-gold"></i>Todos os Eventos</h6>

          <?php if (empty($eventos)): ?>
            <p class="text-muted-ice small text-center py-4 mb-0">Nenhum evento cadastrado.</p>
          <?php else: ?>
            <?php foreach ($eventos as $ev): ?>
              <div class="evento-admin-item d-flex gap-3 mb-3 pb-3 <?= !$ev['ativo'] ? 'opacity-50' : '' ?>" style="border-bottom:1px solid rgba(248,249,250,0.06);">
                <!-- Data -->
                <div class="text-center flex-shrink-0" style="min-width:50px;">
                  <span class="d-block fw-bold" style="font-size:1.5rem;color:var(--gold);line-height:1;font-family:'Montserrat',sans-serif;">
                    <?= date('d', strtotime($ev['data_evento'])) ?>
                  </span>
                  <span class="text-muted-ice" style="font-size:0.7rem;text-transform:uppercase;letter-spacing:1px;">
                    <?= strftime('%b', strtotime($ev['data_evento'])) ?: date('M', strtotime($ev['data_evento'])) ?>
                  </span>
                </div>

                <!-- Info -->
                <div class="flex-grow-1">
                  <div class="d-flex justify-content-between align-items-start">
                    <div>
                      <strong class="small"><?= e($ev['titulo']) ?></strong>
                      <?php if (!$ev['ativo']): ?>
                        <span class="badge bg-secondary ms-1" style="font-size:0.6rem;">Inativo</span>
                      <?php endif; ?>
                    </div>
                    <span class="text-muted-ice" style="font-size:0.75rem;"><?= e($ev['horario']) ?>h</span>
                  </div>

                  <?php if ($ev['palestrantes']): ?>
                    <p class="text-muted-ice mb-1" style="font-size:0.78rem;">
                      <i class="bi bi-people me-1"></i><?= e($ev['palestrantes']) ?>
                    </p>
                  <?php endif; ?>

                  <div class="d-flex gap-1 align-items-center flex-wrap">
                    <span class="badge <?= $ev['local_tipo']==='online' ? 'bg-info' : ($ev['local_tipo']==='hibrido' ? 'bg-warning text-dark' : 'bg-success') ?>" style="font-size:0.65rem;">
                      <?= ucfirst(e($ev['local_tipo'])) ?>
                    </span>

                    <?php if ($ev['link_online']): ?>
                      <a href="<?= e($ev['link_online']) ?>" target="_blank" class="badge bg-primary" style="font-size:0.65rem;text-decoration:none;">
                        <i class="bi bi-camera-video me-1"></i>Link
                      </a>
                    <?php endif; ?>

                    <!-- Ações -->
                    <a href="eventos-admin.php?editar=<?= (int)$ev['id'] ?>" class="badge bg-transparent border" style="font-size:0.65rem;color:var(--gold);border-color:var(--gold)!important;text-decoration:none;">
                      <i class="bi bi-pencil"></i> Editar
                    </a>

                    <form method="post" class="d-inline">
                      <input type="hidden" name="csrf" value="<?= csrf_token() ?>" />
                      <input type="hidden" name="action" value="toggle" />
                      <input type="hidden" name="evento_id" value="<?= (int)$ev['id'] ?>" />
                      <button type="submit" class="badge bg-transparent border" style="font-size:0.65rem;color:var(--ice);border-color:rgba(248,249,250,0.2)!important;cursor:pointer;">
                        <i class="bi bi-<?= $ev['ativo'] ? 'eye-slash' : 'eye' ?>"></i>
                        <?= $ev['ativo'] ? 'Desativar' : 'Ativar' ?>
                      </button>
                    </form>

                    <form method="post" class="d-inline" onsubmit="return confirm('Excluir este evento permanentemente?')">
                      <input type="hidden" name="csrf" value="<?= csrf_token() ?>" />
                      <input type="hidden" name="action" value="excluir" />
                      <input type="hidden" name="evento_id" value="<?= (int)$ev['id'] ?>" />
                      <button type="submit" class="badge bg-transparent border border-danger" style="font-size:0.65rem;color:#dc3545;cursor:pointer;">
                        <i class="bi bi-trash"></i> Excluir
                      </button>
                    </form>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="app.js"></script>
</body>
</html>
