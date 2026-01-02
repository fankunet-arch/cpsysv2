# 数据库结构概览 (DB_SCHEMA)

> 说明：本文件基于 `app/helpers/kds/kds_repo_*.php` 等文件中的 SQL 查询整理，非完整 DDL。如有冲突，以数据库实际结构为准。

## 1. 核心业务表一览

| 表名 | 简要用途 | 相关模块 |
| --- | --- | --- |
| `kds_products` | RMS 产品配方的主表（L1/L3） | RMS |
| `kds_product_recipes` | RMS L1 基础配方（`kds_products` 的子表） | RMS |
| `kds_recipe_adjustments`| RMS L3 特例配方（`kds_products` 的子表） | RMS |
| `kds_global_adjustment_rules` | RMS L2 全局配方规则 | RMS |
| `kds_materials` | 物料字典 | RMS, 库存 |
| `kds_units` | 单位字典 (g, ml, 包, 箱) | RMS, 库存 |
| `kds_cups` | 杯型字典 | RMS |
| `kds_ice_options` | 冰量字典 | RMS |
| `kds_sweetness_options` | 甜度字典 | RMS |
| `pos_menu_items` | POS 商品主表（前端展示） | BMS (POS) |
| `pos_item_variants` | POS 商品规格表（价格） | BMS (POS) |
| `pos_categories` | POS 商品分类 | BMS (POS) |
| `pos_addons` | POS 加料 | BMS (POS) |
| `pos_tags` | POS 标签（用于次卡规则） | BMS (POS), 次卡 |
| `pass_plans` | 会员次卡方案表 | 次卡 |
| `topup_orders` | 会员次卡售卖订单（待审核） | 次卡 |
| `member_passes` | 会员持有的次卡（激活后） | 次卡 |
| `pass_redemption_batches` | 次卡核销批次记录 | 次卡 |
| `pos_members` | 会员信息表 | CRM |
| `pos_member_levels` | 会员等级表 | CRM |
| `pos_promotions` | 营销活动（促销）表 | CRM |
| `pos_point_redemption_rules` | 积分兑换规则表 | CRM |
| `pos_invoices` | 票据主表 | 运营 |
| `pos_invoice_items` | 票据商品详情 | 运营 |
| `pos_shifts` | 门店班次表 | 运营 |
| `expsys_warehouse_stock` | 总仓库存 | 库存 |
| `expsys_store_stock` | 门店库存 | 库存 |
| `kds_material_expiries`| 物料效期追踪表 | 库存, KDS |
| `kds_stores` | 门店信息表 | 系统 |
| `cpsys_users` | HQ 后台用户表 | 系统 |
| `kds_users` | KDS/POS 门店用户表 | 系统 |
| `pos_print_templates` | 打印模板 | 系统 |
| `audit_logs` | 审计日志 (R2) | 系统 |

## 2. 表详情

### 表：kds_products
- **用途**：RMS 配方系统的核心，定义一个“产品”（如“黑糖啵啵”），关联 L1 和 L3 配方。
- **关键字段**：
  - `id`: 主键
  - `product_code`: 产品编码 (P-Code)，**关键业务键**，用于关联 `pos_menu_items`。
  - `status_id`: 状态 ID (关联 `kds_product_statuses`)。
  - `is_active`: (未使用，以 `pos_menu_items.is_active` 为准)。
  - `deleted_at`: 软删除。
- **关联关系**：
  - 1 对多: `kds_products.id` ↔ `kds_product_recipes.product_id` (L1 基础配方)
  - 1 对多: `kds_products.id` ↔ `kds_recipe_adjustments.product_id` (L3 特例)
  - 1 对多: `kds_products.id` ↔ `kds_product_sweetness_options.product_id` (甜度门控)
  - 1 对多: `kds_products.id` ↔ `kds_product_ice_options.product_id` (冰量门控)
  - 1 对 1 (逻辑): `kds_products.product_code` ↔ `pos_menu_items.product_code` (关联 POS 商品)

### 表：kds_materials
- **用途**：定义所有库存和配方中使用的物料。
- **关键字段**：
  - `id`: 主键
  - `material_code`: 物料编码
  - `material_type`: 物料类型 (见下方状态)
  - `base_unit_id`: 基础单位 ID (关联 `kds_units`, e.g., `g` 或 `ml`)
  - `medium_unit_id`: 中级单位 ID (e.g., `包`)
  - `medium_conversion_rate`: 中级换算率 (1 中级 = X 基础)
  - `large_unit_id`: 大单位 ID (e.g., `箱`)
  - `large_conversion_rate`: 大单位换算率 (1 大 = X 中级)
  - `expiry_rule_type`: 效期规则类型 (见下方状态)
  - `expiry_duration`: 效期时长
  - `deleted_at`: 软删除。
- **状态字段说明**：
  - `material_type`: (来自 `material_management_view.php`)
    - `RAW`: 原料
    - `SEMI_FINISHED`: 半成品
    - `PRODUCT`: 成品/直销品
    - `CONSUMABLE`: 耗材
  - `expiry_rule_type`: (来自 `material_management_view.php`)
    - `HOURS`: 按小时
    - `DAYS`: 按天
    - `END_OF_DAY`: 当日有效

### 表：pos_menu_items
- **用途**：POS 机上显示的商品主表。
- **关键字段**：
  - `id`: 主键
  - `product_code`: **关键业务键**，用于关联 `kds_products` (配方)。(注意：在次卡售卖商品中，此字段存的是 `pass_plans.sale_sku`)
  - `pos_category_id`: 关联 `pos_categories.id`。
  - `name_zh`, `name_es`: 双语名称。
  - `is_active`: POS 是否上架。
  - `deleted_at`: 软删除。
- **关联关系**：
  - 1 对多: `pos_menu_items.id` ↔ `pos_item_variants.menu_item_id` (规格/价格)
  - 1 对多 (逻辑): `pos_menu_items.id` ↔ `pos_product_tag_map.product_id` (标签)

### 表：pass_plans
- **用途**：定义一个会员次卡方案。
- **关键字段**：
  - `pass_plan_id`: 主键
  - `name`: 方案名称
  - `total_uses`: 总次数
  - `validity_days`: 有效期（天）
  - `sale_sku`: **关键业务键**，用于关联 `pos_menu_items.product_code`，作为售卖项。
  - `is_active`: 是否可售卖。
- **关联关系**：
  - 1 对 1 (逻辑): `pass_plans.sale_sku` ↔ `pos_menu_items.product_code` (售卖商品)
  - 1 对多: `pass_plans.pass_plan_id` ↔ `topup_orders.pass_plan_id` (售卖订单)

### 表：topup_orders
- **用途**：POS 售卖次卡后生成的待审核订单 (VR - Voucher Review)。
- **关键字段**：
  - `topup_order_id`: 主键
  - `review_status`: 审核状态 (见下方)
  - `member_id`: 购买会员
  - `pass_plan_id`: 购买的方案
  - `store_id`: 售出门店
  - `reviewed_by_user_id`: 审核的 HQ 用户 (关联 `cpsys_users`)
  - `sale_time`: 售卖时间 (UTC)
  - `reviewed_at`: 审核时间 (UTC)
- **状态字段说明**：
  - `review_status`: (来自 `pos_topup_orders_view.php` 和 `handle_topup_order_review`)
    - `PENDING`: 待审核
    - `CONFIRMED` / `APPROVED`: 已批准 (审核通过)
    - `REJECTED`: 已拒绝

### 表：member_passes
- **用途**：会员实际持有的已激活次卡。
- **关键字段**：
  - `member_pass_id`: 主键
  - `member_id`: 持有会员
  - `pass_plan_id`: 关联的方案
  - `topup_order_id`: 来源的售卖订单
  - `total_uses`: 总次数
  - `remaining_uses`: 剩余次数
  - `status`: 状态 (e.g., `active`)
  - `activated_at`: 激活时间
  - `expires_at`: 过期时间
- **关联关系**：
  - 1 对多: `member_passes.member_pass_id` ↔ `pass_redemption_batches.member_pass_id` (核销记录)

### 表：pos_promotions
- **用途**：定义营销活动（自动应用或优惠码）。
- **关键字段**：
  - `id`: 主键
  - `promo_name`: 活动名称
  - `promo_trigger_type`: 触发类型 (见下方)
  - `promo_code`: 优惠码 (如果 `promo_trigger_type` = `COUPON_CODE`)
  - `promo_conditions`: JSON 字符串，定义触发条件
  - `promo_actions`: JSON 字符串，定义优惠动作
  - `promo_is_active`: 是否启用
- **状态字段说明**：
  - `promo_trigger_type`: (来自 `pos_promotion_management_view.php`)
    - `AUTO_APPLY`: 自动应用
    - `COUPON_CODE`: 优惠码