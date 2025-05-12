# 代理记账业务管理系统

本系统为中小代理记账公司开发，致力于帮助企业实现客户合同、服务期、分段、周期性收费和临时收费的高效管理，并提供到期提醒和催收提醒等辅助功能。系统基于 PHP + SQLite3，无需部署数据库服务器，开箱即用！

## 功能简介

- **客户管理**：客户信息增删改查。
- **服务期管理**：支持客户多服务期、服务分段，适合价格变更、补差、续费等场景。
- **分段管理**：每个服务期可灵活拆分多个分段，分段可设置独立年费、套餐、备注。
- **收费管理**：周期性收费与临时收费分开管理，自动统计已收/待收金额，防止超额收款。
- **临时收费**：支持录入预付款、杂费等临时收款，关联客户并可在客户详情中查看。
- **到期提醒**：提前预警服务期即将到期客户，支持自定义提醒天数。
- **催收提醒**：自动筛查所有“未收全款”的服务期，便于及时催收。
- **权限控制**：必须登录才能访问，默认账号admin，密码123456
- **数据安全**：所有数据本地化，无需外部依赖。

## 快速部署

1. **环境要求**
   - PHP 7.4+（建议 8.x），需启用 SQLite3 扩展
   - 支持常见 Web 服务器（Apache/Nginx/IIS/自带 PHP 内置服务器等）

2. **部署步骤**
   - 将所有 PHP 文件和 `db.php` 放在同一目录。
   - 目录需有写权限，首次访问会自动创建 `accounting.db` 数据库文件。
   - 直接访问 `index.php` 即可使用。

3. **数据库说明**
   - 数据库存储于 `accounting.db`，含主要数据表：`contracts`、`service_periods`、`service_segments`、`payments`。
   - 可用 SQLite 工具备份/导出/分析数据。

## 目录结构

```
/index.php               # 客户列表页
/contract_add.php        # 新增客户
/contract_edit.php       # 编辑客户
/contract_delete.php     # 删除客户
/contract_detail.php     # 客户详情/服务期/临时收费
/service_period_add.php  # 新增/续费服务期
/service_period_delete.php # 删除服务期
/segment_add.php         # 服务期分段调整
/payment_list.php        # 周期性收费记录
/payment_add.php         # 新增周期性收费
/payment_delete.php      # 删除周期性收费
/temp_payment.php        # 临时收费录入与管理
/expire_remind.php       # 到期提醒
/remind_list.php         # 催收提醒
/db.php                  # 数据库及表结构初始化
/navbar.php              # 通用导航栏
/accounting.db           # SQLite数据库（自动生成）
```

## 开发文档

### 1. 数据模型

- `contracts` 客户/合同表
  - `id` 客户ID
  - `client_name` 客户名
  - `contact_person`/`contact_phone`/`contact_email`/`remark`

- `service_periods` 服务期
  - `id`, `contract_id`, `service_start`, `service_end`, `month_count`, `package_type`

- `service_segments` 分段
  - `id`, `service_period_id`, `start_date`, `end_date`, `price_per_year`, `segment_fee`, `package_type`, `remark`

- `payments` 收费记录
  - `id`, `contract_id`, `service_segment_id`, `pay_date`, `amount`, `remark`, `is_temp`
  - `is_temp=1`为临时收费，`is_temp=0`为周期性收费

### 2. 主要业务逻辑

- **合同金额** = 服务期下所有分段的金额之和
- **周期性收费**：只允许录入至不超过合同金额，系统自动判断并禁止超额
- **催收提醒**：自动筛查所有未收全款的服务期
- **到期提醒**：只提醒未被续期的服务期，支持自定义天数

### 3. 页面说明

- 客户详情页展示周期性服务期（及分段）、临时收费记录
- 服务期分段可灵活拆分，适合补差、价格调整
- 所有“新增收费”页面严格校验总收费金额不得超合同金额
- 临时收费可全局管理，也可在单一客户下录入和查看

### 4. 常见自定义扩展

- 可扩展套餐类型、分段备注等字段
- 如需导出数据，可用 SQLite 工具操作 `accounting.db`
- 如需用户登录权限管理，建议在现有页面加 session 校验

## 常见问题

- **Q：如何重置/备份数据？**  
  直接备份/替换 `accounting.db` 文件即可。

- **Q：支持多人协作吗？**  
  本地 SQLite 适合单用户/小团队，若并发需求高建议迁移至 MySQL 并适配 SQL 语句。

- **Q：如何添加其它字段？**  
  修改相关表结构和页面即可，代码结构清晰，易于扩展。

---

## 联系与支持

如有问题或定制需求，请联系开发者。
