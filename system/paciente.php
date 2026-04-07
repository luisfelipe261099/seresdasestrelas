<?php
/**
 * Perfil do Paciente — Detalhes, Bloco, Sessões
 */
require_once __DIR__ . '/auth.php';
requireLogin();

$db   = getDB();
$user = currentUser();
$id   = (int)($_GET['id'] ?? 0);

if (!$id) { header('Location: pacientes.php'); exit; }

$stmt = $db->prepare('SELECT * FROM pacientes WHERE id = ?');
$stmt->execute([$id]);
$pac = $stmt->fetch();
if (!$pac) { header('Location: pacientes.php'); exit; }

// Buscar blocos ativos do banco
$blocosDisp = $db->query("SELECT * FROM blocos WHERE ativo = 1 ORDER BY ordem ASC, numero ASC")->fetchAll();
$blocosMap  = [];
foreach ($blocosDisp as $bl) $blocosMap[$bl['numero']] = $bl;

// Atualizar bloco
$confete = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    csrf_check();

    if ($_POST['action'] === 'update_bloco') {
        $novoBloco = (int)$_POST['bloco_atual'];
        $blocoAnterior = $pac['bloco_atual'];
        $stUp = $db->prepare('UPDATE pacientes SET bloco_atual = ? WHERE id = ?');
        $stUp->execute([$novoBloco, $id]);
        $pac['bloco_atual'] = $novoBloco;
        if ($novoBloco > $blocoAnterior) $confete = true;
    }

    if ($_POST['action'] === 'add_sessao') {
        $dataSessao = $_POST['data_sessao'] ?? date('Y-m-d');
        $nota       = trim($_POST['texto_nota'] ?? '');
        $tipo       = trim($_POST['tipo_tratamento'] ?? 'Psicanálise');
        if ($nota) {
            $stIns = $db->prepare('INSERT INTO sessoes_notas (paciente_id, data_sessao, texto_nota, tipo_tratamento, bloco_referente) VALUES (?,?,?,?,?)');
            $stIns->execute([$id, $dataSessao, $nota, $tipo, $pac['bloco_atual']]);
        }
    }

    header("Location: paciente.php?id={$id}" . ($confete ? '&up=1' : ''));
    exit;
}

$confete = isset($_GET['up']);

// Sessões
$sessoes = $db->prepare('SELECT * FROM sessoes_notas WHERE paciente_id = ? ORDER BY data_sessao DESC, criado_em DESC');
$sessoes->execute([$id]);
$sessoes = $sessoes->fetchAll();

// Anamnese
$anam = $db->prepare('SELECT * FROM anamneses WHERE paciente_id = ? ORDER BY criado_em DESC LIMIT 1');
$anam->execute([$id]);
$anamnese = $anam->fetch();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= e($pac['nome']) ?> — Seres das Estrelas OS</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="style-system.css" />
  <?php if ($confete): ?>
  <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.3/dist/confetti.browser.min.js"></script>
  <?php endif; ?>
</head>
<body>
  <?php include __DIR__ . '/partials/sidebar.php'; ?>

  <main class="main-content">
    <div class="topbar d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
      <div>
        <button class="btn btn-sm btn-outline-light d-lg-none me-2" id="sidebarToggle"><i class="bi bi-list"></i></button>
        <a href="pacientes.php" class="text-muted-ice me-2"><i class="bi bi-arrow-left"></i></a>
        <h4 class="d-inline fw-bold mb-0"><?= e($pac['nome']) ?></h4>
      </div>
      <a href="<?= whatsapp_link($pac['whatsapp']) ?>" target="_blank" class="btn btn-sm btn-success">
        <i class="bi bi-whatsapp me-1"></i><?= e($pac['whatsapp']) ?>
      </a>
    </div>

    <div class="row g-4">
      <!-- Dados + Bloco -->
      <div class="col-lg-4">
        <div class="glass-card p-4 mb-4">
          <h6 class="fw-bold mb-3"><i class="bi bi-person me-2 text-gold"></i>Dados Pessoais</h6>
          <ul class="list-unstyled small mb-0">
            <li class="mb-2"><strong>Nome:</strong> <?= e($pac['nome']) ?></li>
            <li class="mb-2"><strong>WhatsApp:</strong> <?= e($pac['whatsapp']) ?></li>
            <li class="mb-2"><strong>E-mail:</strong> <?= $pac['email'] ? e($pac['email']) : '—' ?></li>
            <li class="mb-2"><strong>Nascimento:</strong> <?= $pac['data_nascimento'] ? date('d/m/Y', strtotime($pac['data_nascimento'])) : '—' ?></li>
            <li><strong>Ocupação:</strong> <?= $pac['ocupacao'] ? e($pac['ocupacao']) : '—' ?></li>
          </ul>
        </div>

        <!-- Status de Bloco -->
        <div class="glass-card p-4 mb-4">
          <h6 class="fw-bold mb-3"><i class="bi bi-stars me-2 text-gold"></i>Status Estelar</h6>
          <form method="post">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>" />
            <input type="hidden" name="action" value="update_bloco" />
            <div class="bloco-selector mb-3">
              <?php foreach ($blocosDisp as $bl): ?>
                <label class="bloco-option <?= $pac['bloco_atual'] == $bl['numero'] ? 'active' : '' ?>">
                  <input type="radio" name="bloco_atual" value="<?= (int)$bl['numero'] ?>" <?= $pac['bloco_atual'] == $bl['numero'] ? 'checked' : '' ?> />
                  <span class="bloco-option-inner" style="border-left:3px solid <?= e($bl['cor']) ?> !important;">
                    <strong>Bloco <?= (int)$bl['numero'] ?></strong>
                    <small><?= e($bl['nome']) ?></small>
                  </span>
                </label>
              <?php endforeach; ?>
            </div>
            <button type="submit" class="btn btn-gold btn-sm w-100"><i class="bi bi-arrow-up-circle me-1"></i>Atualizar Bloco</button>
          </form>
        </div>

        <!-- Info Financeira -->
        <div class="glass-card p-4 mb-4">
          <h6 class="fw-bold mb-3"><i class="bi bi-wallet2 me-2 text-gold"></i>Financeiro</h6>
          <ul class="list-unstyled small mb-0">
            <li class="mb-2"><strong>Tipo:</strong>
              <?= match($pac['tipo_cobranca'] ?? 'mensal') { 'mensal' => 'Mensal', 'avista' => 'À Vista', 'parcelado' => 'Parcelado', default => '—' } ?>
            </li>
            <li class="mb-2"><strong>Valor:</strong> R$ <?= number_format((float)($pac['valor_mensal'] ?? 0), 2, ',', '.') ?></li>
            <li class="mb-2"><strong>Pagamento:</strong> <?= e(ucfirst($pac['forma_pagamento'] ?? 'pix')) ?></li>
            <?php if (($pac['tipo_cobranca'] ?? '') === 'parcelado'): ?>
              <li class="mb-2"><strong>Parcelas:</strong> <?= (int)($pac['parcelas_pagas'] ?? 0) ?>/<?= (int)($pac['parcelas_total'] ?? 1) ?></li>
            <?php endif; ?>
            <li class="mb-2"><strong>Vencimento:</strong> dia <?= (int)($pac['dia_vencimento'] ?? 10) ?></li>
            <?php if ($pac['observacao_financeira'] ?? ''): ?>
              <li><strong>Obs:</strong> <?= e($pac['observacao_financeira']) ?></li>
            <?php endif; ?>
          </ul>
          <a href="mensalidades.php" class="btn btn-outline-gold btn-sm w-100 mt-3"><i class="bi bi-receipt me-1"></i>Ver Mensalidades</a>
        </div>

        <?php if ($anamnese): ?>
        <div class="glass-card p-4">
          <h6 class="fw-bold mb-3"><i class="bi bi-clipboard-pulse me-2 text-gold"></i>Anamnese</h6>
          <p class="text-muted-ice small mb-1">Enviada em <?= date('d/m/Y', strtotime($anamnese['criado_em'])) ?></p>
          <?php
            $resp = json_decode($anamnese['respostas_json'], true);
            if ($resp):
              foreach ($resp as $key => $val): ?>
                <div class="mb-2">
                  <strong class="small"><?= e($key) ?>:</strong>
                  <p class="small text-muted-ice mb-0"><?= e($val) ?></p>
                </div>
              <?php endforeach;
            endif;
          ?>
        </div>
        <?php endif; ?>
      </div>

      <!-- Sessões (Linha do Tempo) -->
      <div class="col-lg-8">
        <!-- Formulário Nova Sessão -->
        <div class="glass-card p-4 mb-4">
          <h6 class="fw-bold mb-3"><i class="bi bi-plus-circle me-2 text-gold"></i>Registrar Sessão</h6>
          <form method="post">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>" />
            <input type="hidden" name="action" value="add_sessao" />
            <div class="row g-3">
              <div class="col-6 col-md-4">
                <label class="form-label small">Data</label>
                <input type="date" name="data_sessao" class="form-control input-dark" value="<?= date('Y-m-d') ?>" required />
              </div>
              <div class="col-6 col-md-4">
                <label class="form-label small">Tipo</label>
                <select name="tipo_tratamento" class="form-select input-dark">
                  <option>Psicanálise</option>
                  <option>Sistêmica</option>
                  <option>Reflexologia</option>
                  <option>Tecnologia Estelar</option>
                </select>
              </div>
              <div class="col-12">
                <label class="form-label small">Notas de Evolução</label>
                <textarea name="texto_nota" class="form-control input-dark" rows="4" placeholder="Registre aqui as observações clínicas e de tratamento..." required></textarea>
              </div>
              <div class="col-12">
                <button type="submit" class="btn btn-gold btn-sm"><i class="bi bi-save me-1"></i>Salvar Sessão</button>
              </div>
            </div>
          </form>
        </div>

        <!-- Timeline de Sessões -->
        <div class="glass-card p-4">
          <h6 class="fw-bold mb-3"><i class="bi bi-clock-history me-2 text-gold"></i>Histórico de Sessões (<?= count($sessoes) ?>)</h6>
          <?php if (empty($sessoes)): ?>
            <p class="text-muted-ice small mb-0">Nenhuma sessão registrada ainda.</p>
          <?php else: ?>
            <div class="session-timeline">
              <?php foreach ($sessoes as $s): ?>
                <div class="session-item">
                  <div class="session-marker"></div>
                  <div class="session-content">
                    <div class="d-flex justify-content-between align-items-start mb-1 flex-wrap gap-1">
                      <span class="small fw-bold"><?= date('d/m/Y', strtotime($s['data_sessao'])) ?></span>
                      <div>
                        <?= bloco_badge($s['bloco_referente']) ?>
                        <span class="badge bg-secondary ms-1" style="font-size:0.7rem;"><?= e($s['tipo_tratamento']) ?></span>
                      </div>
                    </div>
                    <p class="small text-muted-ice mb-0"><?= nl2br(e($s['texto_nota'])) ?></p>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="app.js"></script>
  <?php if ($confete): ?>
  <script>
    confetti({ particleCount: 150, spread: 80, origin: { y: 0.6 }, colors: ['#E0A458','#5E548E','#F8F9FA'] });
    setTimeout(() => confetti({ particleCount: 80, spread: 60, origin: { y: 0.5 } }), 300);
  </script>
  <?php endif; ?>
</body>
</html>
