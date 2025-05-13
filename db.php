<?php
$dbFile = __DIR__ . '/accounting.db';
$db = new SQLite3($dbFile);

// 客户（合同）表，client_name 唯一
$db->exec("CREATE TABLE IF NOT EXISTS contracts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    client_name TEXT NOT NULL UNIQUE,
    contact_person TEXT,
    contact_phone TEXT,
    contact_email TEXT,
    remark TEXT
)");

// 服务期表
$db->exec("CREATE TABLE IF NOT EXISTS service_periods (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    contract_id INTEGER NOT NULL,
    service_start TEXT,
    service_end TEXT,
    month_count INTEGER,
    package_type TEXT,
    FOREIGN KEY (contract_id) REFERENCES contracts(id)
)");

// 服务分段表
$db->exec("CREATE TABLE IF NOT EXISTS service_segments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    service_period_id INTEGER NOT NULL,
    start_date TEXT NOT NULL,
    end_date TEXT NOT NULL,
    price_per_year REAL NOT NULL,
    segment_fee REAL NOT NULL,
    package_type TEXT,
    remark TEXT,
    FOREIGN KEY (service_period_id) REFERENCES service_periods(id)
)");

// 收费记录表
$db->exec("CREATE TABLE IF NOT EXISTS payments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    contract_id INTEGER NOT NULL,
    service_segment_id INTEGER,
    pay_date TEXT,
    amount REAL,
    remark TEXT,
    is_temp INTEGER DEFAULT 0,
    FOREIGN KEY (contract_id) REFERENCES contracts(id),
    FOREIGN KEY (service_segment_id) REFERENCES service_segments(id)
)");

// 用户表（登录用），含角色字段
$db->exec("CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT UNIQUE NOT NULL,
    password TEXT NOT NULL,
    role TEXT DEFAULT 'user'
)");

// 工商年报登记表
$db->exec("CREATE TABLE IF NOT EXISTS annual_reports (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    contract_id INTEGER NOT NULL,
    year INTEGER NOT NULL,
    reported_at TEXT,
    FOREIGN KEY (contract_id) REFERENCES contracts(id)
)");

// 税务申报登记表，带独立remark列
$db->exec("CREATE TABLE IF NOT EXISTS tax_declare_records (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    contract_id INTEGER NOT NULL,
    declare_period TEXT NOT NULL,
    ele_tax_reported_at TEXT,
    personal_tax_reported_at TEXT,
    operator TEXT,
    remark TEXT,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (contract_id) REFERENCES contracts(id)
)");

// 合同模板表
$db->exec("CREATE TABLE IF NOT EXISTS contract_templates (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    content TEXT,
    created_at TEXT
)");

// 签章模板表（全局签章管理）
$db->exec("CREATE TABLE IF NOT EXISTS seal_templates (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT,
    image_path TEXT,
    created_at TEXT
)");

// 合同实例表（每个合同记录）
// 新增 sign_date 字段，记录签署日期
// 新增 content_snapshot 字段，记录快照内容
$db->exec("CREATE TABLE IF NOT EXISTS contracts_agreement (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    uuid TEXT UNIQUE, -- 新增的唯一标识
    client_id INTEGER,
    template_id INTEGER,
    seal_id INTEGER,
    sign_status TEXT DEFAULT '',
    sign_image TEXT,
    sign_time TEXT,
    created_at TEXT,
    sign_date TEXT,
    service_period_id INTEGER,
    service_segment_id INTEGER,
    content_snapshot TEXT, -- 新增快照字段
    FOREIGN KEY(client_id) REFERENCES contracts(id),
    FOREIGN KEY(template_id) REFERENCES contract_templates(id),
    FOREIGN KEY(seal_id) REFERENCES seal_templates(id),
    FOREIGN KEY(service_period_id) REFERENCES service_periods(id),
    FOREIGN KEY(service_segment_id) REFERENCES service_segments(id)
)");

// 自动升级：users表是否有role字段，否则自动添加
$res = $db->query("PRAGMA table_info(users)");
$has_role = false;
while ($col = $res->fetchArray(SQLITE3_ASSOC)) {
    if ($col['name'] === 'role') {
        $has_role = true;
        break;
    }
}
if (!$has_role) {
    $db->exec("ALTER TABLE users ADD COLUMN role TEXT DEFAULT 'user'");
    $db->exec("UPDATE users SET role='admin' WHERE username='admin'");
}

// 自动升级：tax_declare_records表检查remark字段
$res2 = $db->query("PRAGMA table_info(tax_declare_records)");
$has_remark = false;
while ($col = $res2->fetchArray(SQLITE3_ASSOC)) {
    if ($col['name'] === 'remark') {
        $has_remark = true;
        break;
    }
}
if (!$has_remark) {
    $db->exec("ALTER TABLE tax_declare_records ADD COLUMN remark TEXT");
}

// 自动升级：contracts_agreement表检查sign_date字段
$res3 = $db->query("PRAGMA table_info(contracts_agreement)");
$has_sign_date = false;
while ($col = $res3->fetchArray(SQLITE3_ASSOC)) {
    if ($col['name'] === 'sign_date') {
        $has_sign_date = true;
        break;
    }
}
if (!$has_sign_date) {
    $db->exec("ALTER TABLE contracts_agreement ADD COLUMN sign_date TEXT");
}

// 自动升级：contracts_agreement表检查service_period_id、service_segment_id字段
$res4 = $db->query("PRAGMA table_info(contracts_agreement)");
$has_period = false;
$has_segment = false;
while ($col = $res4->fetchArray(SQLITE3_ASSOC)) {
    if ($col['name'] === 'service_period_id') $has_period = true;
    if ($col['name'] === 'service_segment_id') $has_segment = true;
}
if (!$has_period) {
    $db->exec("ALTER TABLE contracts_agreement ADD COLUMN service_period_id INTEGER");
}
if (!$has_segment) {
    $db->exec("ALTER TABLE contracts_agreement ADD COLUMN service_segment_id INTEGER");
}

// 自动升级：contracts_agreement表检查content_snapshot字段
$res5 = $db->query("PRAGMA table_info(contracts_agreement)");
$has_snapshot = false;
while ($col = $res5->fetchArray(SQLITE3_ASSOC)) {
    if ($col['name'] === 'content_snapshot') {
        $has_snapshot = true;
        break;
    }
}
if (!$has_snapshot) {
    $db->exec("ALTER TABLE contracts_agreement ADD COLUMN content_snapshot TEXT");
}

// 检查是否已存在用户，无则插入初始管理员 admin/123456
$userCheck = $db->querySingle("SELECT COUNT(*) FROM users");
if ($userCheck == 0) {
    $admin = 'admin';
    $pass = password_hash('123456', PASSWORD_DEFAULT);
    $stmt = $db->prepare("INSERT INTO users (username, password, role) VALUES (:u,:p,'admin')");
    $stmt->bindValue(':u', $admin, SQLITE3_TEXT);
    $stmt->bindValue(':p', $pass, SQLITE3_TEXT);
    $stmt->execute();
}
?>