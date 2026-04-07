<?php
/**
 * Visualizar Anamneses (área admin)
 */
require_once __DIR__ . '/auth.php';
requireLogin();

$db   = getDB();
$user = currentUser();

$id = (int)($_GET['id'] ?? 0);

if ($id) {
    $stmt = $db->prepare('SELECT * FROM anamneses WHERE id = ?');
    $stmt->execute([$id]);
    $anam = $stmt->fetch();
}

$lista = $db->query('SELECT a.*, p.nome AS pac_nome FROM anamneses a LEFT JOIN pacientes p ON p.id = a.paciente_id ORDER BY a.criado_em DESC LIMIT 50')->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Anamneses — Seres das Estrelas OS</title>
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
      <h4 class="d-inline fw-bold mb-0">Anamneses</h4>
    </div>

    <?php if ($id && !empty($anam)): ?>
      <!-- Detail view -->
      <div class="row justify-content-center">
        <div class="col-lg-8">
          <a href="anamnese-ver.php" class="text-muted-ice small mb-3 d-inline-block"><i class="bi bi-arrow-left me-1"></i>Voltar à lista</a>
          <div class="glass-card p-4">
            <div class="d-flex justify-content-between align-items-start mb-3">
              <div>
                <h5 class="fw-bold mb-1"><?= e($anam['nome']) ?></h5>
                <span class="text-muted-ice small"><i class="bi bi-whatsapp me-1"></i><?= e($anam['whatsapp']) ?></span>
              </div>
              <span class="text-muted-ice small"><?= date('d/m/Y H:i', strtotime($anam['criado_em'])) ?></span>
            </div>
            <hr style="border-color:rgba(248,249,250,0.1);" />
            <?php
              $resp = json_decode($anam['respostas_json'], true);
              if ($resp):
                foreach ($resp as $key => $val): ?>
                  <div class="mb-3">
                    <label class="small fw-bold text-gold"><?= e($key) ?></label>
                    <p class="small text-muted-ice mb-0"><?= nl2br(e($val)) ?></p>
                  </div>
                <?php endforeach;
              endif;
            ?>
            <?php if (!$anam['paciente_id']): ?>
              <hr style="border-color:rgba(248,249,250,0.1);" />
              <p class="text-warning small mb-0"><i class="bi bi-exclamation-triangle me-1"></i>Não vinculada a nenhum paciente.</p>
            <?php endif; ?>
          </div>
        </div>
      </div>
    <?php else: ?>
      <!-- List view -->
      <div class="glass-card p-3">
        <?php if (empty($lista)): ?>
          <p class="text-muted-ice text-center py-4 mb-0">Nenhuma anamnese recebida.</p>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-dark-custom mb-0 align-middle">
              <thead>
                <tr>
                  <th>Paciente</th>
                  <th>WhatsApp</th>
                  <th>Data</th>
                  <th>Vinculado</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($lista as $a): ?>
                  <tr>
                    <td class="fw-bold small"><?= e($a['nome']) ?></td>
                    <td class="small"><?= e($a['whatsapp']) ?></td>
                    <td class="small text-muted-ice"><?= date('d/m/Y', strtotime($a['criado_em'])) ?></td>
                    <td>
                      <?php if ($a['paciente_id']): ?>
                        <span class="badge bg-success" style="font-size:0.7rem;">Sim</span>
                      <?php else: ?>
                        <span class="badge bg-warning text-dark" style="font-size:0.7rem;">Pendente</span>
                      <?php endif; ?>
                    </td>
                    <td><a href="anamnese-ver.php?id=<?= (int)$a['id'] ?>" class="btn btn-sm btn-outline-gold"><i class="bi bi-eye"></i></a></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="app.js"></script>
</body>
</html>
