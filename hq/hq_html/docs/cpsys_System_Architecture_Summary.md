toptea-cpsys系统架构文件清单 (System Architecture File Summary)
一、核心架构与系统配置 (Core Architecture & Configuration)
这些文件是系统的引导、安全和基础配置层，是系统运行的基石。

core/config.php
职责：数据库连接配置，PDO 初始化，强制设定时区为 UTC (SET time_zone='+00:00')。

core/auth_core.php
职责：核心权限认证模块，检查用户 Session 和登录状态。

core/helpers.php
职责：全局工具函数，主要提供本地时间/UTC 时间的转换。

html/cpsys/index.php
职责：系统主入口控制器，负责加载核心文件、权限检查和路由视图。

html/cpsys/login.php / html/cpsys/logout.php
职责：登录/登出处理页面。

html/cpsys/api/captcha_generator.php
职责：API 验证码生成服务。

html/cpsys/diag/time.php
职责：系统时间诊断工具。

二、API 网关与引擎 (API Gateway & Engine)
所有前端异步请求的统一入口和业务分发核心。

html/cpsys/api/cpsys_api_gateway.php
职责：API 请求的总入口，合并所有资源注册表。

app/core/api_core.php
职责：API 核心引擎，根据 res (资源) 和 act (动作) 执行 CRUD 或自定义处理函数。

app/helpers/http_json_helper.php
职责：统一的 JSON 响应格式化工具，处理输入和输出。

app/helpers/auth_helper.php
职责：角色（Role）和权限（RBAC）相关的常量和检查函数。

三、数据访问层 (DAL - Data Access Layer / Repository)
封装数据库查询逻辑，主要通过 kds_repo_*.php 文件族实现。

app/helpers/kds_helper.php
职责：DAL 引导文件，集合并导入所有 kds_repo_ 助手。

app/helpers/kds/kds_repo_a.php
职责：基础实体查询（HQ 用户、门店、物料、促销等）。

app/helpers/kds/kds_repo_b.php
职责：POS 菜单项、配方详情等查询。

app/helpers/kds/kds_repo_c.php
职责：字典、次卡订单、会员数据等复杂查询。

app/helpers/kds/kds_repo_c_reports.php
职责：报告数据查询，包括 KPI、销售趋势、库存预警等。

app/helpers/kds/kds_sop_engine.php
职责：KDS SOP 规则代码解析和处理引擎。

四、业务逻辑处理器 (Business Logic Handlers - Registries)
API 注册表，定义了资源的数据库表、权限和具体的业务逻辑（CRUD 或自定义动作）。

基础与扩展

html/cpsys/api/registries/cpsys_registry_base.php

html/cpsys/api/registries/cpsys_registry_base_dicts.php (冰量、甜度、杯型字典)

html/cpsys/api/registries/cpsys_registry_ext.php (外部接口/通用注册)

配方管理系统 (RMS)

html/cpsys/api/registries/cpsys_registry_rms.php

html/cpsys/api/registries/cpsys_registry_rms_handlers.php (物料保存、库存入库/调拨的业务逻辑)

业务管理系统 (BMS/POS)

html/cpsys/api/registries/cpsys_registry_bms.php

html/cpsys/api/registries/cpsys_registry_bms_menu_a.php (POS 菜单、加料 CRUD，包含次卡商品锁定检查)

html/cpsys/api/registries/cpsys_registry_bms_pass_plan.php (次卡方案创建/更新及 POS 同步逻辑)

html/cpsys/api/registries/cpsys_registry_bms_member.php (会员等级、积分、售卡订单审核处理)

html/cpsys/api/registries/cpsys_registry_bms_menu_b.php (班次复核、票据作废/更正逻辑)

通用助手

app/helpers/audit_helper.php (关键操作的审计日志记录)

五、前端视图 (Views - PHP)
所有页面结构和数据呈现的模板文件。

布局与通用

app/views/cpsys/layouts/main.php (全局 UI 框架、侧边栏)
app/views/cpsys/dashboard_view.php
app/views/cpsys/access_denied_view.php
app/views/cpsys/login_view.php

RMS/配方/库存

app/views/cpsys/material_management_view.php
app/views/cpsys/rms/rms_product_management_view.php (配方 L1/L3 配置，集成 KDS SOP 规则)
app/views/cpsys/warehouse_stock_management_view.php

BMS/POS 业务

app/views/cpsys/store_management_view.php (门店配置，支持多打印机、票号前缀)
app/views/cpsys/pos_invoice_detail_view.php (票据详情，支持作废/更正操作)
app/views/cpsys/pos_topup_orders_view.php (售卡订单审核视图)
app/views/cpsys/pos_pass_plan_management_view.php (次卡方案配置视图)
app/views/cpsys/pos_print_template_management_view.php (打印模板可视化编辑器入口)

六、前端脚本与样式 (Frontend Assets - JS/CSS)
处理前端交互、数据绑定和表单提交的 JavaScript 文件。

样式文件

html/cpsys/css/style.css
html/cpsys/css/login.css

通用与系统 JS

html/cpsys/js/store_management.js (门店多打印机配置逻辑)
html/cpsys/js/profile.js

RMS/配方 JS

html/cpsys/js/rms/rms_product_management.js (L3 配方 PMAT 编码预览逻辑)
html/cpsys/js/pos_addon_management.js (包含全局加料设置抽屉逻辑)

BMS/POS 业务 JS

html/cpsys/js/pos_topup_orders.js (订单审核提交逻辑)
html/cpsys/js/pos_pass_plan_management.js (次卡方案表单处理)
html/cpsys/js/pos_print_template_editor.js (可视化模板编辑器交互)
html/cpsys/js/pos_tags_management.js (POS 标签管理 CRUD)