<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
  <div class="container">
    <a class="navbar-brand" href="index.php">代理记账管理</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="index.php">客户列表</a></li>
        <li class="nav-item"><a class="nav-link" href="contract_add.php">新增客户</a></li>
        <li class="nav-item"><a class="nav-link" href="expire_remind.php">到期提醒</a></li>
        <li class="nav-item"><a class="nav-link" href="remind_list.php">催收提醒</a></li>
        <li class="nav-item"><a class="nav-link" href="temp_payment.php">临时收费</a></li>
		<li class="nav-item"><a class="nav-link" href="tax_report.php">报税登记</a></li>
		<li class="nav-item"><a class="nav-link" href="annual_report.php">年报登记</a></li>
		<li class="nav-item"><a class="nav-link" href="user_profile.php">修改密码</a></li>
		<?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
		<li class="nav-item"><a class="nav-link" href="user_manage.php">用户管理</a></li>
		<li class="nav-item"><a class="nav-link" href="export_all_data.php">导出数据</a></li>
    <?php endif; ?>
        <li class="nav-item"><a class="nav-link" href="logout.php">退出</a></li>
      </ul>
    </div>
  </div>
</nav>