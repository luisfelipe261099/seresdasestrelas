<?php
/**
 * Página pública de Eventos — puxa do banco de dados
 */
require_once __DIR__ . '/system/config.php';

$db = getDB();
$eventos = $db->query("SELECT * FROM eventos WHERE ativo = 1 AND data_evento >= CURDATE() ORDER BY data_evento ASC, horario ASC")->fetchAll();
$eventosPassados = $db->query("SELECT * FROM eventos WHERE ativo = 1 AND data_evento < CURDATE() ORDER BY data_evento DESC LIMIT 4")->fetchAll();

$meses = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];

function mesAbrev(string $data): string {
    global $meses;
    $m = (int)date('m', strtotime($data));
    return strtoupper($meses[$m - 1] ?? 'JAN');
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Eventos do Mês | Seres das Estrelas</title>
  <meta name="description" content="Confira os próximos eventos, palestras e encontros sistêmicos da Seres das Estrelas em Curitiba." />

  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="style.css" />
</head>
<body>

  <!-- NAVBAR -->
  <nav class="navbar navbar-expand-lg navbar-dark fixed-top glass-nav" id="mainNav">
    <div class="container">
      <a class="navbar-brand fw-bold d-flex align-items-center" href="index.html">
        <img src="logo.jpeg" alt="Seres das Estrelas" class="navbar-logo me-2" /> Seres das Estrelas
      </a>
      <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Abrir menu">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto me-3 align-items-center gap-lg-1">
          <li class="nav-item"><a class="nav-link" href="index.html">Início</a></li>
          <li class="nav-item"><a class="nav-link" href="index.html#pilares">Método</a></li>
          <li class="nav-item"><a class="nav-link" href="index.html#sobre">Sobre</a></li>
          <li class="nav-item"><a class="nav-link active" href="eventos.php">Eventos</a></li>
        </ul>
        <a href="https://wa.me/5541991285254" target="_blank" rel="noopener noreferrer" class="btn btn-gold">Agendar Consulta</a>
      </div>
    </div>
  </nav>

  <!-- HERO -->
  <header class="eventos-hero">
    <div class="stars-bg" aria-hidden="true"></div>
    <div class="stars-bg stars-bg--layer2" aria-hidden="true"></div>
    <div class="container position-relative z-1 text-center">
      <p class="hero-tag fade-in">✦ Agenda</p>
      <h1 class="hero-title fade-in">Eventos do <span class="text-gold">Mês</span></h1>
      <p class="hero-subtitle fade-in">Palestras, encontros sistêmicos e vivências para sua evolução.</p>
    </div>
  </header>

  <!-- EVENTOS -->
  <section class="section-eventos">
    <div class="container">

      <div class="text-center mb-5 fade-in">
        <span class="eventos-mes-badge">
          <i class="bi bi-calendar3 me-2"></i><?= date('F Y') ?>
        </span>
      </div>

      <div class="row g-4 justify-content-center">
        <?php if (empty($eventos)): ?>
          <!-- Sem eventos -->
          <div class="col-md-6 col-lg-5 fade-in">
            <div class="glass-card evento-card p-0 h-100 evento-em-breve">
              <div class="evento-date-strip">
                <span class="evento-dia">—</span>
                <span class="evento-mes-label"><?= strtoupper(date('M')) ?></span>
              </div>
              <div class="evento-body d-flex flex-column align-items-center justify-content-center text-center">
                <i class="bi bi-stars evento-breve-icon"></i>
                <h5 class="card-title-custom mt-3 mb-1">Em breve</h5>
                <p class="card-text-custom mb-0">Novos eventos serão anunciados aqui.<br />Fique atenta às redes sociais!</p>
              </div>
            </div>
          </div>
        <?php else: ?>
          <?php foreach ($eventos as $ev): ?>
            <div class="col-md-6 col-lg-5 fade-in">
              <div class="glass-card evento-card p-0 h-100">
                <div class="evento-date-strip">
                  <span class="evento-dia"><?= date('d', strtotime($ev['data_evento'])) ?></span>
                  <span class="evento-mes-label"><?= mesAbrev($ev['data_evento']) ?></span>
                </div>
                <div class="evento-body">
                  <div class="evento-horario">
                    <i class="bi bi-clock me-1"></i><?= htmlspecialchars($ev['horario']) ?>h
                  </div>
                  <h4 class="evento-titulo"><?= htmlspecialchars($ev['titulo']) ?></h4>

                  <?php if ($ev['descricao']): ?>
                    <p class="evento-descricao"><?= htmlspecialchars($ev['descricao']) ?></p>
                  <?php endif; ?>

                  <?php if ($ev['palestrantes']): ?>
                    <div class="evento-speakers">
                      <?php foreach (explode(',', $ev['palestrantes']) as $p): ?>
                        <div class="speaker">
                          <i class="bi bi-person-circle me-1"></i>
                          <span><strong><?= htmlspecialchars(trim($p)) ?></strong></span>
                        </div>
                      <?php endforeach; ?>
                    </div>
                  <?php endif; ?>

                  <!-- Badges tipo -->
                  <div class="d-flex gap-2 mt-2 flex-wrap">
                    <?php if ($ev['local_tipo'] === 'online' || $ev['local_tipo'] === 'hibrido'): ?>
                      <span class="badge" style="background:rgba(13,202,240,0.15);color:#0dcaf0;font-size:0.72rem;">
                        <i class="bi bi-wifi me-1"></i><?= $ev['local_tipo'] === 'hibrido' ? 'Híbrido' : 'Online' ?>
                      </span>
                    <?php else: ?>
                      <span class="badge" style="background:rgba(25,135,84,0.15);color:#198754;font-size:0.72rem;">
                        <i class="bi bi-geo-alt me-1"></i>Presencial
                      </span>
                    <?php endif; ?>
                  </div>

                  <!-- Botões -->
                  <div class="d-flex gap-2 mt-3 flex-wrap">
                    <?php if ($ev['link_online']): ?>
                      <a href="<?= htmlspecialchars($ev['link_online']) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-gold btn-sm flex-fill">
                        <i class="bi bi-camera-video me-1"></i>Entrar na Reunião
                      </a>
                    <?php endif; ?>
                    <a href="https://wa.me/5541991285254?text=<?= urlencode('Olá! Gostaria de participar do evento "' . $ev['titulo'] . '" do dia ' . date('d/m', strtotime($ev['data_evento'])) . '.') ?>" target="_blank" rel="noopener noreferrer" class="btn <?= $ev['link_online'] ? 'btn-outline-gold' : 'btn-gold' ?> btn-sm flex-fill">
                      <i class="bi bi-whatsapp me-1"></i>Quero Participar
                    </a>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <?php if (!empty($eventosPassados)): ?>
        <div class="text-center mt-5 pt-4">
          <h5 class="fw-bold mb-4" style="color:rgba(248,249,250,0.4);font-family:'Montserrat',sans-serif;">Eventos Anteriores</h5>
        </div>
        <div class="row g-3 justify-content-center">
          <?php foreach ($eventosPassados as $ep): ?>
            <div class="col-md-6 col-lg-4 fade-in">
              <div class="glass-card p-3 text-center" style="opacity:0.5;">
                <span class="small text-muted-ice"><?= date('d/m/Y', strtotime($ep['data_evento'])) ?></span>
                <h6 class="fw-bold mt-1 mb-0" style="font-size:0.9rem;"><?= htmlspecialchars($ep['titulo']) ?></h6>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </section>

  <!-- CTA -->
  <section class="section-cta">
    <div class="container text-center">
      <div class="row justify-content-center">
        <div class="col-lg-7 fade-in">
          <h2 class="section-title">Quer ser avisada sobre <span class="text-gold">novos eventos</span>?</h2>
          <p class="section-subtitle mx-auto">Entre em contato e receba as novidades direto no seu WhatsApp.</p>
          <a href="https://wa.me/5541991285254?text=Ol%C3%A1!%20Quero%20receber%20novidades%20sobre%20os%20pr%C3%B3ximos%20eventos." target="_blank" rel="noopener noreferrer" class="btn btn-gold btn-lg mt-3">
            <i class="bi bi-whatsapp me-2"></i>Receber Novidades
          </a>
        </div>
      </div>
    </div>
  </section>

  <!-- FOOTER -->
  <footer class="site-footer">
    <div class="container">
      <div class="row g-4 align-items-center">
        <div class="col-lg-4 text-center text-lg-start">
          <a class="footer-brand d-flex align-items-center justify-content-center justify-content-lg-start" href="index.html">
            <img src="logo.jpeg" alt="Seres das Estrelas" class="footer-logo me-2" /> Seres das Estrelas
          </a>
          <p class="footer-sub mt-2">Psicanálise &amp; Tecnologia Estelar<br />Curitiba — PR</p>
        </div>
        <div class="col-lg-4 text-center">
          <div class="footer-socials">
            <a href="https://instagram.com/" target="_blank" rel="noopener noreferrer" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
            <a href="https://wa.me/5541991285254" target="_blank" rel="noopener noreferrer" aria-label="WhatsApp"><i class="bi bi-whatsapp"></i></a>
            <a href="mailto:contato@seresdasestrelas.com" aria-label="E-mail"><i class="bi bi-envelope"></i></a>
          </div>
        </div>
        <div class="col-lg-4 text-center text-lg-end">
          <a href="system/login.php" class="footer-system-link"><i class="bi bi-lock me-1"></i>Acesso Restrito ao Sistema</a>
        </div>
      </div>
      <hr class="footer-divider" />
      <p class="footer-copy text-center">&copy; 2026 Seres das Estrelas. Todos os direitos reservados.</p>
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const faders = document.querySelectorAll('.fade-in');
      const observerOpts = { threshold: 0.15, rootMargin: '0px 0px -40px 0px' };
      const fadeObserver = new IntersectionObserver((entries, obs) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            entry.target.classList.add('visible');
            obs.unobserve(entry.target);
          }
        });
      }, observerOpts);
      faders.forEach(el => fadeObserver.observe(el));

      const nav = document.getElementById('mainNav');
      window.addEventListener('scroll', () => {
        nav.classList.toggle('scrolled', window.scrollY > 60);
      });

      const navLinks = document.querySelectorAll('.navbar-nav .nav-link');
      const navCollapse = document.getElementById('navbarNav');
      navLinks.forEach(link => {
        link.addEventListener('click', () => {
          if (navCollapse.classList.contains('show')) {
            bootstrap.Collapse.getOrCreateInstance(navCollapse).hide();
          }
        });
      });
    });
  </script>
</body>
</html>
