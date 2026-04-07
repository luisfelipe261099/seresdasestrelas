<?php
/**
 * Formulário Público de Anamnese — o paciente preenche antes da consulta
 */
require_once __DIR__ . '/config.php';
session_start();

$enviado = false;
$erro    = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome     = trim($_POST['nome'] ?? '');
    $wpp      = trim($_POST['whatsapp'] ?? '');
    $queixa   = trim($_POST['queixa'] ?? '');
    $historico = trim($_POST['historico'] ?? '');
    $terapias = trim($_POST['terapias'] ?? '');
    $objetivos = trim($_POST['objetivos'] ?? '');

    if (!$nome || !$wpp || !$queixa) {
        $erro = 'Preencha os campos obrigatórios.';
    } else {
        $respostas = json_encode([
            'Queixa Principal'            => $queixa,
            'Histórico de Saúde Mental'   => $historico,
            'Experiências com Terapias'   => $terapias,
            'Objetivos com a Seres das Estrelas' => $objetivos
        ], JSON_UNESCAPED_UNICODE);

        // Tenta vincular a paciente existente
        $db = getDB();
        $stFind = $db->prepare('SELECT id FROM pacientes WHERE whatsapp LIKE ? LIMIT 1');
        $wppClean = preg_replace('/\D/', '', $wpp);
        $stFind->execute(["%{$wppClean}%"]);
        $pacId = $stFind->fetchColumn() ?: null;

        $stIns = $db->prepare('INSERT INTO anamneses (paciente_id, nome, whatsapp, respostas_json) VALUES (?,?,?,?)');
        $stIns->execute([$pacId, $nome, $wpp, $respostas]);
        $enviado = true;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Anamnese — Seres das Estrelas</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="../style.css" />
  <style>
    .anamnese-wrap { max-width: 640px; margin: 0 auto; }
    .form-label { color: rgba(248,249,250,0.8); }
    .input-anamnese {
      background: rgba(94,84,142,0.12);
      border: 1px solid rgba(224,164,88,0.18);
      color: #F8F9FA;
      border-radius: 12px;
    }
    .input-anamnese:focus {
      background: rgba(94,84,142,0.2);
      border-color: #E0A458;
      color: #F8F9FA;
      box-shadow: 0 0 0 3px rgba(224,164,88,0.15);
    }
    .input-anamnese::placeholder { color: rgba(248,249,250,0.35); }
    .success-box { text-align: center; padding: 3rem 2rem; }
    .success-box i { font-size: 3.5rem; color: #E0A458; }
  </style>
</head>
<body>

  <section style="padding: 120px 0 80px; min-height: 100vh;">
    <div class="container">
      <div class="anamnese-wrap">
        <div class="text-center mb-4">
          <img src="../logo.jpeg" alt="Logo" style="height:60px;border-radius:50%;" class="mb-3" />
          <h2 class="fw-bold" style="font-family:'Montserrat',sans-serif;">Ficha de <span class="text-gold">Anamnese</span></h2>
          <p style="color:rgba(248,249,250,0.6); font-size:0.95rem;">Preencha com atenção. Suas respostas ajudarão a Joyce a preparar seu atendimento.</p>
        </div>

        <?php if ($enviado): ?>
          <div class="glass-card success-box">
            <i class="bi bi-check-circle"></i>
            <h5 class="fw-bold mt-3">Anamnese enviada com sucesso!</h5>
            <p style="color:rgba(248,249,250,0.6);">A Joyce receberá suas respostas antes da consulta. Obrigada!</p>
            <a href="../index.html" class="btn btn-gold mt-2">Voltar ao Site</a>
          </div>
        <?php else: ?>
          <?php if ($erro): ?>
            <div class="alert alert-danger py-2"><?= e($erro) ?></div>
          <?php endif; ?>

          <div class="glass-card p-4">
            <form method="post">
              <div class="mb-3">
                <label class="form-label small">Nome completo *</label>
                <input type="text" name="nome" class="form-control input-anamnese" required value="<?= e($nome ?? '') ?>" />
              </div>
              <div class="mb-3">
                <label class="form-label small">WhatsApp *</label>
                <input type="text" name="whatsapp" class="form-control input-anamnese" placeholder="(41) 99128-5254" required value="<?= e($wpp ?? '') ?>" />
              </div>
              <div class="mb-3">
                <label class="form-label small">Queixa principal — o que te traz aqui? *</label>
                <textarea name="queixa" class="form-control input-anamnese" rows="3" required><?= e($queixa ?? '') ?></textarea>
              </div>
              <div class="mb-3">
                <label class="form-label small">Histórico de saúde mental</label>
                <textarea name="historico" class="form-control input-anamnese" rows="3" placeholder="Já fez terapia antes? Toma medicação?"><?= e($historico ?? '') ?></textarea>
              </div>
              <div class="mb-3">
                <label class="form-label small">Experiências com terapias energéticas</label>
                <textarea name="terapias" class="form-control input-anamnese" rows="2" placeholder="Constelação, reiki, florais..."><?= e($terapias ?? '') ?></textarea>
              </div>
              <div class="mb-4">
                <label class="form-label small">Seus objetivos com a Seres das Estrelas</label>
                <textarea name="objetivos" class="form-control input-anamnese" rows="3" placeholder="O que você espera alcançar com esse tratamento?"><?= e($objetivos ?? '') ?></textarea>
              </div>
              <button type="submit" class="btn btn-gold w-100">
                <i class="bi bi-send me-2"></i>Enviar Anamnese
              </button>
            </form>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </section>

</body>
</html>
