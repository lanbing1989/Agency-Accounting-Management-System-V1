<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
  <div class="container">
    <a class="navbar-brand" href="index.php">代理记账业务管理系统</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <!-- 客户管理 -->
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="customerDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            客户管理
          </a>
          <ul class="dropdown-menu" aria-labelledby="customerDropdown">
            <li><a class="dropdown-item" href="index.php">客户列表</a></li>
            <li><a class="dropdown-item" href="contract_add.php">新增客户</a></li>
          </ul>
        </li>
        <!-- 提醒通知 -->
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="remindDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            提醒通知
          </a>
          <ul class="dropdown-menu" aria-labelledby="remindDropdown">
            <li><a class="dropdown-item" href="expire_remind.php">到期提醒</a></li>
            <li><a class="dropdown-item" href="remind_list.php">催收提醒</a></li>
          </ul>
        </li>
        <!-- 申报标记 -->
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="reportDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            申报标记
          </a>
          <ul class="dropdown-menu" aria-labelledby="reportDropdown">
            <li><a class="dropdown-item" href="tax_report.php">报税登记</a></li>
            <li><a class="dropdown-item" href="annual_report.php">年报登记</a></li>
          </ul>
        </li>
        <!-- 电子合同 -->
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="contractDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            电子合同
          </a>
          <ul class="dropdown-menu" aria-labelledby="contractDropdown">
            <li><a class="dropdown-item" href="ht_agreements.php">合同管理</a></li>
            <li><a class="dropdown-item" href="ht_contract_templates.php">合同模板</a></li>
            <li><a class="dropdown-item" href="ht_seal_templates.php">签章管理</a></li>
          </ul>
        </li>
        <!-- 其它 -->
        <li class="nav-item"><a class="nav-link" href="temp_payment.php">临时收费</a></li>
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