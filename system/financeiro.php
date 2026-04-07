<?php
/**
 * Seres das Estrelas OS — Painel Financeiro
 */
require_once __DIR__ . '/auth.php';
requireLogin();

$db   = getDB();
$user = currentUser();

$mesAtual  = $_GET['mes'] ?? date('Y-m');
$mesLabel  = date('m/Y', strtotime($mesAtual . '-01'));

// Query 1: Receitas + despesas + categorias tudo junto
$stFin = $db->prepare("
    SELECT tipo, categoria, 
           SUM(valor) as total,
           COUNT(*) as qtd
    FROM lancamentos 
    WHERE DATE_FORMAT(data_lancamento,'%Y-%m') = ?
    GROUP BY tipo, categoria
");
$stFin->execute([$mesAtual]);
$finRows = $stFin->fetchAll();

$totalReceitas = 0.0;
$totalDespesas = 0.0;
$receitasPorCat = [];
$despesasPorCat = [];
foreach ($finRows as $r) {
    if ($r['tipo'] === 'receita') {
        $totalReceitas += (float)$r['total'];
        $receitasPorCat[] = ['categoria' => $r['categoria'], 'total' => $r['total']];
    } else {
        $totalDespesas += (float)$r['total'];
        $despesasPorCat[] = ['categoria' => $r['categoria'], 'total' => $r['total']];
    }
}
usort($receitasPorCat, fn($a, $b) => $b['total'] <=> $a['total']);
usort($despesasPorCat, fn($a, $b) => $b['total'] <=> $a['total']);
$receitasPorCat = array_slice($receitasPorCat, 0, 5);
$despesasPorCat = array_slice($despesasPorCat, 0, 5);
$saldo = $totalReceitas - $totalDespesas;

// Query 2: Mensalidades do mês
$stMens = $db->prepare("SELECT status, COUNT(*) as qtd, COALESCE(SUM(valor),0) as total FROM mensalidades WHERE mes_referencia = ? GROUP BY status");
$stMens->execute([$mesAtual]);
$mensPorStatus = [];
foreach ($stMens->fetchAll() as $r) {
    $mensPorStatus[$r['status']] = $r;
}
$mensPagas     = (int)($mensPorStatus['pago']['qtd'] ?? 0);
$mensPendentes = (int)($mensPorStatus['pendente']['qtd'] ?? 0);
$mensAtrasadas = (int)($mensPorStatus['atrasado']['qtd'] ?? 0);
$valorRecebido = (float)($mensPorStatus['pago']['total'] ?? 0);
$valorPendente = (float)($mensPorStatus['pendente']['total'] ?? 0) + (float)($mensPorStatus['atrasado']['total'] ?? 0);

// Query 3: Últimos lançamentos
$stUlt = $db->prepare("SELECT l.*, p.nome AS paciente_nome FROM lancamentos l LEFT JOIN pacientes p ON p.id = l.paciente_id WHERE DATE_FORMAT(l.data_lancamento,'%Y-%m') = ? ORDER BY l.data_lancamento DESC, l.id DESC LIMIT 15");
$stUlt->execute([$mesAtual]);
$ultimosLanc = $stUlt->fetchAll();

// Query 4: Faturamento últimos 6 meses (para mini gráfico)
$fat6 = $db->query("
    SELECT DATE_FORMAT(data_lancamento,'%Y-%m') AS mes, 
           SUM(CASE WHEN tipo='receita' THEN valor ELSE 0 END) AS receitas,
           SUM(CASE WHEN tipo='despesa' THEN valor ELSE 0 END) AS despesas
    FROM lancamentos 
    WHERE data_lancamento >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(data_lancamento,'%Y-%m')
    ORDER BY mes ASC
")->fetchAll();

// Navegação de meses
$mesAnterior = date('Y-m', strtotime($mesAtual . '-01 -1 month'));
$mesProximo  = date('Y-m', strtotime($mesAtual . '-01 +1 month'));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Financeiro — Seres das Estrelas OS</title>
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
        <h4 class="d-inline fw-bold mb-0"><i class="bi bi-wallet2 me-2 text-gold"></i>Financeiro</h4>
      </div>
      <div class="d-flex align-items-center gap-2">
        <a href="?mes=<?= $mesAnterior ?>" class="btn btn-sm btn-outline-gold"><i class="bi bi-chevron-left"></i></a>
        <span class="fw-bold" style="font-family:'Montserrat',sans-serif;min-width:80px;text-align:center;"><?= $mesLabel ?></span>
        <a href="?mes=<?= $mesProximo ?>" class="btn btn-sm btn-outline-gold"><i class="bi bi-chevron-right"></i></a>
      </div>
    </div>

    <!-- Cards Resumo -->
    <div class="row g-3 mb-4">
      <div class="col-6 col-lg-3">
        <div class="glass-card stat-card p-3">
          <div class="stat-card-icon text-success"><i class="bi bi-arrow-up-circle"></i></div>
          <span class="stat-card-number text-success" style="font-size:1.3rem;">R$ <?= number_format($totalReceitas, 2, ',', '.') ?></span>
          <span class="stat-card-label">Receitas</span>
        </div>
      </div>
      <div class="col-6 col-lg-3">
        <div class="glass-card stat-card p-3">
          <div class="stat-card-icon text-danger"><i class="bi bi-arrow-down-circle"></i></div>
          <span class="stat-card-number text-danger" style="font-size:1.3rem;">R$ <?= number_format($totalDespesas, 2, ',', '.') ?></span>
          <span class="stat-card-label">Despesas</span>
        </div>
      </div>
      <div class="col-6 col-lg-3">
        <div class="glass-card stat-card p-3">
          <div class="stat-card-icon" style="color:<?= $saldo >= 0 ? '#198754' : '#dc3545' ?>;"><i class="bi bi-cash-stack"></i></div>
          <span class="stat-card-number" style="font-size:1.3rem;color:<?= $saldo >= 0 ? '#198754' : '#dc3545' ?>;">R$ <?= number_format($saldo, 2, ',', '.') ?></span>
          <span class="stat-card-label">Saldo do Mês</span>
        </div>
      </div>
      <div class="col-6 col-lg-3">
        <div class="glass-card stat-card p-3">
          <div class="stat-card-icon text-warning"><i class="bi bi-exclamation-triangle"></i></div>
          <span class="stat-card-number text-warning" style="font-size:1.3rem;">R$ <?= number_format($valorPendente, 2, ',', '.') ?></span>
          <span class="stat-card-label">A Receber</span>
        </div>
      </div>
    </div>

    <!-- Atalhos -->
    <div class="d-flex gap-2 mb-4 flex-wrap">
      <a href="mensalidades.php?mes=<?= $mesAtual ?>" class="btn btn-gold btn-sm"><i class="bi bi-receipt me-1"></i>Mensalidades</a>
      <a href="lancamentos.php?mes=<?= $mesAtual ?>" class="btn btn-outline-gold btn-sm"><i class="bi bi-journal-plus me-1"></i>Lançamentos</a>
    </div>

    <div class="row g-4">
      <!-- Mensalidades Resumo -->
      <div class="col-lg-4">
        <div class="glass-card p-4 mb-4">
          <h6 class="fw-bold mb-3"><i class="bi bi-receipt me-2 text-gold"></i>Mensalidades</h6>
          <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="small text-muted-ice">Pagas</span>
            <span class="badge" style="background:rgba(25,135,84,0.2);color:#198754;"><?= $mensPagas ?></span>
          </div>
          <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="small text-muted-ice">Pendentes</span>
            <span class="badge" style="background:rgba(255,193,7,0.2);color:#ffc107;"><?= $mensPendentes ?></span>
          </div>
          <div class="d-flex justify-content-between align-items-center mb-3">
            <span class="small text-muted-ice">Atrasadas</span>
            <span class="badge" style="background:rgba(220,53,69,0.2);color:#dc3545;"><?= $mensAtrasadas ?></span>
          </div>
          <div class="d-flex justify-content-between align-items-center pt-2 border-top" style="border-color:rgba(248,249,250,0.08)!important;">
            <span class="small fw-bold">Recebido</span>
            <span class="text-success fw-bold small">R$ <?= number_format($valorRecebido, 2, ',', '.') ?></span>
          </div>
          <a href="mensalidades.php?mes=<?= $mesAtual ?>" class="btn btn-outline-gold btn-sm w-100 mt-3">Ver Mensalidades</a>
        </div>

        <!-- Receitas por categoria -->
        <?php if (!empty($receitasPorCat)): ?>
        <div class="glass-card p-4 mb-4">
          <h6 class="fw-bold mb-3"><i class="bi bi-pie-chart me-2 text-gold"></i>Receitas por Categoria</h6>
          <?php foreach ($receitasPorCat as $cat): ?>
            <?php $pct = $totalReceitas > 0 ? ($cat['total'] / $totalReceitas) * 100 : 0; ?>
            <div class="mb-2">
              <div class="d-flex justify-content-between small mb-1">
                <span><?= e($cat['categoria']) ?></span>
                <span class="text-success">R$ <?= number_format($cat['total'], 2, ',', '.') ?></span>
              </div>
              <div class="progress" style="height:5px;background:rgba(255,255,255,0.05);">
                <div class="progress-bar bg-success" style="width:<?= $pct ?>%"></div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Despesas por categoria -->
        <?php if (!empty($despesasPorCat)): ?>
        <div class="glass-card p-4">
          <h6 class="fw-bold mb-3"><i class="bi bi-pie-chart me-2 text-danger"></i>Despesas por Categoria</h6>
          <?php foreach ($despesasPorCat as $cat): ?>
            <?php $pct = $totalDespesas > 0 ? ($cat['total'] / $totalDespesas) * 100 : 0; ?>
            <div class="mb-2">
              <div class="d-flex justify-content-between small mb-1">
                <span><?= e($cat['categoria']) ?></span>
                <span class="text-danger">R$ <?= number_format($cat['total'], 2, ',', '.') ?></span>
              </div>
              <div class="progress" style="height:5px;background:rgba(255,255,255,0.05);">
                <div class="progress-bar bg-danger" style="width:<?= $pct ?>%"></div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>

      <!-- Últimos Lançamentos -->
      <div class="col-lg-8">
        <div class="glass-card p-4 mb-4">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-bold mb-0"><i class="bi bi-journal-text me-2 text-gold"></i>Últimos Lançamentos</h6>
            <a href="lancamentos.php?mes=<?= $mesAtual ?>" class="text-gold small">Ver todos <i class="bi bi-arrow-right"></i></a>
          </div>
          <?php if (empty($ultimosLanc)): ?>
            <p class="text-muted-ice small mb-0">Nenhum lançamento neste mês.</p>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table table-dark-custom mb-0 align-middle">
                <thead>
                  <tr>
                    <th>Data</th>
                    <th>Descrição</th>
                    <th>Categoria</th>
                    <th class="text-end">Valor</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($ultimosLanc as $l): ?>
                    <tr>
                      <td class="small"><?= date('d/m', strtotime($l['data_lancamento'])) ?></td>
                      <td>
                        <span class="small"><?= e($l['descricao'] ?: $l['categoria']) ?></span>
                        <?php if ($l['paciente_nome']): ?>
                          <span class="d-block text-muted-ice" style="font-size:0.72rem;"><?= e($l['paciente_nome']) ?></span>
                        <?php endif; ?>
                      </td>
                      <td><span class="badge" style="background:rgba(94,84,142,0.2);color:#a78bfa;font-size:0.7rem;"><?= e($l['categoria']) ?></span></td>
                      <td class="text-end fw-bold small <?= $l['tipo'] === 'receita' ? 'text-success' : 'text-danger' ?>">
                        <?= $l['tipo'] === 'receita' ? '+' : '-' ?> R$ <?= number_format($l['valor'], 2, ',', '.') ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>

        <!-- Mini gráfico faturamento -->
        <?php if (!empty($fat6)): ?>
        <div class="glass-card p-4">
          <h6 class="fw-bold mb-3"><i class="bi bi-bar-chart me-2 text-gold"></i>Faturamento — Últimos Meses</h6>
          <div class="d-flex align-items-end gap-2" style="height:120px;">
            <?php
            $maxVal = max(array_map(fn($r) => max((float)$r['receitas'], (float)$r['despesas']), $fat6)) ?: 1;
            foreach ($fat6 as $f):
              $hRec  = ((float)$f['receitas'] / $maxVal) * 100;
              $hDesp = ((float)$f['despesas'] / $maxVal) * 100;
              $label = substr($f['mes'], 5);
            ?>
            <div class="text-center flex-fill">
              <div class="d-flex align-items-end justify-content-center gap-1" style="height:100px;">
                <div style="width:12px;background:rgba(25,135,84,0.5);border-radius:4px 4px 0 0;height:<?= max($hRec, 2) ?>%;transition:height 0.5s;"></div>
                <div style="width:12px;background:rgba(220,53,69,0.5);border-radius:4px 4px 0 0;height:<?= max($hDesp, 2) ?>%;transition:height 0.5s;"></div>
              </div>
              <span class="d-block text-muted-ice" style="font-size:0.65rem;margin-top:4px;"><?= $label ?></span>
            </div>
            <?php endforeach; ?>
          </div>
          <div class="d-flex gap-3 mt-2 justify-content-center">
            <span class="small text-muted-ice"><span style="display:inline-block;width:10px;height:10px;background:rgba(25,135,84,0.5);border-radius:2px;margin-right:4px;"></span>Receitas</span>
            <span class="small text-muted-ice"><span style="display:inline-block;width:10px;height:10px;background:rgba(220,53,69,0.5);border-radius:2px;margin-right:4px;"></span>Despesas</span>
          </div>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="app.js"></script>
</body>
</html>
