<?php
// 升级 tax_declare_records 表，增加 remark 字段（若不存在）
$dbFile = __DIR__ . '/accounting.db';
$db = new SQLite3($dbFile);

// 检查 remark 是否已存在
$exists = false;
$res = $db->query("PRAGMA table_info(tax_declare_records)");
while ($col = $res->fetchArray(SQLITE3_ASSOC)) {
    if ($col['name'] === 'remark') {
        $exists = true;
        break;
    }
}

if (!$exists) {
    $db->exec("ALTER TABLE tax_declare_records ADD COLUMN remark TEXT");
    echo "已成功添加 remark 字段。\n";
} else {
    echo "remark 字段已存在，无需升级。\n";
}
?>