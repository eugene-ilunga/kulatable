<?php 
return [
  'title' => 'API参考',
  'subtitle' => '开发者文档',
  'intro' => '欢迎使用完整的 API 参考。该系统允许与 POS 后端深度集成，使您能够构建服务员应用程序、客户信息亭或自定义仪表板。',
  'search_placeholder' => '搜索端点...',
  'base_url' => '基本网址',
  'auth_header' => '验证',
  'auth_desc' => '通过承载令牌进行身份验证。在标头中包含“授权：持有者 <令牌>”。',
  'sections' => [
    'auth' => '验证',
    'platform' => '平台',
    'resources' => '资源',
    'customers' => '顾客',
    'catalog' => '目录',
    'sales' => '销售与订单',
    'kot' => '厨房订单 (KOT)',
    'delivery' => '配送管理',
    'operations' => '运营',
    'hardware' => '硬件与设备',
    'pusher' => '实时&推送',
  ],
  'endpoints' => [
    'login' => [
      'title' => '登录',
      'desc' => '获取访问令牌。',
    ],
    'me' => [
      'title' => '用户资料',
      'desc' => '获取当前用户和权限。',
    ],
    'config' => [
      'title' => '配置与特点',
      'desc' => '获取系统设置、功能标志和活动模块。',
    ],
    'permissions' => [
      'title' => '权限',
      'desc' => '列出用户角色和能力。',
    ],
    'printers' => [
      'title' => '打印机',
      'desc' => '获取配置的收据/KOT 打印机。',
    ],
    'receipts' => [
      'title' => '收据设置',
      'desc' => '获取收据样式配置。',
    ],
    'switch_branch' => [
      'title' => '切换分支',
      'desc' => '更改活动分支上下文。',
    ],
    'langs' => [
      'title' => '语言',
      'desc' => '获取可用的语言。',
    ],
    'currencies' => [
      'title' => '货币',
      'desc' => '获取系统货币。',
    ],
    'gateways' => [
      'title' => '支付网关',
      'desc' => '获取公共网关凭据。',
    ],
    'staff' => [
      'title' => '员工名单',
      'desc' => '获取所有工作人员。',
    ],
    'roles' => [
      'title' => '角色',
      'desc' => '获取可用的用户角色。',
    ],
    'areas' => [
      'title' => '领域',
      'desc' => '获取平面图区域。',
    ],
    'addr_list' => [
      'title' => '列出地址',
      'desc' => '获取客户的地址。',
    ],
    'addr_create' => [
      'title' => '创建地址',
      'desc' => '添加新的送货地址。',
    ],
    'addr_update' => [
      'title' => '更新地址',
      'desc' => '修改现有地址。',
    ],
    'addr_delete' => [
      'title' => '删除地址',
      'desc' => '删除地址。',
    ],
    'menus' => [
      'title' => '菜单',
      'desc' => '获取活动菜单。',
    ],
    'categories' => [
      'title' => '类别',
      'desc' => '获取项目类别。',
    ],
    'items' => [
      'title' => '所有项目',
      'desc' => '获取包含价格和修饰符的完整商品目录。',
    ],
    'items_filter' => [
      'title' => '过滤项目',
      'desc' => '按类别或菜单获取项目。',
    ],
    'variations' => [
      'title' => '项目变化',
      'desc' => '获取特定项目的变体。',
    ],
    'modifiers' => [
      'title' => '物品修改器',
      'desc' => '获取特定项目的修饰符组。',
    ],
    'orders_create' => [
      'title' => '提交订单',
      'desc' => '创建新订单（堂食/送货）。',
    ],
    'orders_list' => [
      'title' => '列出订单',
      'desc' => '获取订单历史记录。',
    ],
    'orders_detail' => [
      'title' => '订单详情',
      'desc' => '获取完整订单对象。',
    ],
    'orders_status' => [
      'title' => '更新状态',
      'desc' => '更改订单状态（例如已准备好）。',
    ],
    'orders_pay' => [
      'title' => '付款订单',
      'desc' => '记录付款并关闭订单。',
    ],
    'order_number' => [
      'title' => '预览号码',
      'desc' => '获取下一个订单号。',
    ],
    'order_types' => [
      'title' => '订单类型',
      'desc' => '获取类型（堂食、外卖）。',
    ],
    'actions' => [
      'title' => '允许的操作',
      'desc' => '获取有效的订单操作（kot、bill）。',
    ],
    'platforms' => [
      'title' => '交付平台',
      'desc' => '获取第三方平台。',
    ],
    'charges' => [
      'title' => '额外费用',
      'desc' => '获取服务费/费用。',
    ],
    'taxes' => [
      'title' => '税收',
      'desc' => '获取配置的税率。',
    ],
    'tables' => [
      'title' => '表格',
      'desc' => '获取实时表状态。',
    ],
    'unlock' => [
      'title' => '解锁表',
      'desc' => '强制解锁表。',
    ],
    'res_today' => [
      'title' => '今日预订',
      'desc' => '获取仪表板的预订。',
    ],
    'res_list' => [
      'title' => '所有预订',
      'desc' => '获取分页预订。',
    ],
    'res_create' => [
      'title' => '创建预订',
      'desc' => '预订餐桌。',
    ],
    'res_status' => [
      'title' => '更新预订',
      'desc' => '更改预订状态。',
    ],
    'cust_search' => [
      'title' => '搜寻客户',
      'desc' => '按姓名/电话查找。',
    ],
    'cust_save' => [
      'title' => '拯救客户',
      'desc' => '创建或更新个人资料。',
    ],
    'waiters' => [
      'title' => '服务员',
      'desc' => '聘请担任服务员/司机角色的员工。',
    ],
    'kot_list' => [
      'title' => '列出 KOT',
      'desc' => '获取厨房订单票以供展示。',
    ],
    'kot_detail' => [
      'title' => '详细信息',
      'desc' => '使用物品获得单个 KOT。',
    ],
    'kot_create' => [
      'title' => '创建KOT',
      'desc' => '为现有订单创建新的 KOT。',
    ],
    'kot_status' => [
      'title' => '更新 KOT 状态',
      'desc' => '更改 KOT 状态（厨房内、食物准备好、已送达、已取消）。',
    ],
    'kot_item_status' => [
      'title' => '更新项目状态',
      'desc' => '更新单个项目的状态（正在烹饪、准备就绪、已取消）。',
    ],
    'kot_places' => [
      'title' => '厨房地方',
      'desc' => '获取厨房站/地方。',
    ],
    'kot_cancel_reasons' => [
      'title' => '取消原因',
      'desc' => '获取 KOT 取消原因。',
    ],
    'order_kots' => [
      'title' => '订购 KOT',
      'desc' => '获取特定订单的所有 KOT。',
    ],
    'delivery_settings' => [
      'title' => '传送设置',
      'desc' => '获取分支机构交付配置（半径、费用、时间表）。',
    ],
    'delivery_fee_calc' => [
      'title' => '计算费用',
      'desc' => '根据客户所在位置计算送货费用。',
    ],
    'delivery_fee_tiers' => [
      'title' => '费用等级',
      'desc' => '获取基于距离的费用等级。',
    ],
    'delivery_platforms_list' => [
      'title' => '列出平台',
      'desc' => '获取活跃的配送平台（Uber Eats 优食等）。',
    ],
    'delivery_platform_get' => [
      'title' => '平台详情',
      'desc' => '获取包含佣金信息的单一交付平台。',
    ],
    'delivery_platform_create' => [
      'title' => '创建平台',
      'desc' => '添加新的交付平台。',
    ],
    'delivery_platform_update' => [
      'title' => '更新平台',
      'desc' => '修改平台设置/佣金。',
    ],
    'delivery_platform_delete' => [
      'title' => '删除平台',
      'desc' => '删除或停用交付平台。',
    ],
    'delivery_exec_list' => [
      'title' => '列出高管人员名单',
      'desc' => '为送货人员提供状态过滤器。',
    ],
    'delivery_exec_create' => [
      'title' => '创建执行官',
      'desc' => '添加新的交付主管。',
    ],
    'delivery_exec_update' => [
      'title' => '更新主管',
      'desc' => '修改配送主管信息。',
    ],
    'delivery_exec_delete' => [
      'title' => '删除执行人员',
      'desc' => '删除或停用交付主管。',
    ],
    'delivery_exec_status' => [
      'title' => '行政地位',
      'desc' => '更新可用性（可用/on_delivery/非活动）。',
    ],
    'delivery_assign' => [
      'title' => '分配交货',
      'desc' => '根据订单分配执行人员/平台。',
    ],
    'delivery_order_status' => [
      'title' => '交货状态',
      'desc' => '更新订单交付状态（准备、out_for_delivery、已交付）。',
    ],
    'delivery_orders' => [
      'title' => '交货单',
      'desc' => '获取已过滤的交货订单列表。',
    ],
    'multipos_reg' => [
      'title' => '注册设备',
      'desc' => '链接物理硬件。',
    ],
    'multipos_check' => [
      'title' => '检查设备',
      'desc' => '验证注册。',
    ],
    'notif_token' => [
      'title' => '注册 FCM',
      'desc' => '保存推送令牌。',
    ],
    'notif_list' => [
      'title' => '通知',
      'desc' => '获取应用内提醒。',
    ],
    'notif_read' => [
      'title' => '马克·里德',
      'desc' => '关闭通知。',
    ],
    'pusher_settings' => [
      'title' => '获取推送器设置',
      'desc' => '检索完整的 Pusher 配置。所有经过身份验证的用户（超级管理员、管理员、员工）均可访问。',
    ],
    'pusher_broadcast' => [
      'title' => '获取广播设置',
      'desc' => '获取Pusher实时广播配置。适用于所有用户的系统范围设置。',
    ],
    'pusher_beams' => [
      'title' => '获取光束设置',
      'desc' => '获取 Pusher Beams 推送通知配置。所有经过身份验证的用户都可以访问。',
    ],
    'pusher_status' => [
      'title' => '检查推送器状态',
      'desc' => '快速状态检查以验证 Pusher 服务是否已启用。可供所有用户使用。',
    ],
    'pusher_authorize' => [
      'title' => '授权渠道',
      'desc' => '授权用户访问私人频道和在线频道。需要有效的身份验证。',
    ],
    'pusher_presence' => [
      'title' => '获取会员',
      'desc' => '检索当前连接到状态通道的用户列表。系统范围的数据。',
    ],
  ],
];