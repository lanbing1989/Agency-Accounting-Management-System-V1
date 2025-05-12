<?php
$dbFile = __DIR__ . '/accounting.db';
$db = new SQLite3($dbFile);

// 客户（合同）表
$db->exec("CREATE TABLE IF NOT EXISTS contracts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    client_name TEXT NOT NULL,
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

// 用户表（登录用）
$db->exec("CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT UNIQUE NOT NULL,
    password TEXT NOT NULL
)");

// 检查是否已存在用户，无则插入初始管理员
$userCheck = $db->querySingle("SELECT COUNT(*) FROM users");
if ($userCheck == 0) {
    // 默认账号 admin/123456
    $admin = 'admin';
    $pass = password_hash('123456', PASSWORD_DEFAULT);
    $stmt = $db->prepare("INSERT INTO users (username, password) VALUES (:u,:p)");
    $stmt->bindValue(':u', $admin, SQLITE3_TEXT);
    $stmt->bindValue(':p', $pass, SQLITE3_TEXT);
    $stmt->execute();
}
?>