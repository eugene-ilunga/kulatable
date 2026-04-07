<?php 
return [
  'title' => 'API參考',
  'subtitle' => '開發者文檔',
  'intro' => '歡迎使用完整的 API 參考。該系統允許與 POS 後端深度集成，使您能夠構建服務員應用程序、客戶信息亭或自定義儀表板。',
  'search_placeholder' => '搜索端點...',
  'base_url' => '基本網址',
  'auth_header' => '驗證',
  'auth_desc' => '通過承載令牌進行身份驗證。在標頭中包含“授權：持有者 <令牌>”。',
  'sections' => [
    'auth' => '驗證',
    'platform' => '平台',
    'resources' => '資源',
    'customers' => '顧客',
    'catalog' => '目錄',
    'sales' => '銷售與訂單',
    'kot' => '廚房訂單 (KOT)',
    'delivery' => '配送管理',
    'operations' => '營運',
    'hardware' => '硬件與設備',
    'pusher' => '實時&推送',
  ],
  'endpoints' => [
    'login' => [
      'title' => '登入',
      'desc' => '獲取訪問令牌。',
    ],
    'me' => [
      'title' => '用戶資料',
      'desc' => '獲取當前用戶和權限。',
    ],
    'config' => [
      'title' => '配置與特點',
      'desc' => '獲取系統設置、功能標誌和活動模塊。',
    ],
    'permissions' => [
      'title' => '權限',
      'desc' => '列出用戶角色和能力。',
    ],
    'printers' => [
      'title' => '印表機',
      'desc' => '獲取配置的收據/KOT 打印機。',
    ],
    'receipts' => [
      'title' => '收據設置',
      'desc' => '獲取收據樣式配置。',
    ],
    'switch_branch' => [
      'title' => '切換分支',
      'desc' => '更改活動分支上下文。',
    ],
    'langs' => [
      'title' => '語言',
      'desc' => '獲取可用的語言。',
    ],
    'currencies' => [
      'title' => '貨幣',
      'desc' => '獲取系統貨幣。',
    ],
    'gateways' => [
      'title' => '支付網關',
      'desc' => '獲取公共網關憑據。',
    ],
    'staff' => [
      'title' => '員工名單',
      'desc' => '獲取所有工作人員。',
    ],
    'roles' => [
      'title' => '角色',
      'desc' => '獲取可用的用戶角色。',
    ],
    'areas' => [
      'title' => '領域',
      'desc' => '獲取平面圖區域。',
    ],
    'addr_list' => [
      'title' => '列出地址',
      'desc' => '獲取客戶的地址。',
    ],
    'addr_create' => [
      'title' => '創建地址',
      'desc' => '添加新的送貨地址。',
    ],
    'addr_update' => [
      'title' => '更新地址',
      'desc' => '修改現有地址。',
    ],
    'addr_delete' => [
      'title' => '刪除地址',
      'desc' => '刪除地址。',
    ],
    'menus' => [
      'title' => '菜單',
      'desc' => '獲取活動菜單。',
    ],
    'categories' => [
      'title' => '類別',
      'desc' => '獲取項目類別。',
    ],
    'items' => [
      'title' => '所有項目',
      'desc' => '獲取包含價格和修飾符的完整商品目錄。',
    ],
    'items_filter' => [
      'title' => '過濾項目',
      'desc' => '按類別或菜單獲取項目。',
    ],
    'variations' => [
      'title' => '項目變化',
      'desc' => '獲取特定項目的變體。',
    ],
    'modifiers' => [
      'title' => '物品修改器',
      'desc' => '獲取特定項目的修飾符組。',
    ],
    'orders_create' => [
      'title' => '提交訂單',
      'desc' => '創建新訂單（堂食/送貨）。',
    ],
    'orders_list' => [
      'title' => '列出訂單',
      'desc' => '獲取訂單歷史記錄。',
    ],
    'orders_detail' => [
      'title' => '訂單詳情',
      'desc' => '獲取完整訂單對象。',
    ],
    'orders_status' => [
      'title' => '更新狀態',
      'desc' => '更改訂單狀態（例如已準備好）。',
    ],
    'orders_pay' => [
      'title' => '付款訂單',
      'desc' => '記錄付款並關閉訂單。',
    ],
    'order_number' => [
      'title' => '預覽號碼',
      'desc' => '獲取下一個訂單號。',
    ],
    'order_types' => [
      'title' => '訂單類型',
      'desc' => '獲取類型（堂食、外賣）。',
    ],
    'actions' => [
      'title' => '允許的操作',
      'desc' => '獲取有效的訂單操作（kot、bill）。',
    ],
    'platforms' => [
      'title' => '交付平台',
      'desc' => '獲取第三方平台。',
    ],
    'charges' => [
      'title' => '額外費用',
      'desc' => '獲取服務費/費用。',
    ],
    'taxes' => [
      'title' => '稅收',
      'desc' => '獲取配置的稅率。',
    ],
    'tables' => [
      'title' => '表格',
      'desc' => '獲取實時表狀態。',
    ],
    'unlock' => [
      'title' => '解鎖表',
      'desc' => '強制解鎖表。',
    ],
    'res_today' => [
      'title' => '今日預訂',
      'desc' => '獲取儀表板的預訂。',
    ],
    'res_list' => [
      'title' => '所有預訂',
      'desc' => '獲取分頁預訂。',
    ],
    'res_create' => [
      'title' => '創建預訂',
      'desc' => '預訂餐桌。',
    ],
    'res_status' => [
      'title' => '更新預訂',
      'desc' => '更改預訂狀態。',
    ],
    'cust_search' => [
      'title' => '搜尋客戶',
      'desc' => '按姓名/電話查找。',
    ],
    'cust_save' => [
      'title' => '拯救客戶',
      'desc' => '創建或更新個人資料。',
    ],
    'waiters' => [
      'title' => '服務生',
      'desc' => '聘請擔任服務員/司機角色的員工。',
    ],
    'kot_list' => [
      'title' => '列出 KOT',
      'desc' => '獲取廚房訂單票以供展示。',
    ],
    'kot_detail' => [
      'title' => '詳細信息',
      'desc' => '使用物品獲得單個 KOT。',
    ],
    'kot_create' => [
      'title' => '創建KOT',
      'desc' => '為現有訂單創建新的 KOT。',
    ],
    'kot_status' => [
      'title' => '更新 KOT 狀態',
      'desc' => '更改 KOT 狀態（廚房內、食物準備好、已送達、已取消）。',
    ],
    'kot_item_status' => [
      'title' => '更新項目狀態',
      'desc' => '更新單個項目的狀態（正在烹飪、準備就緒、已取消）。',
    ],
    'kot_places' => [
      'title' => '廚房地方',
      'desc' => '獲取廚房站/地方。',
    ],
    'kot_cancel_reasons' => [
      'title' => '取消原因',
      'desc' => '獲取 KOT 取消原因。',
    ],
    'order_kots' => [
      'title' => '訂購 KOT',
      'desc' => '獲取特定訂單的所有 KOT。',
    ],
    'delivery_settings' => [
      'title' => '傳送設置',
      'desc' => '獲取分支機構交付配置（半徑、費用、時間表）。',
    ],
    'delivery_fee_calc' => [
      'title' => '計算費用',
      'desc' => '根據客戶所在位置計算送貨費用。',
    ],
    'delivery_fee_tiers' => [
      'title' => '費用等級',
      'desc' => '獲取基於距離的費用等級。',
    ],
    'delivery_platforms_list' => [
      'title' => '列出平台',
      'desc' => '獲取活躍的配送平台（Uber Eats 優食等）。',
    ],
    'delivery_platform_get' => [
      'title' => '平台詳情',
      'desc' => '獲取包含佣金信息的單一交付平台。',
    ],
    'delivery_platform_create' => [
      'title' => '創建平台',
      'desc' => '添加新的交付平台。',
    ],
    'delivery_platform_update' => [
      'title' => '更新平台',
      'desc' => '修改平台設置/佣金。',
    ],
    'delivery_platform_delete' => [
      'title' => '刪除平台',
      'desc' => '刪除或停用交付平台。',
    ],
    'delivery_exec_list' => [
      'title' => '列出高管人員名單',
      'desc' => '為送貨人員提供狀態過濾器。',
    ],
    'delivery_exec_create' => [
      'title' => '創建執行官',
      'desc' => '添加新的交付主管。',
    ],
    'delivery_exec_update' => [
      'title' => '更新主管',
      'desc' => '修改配送主管信息。',
    ],
    'delivery_exec_delete' => [
      'title' => '刪除執行人員',
      'desc' => '刪除或停用交付主管。',
    ],
    'delivery_exec_status' => [
      'title' => '行政地位',
      'desc' => '更新可用性（可用/on_delivery/非活動）。',
    ],
    'delivery_assign' => [
      'title' => '分配交貨',
      'desc' => '根據訂單分配執行人員/平台。',
    ],
    'delivery_order_status' => [
      'title' => '交貨狀態',
      'desc' => '更新訂單交付狀態（準備、out_for_delivery、已交付）。',
    ],
    'delivery_orders' => [
      'title' => '交貨單',
      'desc' => '獲取已過濾的交貨​​訂單列表。',
    ],
    'multipos_reg' => [
      'title' => '註冊設備',
      'desc' => '鏈接物理硬件。',
    ],
    'multipos_check' => [
      'title' => '檢查設備',
      'desc' => '驗證註冊。',
    ],
    'notif_token' => [
      'title' => '註冊 FCM',
      'desc' => '保存推送令牌。',
    ],
    'notif_list' => [
      'title' => '通知',
      'desc' => '獲取應用內提醒。',
    ],
    'notif_read' => [
      'title' => '馬克·里德',
      'desc' => '關閉通知。',
    ],
    'pusher_settings' => [
      'title' => '獲取推送器設置',
      'desc' => '檢索完整的 Pusher 配置。所有經過身份驗證的用戶（超級管理員、管理員、員工）均可訪問。',
    ],
    'pusher_broadcast' => [
      'title' => '獲取廣播設置',
      'desc' => '獲取Pusher實時廣播配置。適用於所有用戶的系統範圍設置。',
    ],
    'pusher_beams' => [
      'title' => '獲取光束設置',
      'desc' => '獲取 Pusher Beams 推送通知配置。所有經過身份驗證的用戶都可以訪問。',
    ],
    'pusher_status' => [
      'title' => '檢查推送器狀態',
      'desc' => '快速狀態檢查以驗證 Pusher 服務是否已啟用。可供所有用戶使用。',
    ],
    'pusher_authorize' => [
      'title' => '授權渠道',
      'desc' => '授權用戶訪問私人頻道和在線頻道。需要有效的身份驗證。',
    ],
    'pusher_presence' => [
      'title' => '獲取會員',
      'desc' => '檢索當前連接到狀態通道的用戶列表。系統範圍的數據。',
    ],
  ],
];