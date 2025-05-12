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

// 收费记录表（is_temp=1为临时收费）
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
?>