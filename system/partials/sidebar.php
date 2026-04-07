<!-- Sidebar Navigation -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-brand">
    <img src="../logo.jpeg" alt="Logo" class="sidebar-logo" />
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
    <a href="eventos-admin.php" class="sidebar-link <?= basename($_SERVER['PHP_SELF']) === 'eventos-admin.php' ? 'active' : '' ?>">
      <i class="bi bi-calendar-event"></i><span>Eventos</span>
    </a>
  </nav>
  <div class="sidebar-footer">
    <a href="logout.php" class="sidebar-link text-danger">
      <i class="bi bi-box-arrow-left"></i><span>Sair</span>
    </a>
  </div>
</aside>
<div class="sidebar-overlay" id="sidebarOverlay"></div>
