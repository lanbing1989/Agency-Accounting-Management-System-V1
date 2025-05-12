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

// 自动升级：检查users表是否有role字段，否则自动添加并赋予admin权限
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

?>