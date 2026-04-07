<?php
/**
 * Seres das Estrelas OS — Gestão de Mensalidades
 */
require_once __DIR__ . '/auth.php';
requireLogin();

$db   = getDB();
$user = currentUser();
$mesAtual = $_GET['mes'] ?? date('Y-m');

// ── AÇÕES POST ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    csrf_check();

    // Gerar mensalidades do mês para todos pacientes ativos
    if ($_POST['action'] === 'gerar_mes') {
        $valor = (float)str_replace(',', '.', str_replace('.', '', $_POST['valor_padrao'] ?? '0'));
        $vencimento = $_POST['dia_vencimento'] ?? 10;
        $mesGerar = $_POST['mes_gerar'] ?? $mesAtual;
        $diaVenc  = str_pad((int)$vencimento, 2, '0', STR_PAD_LEFT);
        $dataVenc = $mesGerar . '-' . $diaVenc;

        $pacientes = $db->query("SELECT id FROM pacientes WHERE status_ativo = 1")->fetchAll();
        $inseridos = 0;
        $stIns = $db->prepare("INSERT IGNORE INTO mensalidades (paciente_id, mes_referencia, valor, data_vencimento, status) VALUES (?, ?, ?, ?, 'pendente')");
        foreach ($pacientes as $p) {
            $stIns->execute([$p['id'], $mesGerar, $valor, $dataVenc]);
            if ($stIns->rowCount()) $inseridos++;
        }
        header("Location: mensalidades.php?mes={$mesGerar}&msg=gerado&n={$inseridos}");
        exit;
    }

    // Marcar como pago
    if ($_POST['action'] === 'pagar') {
        $mid = (int)$_POST['mensalidade_id'];
        $forma = trim($_POST['forma_pagamento'] ?? 'pix');
        $dataPag = $_POST['data_pagamento'] ?? date('Y-m-d');

        $stUp = $db->prepare("UPDATE mensalidades SET status = 'pago', forma_pagamento = ?, data_pagamento = ? WHERE id = ?");
        $stUp->execute([$forma, $dataPag, $mid]);

        // Criar lançamento de receita automático
        $stM = $db->prepare("SELECT m.*, p.nome FROM mensalidades m JOIN pacientes p ON p.id = m.paciente_id WHERE m.id = ?");
        $stM->execute([$mid]);
        $mens = $stM->fetch();
        if ($mens) {
            $stL = $db->prepare("INSERT INTO lancamentos (tipo, categoria, descricao, valor, data_lancamento, forma_pagamento, paciente_id, mensalidade_id) VALUES ('receita','Mensalidade',?,?,?,?,?,?)");
            $stL->execute([
                'Mensalidade ' . $mens['mes_referencia'] . ' - ' . $mens['nome'],
                $dataPag,
                $forma,
                $mens['paciente_id'],
                $mid,
                $mens['valor']
            ]);
        }
        header("Location: mensalidades.php?mes={$mesAtual}&msg=pago");
        exit;
    }

    // Cancelar mensalidade
    if ($_POST['action'] === 'cancelar') {
        $mid = (int)$_POST['mensalidade_id'];
        $db->prepare("UPDATE mensalidades SET status = 'cancelado' WHERE id = ?")->execute([$mid]);
        header("Location: mensalidades.php?mes={$mesAtual}&msg=cancelado");
        exit;
    }

    // Desfazer pagamento
    if ($_POST['action'] === 'desfazer') {
        $mid = (int)$_POST['mensalidade_id'];
        $db->prepare("UPDATE mensalidades SET status = 'pendente', forma_pagamento = NULL, data_pagamento = NULL WHERE id = ?")->execute([$mid]);
        // Remove lançamento vinculado
        $db->prepare("DELETE FROM lancamentos WHERE mensalidade_id = ?")->execute([$mid]);
        header("Location: mensalidades.php?mes={$mesAtual}&msg=desfeito");
        exit;
    }

    // Editar valor
    if ($_POST['action'] === 'editar_valor') {
        $mid = (int)$_POST['mensalidade_id'];
        $novoValor = (float)str_replace(',', '.', str_replace('.', '', $_POST['valor'] ?? '0'));
        $db->prepare("UPDATE mensalidades SET valor = ? WHERE id = ?")->execute([$novoValor, $mid]);
        header("Location: mensalidades.php?mes={$mesAtual}&msg=atualizado");
        exit;
    }
}

// Atualizar atrasadas automaticamente
$db->prepare("UPDATE mensalidades SET status = 'atrasado' WHERE status = 'pendente' AND data_vencimento < CURDATE() AND mes_referencia = ?")->execute([$mesAtual]);

// Listar mensalidades do mês
$stList = $db->prepare("
    SELECT m.*, p.nome AS paciente_nome, p.whatsapp 
    FROM mensalidades m 
    JOIN pacientes p ON p.id = m.paciente_id 
    WHERE m.mes_referencia = ? 
    ORDER BY 
        CASE m.status WHEN 'atrasado' THEN 0 WHEN 'pendente' THEN 1 WHEN 'pago' THEN 2 ELSE 3 END,
        p.nome ASC
");
$stList->execute([$mesAtual]);
$mensalidades = $stList->fetchAll();

// Totais
$totalGeral = array_sum(array_column($mensalidades, 'valor'));
$totalPago  = array_sum(array_map(fn($m) => $m['status'] === 'pago' ? (float)$m['valor'] : 0, $mensalidades));
$totalAberto = $totalGeral - $totalPago;

// Navegação de meses
$mesAnterior = date('Y-m', strtotime($mesAtual . '-01 -1 month'));
$mesProximo  = date('Y-m', strtotime($mesAtual . '-01 +1 month'));
$mesLabel    = date('m/Y', strtotime($mesAtual . '-01'));

$msg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Mensalidades — Seres das Estrelas OS</title>
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
        <a href="financeiro.php" class="text-muted-ice me-2"><i class="bi bi-arrow-left"></i></a>
        <h4 class="d-inline fw-bold mb-0"><i class="bi bi-receipt me-2 text-gold"></i>Mensalidades</h4>
      </div>
      <div class="d-flex align-items-center gap-2">
        <a href="?mes=<?= $mesAnterior ?>" class="btn btn-sm btn-outline-gold"><i class="bi bi-chevron-left"></i></a>
        <span class="fw-bold" style="font-family:'Montserrat',sans-serif;min-width:80px;text-align:center;"><?= $mesLabel ?></span>
        <a href="?mes=<?= $mesProximo ?>" class="btn btn-sm btn-outline-gold"><i class="bi bi-chevron-right"></i></a>
      </div>
    </div>

    <?php if ($msg): ?>
      <div class="alert alert-success py-2 small" style="background:rgba(25,135,84,0.15);border:1px solid rgba(25,135,84,0.3);color:#a3cfbb;border-radius:var(--radius);">
        <?php
        echo match($msg) {
            'gerado'     => '<i class="bi bi-check-circle me-1"></i>Mensalidades geradas! ' . ((int)($_GET['n'] ?? 0)) . ' criadas.',
            'pago'       => '<i class="bi bi-check-circle me-1"></i>Pagamento registrado!',
            'cancelado'  => '<i class="bi bi-x-circle me-1"></i>Mensalidade cancelada.',
            'desfeito'   => '<i class="bi bi-arrow-counterclockwise me-1"></i>Pagamento desfeito.',
            'atualizado' => '<i class="bi bi-check-circle me-1"></i>Valor atualizado.',
            default      => ''
        };
        ?>
      </div>
    <?php endif; ?>

    <!-- Resumo Cards -->
    <div class="row g-3 mb-4">
      <div class="col-4">
        <div class="glass-card p-3 text-center">
          <span class="d-block fw-bold text-success" style="font-size:1.1rem;">R$ <?= number_format($totalPago, 2, ',', '.') ?></span>
          <span class="stat-card-label">Recebido</span>
        </div>
      </div>
      <div class="col-4">
        <div class="glass-card p-3 text-center">
          <span class="d-block fw-bold text-warning" style="font-size:1.1rem;">R$ <?= number_format($totalAberto, 2, ',', '.') ?></span>
          <span class="stat-card-label">Em Aberto</span>
        </div>
      </div>
      <div class="col-4">
        <div class="glass-card p-3 text-center">
          <span class="d-block fw-bold text-ice" style="font-size:1.1rem;">R$ <?= number_format($totalGeral, 2, ',', '.') ?></span>
          <span class="stat-card-label">Total Previsto</span>
        </div>
      </div>
    </div>

    <!-- Gerar Mensalidades -->
    <div class="glass-card p-4 mb-4">
      <h6 class="fw-bold mb-3"><i class="bi bi-magic me-2 text-gold"></i>Gerar Mensalidades do Mês</h6>
      <form method="post" class="row g-3 align-items-end">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>" />
        <input type="hidden" name="action" value="gerar_mes" />
        <input type="hidden" name="mes_gerar" value="<?= e($mesAtual) ?>" />
        <div class="col-6 col-md-3">
          <label class="form-label small">Valor Padrão (R$)</label>
          <input type="text" name="valor_padrao" class="form-control input-dark" placeholder="250,00" required />
        </div>
        <div class="col-6 col-md-3">
          <label class="form-label small">Dia Vencimento</label>
          <input type="number" name="dia_vencimento" class="form-control input-dark" value="10" min="1" max="28" />
        </div>
        <div class="col-12 col-md-3">
          <button type="submit" class="btn btn-gold btn-sm w-100" onclick="return confirm('Gerar mensalidades para todos pacientes ativos?')">
            <i class="bi bi-plus-circle me-1"></i>Gerar para Todos
          </button>
        </div>
      </form>
      <p class="text-muted-ice mt-2 mb-0" style="font-size:0.75rem;">Gera uma mensalidade para cada paciente ativo que ainda não tem uma neste mês.</p>
    </div>

    <!-- Tabela de Mensalidades -->
    <div class="glass-card p-4">
      <h6 class="fw-bold mb-3"><i class="bi bi-list-check me-2 text-gold"></i>Mensalidades de <?= $mesLabel ?></h6>
      <?php if (empty($mensalidades)): ?>
        <p class="text-muted-ice text-center py-4 mb-0">Nenhuma mensalidade gerada para este mês.</p>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-dark-custom mb-0 align-middle">
            <thead>
              <tr>
                <th>Paciente</th>
                <th>Valor</th>
                <th>Vencimento</th>
                <th>Status</th>
                <th class="d-none d-md-table-cell">Pagamento</th>
                <th>Ações</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($mensalidades as $m): ?>
                <tr>
                  <td>
                    <a href="paciente.php?id=<?= (int)$m['paciente_id'] ?>" class="text-ice fw-bold small"><?= e($m['paciente_nome']) ?></a>
                  </td>
                  <td>
                    <?php if ($m['status'] !== 'pago' && $m['status'] !== 'cancelado'): ?>
                      <form method="post" class="d-inline" style="max-width:100px;">
                        <input type="hidden" name="csrf" value="<?= csrf_token() ?>" />
                        <input type="hidden" name="action" value="editar_valor" />
                        <input type="hidden" name="mensalidade_id" value="<?= $m['id'] ?>" />
                        <input type="text" name="valor" value="<?= number_format($m['valor'], 2, ',', '.') ?>" class="form-control form-control-sm input-dark text-center p-1" style="font-size:0.8rem;max-width:90px;" onchange="this.form.submit()" />
                      </form>
                    <?php else: ?>
                      <span class="small">R$ <?= number_format($m['valor'], 2, ',', '.') ?></span>
                    <?php endif; ?>
                  </td>
                  <td class="small"><?= $m['data_vencimento'] ? date('d/m', strtotime($m['data_vencimento'])) : '—' ?></td>
                  <td>
                    <?php
                    $statusBadge = match($m['status']) {
                        'pago'      => '<span class="badge" style="background:rgba(25,135,84,0.2);color:#198754;">Pago</span>',
                        'pendente'  => '<span class="badge" style="background:rgba(255,193,7,0.2);color:#ffc107;">Pendente</span>',
                        'atrasado'  => '<span class="badge" style="background:rgba(220,53,69,0.2);color:#dc3545;">Atrasado</span>',
                        'cancelado' => '<span class="badge" style="background:rgba(108,117,125,0.2);color:#6c757d;">Cancelado</span>',
                        default     => ''
                    };
                    echo $statusBadge;
                    ?>
                  </td>
                  <td class="d-none d-md-table-cell small text-muted-ice">
                    <?php if ($m['status'] === 'pago'): ?>
                      <?= date('d/m', strtotime($m['data_pagamento'])) ?> — <?= e($m['forma_pagamento'] ?? '') ?>
                    <?php else: ?>
                      —
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if ($m['status'] === 'pendente' || $m['status'] === 'atrasado'): ?>
                      <!-- Botão Pagar → Modal -->
                      <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#modalPagar<?= $m['id'] ?>" title="Registrar pagamento">
                        <i class="bi bi-check-lg"></i>
                      </button>
                      <form method="post" class="d-inline">
                        <input type="hidden" name="csrf" value="<?= csrf_token() ?>" />
                        <input type="hidden" name="action" value="cancelar" />
                        <input type="hidden" name="mensalidade_id" value="<?= $m['id'] ?>" />
                        <button class="btn btn-sm btn-outline-secondary" onclick="return confirm('Cancelar esta mensalidade?')" title="Cancelar"><i class="bi bi-x-lg"></i></button>
                      </form>
                      <!-- Cobrar WhatsApp -->
                      <a href="https://wa.me/<?= preg_replace('/\D/', '', $m['whatsapp'] ?? '') ?>?text=<?= urlencode('Olá ' . $m['paciente_nome'] . '! 🌟 Lembrando da mensalidade de ' . $mesLabel . ' no valor de R$ ' . number_format($m['valor'], 2, ',', '.') . '. Caso já tenha pago, por favor desconsidere. Obrigada! ✨') ?>" target="_blank" class="btn btn-sm btn-outline-success" title="Cobrar via WhatsApp">
                        <i class="bi bi-whatsapp"></i>
                      </a>
                    <?php elseif ($m['status'] === 'pago'): ?>
                      <form method="post" class="d-inline">
                        <input type="hidden" name="csrf" value="<?= csrf_token() ?>" />
                        <input type="hidden" name="action" value="desfazer" />
                        <input type="hidden" name="mensalidade_id" value="<?= $m['id'] ?>" />
                        <button class="btn btn-sm btn-outline-warning" onclick="return confirm('Desfazer este pagamento?')" title="Desfazer"><i class="bi bi-arrow-counterclockwise"></i></button>
                      </form>
                    <?php endif; ?>
                  </td>
                </tr>

                <?php if ($m['status'] === 'pendente' || $m['status'] === 'atrasado'): ?>
                <!-- Modal Pagar -->
                <div class="modal fade" id="modalPagar<?= $m['id'] ?>" tabindex="-1">
                  <div class="modal-dialog modal-sm modal-dialog-centered">
                    <div class="modal-content" style="background:var(--navy-light);border:1px solid var(--glass-border);border-radius:var(--radius);">
                      <form method="post">
                        <div class="modal-header border-0 pb-0">
                          <h6 class="modal-title fw-bold"><i class="bi bi-cash-coin me-2 text-gold"></i>Registrar Pagamento</h6>
                          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                          <input type="hidden" name="csrf" value="<?= csrf_token() ?>" />
                          <input type="hidden" name="action" value="pagar" />
                          <input type="hidden" name="mensalidade_id" value="<?= $m['id'] ?>" />
                          <p class="small text-muted-ice mb-3"><?= e($m['paciente_nome']) ?> — R$ <?= number_format($m['valor'], 2, ',', '.') ?></p>
                          <div class="mb-3">
                            <label class="form-label small">Forma de Pagamento</label>
                            <select name="forma_pagamento" class="form-select form-select-sm input-dark">
                              <option value="pix">PIX</option>
                              <option value="dinheiro">Dinheiro</option>
                              <option value="cartao">Cartão</option>
                              <option value="transferencia">Transferência</option>
                            </select>
                          </div>
                          <div class="mb-3">
                            <label class="form-label small">Data do Pagamento</label>
                            <input type="date" name="data_pagamento" class="form-control form-control-sm input-dark" value="<?= date('Y-m-d') ?>" />
                          </div>
                        </div>
                        <div class="modal-footer border-0 pt-0">
                          <button type="submit" class="btn btn-gold btn-sm w-100"><i class="bi bi-check-circle me-1"></i>Confirmar Pagamento</button>
                        </div>
                      </form>
                    </div>
                  </div>
                </div>
                <?php endif; ?>
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
