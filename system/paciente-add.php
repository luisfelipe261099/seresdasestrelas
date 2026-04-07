<?php
/**
 * Adicionar Novo Paciente
 */
require_once __DIR__ . '/auth.php';
requireLogin();

$db   = getDB();
$user = currentUser();
$erro = '';

// Buscar blocos ativos
$blocosDisp = $db->query("SELECT * FROM blocos WHERE ativo = 1 ORDER BY ordem ASC, numero ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $nome  = trim($_POST['nome'] ?? '');
    $wpp   = trim($_POST['whatsapp'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $nasc  = $_POST['data_nascimento'] ?? '';
    $ocup  = trim($_POST['ocupacao'] ?? '');
    $bloco = (int)($_POST['bloco_atual'] ?? ($blocosDisp[0]['numero'] ?? 1));

    // Financeiro
    $valorMensal   = (float)str_replace(',', '.', str_replace('.', '', $_POST['valor_mensal'] ?? '0'));
    $formaPgto     = trim($_POST['forma_pagamento'] ?? 'pix');
    $tipoCobranca  = in_array($_POST['tipo_cobranca'] ?? '', ['mensal','avista','parcelado']) ? $_POST['tipo_cobranca'] : 'mensal';
    $parcelasTotal = max(1, (int)($_POST['parcelas_total'] ?? 1));
    $diaVenc       = max(1, min(28, (int)($_POST['dia_vencimento'] ?? 10)));
    $obsFin        = trim($_POST['observacao_financeira'] ?? '');

    if (!$nome || !$wpp) {
        $erro = 'Nome e WhatsApp são obrigatórios.';
    } else {
        $st = $db->prepare('INSERT INTO pacientes (nome, whatsapp, email, data_nascimento, ocupacao, bloco_atual, valor_mensal, forma_pagamento, tipo_cobranca, parcelas_total, dia_vencimento, observacao_financeira) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)');
        $st->execute([$nome, $wpp, $email ?: null, $nasc ?: null, $ocup ?: null, $bloco, $valorMensal, $formaPgto, $tipoCobranca, $parcelasTotal, $diaVenc, $obsFin ?: null]);
        $newId = (int)$db->lastInsertId();

        // Auto-gerar mensalidade do mês atual se tipo mensal e valor > 0
        if ($tipoCobranca === 'mensal' && $valorMensal > 0) {
            $mesRef   = date('Y-m');
            $dataVenc = $mesRef . '-' . str_pad($diaVenc, 2, '0', STR_PAD_LEFT);
            $stM = $db->prepare("INSERT IGNORE INTO mensalidades (paciente_id, mes_referencia, valor, data_vencimento, status) VALUES (?, ?, ?, ?, 'pendente')");
            $stM->execute([$newId, $mesRef, $valorMensal, $dataVenc]);
        }

        // Auto-gerar parcelas se parcelado e valor > 0
        if ($tipoCobranca === 'parcelado' && $valorMensal > 0 && $parcelasTotal > 1) {
            for ($p = 0; $p < $parcelasTotal; $p++) {
                $mesRef   = date('Y-m', strtotime("+{$p} months"));
                $dataVenc = $mesRef . '-' . str_pad($diaVenc, 2, '0', STR_PAD_LEFT);
                $stP = $db->prepare("INSERT IGNORE INTO mensalidades (paciente_id, mes_referencia, valor, data_vencimento, status) VALUES (?, ?, ?, ?, 'pendente')");
                $stP->execute([$newId, $mesRef, $valorMensal, $dataVenc]);
            }
        }

        // Auto-gerar lançamento se à vista e valor > 0
        if ($tipoCobranca === 'avista' && $valorMensal > 0) {
            $stL = $db->prepare("INSERT INTO lancamentos (tipo, categoria, descricao, valor, data_lancamento, forma_pagamento, paciente_id) VALUES ('receita','Pagamento à Vista',?,?,CURDATE(),?,?)");
            $stL->execute(["Pgto à vista - {$nome}", $valorMensal, $formaPgto, $newId]);
        }

        header("Location: paciente.php?id={$newId}");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Novo Paciente — Seres das Estrelas OS</title>
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
      <a href="pacientes.php" class="text-muted-ice me-2"><i class="bi bi-arrow-left"></i></a>
      <h4 class="d-inline fw-bold mb-0">Novo Paciente</h4>
    </div>

    <div class="row justify-content-center">
      <div class="col-lg-8">
        <div class="glass-card p-4">
          <?php if ($erro): ?>
            <div class="alert alert-danger py-2"><?= e($erro) ?></div>
          <?php endif; ?>

          <form method="post" id="formPac">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>" />

            <!-- Dados Pessoais -->
            <h6 class="fw-bold mb-3"><i class="bi bi-person me-2 text-gold"></i>Dados Pessoais</h6>
            <div class="row g-3 mb-4">
              <div class="col-12">
                <label class="form-label small">Nome completo *</label>
                <input type="text" name="nome" class="form-control input-dark" required value="<?= e($_POST['nome'] ?? '') ?>" />
              </div>
              <div class="col-md-6">
                <label class="form-label small">WhatsApp *</label>
                <input type="text" name="whatsapp" class="form-control input-dark" placeholder="41991285254" required value="<?= e($_POST['whatsapp'] ?? '') ?>" />
              </div>
              <div class="col-md-6">
                <label class="form-label small">E-mail</label>
                <input type="email" name="email" class="form-control input-dark" value="<?= e($_POST['email'] ?? '') ?>" />
              </div>
              <div class="col-md-6">
                <label class="form-label small">Data de Nascimento</label>
                <input type="date" name="data_nascimento" class="form-control input-dark" value="<?= e($_POST['data_nascimento'] ?? '') ?>" />
              </div>
              <div class="col-md-6">
                <label class="form-label small">Ocupação</label>
                <input type="text" name="ocupacao" class="form-control input-dark" value="<?= e($_POST['ocupacao'] ?? '') ?>" />
              </div>
            </div>

            <!-- Bloco Inicial -->
            <h6 class="fw-bold mb-3"><i class="bi bi-stars me-2 text-gold"></i>Bloco Inicial</h6>
            <div class="row g-3 mb-4">
              <div class="col-12">
                <select name="bloco_atual" class="form-select input-dark">
                  <?php foreach ($blocosDisp as $bl): ?>
                    <option value="<?= (int)$bl['numero'] ?>" <?= ((int)($_POST['bloco_atual'] ?? 0) === (int)$bl['numero']) ? 'selected' : '' ?>>
                      Bloco <?= (int)$bl['numero'] ?> — <?= e($bl['nome']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>

            <hr style="border-color:rgba(248,249,250,0.08);" />

            <!-- Financeiro -->
            <h6 class="fw-bold mb-3"><i class="bi bi-wallet2 me-2 text-gold"></i>Configuração Financeira</h6>
            <div class="row g-3 mb-4">
              <div class="col-md-4">
                <label class="form-label small">Tipo de Cobrança</label>
                <select name="tipo_cobranca" id="tipoCobranca" class="form-select input-dark">
                  <option value="mensal" <?= ($_POST['tipo_cobranca'] ?? '') === 'mensal' ? 'selected' : '' ?>>Mensal</option>
                  <option value="avista" <?= ($_POST['tipo_cobranca'] ?? '') === 'avista' ? 'selected' : '' ?>>À Vista</option>
                  <option value="parcelado" <?= ($_POST['tipo_cobranca'] ?? '') === 'parcelado' ? 'selected' : '' ?>>Parcelado</option>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label small" id="labelValor">Valor Mensal (R$)</label>
                <input type="text" name="valor_mensal" class="form-control input-dark" placeholder="250,00" value="<?= e($_POST['valor_mensal'] ?? '') ?>" />
              </div>
              <div class="col-md-4">
                <label class="form-label small">Forma de Pagamento</label>
                <select name="forma_pagamento" class="form-select input-dark">
                  <option value="pix" <?= ($_POST['forma_pagamento'] ?? '') === 'pix' ? 'selected' : '' ?>>PIX</option>
                  <option value="dinheiro" <?= ($_POST['forma_pagamento'] ?? '') === 'dinheiro' ? 'selected' : '' ?>>Dinheiro</option>
                  <option value="cartao" <?= ($_POST['forma_pagamento'] ?? '') === 'cartao' ? 'selected' : '' ?>>Cartão</option>
                  <option value="transferencia" <?= ($_POST['forma_pagamento'] ?? '') === 'transferencia' ? 'selected' : '' ?>>Transferência</option>
                </select>
              </div>
              <div class="col-md-4" id="divParcelas" style="display:none;">
                <label class="form-label small">Nº de Parcelas</label>
                <input type="number" name="parcelas_total" class="form-control input-dark" min="2" max="24" value="<?= e($_POST['parcelas_total'] ?? '3') ?>" />
              </div>
              <div class="col-md-4" id="divVencimento">
                <label class="form-label small">Dia do Vencimento</label>
                <input type="number" name="dia_vencimento" class="form-control input-dark" min="1" max="28" value="<?= e($_POST['dia_vencimento'] ?? '10') ?>" />
              </div>
              <div class="col-12">
                <label class="form-label small">Observação Financeira</label>
                <input type="text" name="observacao_financeira" class="form-control input-dark" placeholder="Ex: desconto de 10% por indicação" value="<?= e($_POST['observacao_financeira'] ?? '') ?>" />
              </div>
            </div>

            <div class="glass-card p-3 mb-4" style="background:rgba(224,164,88,0.06);border-color:rgba(224,164,88,0.2);">
              <p class="small text-muted-ice mb-0">
                <i class="bi bi-info-circle me-1 text-gold"></i>
                <strong>Mensal:</strong> gera mensalidade automática todo mês.
                <strong>À Vista:</strong> registra receita imediata.
                <strong>Parcelado:</strong> gera todas as parcelas automaticamente.
              </p>
            </div>

            <button type="submit" class="btn btn-gold"><i class="bi bi-person-plus me-1"></i>Cadastrar Paciente</button>
          </form>
        </div>
      </div>
    </div>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="app.js"></script>
  <script>
    const tipoSel  = document.getElementById('tipoCobranca');
    const divParc   = document.getElementById('divParcelas');
    const divVenc   = document.getElementById('divVencimento');
    const labelVal  = document.getElementById('labelValor');

    function updateTipo() {
      const v = tipoSel.value;
      divParc.style.display   = v === 'parcelado' ? '' : 'none';
      divVenc.style.display   = v === 'avista' ? 'none' : '';
      labelVal.textContent    = v === 'mensal' ? 'Valor Mensal (R$)' : v === 'avista' ? 'Valor Total (R$)' : 'Valor da Parcela (R$)';
    }
    tipoSel.addEventListener('change', updateTipo);
    updateTipo();
  </script>
</body>
</html>
