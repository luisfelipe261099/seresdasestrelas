<?php
/**
 * Seres das Estrelas OS — Lançamentos (Fluxo de Caixa)
 */
require_once __DIR__ . '/auth.php';
requireLogin();

$db   = getDB();
$user = currentUser();
$mesAtual = $_GET['mes'] ?? date('Y-m');

// ── AÇÕES POST ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    csrf_check();

    if ($_POST['action'] === 'criar') {
        $tipo       = in_array($_POST['tipo'] ?? '', ['receita','despesa']) ? $_POST['tipo'] : 'receita';
        $categoria  = trim($_POST['categoria'] ?? '');
        $descricao  = trim($_POST['descricao'] ?? '');
        $valor      = (float)str_replace(',', '.', str_replace('.', '', $_POST['valor'] ?? '0'));
        $data       = $_POST['data_lancamento'] ?? date('Y-m-d');
        $forma      = trim($_POST['forma_pagamento'] ?? '');
        $pacId      = (int)($_POST['paciente_id'] ?? 0) ?: null;
        $obs        = trim($_POST['observacao'] ?? '');

        if ($categoria && $valor > 0) {
            $st = $db->prepare("INSERT INTO lancamentos (tipo, categoria, descricao, valor, data_lancamento, forma_pagamento, paciente_id, observacao) VALUES (?,?,?,?,?,?,?,?)");
            $st->execute([$tipo, $categoria, $descricao, $valor, $data, $forma, $pacId, $obs]);
        }
        $mesRedir = date('Y-m', strtotime($data));
        header("Location: lancamentos.php?mes={$mesRedir}&msg=criado");
        exit;
    }

    if ($_POST['action'] === 'excluir') {
        $lid = (int)$_POST['lancamento_id'];
        // Não permite excluir lançamentos de mensalidade (vinculados)
        $chk = $db->prepare("SELECT mensalidade_id FROM lancamentos WHERE id = ?");
        $chk->execute([$lid]);
        $lanc = $chk->fetch();
        if ($lanc && $lanc['mensalidade_id']) {
            header("Location: lancamentos.php?mes={$mesAtual}&msg=erro_vinc");
            exit;
        }
        $db->prepare("DELETE FROM lancamentos WHERE id = ? AND mensalidade_id IS NULL")->execute([$lid]);
        header("Location: lancamentos.php?mes={$mesAtual}&msg=excluido");
        exit;
    }
}

// Listar lançamentos do mês
$filtroTipo = $_GET['tipo'] ?? '';
$sql = "SELECT l.*, p.nome AS paciente_nome FROM lancamentos l LEFT JOIN pacientes p ON p.id = l.paciente_id WHERE DATE_FORMAT(l.data_lancamento,'%Y-%m') = ?";
$params = [$mesAtual];
if ($filtroTipo === 'receita' || $filtroTipo === 'despesa') {
    $sql .= " AND l.tipo = ?";
    $params[] = $filtroTipo;
}
$sql .= " ORDER BY l.data_lancamento DESC, l.id DESC";
$stList = $db->prepare($sql);
$stList->execute($params);
$lancamentos = $stList->fetchAll();

// Totais
$totalRec  = 0;
$totalDesp = 0;
foreach ($lancamentos as $l) {
    if ($l['tipo'] === 'receita') $totalRec += (float)$l['valor'];
    else $totalDesp += (float)$l['valor'];
}

// Pacientes para dropdown
$pacientesList = $db->query("SELECT id, nome FROM pacientes WHERE status_ativo = 1 ORDER BY nome")->fetchAll();

// Categorias sugeridas
$categoriasReceita = ['Mensalidade','Consulta Avulsa','Evento','Supervisão','Outros'];
$categoriasDespesa = ['Aluguel','Material','Marketing','Software','Transporte','Alimentação','Impostos','Outros'];

// Navegação meses
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
  <title>Lançamentos — Seres das Estrelas OS</title>
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
        <h4 class="d-inline fw-bold mb-0"><i class="bi bi-journal-plus me-2 text-gold"></i>Lançamentos</h4>
      </div>
      <div class="d-flex align-items-center gap-2">
        <a href="?mes=<?= $mesAnterior ?>" class="btn btn-sm btn-outline-gold"><i class="bi bi-chevron-left"></i></a>
        <span class="fw-bold" style="font-family:'Montserrat',sans-serif;min-width:80px;text-align:center;"><?= $mesLabel ?></span>
        <a href="?mes=<?= $mesProximo ?>" class="btn btn-sm btn-outline-gold"><i class="bi bi-chevron-right"></i></a>
      </div>
    </div>

    <?php if ($msg): ?>
      <div class="alert py-2 small" style="background:rgba(25,135,84,0.15);border:1px solid rgba(25,135,84,0.3);color:#a3cfbb;border-radius:var(--radius);">
        <?php
        echo match($msg) {
            'criado'     => '<i class="bi bi-check-circle me-1"></i>Lançamento criado!',
            'excluido'   => '<i class="bi bi-trash me-1"></i>Lançamento excluído.',
            'erro_vinc'  => '<i class="bi bi-exclamation-triangle me-1"></i>Não é possível excluir: vinculado a uma mensalidade. Desfaça o pagamento na tela de Mensalidades.',
            default      => ''
        };
        ?>
      </div>
    <?php endif; ?>

    <!-- Cards Resumo -->
    <div class="row g-3 mb-4">
      <div class="col-4">
        <div class="glass-card p-3 text-center">
          <span class="d-block fw-bold text-success" style="font-size:1rem;">+ R$ <?= number_format($totalRec, 2, ',', '.') ?></span>
          <span class="stat-card-label">Receitas</span>
        </div>
      </div>
      <div class="col-4">
        <div class="glass-card p-3 text-center">
          <span class="d-block fw-bold text-danger" style="font-size:1rem;">- R$ <?= number_format($totalDesp, 2, ',', '.') ?></span>
          <span class="stat-card-label">Despesas</span>
        </div>
      </div>
      <div class="col-4">
        <?php $sld = $totalRec - $totalDesp; ?>
        <div class="glass-card p-3 text-center">
          <span class="d-block fw-bold" style="font-size:1rem;color:<?= $sld >= 0 ? '#198754' : '#dc3545' ?>;">R$ <?= number_format($sld, 2, ',', '.') ?></span>
          <span class="stat-card-label">Saldo</span>
        </div>
      </div>
    </div>

    <!-- Novo Lançamento -->
    <div class="glass-card p-4 mb-4">
      <h6 class="fw-bold mb-3"><i class="bi bi-plus-circle me-2 text-gold"></i>Novo Lançamento</h6>
      <form method="post" id="formLanc">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>" />
        <input type="hidden" name="action" value="criar" />
        <div class="row g-3">
          <!-- Tipo -->
          <div class="col-6 col-md-2">
            <label class="form-label small">Tipo</label>
            <select name="tipo" id="selTipo" class="form-select form-select-sm input-dark" required>
              <option value="receita">Receita</option>
              <option value="despesa">Despesa</option>
            </select>
          </div>
          <!-- Categoria -->
          <div class="col-6 col-md-3">
            <label class="form-label small">Categoria</label>
            <input type="text" name="categoria" id="inputCat" class="form-control form-control-sm input-dark" list="listCat" placeholder="Ex: Mensalidade" required />
            <datalist id="listCat">
              <?php foreach ($categoriasReceita as $c): ?>
                <option value="<?= $c ?>" data-tipo="receita">
              <?php endforeach; ?>
              <?php foreach ($categoriasDespesa as $c): ?>
                <option value="<?= $c ?>" data-tipo="despesa">
              <?php endforeach; ?>
            </datalist>
          </div>
          <!-- Descrição -->
          <div class="col-12 col-md-3">
            <label class="form-label small">Descrição</label>
            <input type="text" name="descricao" class="form-control form-control-sm input-dark" placeholder="Detalhe opcional" />
          </div>
          <!-- Valor -->
          <div class="col-6 col-md-2">
            <label class="form-label small">Valor (R$)</label>
            <input type="text" name="valor" class="form-control form-control-sm input-dark" placeholder="150,00" required />
          </div>
          <!-- Data -->
          <div class="col-6 col-md-2">
            <label class="form-label small">Data</label>
            <input type="date" name="data_lancamento" class="form-control form-control-sm input-dark" value="<?= date('Y-m-d') ?>" required />
          </div>
          <!-- Forma -->
          <div class="col-6 col-md-2">
            <label class="form-label small">Forma Pgto</label>
            <select name="forma_pagamento" class="form-select form-select-sm input-dark">
              <option value="">—</option>
              <option value="pix">PIX</option>
              <option value="dinheiro">Dinheiro</option>
              <option value="cartao">Cartão</option>
              <option value="transferencia">Transferência</option>
            </select>
          </div>
          <!-- Paciente -->
          <div class="col-6 col-md-3">
            <label class="form-label small">Paciente (opcional)</label>
            <select name="paciente_id" class="form-select form-select-sm input-dark">
              <option value="">Nenhum</option>
              <?php foreach ($pacientesList as $p): ?>
                <option value="<?= $p['id'] ?>"><?= e($p['nome']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <!-- Obs -->
          <div class="col-12 col-md-5">
            <label class="form-label small">Observação</label>
            <input type="text" name="observacao" class="form-control form-control-sm input-dark" placeholder="Nota interna" />
          </div>
          <div class="col-12 col-md-2 d-flex align-items-end">
            <button type="submit" class="btn btn-gold btn-sm w-100"><i class="bi bi-plus-lg me-1"></i>Salvar</button>
          </div>
        </div>
      </form>
    </div>

    <!-- Filtros -->
    <div class="d-flex gap-2 mb-3 flex-wrap">
      <a href="?mes=<?= $mesAtual ?>" class="btn btn-sm <?= !$filtroTipo ? 'btn-gold' : 'btn-outline-gold' ?>">Todos</a>
      <a href="?mes=<?= $mesAtual ?>&tipo=receita" class="btn btn-sm <?= $filtroTipo === 'receita' ? 'btn-gold' : 'btn-outline-gold' ?>"><i class="bi bi-arrow-up-circle me-1"></i>Receitas</a>
      <a href="?mes=<?= $mesAtual ?>&tipo=despesa" class="btn btn-sm <?= $filtroTipo === 'despesa' ? 'btn-gold' : 'btn-outline-gold' ?>"><i class="bi bi-arrow-down-circle me-1"></i>Despesas</a>
    </div>

    <!-- Tabela Lançamentos -->
    <div class="glass-card p-4">
      <?php if (empty($lancamentos)): ?>
        <p class="text-muted-ice text-center py-4 mb-0">Nenhum lançamento neste mês.</p>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-dark-custom mb-0 align-middle">
            <thead>
              <tr>
                <th>Data</th>
                <th>Tipo</th>
                <th>Categoria</th>
                <th>Descrição</th>
                <th class="d-none d-md-table-cell">Forma</th>
                <th class="text-end">Valor</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($lancamentos as $l): ?>
                <tr>
                  <td class="small"><?= date('d/m', strtotime($l['data_lancamento'])) ?></td>
                  <td>
                    <?php if ($l['tipo'] === 'receita'): ?>
                      <span class="badge" style="background:rgba(25,135,84,0.2);color:#198754;font-size:0.7rem;"><i class="bi bi-arrow-up"></i></span>
                    <?php else: ?>
                      <span class="badge" style="background:rgba(220,53,69,0.2);color:#dc3545;font-size:0.7rem;"><i class="bi bi-arrow-down"></i></span>
                    <?php endif; ?>
                  </td>
                  <td><span class="small"><?= e($l['categoria']) ?></span></td>
                  <td>
                    <span class="small"><?= e($l['descricao'] ?: '—') ?></span>
                    <?php if ($l['paciente_nome']): ?>
                      <span class="d-block text-muted-ice" style="font-size:0.7rem;"><?= e($l['paciente_nome']) ?></span>
                    <?php endif; ?>
                    <?php if ($l['mensalidade_id']): ?>
                      <span class="badge" style="background:rgba(94,84,142,0.2);color:#a78bfa;font-size:0.6rem;">Mensalidade</span>
                    <?php endif; ?>
                  </td>
                  <td class="d-none d-md-table-cell small text-muted-ice"><?= e($l['forma_pagamento'] ?: '—') ?></td>
                  <td class="text-end fw-bold small <?= $l['tipo'] === 'receita' ? 'text-success' : 'text-danger' ?>">
                    <?= $l['tipo'] === 'receita' ? '+' : '-' ?> R$ <?= number_format($l['valor'], 2, ',', '.') ?>
                  </td>
                  <td>
                    <?php if (!$l['mensalidade_id']): ?>
                      <form method="post" class="d-inline">
                        <input type="hidden" name="csrf" value="<?= csrf_token() ?>" />
                        <input type="hidden" name="action" value="excluir" />
                        <input type="hidden" name="lancamento_id" value="<?= $l['id'] ?>" />
                        <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Excluir este lançamento?')" title="Excluir"><i class="bi bi-trash"></i></button>
                      </form>
                    <?php endif; ?>
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
