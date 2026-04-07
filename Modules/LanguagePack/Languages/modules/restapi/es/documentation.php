<?php 
return [
  'title' => 'Referencia de API',
  'subtitle' => 'Documentación del desarrollador',
  'intro' => 'Bienvenido a la referencia completa de API. Este sistema permite una integración profunda con el backend de POS, lo que le permite crear aplicaciones para camareros, quioscos para clientes o paneles personalizados.',
  'search_placeholder' => 'Buscar puntos finales...',
  'base_url' => 'URL base',
  'auth_header' => 'Autenticación',
  'auth_desc' => 'Autenticar mediante token de portador. Incluya `Autorización: Portador <token>` en los encabezados.',
  'sections' => [
    'auth' => 'Autenticación',
    'platform' => 'Plataforma',
    'resources' => 'Recursos',
    'customers' => 'Clientes',
    'catalog' => 'Catalogar',
    'sales' => 'Ventas y pedidos',
    'kot' => 'Órdenes de cocina (KOT)',
    'delivery' => 'Gestión de entrega',
    'operations' => 'Operaciones',
    'hardware' => 'Hardware y dispositivos',
    'pusher' => 'En tiempo real y push',
  ],
  'endpoints' => [
    'login' => [
      'title' => 'Acceso',
      'desc' => 'Obtener token de acceso.',
    ],
    'me' => [
      'title' => 'Perfil de usuario',
      'desc' => 'Obtenga usuarios y permisos actuales.',
    ],
    'config' => [
      'title' => 'Configuración y características',
      'desc' => 'Obtenga configuraciones del sistema, indicadores de funciones y módulos activos.',
    ],
    'permissions' => [
      'title' => 'Permisos',
      'desc' => 'Enumere las funciones y capacidades de los usuarios.',
    ],
    'printers' => [
      'title' => 'Impresoras',
      'desc' => 'Obtenga recibos configurados/impresoras KOT.',
    ],
    'receipts' => [
      'title' => 'Configuración de recibo',
      'desc' => 'Obtenga la configuración de estilo del recibo.',
    ],
    'switch_branch' => [
      'title' => 'Cambiar rama',
      'desc' => 'Cambiar el contexto de la rama activa.',
    ],
    'langs' => [
      'title' => 'Idiomas',
      'desc' => 'Obtenga idiomas disponibles.',
    ],
    'currencies' => [
      'title' => 'Monedas',
      'desc' => 'Obtenga monedas del sistema.',
    ],
    'gateways' => [
      'title' => 'Pasarelas de Pago',
      'desc' => 'Obtenga credenciales de puerta de enlace pública.',
    ],
    'staff' => [
      'title' => 'Lista de personal',
      'desc' => 'Consiga a todos los miembros del personal.',
    ],
    'roles' => [
      'title' => 'Roles',
      'desc' => 'Obtenga roles de usuario disponibles.',
    ],
    'areas' => [
      'title' => 'Áreas',
      'desc' => 'Obtenga áreas de planos de planta.',
    ],
    'addr_list' => [
      'title' => 'Listar direcciones',
      'desc' => 'Obtener direcciones de un cliente.',
    ],
    'addr_create' => [
      'title' => 'Crear dirección',
      'desc' => 'Añade una nueva dirección de entrega.',
    ],
    'addr_update' => [
      'title' => 'Actualizar dirección',
      'desc' => 'Modificar una dirección existente.',
    ],
    'addr_delete' => [
      'title' => 'Eliminar dirección',
      'desc' => 'Eliminar una dirección.',
    ],
    'menus' => [
      'title' => 'Menús',
      'desc' => 'Obtener menús activos.',
    ],
    'categories' => [
      'title' => 'Categorías',
      'desc' => 'Obtener categorías de artículos.',
    ],
    'items' => [
      'title' => 'Todos los artículos',
      'desc' => 'Obtenga el catálogo de artículos completo con precios y modificadores.',
    ],
    'items_filter' => [
      'title' => 'Filtrar artículos',
      'desc' => 'Obtenga artículos por categoría o menú.',
    ],
    'variations' => [
      'title' => 'Variaciones de artículos',
      'desc' => 'Obtenga variaciones para un artículo específico.',
    ],
    'modifiers' => [
      'title' => 'Modificadores de artículos',
      'desc' => 'Obtenga grupos de modificadores para un artículo específico.',
    ],
    'orders_create' => [
      'title' => 'Enviar pedido',
      'desc' => 'Cree un nuevo pedido (cena en el restaurante/entrega a domicilio).',
    ],
    'orders_list' => [
      'title' => 'Listar pedidos',
      'desc' => 'Obtener historial de pedidos.',
    ],
    'orders_detail' => [
      'title' => 'Detalle del pedido',
      'desc' => 'Obtener objeto de pedido completo.',
    ],
    'orders_status' => [
      'title' => 'Estado de actualización',
      'desc' => 'Cambiar el estado del pedido (por ejemplo, preparado).',
    ],
    'orders_pay' => [
      'title' => 'Orden de pago',
      'desc' => 'Registrar pago y cerrar pedido.',
    ],
    'order_number' => [
      'title' => 'Número de vista previa',
      'desc' => 'Obtenga el siguiente número de pedido.',
    ],
    'order_types' => [
      'title' => 'Tipos de orden',
      'desc' => 'Obtenga tipos (para cenar, para llevar).',
    ],
    'actions' => [
      'title' => 'Acciones permitidas',
      'desc' => 'Obtenga acciones de pedido válidas (kot, bill).',
    ],
    'platforms' => [
      'title' => 'Plataformas de entrega',
      'desc' => 'Obtenga plataformas de terceros.',
    ],
    'charges' => [
      'title' => 'Cargos adicionales',
      'desc' => 'Obtenga cargos/tarifas de servicio.',
    ],
    'taxes' => [
      'title' => 'Impuestos',
      'desc' => 'Obtenga tasas impositivas configuradas.',
    ],
    'tables' => [
      'title' => 'Mesas',
      'desc' => 'Obtenga el estado de la tabla en tiempo real.',
    ],
    'unlock' => [
      'title' => 'Desbloquear tabla',
      'desc' => 'Forzar el desbloqueo de una mesa.',
    ],
    'res_today' => [
      'title' => 'Reservas de hoy',
      'desc' => 'Obtenga reservas para el tablero.',
    ],
    'res_list' => [
      'title' => 'Todas las Reservas',
      'desc' => 'Obtenga reservas paginadas.',
    ],
    'res_create' => [
      'title' => 'Crear reserva',
      'desc' => 'Reserva una mesa.',
    ],
    'res_status' => [
      'title' => 'Actualizar reserva',
      'desc' => 'Cambiar el estado de la reserva.',
    ],
    'cust_search' => [
      'title' => 'Buscar clientes',
      'desc' => 'Buscar por nombre/teléfono.',
    ],
    'cust_save' => [
      'title' => 'Guardar cliente',
      'desc' => 'Crear o actualizar perfil.',
    ],
    'waiters' => [
      'title' => 'camareros',
      'desc' => 'Consiga personal con funciones de camarero/conductor.',
    ],
    'kot_list' => [
      'title' => 'Listar KOT',
      'desc' => 'Obtenga boletos de pedido de cocina para exhibir.',
    ],
    'kot_detail' => [
      'title' => 'Detalle KOT',
      'desc' => 'Obtenga un KOT único con artículos.',
    ],
    'kot_create' => [
      'title' => 'Crear KOT',
      'desc' => 'Cree un nuevo KOT para el pedido existente.',
    ],
    'kot_status' => [
      'title' => 'Actualizar estado KOT',
      'desc' => 'Cambiar el estado de KOT (en_cocina, comida_lista, servido, cancelado).',
    ],
    'kot_item_status' => [
      'title' => 'Actualizar estado del artículo',
      'desc' => 'Actualizar el estado del artículo individual (cocinando, listo, cancelado).',
    ],
    'kot_places' => [
      'title' => 'Lugares de cocina',
      'desc' => 'Consiga estaciones/lugares de cocina.',
    ],
    'kot_cancel_reasons' => [
      'title' => 'Cancelar razones',
      'desc' => 'Obtenga motivos de cancelación de KOT.',
    ],
    'order_kots' => [
      'title' => 'Solicitar KOT',
      'desc' => 'Obtenga todos los KOT para un pedido específico.',
    ],
    'delivery_settings' => [
      'title' => 'Configuración de entrega',
      'desc' => 'Obtenga configuración de entrega en sucursal (radio, tarifas, horario).',
    ],
    'delivery_fee_calc' => [
      'title' => 'Calcular tarifa',
      'desc' => 'Calcule la tarifa de envío según la ubicación del cliente.',
    ],
    'delivery_fee_tiers' => [
      'title' => 'Niveles de tarifas',
      'desc' => 'Obtenga niveles de tarifas basados ​​en la distancia.',
    ],
    'delivery_platforms_list' => [
      'title' => 'Listar plataformas',
      'desc' => 'Consigue plataformas de entrega activas (Uber Eats, etc.).',
    ],
    'delivery_platform_get' => [
      'title' => 'Detalle de la plataforma',
      'desc' => 'Obtenga una plataforma de entrega única con información de comisiones.',
    ],
    'delivery_platform_create' => [
      'title' => 'Crear plataforma',
      'desc' => 'Agregar nueva plataforma de entrega.',
    ],
    'delivery_platform_update' => [
      'title' => 'Actualizar plataforma',
      'desc' => 'Modificar la configuración/comisión de la plataforma.',
    ],
    'delivery_platform_delete' => [
      'title' => 'Eliminar plataforma',
      'desc' => 'Eliminar o desactivar la plataforma de entrega.',
    ],
    'delivery_exec_list' => [
      'title' => 'Lista de ejecutivos',
      'desc' => 'Obtenga personal de entrega con filtro de estado.',
    ],
    'delivery_exec_create' => [
      'title' => 'Crear Ejecutivo',
      'desc' => 'Agregar nuevo ejecutivo de entrega.',
    ],
    'delivery_exec_update' => [
      'title' => 'Ejecutivo de actualización',
      'desc' => 'Modificar información del ejecutivo de entrega.',
    ],
    'delivery_exec_delete' => [
      'title' => 'Eliminar ejecutivo',
      'desc' => 'Eliminar o desactivar el ejecutivo de entrega.',
    ],
    'delivery_exec_status' => [
      'title' => 'Estado ejecutivo',
      'desc' => 'Actualizar disponibilidad (disponible/en_entrega/inactiva).',
    ],
    'delivery_assign' => [
      'title' => 'Asignar entrega',
      'desc' => 'Asignar ejecutivo/plataforma al pedido.',
    ],
    'delivery_order_status' => [
      'title' => 'Estado de entrega',
      'desc' => 'Actualizar el estado de entrega del pedido (preparando, fuera_para_entrega, entregado).',
    ],
    'delivery_orders' => [
      'title' => 'Órdenes de entrega',
      'desc' => 'Obtenga una lista filtrada de pedidos de entrega.',
    ],
    'multipos_reg' => [
      'title' => 'Registrar dispositivo',
      'desc' => 'Vincular hardware físico.',
    ],
    'multipos_check' => [
      'title' => 'Verificar dispositivo',
      'desc' => 'Verificar registro.',
    ],
    'notif_token' => [
      'title' => 'Registrarse FCM',
      'desc' => 'Guardar token de inserción.',
    ],
    'notif_list' => [
      'title' => 'Notificaciones',
      'desc' => 'Recibe alertas en la aplicación.',
    ],
    'notif_read' => [
      'title' => 'Marcar como leído',
      'desc' => 'Descartar notificación.',
    ],
    'pusher_settings' => [
      'title' => 'Obtener configuración del empujador',
      'desc' => 'Recupere la configuración completa de Pusher. Accesible para todos los usuarios autenticados (superadministrador, administrador, personal).',
    ],
    'pusher_broadcast' => [
      'title' => 'Obtener configuración de transmisión',
      'desc' => 'Obtenga la configuración de transmisión en tiempo real de Pusher. Configuraciones de todo el sistema para todos los usuarios.',
    ],
    'pusher_beams' => [
      'title' => 'Obtener configuración de vigas',
      'desc' => 'Obtenga la configuración de notificaciones push de Pusher Beams. Accesible para todos los usuarios autenticados.',
    ],
    'pusher_status' => [
      'title' => 'Verificar el estado del empujador',
      'desc' => 'Comprobación de estado rápida para verificar si los servicios Pusher están habilitados. Disponible para todos los usuarios.',
    ],
    'pusher_authorize' => [
      'title' => 'Autorizar canal',
      'desc' => 'Autorizar el acceso de los usuarios a canales privados y de presencia. Requiere autenticación válida.',
    ],
    'pusher_presence' => [
      'title' => 'Obtener miembros presentes',
      'desc' => 'Recuperar la lista de usuarios actualmente conectados a un canal de presencia. Datos de todo el sistema.',
    ],
  ],
];