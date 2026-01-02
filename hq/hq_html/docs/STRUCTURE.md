# 系统结构概览 (STRUCTURE)

## 1. 根目录与核心
- 项目根目录：`hq/hq_html/`
- 主要子目录说明：
  - `app/`: 核心业务逻辑与视图。
    - `app/helpers/`: 数据仓库（Models）和辅助函数。
    - `app/helpers/kds/`: **关键数据逻辑**，所有 `kds_repo_*.php` 文件都在此，用于数据库查询。
    - `app/views/cpsys/`: 所有后台页面的 PHP 视图文件。
  - `core/`: 框架核心。
    - `core/config.php`: 数据库连接、全局路径、时区（UTC）定义。
    - `core/auth_core.php`: 登录会话检查。
    - `core/api_core.php`: 核心 API 引擎 `run_api()`。
  - `html/cpsys/`: Web 公共访问目录 (Web Root)。
    - `html/cpsys/index.php`: **页面主入口**，通过 `$_GET['page']` 加载 `app/views/cpsys/` 中的视图。
    - `html/cpsys/api/`: API 接口目录。
    - `html/cpsys/api/cpsys_api_gateway.php`: **全局 API 网关**，所有 AJAX 请求的唯一入口。
    - `html/cpsys/api/registries/`: API 资源注册表，定义 API 路由和处理器。
    - `html/cpsys/js/`: 对应各个页面的 JavaScript 文件。

## 2. 业务模块一览

### 模块：RMS (Recipe Management System - 配方管理)
- **功能概述**：管理 KDS 使用的物料、配方和相关字典数据。
- **目录路径**：
  - 后端 (API 定义): `html/cpsys/api/registries/cpsys_registry_rms.php`
  - 后端 (API 处理器): `html/cpsys/api/registries/cpsys_registry_rms_handlers.php`
  - 后端 (数据模型): `app/helpers/kds/kds_repo_b.php` (配方), `kds_repo_c.php` (物料/字典)
- **典型入口**：
  - 前端页面: `app/views/cpsys/rms/rms_product_management_view.php` (配方 L1/L3)
  - 前端页面: `app/views/cpsys/rms/rms_global_rules_view.php` (配方 L2)
  - 前端页面: `app/views/cpsys/material_management_view.php` (物料管理)
  - 前端 JS: `html/cpsys/js/rms/rms_product_management.js`
- **主要 API 资源 (`res=...`)**：
  - `rms_products`: KDS 产品配方（L1/L3）
  - `rms_global_rules`: 全局配方规则（L2）
  - `materials`: 物料
  - `units`, `cups`, `ice_options`, `sweetness_options`: 字典数据

### 模块：BMS (Business Management System - POS 业务管理)
- **功能概述**：管理 POS 机上显示的菜单、商品、价格、分类和加料。
- **目录路径**：
  - 后端 (API 定义): `html/cpsys/api/registries/cpsys_registry_bms.php`
  - 后端 (API 处理器): `html/cpsys/api/registries/cpsys_registry_bms_menu_a.php`
  - 后端 (数据模型): `app/helpers/kds/kds_repo_b.php`, `kds_repo_c.php`
- **典型入口**：
  - 前端页面: `app/views/cpsys/pos_menu_management_view.php` (商品管理)
  - 前端页面: `app/views/cpsys/pos_variants_management_view.php` (规格管理)
  - 前端页面: `app/views/cpsys/pos_category_management_view.php` (分类管理)
  - 前端页面: `app/views/cpsys/pos_addon_management_view.php` (加料管理)
  - 前端 JS: `html/cpsys/js/pos_menu_management.js`
- **主要 API 资源 (`res=...`)**：
  - `pos_menu_items`: POS 商品
  - `pos_item_variants`: POS 商品规格
  - `pos_categories`: POS 分类
  - `pos_addons`: POS 加料

### 模块：CRM (会员、积分与营销)
- **功能概述**：管理会员信息、等级、积分规则和营销活动（促销）。
- **目录路径**：
  - 后端 (API 定义): `html/cpsys/api/registries/cpsys_registry_bms.php`
  - 后端 (API 处理器): `..._bms_member.php` (会员), `..._bms_menu_b.php` (促销)
  - 后端 (数据模型): `app/helpers/kds/kds_repo_a.php`
- **典型入口**：
  - 前端页面: `app/views/cpsys/pos_member_management_view.php` (会员管理)
  - 前端页面: `app/views/cpsys/pos_member_level_management_view.php` (会员等级)
  - 前端页面: `app/views/cpsys/pos_point_redemption_rules_view.php` (积分兑换)
  - 前端页面: `app/views/cpsys/pos_promotion_management_view.php` (营销活动)
  - 前端 JS: `html/cpsys/js/pos_member_management.js`, `pos_promotion_management.js`
- **主要 API 资源 (`res=...`)**：
  - `pos_members`: 会员
  - `pos_member_levels`: 会员等级
  - `pos_promotions`: 营销活动
  - `pos_redemption_rules`: 积分兑换规则

### 模块：会员次卡 (Seasons Pass)
- **功能概述**：BMS 的一个子模块，用于创建、售卖、审核和查询会员次卡。
- **目录路径**：
  - 后端 (API 定义): `html/cpsys/api/registries/cpsys_registry_bms.php`
  - 后端 (API 处理器): `..._bms_pass_plan.php` (方案管理), `..._bms_member.php` (订单审核)
  - 后端 (数据模型): `app/helpers/kds/kds_repo_c.php`, `kds_repo_c_reports.php`
- **典型入口**：
  - 前端页面: `app/views/cpsys/pos_pass_plan_management_view.php` (P - 方案管理)
  - 前端页面: `app/views/cpsys/pos_topup_orders_view.php` (B1 - 售卡审核)
  - 前端页面: `app/views/cpsys/pos_redemptions_view.php` (B2 - 核销查询)
  - 前端页面: `app/views/cpsys/pos_seasons_pass_dashboard_view.php` (B3 - 看板)
  - 前端 JS: `html/cpsys/js/pos_pass_plan_management.js`, `pos_topup_orders.js`
- **主要 API 资源 (`res=...`)**：
  - `pos_pass_plans`: 次卡方案 (CRUD)
  - `topup_orders`: 售卡订单 (审核 `review` 动作)
  - `pos_tags`: 次卡规则依赖的标签

### 模块：库存与效期 (Stock & Expiry)
- **功能概述**：管理总仓库存、门店库存、库存调拨和物料效期。
- **目录路径**：
  - 后端 (API 定义): `html/cpsys/api/registries/cpsys_registry_rms.php`
  - 后端 (API 处理器): `..._rms_handlers.php` (Handler: `cprms_stock_actions`)
  - 后端 (数据模型): `app/helpers/kds/kds_repo_c_reports.php`
- **典型入口**：
  - 前端页面: `app/views/cpsys/warehouse_stock_management_view.php` (总仓)
  - 前端页面: `app/views/cpsys/stock_allocation_view.php` (调拨)
  - 前端页面: `app/views/cpsys/store_stock_view.php` (门店库存)
  - 前端页面: `app/views/cpsys/expiry_management_view.php` (效期)
  - 前端 JS: `html/cpsys/js/warehouse_stock_logic.js`, `stock_allocation.js`
- **主要 API 资源 (`res=...`)**：
  - `stock`: 库存操作 (Action: `add_warehouse_stock`, `allocate_to_store`)

### 模块：运营与财务 (Operations)
- **功能概述**：查看票据、日结报告、复核异常班次。
- **目录路径**：
  - 后端 (API 定义): `html/cpsys/api/registries/cpsys_registry_bms.php`
  - 后端 (API 处理器): `..._bms_menu_b.php` (Handlers: `handle_invoice_cancel`, `handle_shift_review`)
  - 后端 (数据模型): `app/helpers/kds/kds_repo_c_reports.php`
- **典型入口**：
  - 前端页面: `app/views/cpsys/pos_invoice_list_view.php` (票据列表)
  - 前端页面: `app/views/cpsys/pos_invoice_detail_view.php` (票据详情)
  - 前端页面: `app/views/cpsys/pos_eod_reports_view.php` (日结报告)
  - 前端页面: `app/views/cpsys/pos_shift_review_view.php` (班次复核)
  - 前端 JS: `html/cpsys/js/pos_invoice_management.js`, `pos_shift_review.js`
- **主要 API 资源 (`res=...`)**：
  - `invoices`: 票据操作 (Action: `cancel`, `correct`)
  - `shifts`: 班次操作 (Action: `review`)

### 模块：系统管理 (System)
- **功能概述**：管理 HQ 用户、门店、KDS 用户、打印模板等。
- **目录路径**：
  - 后端 (API 定义): `html/cpsys/api/registries/cpsys_registry_base.php`
  - 后端 (API 处理器): `..._base_core.php`
  - 后端 (数据模型): `app/helpers/kds/kds_repo_a.php`
- **典型入口**：
  - 前端页面: `app/views/cpsys/user_management_view.php` (HQ 用户)
  - 前端页面: `app/views/cpsys/store_management_view.php` (门店)
  - 前端页面: `app/views/cpsys/kds_user_management_view.php` (KDS 用户)
  - 前端页面: `app/views/cpsys/pos_print_template_management_view.php` (打印模板)
- **主要 API 资源 (`res=...`)**：
  - `users`: HQ 用户
  - `stores`: 门店
  - `kds_users`: KDS 用户
  - `print_templates`: 打印模板
  - `kds_sop_rules`: KDS 解析规则