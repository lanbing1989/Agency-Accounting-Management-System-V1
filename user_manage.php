<?php
require 'auth.php';
require 'db.php';

if ($_SESSION['username'] !== 'admin') die('仅管理员可用！');

// 新增用户
if ($_SERVER['REQUEST_METHOD']=='POST' && isset($_POST['add_user'])) {
    $u = trim($_POST['username']);
    $p = $_POST['password'];
    if ($u && $p) {
        $stmt = $db->prepare("INSERT INTO users (username, password) VALUES (?,?)");
        $stmt->bindValue(1, $u);
        $stmt->bindValue(2, password_hash($p, PASSWORD_DEFAULT));
        @$stmt->execute();
    }
}
// 删除用户
if (isset($_GET['del']) && $_GET['del']!=='admin') {
    $stmt = $db->prepare("DELETE FROM users WHERE username=?");
    $stmt->bindValue(1, $_GET['del']);
    $stmt->execute();
}
$users = [];
$res = $db->query("SELECT username FROM users");
while ($row = $res->fetchArray(SQLITE3_ASSOC)) $users[] = $row['username'];
?>
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>用户管理</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="bg-light">
<?php include('navbar.php');?>
<div class="container mt-4">
<h3>用户管理</h3>
<form method="post" class="row g-2 mb-3">
    <div class="col-auto"><input type="text" name="username" class="form-control" placeholder="用户名" required></div>
    <div class="col-auto"><input type="password" name="password" class="form-control" placeholder="密码" required></div>
    <div class="col-auto"><button type="submit" name="add_user" class="btn btn-success">新增用户</button></div>
</form>
<table class="table table-bordered">
    <tr><th>用户名</th><th>操作</th></tr>
    <?php foreach($users as $u):?>
    <tr>
        <td><?=htmlspecialchars($u)?></td>
        <td>
            <?php if($u!=='admin'):?>
            <a href="?del=<?=$u?>" class="btn btn-danger btn-sm" onclick="return confirm('确认删除?')">删除</a>
            <?php else:?>
            <span class="text-muted">系统管理员</span>
            <?php endif;?>
        </td>
    </tr>
    <?php endforeach;?>
</table>
</div>
</body>
</html>