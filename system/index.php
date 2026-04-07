<?php
/**
 * Seres das Estrelas OS — Dashboard Principal
 */
require_once __DIR__ . '/auth.php';
requireLogin();

$db   = getDB();
$user = currentUser();

// Cards resumo
$totalAtivos   = $db->query("SELECT COUNT(*) FROM pacientes WHERE status_ativo = 1")->fetchColumn();
$bloco1        = $db->query("SELECT COUNT(*) FROM pacientes WHERE bloco_atual = 1 AND status_ativo = 1")->fetchColumn();
$bloco2        = $db->query("SELECT COUNT(*) FROM pacientes WHERE bloco_atual = 2 AND status_ativo = 1")->fetchColumn();
$bloco3        = $db->query("SELECT COUNT(*) FROM pacientes WHERE bloco_atual = 3 AND status_ativo = 1")->fetchColumn();

// Sessões da semana
$sessoesSemanais = $db->query("SELECT COUNT(*) FROM sessoes_notas WHERE data_sessao >= DATE_SUB(CURDATE(), INTERVAL DAYOFWEEK(CURDATE())-1 DAY) AND data_sessao <= DATE_ADD(CURDATE(), INTERVAL 7-DAYOFWEEK(CURDATE()) DAY)")->fetchColumn();

// Agenda do dia
$agendaHoje = $db->query("
    SELECT s.id, s.data_sessao, s.tipo_tratamento, s.bloco_referente, p.nome, p.whatsapp
    FROM sessoes_notas s
    JOIN pacientes p ON p.id = s.paciente_id
    WHERE s.data_sessao = CURDATE()
    ORDER BY s.criado_em ASC
    LIMIT 10
")->fetchAll();

// Anamneses recentes não vinculadas
$anamnesesPendentes = $db->query("
    SELECT id, nome, criado_em FROM anamneses WHERE paciente_id IS NULL ORDER BY criado_em DESC LIMIT 5
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dashboard — Seres das Estrelas OS</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="style-system.css" />
</head>
<body>

  <!-- Sidebar -->
  <?php include __DIR__ . '/partials/sidebar.php'; ?>

  <!-- Main -->
  <main class="main-content">
    <div class="topbar d-flex justify-content-between align-items-center mb-4">
      <div>
        <button class="btn btn-sm btn-outline-light d-lg-none me-2" id="sidebarToggle"><i class="bi bi-list"></i></button>
        <h4 class="d-inline fw-bold mb-0">Dashboard</h4>
      </div>
      <span class="text-muted-ice small">Olá, <strong><?= e($user['nome']) ?></strong></span>
    </div>

    <!-- Cards Resumo -->
    <div class="row g-3 mb-4">
      <div class="col-6 col-lg-3">
        <div class="glass-card stat-card p-3">
          <div class="stat-card-icon"><i class="bi bi-people"></i></div>
          <span class="stat-card-number"><?= $totalAtivos ?></span>
          <span class="stat-card-label">Pacientes Ativos</span>
        </div>
      </div>
      <div class="col-6 col-lg-3">
        <div class="glass-card stat-card p-3">
          <div class="stat-card-icon"><i class="bi bi-calendar-check"></i></div>
          <span class="stat-card-number"><?= $sessoesSemanais ?></span>
          <span class="stat-card-label">Sessões na Semana</span>
        </div>
      </div>
      <div class="col-6 col-lg-3">
        <div class="glass-card stat-card p-3 stat-card--blocos">
          <div class="d-flex justify-content-between small mb-1">
            <span class="badge-bloco badge-bloco1">B1: <?= $bloco1 ?></span>
            <span class="badge-bloco badge-bloco2">B2: <?= $bloco2 ?></span>
            <span class="badge-bloco badge-bloco3">B3: <?= $bloco3 ?></span>
          </div>
          <span class="stat-card-label mt-1">Distribuição de Blocos</span>
        </div>
      </div>
      <div class="col-6 col-lg-3">
        <div class="glass-card stat-card p-3">
          <div class="stat-card-icon text-warning"><i class="bi bi-clipboard-pulse"></i></div>
          <span class="stat-card-number"><?= count($anamnesesPendentes) ?></span>
          <span class="stat-card-label">Anamneses Pendentes</span>
        </div>
      </div>
    </div>

    <!-- Atalhos Rápidos -->
    <div class="d-flex gap-2 mb-4 flex-wrap">
      <a href="paciente-add.php" class="btn btn-gold btn-sm"><i class="bi bi-person-plus me-1"></i>Novo Paciente</a>
      <a href="pacientes.php" class="btn btn-outline-gold btn-sm"><i class="bi bi-list-ul me-1"></i>Ver Pacientes</a>
    </div>

    <div class="row g-4">
      <!-- Agenda do Dia -->
      <div class="col-lg-7">
        <div class="glass-card p-4">
          <h6 class="fw-bold mb-3"><i class="bi bi-calendar-event me-2 text-gold"></i>Agenda de Hoje</h6>
          <?php if (empty($agendaHoje)): ?>
            <p class="text-muted-ice small mb-0">Nenhuma sessão agendada para hoje.</p>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table table-dark-custom mb-0">
                <thead>
                  <tr><th>Paciente</th><th>Tipo</th><th>Bloco</th><th></th></tr>
                </thead>
                <tbody>
                  <?php foreach ($agendaHoje as $s): ?>
                    <tr>
                      <td><?= e($s['nome']) ?></td>
                      <td class="small"><?= e($s['tipo_tratamento']) ?></td>
                      <td><?= bloco_badge($s['bloco_referente']) ?></td>
                      <td><a href="<?= whatsapp_link($s['whatsapp']) ?>" target="_blank" class="text-success"><i class="bi bi-whatsapp"></i></a></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Anamneses Pendentes -->
      <div class="col-lg-5">
        <div class="glass-card p-4">
          <h6 class="fw-bold mb-3"><i class="bi bi-clipboard-pulse me-2 text-gold"></i>Anamneses Recentes</h6>
          <?php if (empty($anamnesesPendentes)): ?>
            <p class="text-muted-ice small mb-0">Nenhuma anamnese pendente.</p>
          <?php else: ?>
            <?php foreach ($anamnesesPendentes as $a): ?>
              <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom border-dark">
                <div>
                  <strong class="small"><?= e($a['nome']) ?></strong>
                  <span class="text-muted-ice d-block" style="font-size:0.75rem;"><?= date('d/m/Y H:i', strtotime($a['criado_em'])) ?></span>
                </div>
                <a href="anamnese-ver.php?id=<?= (int)$a['id'] ?>" class="btn btn-sm btn-outline-gold">Ver</a>
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
