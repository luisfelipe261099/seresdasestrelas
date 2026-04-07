<?php
/**
 * Visualizar Anamneses (área admin)
 */
require_once __DIR__ . '/auth.php';
requireLogin();

$db   = getDB();
$user = currentUser();

// Vincular anamnese a paciente
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'vincular') {
    csrf_check();
    $anamId = (int)($_POST['anamnese_id'] ?? 0);
    $pacId  = (int)($_POST['paciente_id'] ?? 0);
    if ($anamId && $pacId) {
        $stVinc = $db->prepare('UPDATE anamneses SET paciente_id = ? WHERE id = ?');
        $stVinc->execute([$pacId, $anamId]);
    }
    header("Location: anamnese-ver.php?id={$anamId}");
    exit;
}

$id = (int)($_GET['id'] ?? 0);

if ($id) {
    $stmt = $db->prepare('SELECT * FROM anamneses WHERE id = ?');
    $stmt->execute([$id]);
    $anam = $stmt->fetch();
}

// Lista de pacientes para vincular
$pacientes = $db->query('SELECT id, nome FROM pacientes ORDER BY nome ASC')->fetchAll();

$lista = $db->query('SELECT a.*, p.nome AS pac_nome FROM anamneses a LEFT JOIN pacientes p ON p.id = a.paciente_id ORDER BY a.criado_em DESC LIMIT 50')->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Fichas de Acolhimento — Seres das Estrelas OS</title>
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
    <div class="topbar mb-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
      <div>
        <button class="btn btn-sm btn-outline-light d-lg-none me-2" id="sidebarToggle"><i class="bi bi-list"></i></button>
        <h4 class="d-inline fw-bold mb-0">Fichas de Acolhimento</h4>
      </div>
      <button class="btn btn-sm btn-outline-gold" id="btnCopyLink" onclick="copyAnamneseLink()">
        <i class="bi bi-link-45deg me-1"></i>Copiar Link de Preenchimento
      </button>
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
              <p class="text-warning small mb-2"><i class="bi bi-exclamation-triangle me-1"></i>Não vinculada a nenhum paciente.</p>
              <form method="post" class="d-flex gap-2 align-items-end">
                <input type="hidden" name="csrf" value="<?= csrf_token() ?>" />
                <input type="hidden" name="action" value="vincular" />
                <input type="hidden" name="anamnese_id" value="<?= (int)$anam['id'] ?>" />
                <div class="flex-grow-1">
                  <label class="form-label small mb-1">Vincular a paciente</label>
                  <select name="paciente_id" class="form-select form-select-sm input-dark" required>
                    <option value="">Selecione...</option>
                    <?php foreach ($pacientes as $p): ?>
                      <option value="<?= (int)$p['id'] ?>"><?= e($p['nome']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <button type="submit" class="btn btn-sm btn-gold"><i class="bi bi-link-45deg me-1"></i>Vincular</button>
              </form>
            <?php else: ?>
              <hr style="border-color:rgba(248,249,250,0.1);" />
              <?php
                $stPacNome = $db->prepare('SELECT nome FROM pacientes WHERE id = ?');
                $stPacNome->execute([$anam['paciente_id']]);
                $pacNome = $stPacNome->fetchColumn() ?: 'paciente';
              ?>
              <p class="text-success small mb-0"><i class="bi bi-check-circle me-1"></i>Vinculada a <a href="paciente.php?id=<?= (int)$anam['paciente_id'] ?>" class="text-gold"><?= e($pacNome) ?></a></p>
            <?php endif; ?>
          </div>
        </div>
      </div>
    <?php else: ?>
      <!-- List view -->
      <div class="glass-card p-3">
        <?php if (empty($lista)): ?>
          <p class="text-muted-ice text-center py-4 mb-0">Nenhuma ficha de acolhimento recebida.</p>
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
  <script>
    function copyAnamneseLink() {
      const url = window.location.origin + '/se/system/anamnese.php';
      navigator.clipboard.writeText(url).then(() => {
        const btn = document.getElementById('btnCopyLink');
        const original = btn.innerHTML;
        btn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Link Copiado!';
        btn.classList.add('btn-gold');
        btn.classList.remove('btn-outline-gold');
        setTimeout(() => {
          btn.innerHTML = original;
          btn.classList.remove('btn-gold');
          btn.classList.add('btn-outline-gold');
        }, 2000);
      });
    }
  </script>
</body>
</html>
