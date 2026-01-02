# 后端接口总览 (API_OVERVIEW)

> 说明：系统使用统一 API 网关 `html/cpsys/api/cpsys_api_gateway.php`。
> 所有请求均使用 `GET` 或 `POST` 方法，通过 `res` (资源) 和 `act` (动作) 两个 Query 参数进行路由。

## 模块：System (系统管理 & 核心字典)
- **API 注册表**: `html/cpsys/api/registries/cpsys_registry_base.php`
- **处理器目录**: `..._base_core.php`, `..._base_dicts.php`

### 接口：保存 HQ 用户
- **方法 + 路径**: `POST /api/cpsys_api_gateway.php?res=users&act=save`
- **后端处理**:
  - 处理器: `handle_user_save` (位于 `..._base_core.php`)
- **请求参数 (JSON Body)**:
  - `data` (object):
    - `id` (int, 可选): 用户 ID (用于更新)
    - `username` (string): 用户名
    - `display_name` (string): 显示名称
    - `password` (string, 可选): 新密码 (使用 Bcrypt 加密)
    - `role_id` (int): 角色 ID
    - `is_active` (int): 1 或 0

### 接口：保存门店
- **方法 + 路径**: `POST /api/cpsys_api_gateway.php?res=stores&act=save`
- **后端处理**:
  - 处理器: `handle_store_save` (位于 `..._base_core.php`)
- **请求参数 (JSON Body)**:
  - `data` (object):
    - `id` (int, 可选): 门店 ID (用于更新)
    - `store_code` (string): 门店码
    - `store_name` (string): 门店名称
    - `invoice_prefix` (string): 票号前缀 (e.g., S1)
    - `tax_id` (string): 税号 (NIF)
    - `billing_system` (string): 票据系统 (`TICKETBAI`, `VERIFACTU`, `NONE`)
    - `pr_receipt_type` (string): 小票打印机类型 (`WIFI`, `BLUETOOTH`, `USB`, `NONE`)
    - `pr_receipt_ip` (string, 可选): IP 地址
    - (更多打印机字段...)

### 接口：保存物料单位
- **方法 + 路径**: `POST /api/cpsys_api_gateway.php?res=units&act=save`
- **后端处理**:
  - 处理器: `handle_unit_save` (位于 `..._base_dicts.php`)
- **请求参数 (JSON Body)**:
  - `data` (object):
    - `id` (int, 可选): 单位 ID (用于更新)
    - `unit_code` (string): 编码
    - `name_zh` (string): 中文名
    - `name_es` (string): 西语名
- **审计**: 此接口会调用 `log_audit_action` 记录 `rms.unit.create` 或 `rms.unit.update`。

---

## 模块：BMS (POS 菜单 & 业务)
- **API 注册表**: `html/cpsys/api/registries/cpsys_registry_bms.php`
- **处理器目录**: `..._bms_menu_a.php`, `..._bms_menu_b.php`, `..._bms_member.php`

### 接口：保存 POS 商品
- **方法 + 路径**: `POST /api/cpsys_api_gateway.php?res=pos_menu_items&act=save`
- **后端处理**:
  - 处理器: `handle_menu_item_save` (位于 `..._bms_menu_a.php`)
- **请求参数 (JSON Body)**:
  - `data` (object):
    - `id` (int, 可选): 商品 ID
    - `pos_category_id` (int): 分类 ID
    - `name_zh` (string): 中文名
    - `name_es` (string): 西语名
    - `is_active` (int): 1 或 0
    - `tag_ids` (array): 关联的标签 ID 数组 (用于次卡)
- **审计**: 调用 `log_audit_action` 记录 `rms.product.create` / `update`。

### 接口：保存 POS 商品规格
- **方法 + 路径**: `POST /api/cpsys_api_gateway.php?res=pos_item_variants&act=save`
- **后端处理**:
  - 处理器: `handle_variant_save` (位于 `..._bms_menu_a.php`)
- **请求参数 (JSON Body)**:
  - `data` (object):
    - `id` (int, 可选): 规格 ID
    - `menu_item_id` (int): 所属商品 ID
    - `variant_name_zh` (string): 规格中文名
    - `price_eur` (float): 价格
    - `product_id` (int): 关联的 RMS 配方 ID (`kds_products.id`)
    - `is_default` (int): 1 或 0
- **特殊逻辑**: 保存时会根据 `product_id` 反查 `kds_products.product_code` 并更新 `pos_menu_items.product_code`。

### 接口：保存营销活动
- **方法 + 路径**: `POST /api/cpsys_api_gateway.php?res=pos_promotions&act=save`
- **后端处理**:
  - 处理器: `handle_promo_save` (位于 `..._bms_menu_b.php`)
- **请求参数 (JSON Body)**:
  - `data` (object):
    - `id` (int, 可选): 活动 ID
    - `promo_name` (string): 活动名称
    - `promo_trigger_type` (string): `AUTO_APPLY` 或 `COUPON_CODE`
    - `promo_code` (string, 可选): 优惠码
    - `promo_conditions` (array): 条件 JSON 对象数组
    - `promo_actions` (array): 动作 JSON 对象数组

### 接口：审核次卡售卖订单 (VR)
- **方法 + 路径**: `POST /api/cpsys_api_gateway.php?res=topup_orders&act=review`
- **后端处理**:
  - 处理器: `handle_topup_order_review` (位于 `..._bms_member.php`)
- **请求参数 (JSON Body)**:
  - `order_id` (int): 待审核的 `topup_orders` ID
  - `action` (string): `APPROVE` 或 `REJECT`
- **特殊逻辑**:
  - `APPROVE` 时: 调用 `activate_member_pass` 助手，更新 `topup_orders.review_status` 为 'confirmed'，并在 `member_passes` 表中插入或更新会员次卡。
  - `REJECT` 时: 更新 `topup_orders.review_status` 为 'rejected'。

### 接口：复核异常班次
- **方法 + 路径**: `POST /api/cpsys_api_gateway.php?res=shifts&act=review`
- **后端处理**:
  - 处理器: `handle_shift_review` (位于 `..._bms_menu_b.php`)
- **请求参数 (JSON Body)**:
  - `shift_id` (int): 班次 ID
  - `counted_cash` (float): 手动清点的现金金额
- **特殊逻辑**: 更新 `pos_shifts` 表，将 `status` = `FORCE_CLOSED` 且 `admin_reviewed` = 0 的记录，更新 `counted_cash` 和 `cash_variance`，并设置 `admin_reviewed` = 1。

---

## 模块：RMS (配方 & 库存)
- **API 注册表**: `html/cpsys/api/registries/cpsys_registry_rms.php`
- **处理器目录**: `..._rms_handlers.php`

### 接口：保存物料
- **方法 + 路径**: `POST /api/cpsys_api_gateway.php?res=materials&act=save`
- **后端处理**:
  - 处理器: `cprms_material_save` (位于 `..._rms_handlers.php`)
- **请求参数 (JSON Body)**:
  - `data` (object):
    - `id` (int, 可选): 物料 ID
    - `material_code` (string): 编码
    - `material_type` (string): `RAW`, `SEMI_FINISHED` 等
    - `name_zh` (string): 中文名
    - `name_es` (string): 西语名
    - `base_unit_id` (int): 基础单位 ID
    - `medium_unit_id` (int, 可选): 中级单位 ID
    - `medium_conversion_rate` (float, 可选): 中级换算率
    - `large_unit_id` (int, 可选): 大单位 ID
    - `large_conversion_rate` (float, 可选): 大单位换算率
- **审计**: 调用 `log_audit_action` 记录 `rms.material.create` / `update`。

### 接口：保存 RMS 配方
- **方法 + 路径**: `POST /api/cpsys_api_gateway.php?res=rms_products&act=save_product`
- **后端处理**:
  - 处理器: `cprms_product_save` (位于 `..._rms_handlers.php`)
- **请求参数 (JSON Body)**:
  - `product` (object): 包含所有配方信息的完整 JSON 对象。
    - `id` (int, 可选): `kds_products.id`
    - `product_code` (string): P-Code
    - `name_zh` (string): 中文名
    - `allowed_sweetness_ids` (array): [1, 2, 3]
    - `allowed_ice_ids` (array): [1, 2]
    - `base_recipes` (array): L1 基础配方对象数组
    - `adjustments` (array): L3 特例规则对象数组
- **特殊逻辑**: 事务性操作，采用“全删全插”模式更新门控、L1配方 (`kds_product_recipes`) 和 L3配方 (`kds_recipe_adjustments`)。
- **审计**: 调用 `log_audit_action` 记录 `rms.recipe.create` / `update`。

### 接口：总仓入库
- **方法 + 路径**: `POST /api/cpsys_api_gateway.php?res=stock&act=add_warehouse_stock`
- **后端处理**:
  - 处理器: `cprms_stock_actions` (位于 `..._rms_handlers.php`)
- **请求参数 (JSON Body)**:
  - `data` (object):
    - `material_id` (int): 物料 ID
    - `quantity` (float): 入库数量
    - `unit_id` (int): 入库单位 ID
- **特殊逻辑**: 调用 `cprms_get_base_quantity` 助手将入库数量（可能为“箱”或“包”）换算为基础单位（“g”或“ml”），然后 `INSERT ... ON DUPLICATE KEY UPDATE` 更新 `expsys_warehouse_stock`。

### 接口：库存调拨
- **方法 + 路径**: `POST /api/cpsys_api_gateway.php?res=stock&act=allocate_to_store`
- **后端处理**:
  - 处理器: `cprms_stock_actions` (位于 `..._rms_handlers.php`)
- **请求参数 (JSON Body)**:
  - `data` (object):
    - `store_id` (int): 目标门店 ID
    - `material_id` (int): 物料 ID
    - `quantity` (float): 调拨数量
    - `unit_id` (int): 调拨单位 ID
- **特殊逻辑**: 事务性操作。
  1. 调用 `cprms_get_base_quantity` 换算为基础单位。
  2. `UPDATE expsys_warehouse_stock SET quantity = quantity - ?` (总仓减库存)。
  3. `UPDATE expsys_store_stock SET quantity = quantity + ?` (门店加库存)。