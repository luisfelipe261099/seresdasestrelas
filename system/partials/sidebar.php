<!-- Sidebar Navigation -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-brand">
    <img src="/logo.jpeg" alt="Logo" class="sidebar-logo" />
    <span>Seres das Estrelas</span>
  </div>
  <nav class="sidebar-nav">
    <a href="index.php" class="sidebar-link <?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '' ?>">
      <i class="bi bi-grid-1x2"></i><span>Dashboard</span>
    </a>
    <a href="pacientes.php" class="sidebar-link <?= in_array(basename($_SERVER['PHP_SELF']), ['pacientes.php','paciente.php','paciente-add.php']) ? 'active' : '' ?>">
      <i class="bi bi-people"></i><span>Pacientes</span>
    </a>
    <a href="anamnese-ver.php" class="sidebar-link <?= basename($_SERVER['PHP_SELF']) === 'anamnese-ver.php' ? 'active' : '' ?>">
      <i class="bi bi-clipboard-pulse"></i><span>Anamneses</span>
    </a>
    <a href="blocos.php" class="sidebar-link <?= basename($_SERVER['PHP_SELF']) === 'blocos.php' ? 'active' : '' ?>">
      <i class="bi bi-stars"></i><span>Blocos</span>
    </a>
    <a href="eventos-admin.php" class="sidebar-link <?= basename($_SERVER['PHP_SELF']) === 'eventos-admin.php' ? 'active' : '' ?>">
      <i class="bi bi-calendar-event"></i><span>Eventos</span>
    </a>

    <div class="sidebar-divider"></div>

    <a href="financeiro.php" class="sidebar-link <?= basename($_SERVER['PHP_SELF']) === 'financeiro.php' ? 'active' : '' ?>">
      <i class="bi bi-wallet2"></i><span>Financeiro</span>
    </a>
    <a href="mensalidades.php" class="sidebar-link <?= basename($_SERVER['PHP_SELF']) === 'mensalidades.php' ? 'active' : '' ?>">
      <i class="bi bi-receipt"></i><span>Mensalidades</span>
    </a>
    <a href="lancamentos.php" class="sidebar-link <?= basename($_SERVER['PHP_SELF']) === 'lancamentos.php' ? 'active' : '' ?>">
      <i class="bi bi-journal-plus"></i><span>Lançamentos</span>
    </a>
  </nav>
  <div class="sidebar-footer">
    <a href="logout.php" class="sidebar-link text-danger">
      <i class="bi bi-box-arrow-left"></i><span>Sair</span>
    </a>
  </div>
</aside>
<div class="sidebar-overlay" id="sidebarOverlay"></div>
