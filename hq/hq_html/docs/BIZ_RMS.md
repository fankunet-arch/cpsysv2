# 业务流程：RMS 配方管理 (BIZ_RMS)

## 1. 概述
- **模块用途**：定义产品（饮品）的制作配方。系统采用“三层”配方模型 (L1, L2, L3) 来计算最终SOP。
- **涉及主要表**：
  - `kds_products`: 配方主表，定义 P-Code 和基础信息。
  - `kds_product_recipes`: **L1 (基础配方)**，定义产品的默认物料用量。
  - `kds_global_adjustment_rules`: **L2 (全局规则)**，基于条件（如冰量、甜度）自动调整 *所有* 产品的物料用量。
  - `kds_recipe_adjustments`: **L3 (特例规则)**，**覆盖** L1 和 L2，为 *特定产品* 的 *特定组合*（如：大杯-多冰-标准糖）指定精确用量。
  - `kds_materials`: 物料表。
  - `kds_units`: 单位表。
  - `kds_product_sweetness_options`: 甜度门控（Gating），定义产品可用的甜度。
  - `kds_product_ice_options`: 冰量门控（Gating），定义产品可用的冰量。
- **涉及主要接口**：
  - `POST /api/cpsys_api_gateway.php?res=rms_products&act=save_product` (保存配方)
  - `GET /api/cpsys_api_gateway.php?res=rms_products&act=get_product_details` (加载配方)
  - `GET /api/cpsys_api_gateway.php?res=kds/sop&act=get` (KDS 解析SOP码)

## 2. 核心流程：保存配方 (HQ)

此流程在 HQ 后台（`rms_product_management_view.php`）完成。

1.  **用户操作**：产品经理在 RMS 界面 (`rms_product_management.js`) 编辑产品。
2.  **数据打包**：点击“保存”时，JS 会打包一个巨大的 JSON 对象，包含：
    - 基础信息 (`id`, `product_code`, `name_zh`, `name_es`, `status_id`)
    - 门控 (Gating) 数组 (`allowed_sweetness_ids`, `allowed_ice_ids`)
    - L1 基础配方数组 (`base_recipes: [...]`)
    - L3 特例规则数组 (`adjustments: [...]`)
3.  **API 调用**：JS 将此 JSON 作为 `product` 键，POST 到 `res=rms_products&act=save_product`。
4.  **后端处理**：调用 `cprms_product_save` 处理器 (位于 `..._rms_handlers.php`)。
5.  **事务开始 (全删全插)**：
    1.  `UPDATE kds_products`：更新产品主表信息。
    2.  `UPDATE kds_product_translations`：更新双语翻译。
    3.  `DELETE FROM kds_product_sweetness_options ...`：删除所有旧的甜度门控。
    4.  `INSERT INTO kds_product_sweetness_options ...`：插入新的甜度门控。
    5.  `DELETE FROM kds_product_ice_options ...`：删除所有旧的冰量门控。
    6.  `INSERT INTO kds_product_ice_options ...`：插入新的冰量门控。
    7.  `DELETE FROM kds_product_recipes ...`：**删除所有 L1 基础配方**。
    8.  `INSERT INTO kds_product_recipes ...`：循环插入所有新的 L1 基础配方。
    9.  `DELETE FROM kds_recipe_adjustments ...`：**删除所有 L3 特例规则**。
    10. `INSERT INTO kds_recipe_adjustments ...`：循环插入所有新的 L3 特例规则。
6.  **审计**: 调用 `log_audit_action` 记录 `rms.recipe.update`。
7.  **事务结束**。

## 3. 核心流程：KDS 解析 SOP 码

此流程用于 KDS 端根据扫码枪或手动输入的SOP码（如 `A1-1-1-1`）获取配方。

1.  **KDS 操作**：(推测) KDS 端 JS 获取到一个SOP码。
2.  **API 调用**：(推测) KDS 端 JS 调用 `res=kds/sop&act=get` 并附带 `code=A1-1-1-1`。
3.  **后端处理 (HQ)**：调用 `handle_kds_sop` 处理器 (位于 `..._registry_kds.php`)。
4.  **调用解析器**：
    1.  `handle_kds_sop` 调用 `getKdsSopByCode` (此函数在 `kds_helper.php` 中被 `require`，但**未在任何 `kds_repo_*.php` 文件中定义**，可能在 `kds_services.php` (已删除) 或更高层级，因此标记 `(待确认)`）。
    2.  `getKdsSopByCode` (待确认) 内部实例化 `KdsSopParser` (位于 `kds_sop_engine.php`)。
    3.  `KdsSopParser->parse(string $code)` 被调用。
5.  **SOP 码解析 (`KdsSopParser`)**：
    1.  `loadRules()`: `SELECT * FROM kds_sop_query_rules`，加载所有SOP解析规则，门店专属优先，然后按 `priority` 排序。
    2.  循环遍历规则，尝试解析 `A1-1-1-1`。
    3.  优先使用 `_parseTemplateV2` (V2 模板解析器)。
    4.  如果规则是 V1 (旧版)，则回退到 `_parseV1Delimiter` (分隔符) 或 `_parseV1KeyValue` (键值对)。
    5.  假设匹配到 `template: {P}-{M}-{A}-{T}` 规则，解析器返回数组：`['p' => 'A1', 'm' => '1', 'a' => '1', 't' => '1']`。
6.  **配方计算 (`getKdsSopByCode` - 待确认)**：
    1.  (待确认) 拿到 P-Code `A1`，`SELECT * FROM kds_products`。
    2.  (待确认) 拿到 `A1` 的 L1 基础配方 (来自 `kds_product_recipes`)。
    3.  (待确认) 拿到所有 L2 全局规则 (来自 `kds_global_adjustment_rules`)，并根据 `m=1, a=1, t=1` 应用 L2，调整 L1 配方。
    4.  (待确认) 拿到 `A1` 的 L3 特例规则 (来自 `kds_recipe_adjustments`)，并根据 `m=1, a=1, t=1` 查找匹配项，**覆盖** L1/L2 的结果。
7.  **返回数据**：`handle_kds_sop` 将最终计算出的配方 (SOP) 作为 JSON 返回给 KDS 端。