<?php
require 'auth.php';
require 'db.php';

// 客户列表
$customers = [];
$res = $db->query("SELECT id, client_name FROM contracts ORDER BY client_name");
while($row = $res->fetchArray(SQLITE3_ASSOC)) $customers[] = $row;

$contract_id = isset($_GET['contract_id']) ? intval($_GET['contract_id']) : 0;

// 新增临时收费处理
if ($_SERVER['REQUEST_METHOD']=='POST') {
    $contract_id_post = intval($_POST['contract_id']);
    $pay_date = $_POST['pay_date'];
    $amount = floatval($_POST['amount']);
    $remark = $_POST['remark'];
    $stmt = $db->prepare("INSERT INTO payments (contract_id, pay_date, amount, remark, is_temp) VALUES (:contract_id, :pay_date, :amount, :remark, 1)");
    $stmt->bindValue(':contract_id', $contract_id_post);
    $stmt->bindValue(':pay_date', $pay_date);
    $stmt->bindValue(':amount', $amount);
    $stmt->bindValue(':remark', $remark);
    $stmt->execute();
    header('Location: temp_payment.php'.($contract_id_post ? '?contract_id='.$contract_id_post : ''));
    exit;
}

// 删除临时收费处理
if (isset($_GET['del']) && is_numeric($_GET['del'])) {
    $del_id = intval($_GET['del']);
    $del_contract_id = isset($_GET['contract_id']) ? intval($_GET['contract_id']) : 0;
    $db->exec("DELETE FROM payments WHERE id=$del_id AND is_temp=1");
    header('Location: temp_payment.php'.($del_contract_id ? '?contract_id='.$del_contract_id : ''));
    exit;
}

// 临时收费列表
if ($contract_id) {
    // 只显示该客户的
    $payments = $db->query("
        SELECT p.*, c.client_name 
        FROM payments p 
        JOIN contracts c ON c.id=p.contract_id 
        WHERE p.is_temp=1 AND p.contract_id=$contract_id
        ORDER BY p.pay_date DESC, p.id DESC
    ");
} else {
    // 显示所有客户的
    $payments = $db->query("
        SELECT p.*, c.client_name 
        FROM payments p 
        JOIN contracts c ON c.id=p.contract_id 
        WHERE p.is_temp=1 
        ORDER BY p.pay_date DESC, p.id DESC
    ");
}

// 客户名->id映射js
$customer_json = json_encode($customers);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>临时收费管理</title>
    <link href="/bootstrap/css/bootstrap.min.css" rel="stylesheet">
	<script src="/bootstrap/js/bootstrap.bundle.min.js"></script>
</head>
<body>
<?php include('navbar.php'); ?>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">临时收费管理</h2>
        <?php if($contract_id): ?>
            <a href="contract_detail.php?id=<?=$contract_id?>" class="btn btn-sm btn-secondary">返回客户详情</a>
        <?php endif; ?>
    </div>
    <form method="post" class="bg-white p-4 rounded shadow-sm mb-4" id="form-temp-pay">
        <div class="row g-2 align-items-center">
            <div class="col-auto">
                <label class="form-label mb-0">客户</label>
                <input class="form-control" list="customer_list" id="customer_input" autocomplete="off" placeholder="输入客户名搜索" value="<?php
                    if($contract_id){
                        foreach($customers as $c){
                            if($c['id']==$contract_id) echo htmlspecialchars($c['client_name']);
                        }
                    }
                ?>" <?= $contract_id ? 'readonly' : '' ?>>
                <datalist id="customer_list">
                    <?php foreach($customers as $c): ?>
                        <option value="<?=htmlspecialchars($c['client_name'])?>">
                    <?php endforeach;?>
                </datalist>
                <input type="hidden" name="contract_id" id="contract_id" value="<?=$contract_id?>">
            </div>
            <div class="col-auto">
                <label class="form-label mb-0">收费日期</label>
                <input type="date" class="form-control" name="pay_date" value="<?=date('Y-m-d')?>" required>
            </div>
            <div class="col-auto">
                <label class="form-label mb-0">金额</label>
                <input type="number" class="form-control" name="amount" step="0.01" required>
            </div>
            <div class="col-auto">
                <label class="form-label mb-0">备注</label>
                <input type="text" class="form-control" name="remark">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-success">新增临时收费</button>
            </div>
        </div>
    </form>
    <h4>历史临时收费</h4>
    <table class="table table-bordered table-hover bg-white">
        <thead class="table-light">
        <tr>
            <th>ID</th>
            <th>客户</th>
            <th>收费日期</th>
            <th>金额</th>
            <th>备注</th>
            <th>操作</th>
        </tr>
        </thead>
        <tbody>
        <?php while($p = $payments->fetchArray(SQLITE3_ASSOC)): ?>
        <tr>
            <td><?=$p['id']?></td>
            <td><?=htmlspecialchars($p['client_name'])?></td>
            <td><?=$p['pay_date']?></td>
            <td><?=$p['amount']?></td>
            <td><?=htmlspecialchars($p['remark'])?></td>
            <td>
                <a href="temp_payment.php?del=<?=$p['id']?><?=($contract_id?'&contract_id='.$contract_id:'')?>" class="btn btn-sm btn-danger" onclick="return confirm('确认删除？')">删除</a>
            </td>
        </tr>
        <?php endwhile;?>
        </tbody>
    </table>
</div>
<script>
const customers = <?=$customer_json?>;
document.getElementById('form-temp-pay').addEventListener('submit', function(e) {
    <?php if(!$contract_id): ?>
    // 将输入的客户名转为id
    const inputName = document.getElementById('customer_input').value.trim();
    const found = customers.find(c => c.client_name === inputName);
    if (!found) {
        alert('客户不存在，请选择正确的客户');
        e.preventDefault();
        return false;
    }
    document.getElementById('contract_id').value = found.id;
    <?php endif; ?>
});
</script>
</body>
</html>