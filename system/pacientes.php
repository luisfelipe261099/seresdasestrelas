<?php
/**
 * Listagem de Pacientes
 */
require_once __DIR__ . '/auth.php';
requireLogin();

$db   = getDB();
$user = currentUser();

$busca = trim($_GET['q'] ?? '');
$filtroBloco = (int)($_GET['bloco'] ?? 0);

$sql = "SELECT p.*, (SELECT MAX(s.data_sessao) FROM sessoes_notas s WHERE s.paciente_id = p.id) AS ultima_sessao
        FROM pacientes p WHERE p.status_ativo = 1";
$params = [];

if ($busca) {
    $sql .= " AND (p.nome LIKE ? OR p.whatsapp LIKE ?)";
    $params[] = "%{$busca}%";
    $params[] = "%{$busca}%";
}
if ($filtroBloco >= 1 && $filtroBloco <= 3) {
    $sql .= " AND p.bloco_atual = ?";
    $params[] = $filtroBloco;
}
$sql .= " ORDER BY p.nome ASC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$pacientes = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Pacientes — Seres das Estrelas OS</title>
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
        <h4 class="d-inline fw-bold mb-0">Pacientes</h4>
        <span class="text-muted-ice ms-2 small">(<?= count($pacientes) ?>)</span>
      </div>
      <a href="paciente-add.php" class="btn btn-gold btn-sm"><i class="bi bi-person-plus me-1"></i>Novo Paciente</a>
    </div>

    <!-- Filtros -->
    <form class="row g-2 mb-4" method="get">
      <div class="col-8 col-md-5">
        <input type="text" name="q" class="form-control input-dark" placeholder="Buscar por nome ou WhatsApp..." value="<?= e($busca) ?>" />
      </div>
      <div class="col-4 col-md-3">
        <select name="bloco" class="form-select input-dark">
          <option value="0">Todos Blocos</option>
          <option value="1" <?= $filtroBloco===1?'selected':'' ?>>Bloco 1</option>
          <option value="2" <?= $filtroBloco===2?'selected':'' ?>>Bloco 2</option>
          <option value="3" <?= $filtroBloco===3?'selected':'' ?>>Bloco 3</option>
        </select>
      </div>
      <div class="col-auto">
        <button class="btn btn-outline-gold btn-sm h-100"><i class="bi bi-search"></i></button>
      </div>
    </form>

    <!-- Tabela -->
    <div class="glass-card p-3">
      <?php if (empty($pacientes)): ?>
        <p class="text-muted-ice text-center py-4 mb-0">Nenhum paciente encontrado.</p>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-dark-custom mb-0 align-middle">
            <thead>
              <tr>
                <th>Nome</th>
                <th class="d-none d-md-table-cell">WhatsApp</th>
                <th>Bloco</th>
                <th class="d-none d-md-table-cell">Última Sessão</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($pacientes as $p): ?>
                <tr>
                  <td>
                    <a href="paciente.php?id=<?= (int)$p['id'] ?>" class="text-ice fw-bold"><?= e($p['nome']) ?></a>
                  </td>
                  <td class="d-none d-md-table-cell">
                    <a href="<?= whatsapp_link($p['whatsapp']) ?>" target="_blank" class="text-success small">
                      <i class="bi bi-whatsapp me-1"></i><?= e($p['whatsapp']) ?>
                    </a>
                  </td>
                  <td><?= bloco_badge($p['bloco_atual']) ?></td>
                  <td class="d-none d-md-table-cell small text-muted-ice">
                    <?= $p['ultima_sessao'] ? date('d/m/Y', strtotime($p['ultima_sessao'])) : '—' ?>
                  </td>
                  <td>
                    <a href="paciente.php?id=<?= (int)$p['id'] ?>" class="btn btn-sm btn-outline-gold"><i class="bi bi-eye"></i></a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="app.js"></script>
</body>
</html>
