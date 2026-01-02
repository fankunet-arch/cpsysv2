# 业务流程：会员次卡 (BIZ_PASS)

## 1. 概述
- **模块用途**：用于创建、售卖、审核和核销“会员次卡”（Seasons Pass）。
- **涉及主要表**：
  - `pass_plans`: (P) 定义次卡方案（如：10次卡，90天有效）。
  - `pos_menu_items`: (P) 自动创建的、用于POS售卖的“次卡商品”。
  - `pos_tags`: (P) 标签，用于定义核销规则（哪些饮品、哪些加料可用）。
  - `pos_product_tag_map`: (P) `pos_menu_items` 和 `pos_tags` 的关联表。
  - `pos_addon_tag_map`: (P) `pos_addons` 和 `pos_tags` 的关联表。
  - `topup_orders`: (B1) 售卡订单，售出后进入 `pending` 状态等待HQ审核 (VR)。
  - `member_passes`: (B1) 审核通过后，会员持有的次卡实例。
  - `pass_redemption_batches`: (B2) 核销记录 (TP)。
- **涉及主要接口**：
  - `POST /api/cpsys_api_gateway.php?res=pos_pass_plans&act=save` (创建/更新方案)
  - `POST /api/cpsys_api_gateway.php?res=topup_orders&act=review` (审核订单)

## 2. 核心流程：次卡方案的创建 (P)

此流程在 HQ 后台（`pos_pass_plan_management.php`）完成，是一个复杂的事务。

1.  **用户操作**：管理员在 HQ 填写次卡方案（名称、次数、有效期、价格、售卖SKU、可核销饮品/加料等）。
2.  **API 调用**：前端 `pos_pass_plan_management.js` 提交一个包含 `plan_details`, `sale_settings`, `rules` 三个部分的 JSON 对象。
3.  **后端处理**：调用 `handle_pass_plan_save` 处理器 (位于 `..._bms_pass_plan.php`)。
4.  **事务开始**：
    1.  **保存方案**：`INSERT` 或 `UPDATE` `pass_plans` 表，存储名称、次数、有效期和**售卖SKU** (`sale_sku`)。
    2.  **创建/更新 POS 商品**：使用 `sale_sku`作为 `product_code`，在 `pos_menu_items` 表中创建或更新一个对应的“售卖商品”。此商品用于 POS 机售卖。
    3.  **标记售卖商品**：使用 `sync_tags` 助手，将此 `pos_menu_items.id` 与 `pass_product` 标签（或 `card_bundle`，依据 `pos_tags_management_view.php`）在 `pos_product_tag_map` 表中关联。
    4.  **创建/更新价格**：在 `pos_item_variants` 表中为此 `pos_menu_items` 创建或更新价格规格。
    5.  **同步核销规则**：
        - 调用 `sync_tags`，将所有“可核销饮品 ID” 列表与 `pass_eligible_beverage` 标签关联 (写入 `pos_product_tag_map`)。
        - 调用 `sync_tags`，将所有“免费加料 ID” 列表与 `free_addon` 标签关联 (写入 `pos_addon_tag_map`)。
        - 调用 `sync_tags`，将所有“付费加料 ID” 列表与 `paid_addon` 标签关联 (写入 `pos_addon_tag_map`)。
5.  **事务结束**。

## 3. 核心流程：售卡与审核 (B1)

### 3.1 售卡 (POS 端 - 推测)
1.  (推测) POS 机同步 `pos_menu_items`，展示 `pass_plans` 对应的售卖商品。
2.  (推测) 收银员售卖此商品，并关联一个 `pos_members` 会员。
3.  (推测) POS 后端 `submit_order.php` (未提供) 在创建 `pos_invoices` 票据的同时，向 `topup_orders` 表插入一条记录，`review_status` = `'pending'`。

### 3.2 审核 (HQ 端 - B1)
1.  **用户操作**：管理员在 HQ (`pos_topup_orders_view.php`) 看到 `pending` 状态的订单。
2.  **API 调用**：点击“通过”时，前端 `pos_topup_orders.js` 调用 `res=topup_orders&act=review`，并发送 `{ "order_id": X, "action": "APPROVE" }`。
3.  **后端处理**：调用 `handle_topup_order_review` 处理器 (位于 `..._bms_member.php`)。
4.  **调用助手**：处理器调用 `activate_member_pass` 助手 (位于 `kds_repo_c.php`)。
5.  **事务开始**：
    1.  **更新订单**：`UPDATE topup_orders` 将 `review_status` 设为 `'confirmed'`，并记录审核人 `reviewed_by_user_id` 和时间 `reviewed_at`。
    2.  **激活次卡**：`INSERT ... ON DUPLICATE KEY UPDATE` `member_passes` 表。
        - 如果会员*没有*此种次卡，则 `INSERT` 一条新记录，包含 `member_id`, `pass_plan_id`, `total_uses`, `remaining_uses`, `expires_at` (基于 `validity_days` 计算)。
        - 如果会员*已有*此种次卡，则 `UPDATE` 现有记录，`remaining_uses = remaining_uses + X`，并可能延长 `expires_at`。
6.  **事务结束**。

## 4. 核心流程：核销 (B2 - 查询)
1.  (推测) POS 端在结账时，如果订单商品满足 `pass_eligible_beverage` 标签，且会员持有 `member_passes`，则允许使用次卡。
2.  (推测) POS 后端在 `submit_order.php` 中，会扣减 `member_passes.remaining_uses`，并记录核销到 `pass_redemption_batches` 和 `pass_redemptions` 表。
3.  **用户操作 (HQ)**：管理员访问 `pos_redemptions_view.php` (B2)。
4.  **后端处理 (HQ)**：`index.php` 调用 `getAllRedemptionBatches` 函数 (位于 `kds_repo_c.php`)。
5.  **数据查询**：该函数 `SELECT` `pass_redemption_batches` 表，并 `JOIN` `member_passes`, `pos_members`, `pass_plans`, `kds_stores` 和 `pos_invoices` (用于关联额外付费) 以显示完整的核销日志。