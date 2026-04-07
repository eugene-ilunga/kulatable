<?php 
return [
  'title' => 'Referência de API',
  'subtitle' => 'Documentação do desenvolvedor',
  'intro' => 'Bem-vindo à referência completa da API. Este sistema permite integração profunda com o back-end do PDV, permitindo que você crie aplicativos para garçons, quiosques para clientes ou painéis personalizados.',
  'search_placeholder' => 'Pesquisar pontos de extremidade...',
  'base_url' => 'URL base',
  'auth_header' => 'Autenticação',
  'auth_desc' => 'Autentique via Bearer Token. Inclua `Autorização: Portador <token>` nos cabeçalhos.',
  'sections' => [
    'auth' => 'Autenticação',
    'platform' => 'Plataforma',
    'resources' => 'Recursos',
    'customers' => 'Clientes',
    'catalog' => 'Catálogo',
    'sales' => 'Vendas e pedidos',
    'kot' => 'Pedidos de cozinha (KOT)',
    'delivery' => 'Gestão de entrega',
    'operations' => 'Operações',
    'hardware' => 'Hardware e dispositivos',
    'pusher' => 'Tempo real e push',
  ],
  'endpoints' => [
    'login' => [
      'title' => 'Conecte-se',
      'desc' => 'Obtenha o token de acesso.',
    ],
    'me' => [
      'title' => 'Perfil de usuário',
      'desc' => 'Obtenha o usuário e as permissões atuais.',
    ],
    'config' => [
      'title' => 'Configuração e recursos',
      'desc' => 'Obtenha configurações do sistema, sinalizadores de recursos e módulos ativos.',
    ],
    'permissions' => [
      'title' => 'Permissões',
      'desc' => 'Liste as funções e capacidades do usuário.',
    ],
    'printers' => [
      'title' => 'Impressoras',
      'desc' => 'Obtenha recibos/impressoras KOT configuradas.',
    ],
    'receipts' => [
      'title' => 'Configurações de recibo',
      'desc' => 'Obtenha a configuração do estilo do recibo.',
    ],
    'switch_branch' => [
      'title' => 'Trocar filial',
      'desc' => 'Altere o contexto da ramificação ativa.',
    ],
    'langs' => [
      'title' => 'Idiomas',
      'desc' => 'Obtenha os idiomas disponíveis.',
    ],
    'currencies' => [
      'title' => 'Moedas',
      'desc' => 'Obtenha moedas do sistema.',
    ],
    'gateways' => [
      'title' => 'Gateways de pagamento',
      'desc' => 'Obtenha credenciais de gateway público.',
    ],
    'staff' => [
      'title' => 'Lista de funcionários',
      'desc' => 'Chame todos os membros da equipe.',
    ],
    'roles' => [
      'title' => 'Funções',
      'desc' => 'Obtenha funções de usuário disponíveis.',
    ],
    'areas' => [
      'title' => 'Áreas',
      'desc' => 'Obtenha áreas da planta baixa.',
    ],
    'addr_list' => [
      'title' => 'Listar endereços',
      'desc' => 'Obtenha endereços de um cliente.',
    ],
    'addr_create' => [
      'title' => 'Criar endereço',
      'desc' => 'Adicione um novo endereço de entrega.',
    ],
    'addr_update' => [
      'title' => 'Atualizar endereço',
      'desc' => 'Modifique um endereço existente.',
    ],
    'addr_delete' => [
      'title' => 'Excluir endereço',
      'desc' => 'Remover um endereço.',
    ],
    'menus' => [
      'title' => 'Cardápios',
      'desc' => 'Obtenha menus ativos.',
    ],
    'categories' => [
      'title' => 'Categorias',
      'desc' => 'Obtenha categorias de itens.',
    ],
    'items' => [
      'title' => 'Todos os itens',
      'desc' => 'Obtenha o catálogo completo de itens com preços e modificadores.',
    ],
    'items_filter' => [
      'title' => 'Filtrar itens',
      'desc' => 'Obtenha itens por categoria ou menu.',
    ],
    'variations' => [
      'title' => 'Variações de itens',
      'desc' => 'Obtenha variações para um item específico.',
    ],
    'modifiers' => [
      'title' => 'Modificadores de itens',
      'desc' => 'Obtenha grupos modificadores para um item específico.',
    ],
    'orders_create' => [
      'title' => 'Enviar pedido',
      'desc' => 'Crie um novo pedido (Dine-in/Delivery).',
    ],
    'orders_list' => [
      'title' => 'Listar pedidos',
      'desc' => 'Obtenha o histórico de pedidos.',
    ],
    'orders_detail' => [
      'title' => 'Detalhes do pedido',
      'desc' => 'Obtenha o objeto de pedido completo.',
    ],
    'orders_status' => [
      'title' => 'Atualizar status',
      'desc' => 'Alterar o status do pedido (por exemplo, preparado).',
    ],
    'orders_pay' => [
      'title' => 'Ordem de pagamento',
      'desc' => 'Registre o pagamento e feche o pedido.',
    ],
    'order_number' => [
      'title' => 'Número de visualização',
      'desc' => 'Obtenha o próximo número do pedido.',
    ],
    'order_types' => [
      'title' => 'Tipos de pedidos',
      'desc' => 'Obtenha tipos (Jantar no local, Takeaway).',
    ],
    'actions' => [
      'title' => 'Ações permitidas',
      'desc' => 'Obtenha ações de pedido válidas (kot, bill).',
    ],
    'platforms' => [
      'title' => 'Plataformas de entrega',
      'desc' => 'Obtenha plataformas de terceiros.',
    ],
    'charges' => [
      'title' => 'Taxas extras',
      'desc' => 'Obtenha taxas/taxas de serviço.',
    ],
    'taxes' => [
      'title' => 'Impostos',
      'desc' => 'Obtenha taxas de imposto configuradas.',
    ],
    'tables' => [
      'title' => 'Tabelas',
      'desc' => 'Obtenha o status da tabela em tempo real.',
    ],
    'unlock' => [
      'title' => 'Tabela de desbloqueio',
      'desc' => 'Forçar o desbloqueio de uma mesa.',
    ],
    'res_today' => [
      'title' => 'Reservas de hoje',
      'desc' => 'Obtenha reservas para o painel.',
    ],
    'res_list' => [
      'title' => 'Todas as reservas',
      'desc' => 'Obtenha reservas paginadas.',
    ],
    'res_create' => [
      'title' => 'Criar reserva',
      'desc' => 'Reserve uma mesa.',
    ],
    'res_status' => [
      'title' => 'Atualizar reserva',
      'desc' => 'Alterar o status da reserva.',
    ],
    'cust_search' => [
      'title' => 'Pesquisar clientes',
      'desc' => 'Encontre por nome/telefone.',
    ],
    'cust_save' => [
      'title' => 'Salvar cliente',
      'desc' => 'Crie ou atualize o perfil.',
    ],
    'waiters' => [
      'title' => 'Garçons',
      'desc' => 'Obtenha funcionários com funções de garçom/motorista.',
    ],
    'kot_list' => [
      'title' => 'Listar KOTs',
      'desc' => 'Obtenha ingressos de pedidos de cozinha para exibição.',
    ],
    'kot_detail' => [
      'title' => 'Detalhe KOT',
      'desc' => 'Obtenha KOT único com itens.',
    ],
    'kot_create' => [
      'title' => 'Criar KOT',
      'desc' => 'Crie um novo KOT para o pedido existente.',
    ],
    'kot_status' => [
      'title' => 'Atualizar status do KOT',
      'desc' => 'Alterar o status do KOT (in_kitchen, food_ready, servido, cancelado).',
    ],
    'kot_item_status' => [
      'title' => 'Atualizar status do item',
      'desc' => 'Atualize o status de item individual (cozinhando, pronto, cancelado).',
    ],
    'kot_places' => [
      'title' => 'Locais de cozinha',
      'desc' => 'Obtenha estações/locais de cozinha.',
    ],
    'kot_cancel_reasons' => [
      'title' => 'Razões para cancelar',
      'desc' => 'Obtenha os motivos de cancelamento do KOT.',
    ],
    'order_kots' => [
      'title' => 'Encomendar KOTs',
      'desc' => 'Obtenha todos os KOTs para um pedido específico.',
    ],
    'delivery_settings' => [
      'title' => 'Configurações de entrega',
      'desc' => 'Obtenha a configuração de entrega da filial (raio, taxas, cronograma).',
    ],
    'delivery_fee_calc' => [
      'title' => 'Calcular taxa',
      'desc' => 'Calcule a taxa de entrega com base na localização do cliente.',
    ],
    'delivery_fee_tiers' => [
      'title' => 'Níveis de taxas',
      'desc' => 'Obtenha níveis de taxas com base na distância.',
    ],
    'delivery_platforms_list' => [
      'title' => 'Listar plataformas',
      'desc' => 'Obtenha plataformas de entrega ativas (Uber Eats, etc.).',
    ],
    'delivery_platform_get' => [
      'title' => 'Detalhe da plataforma',
      'desc' => 'Obtenha uma plataforma de entrega única com informações de comissão.',
    ],
    'delivery_platform_create' => [
      'title' => 'Criar plataforma',
      'desc' => 'Adicione nova plataforma de entrega.',
    ],
    'delivery_platform_update' => [
      'title' => 'Atualizar plataforma',
      'desc' => 'Modifique as configurações/comissão da plataforma.',
    ],
    'delivery_platform_delete' => [
      'title' => 'Excluir plataforma',
      'desc' => 'Remova ou desative a plataforma de entrega.',
    ],
    'delivery_exec_list' => [
      'title' => 'Listar Executivos',
      'desc' => 'Obtenha equipe de entrega com filtro de status.',
    ],
    'delivery_exec_create' => [
      'title' => 'Criar Executivo',
      'desc' => 'Adicionar novo executivo de entrega.',
    ],
    'delivery_exec_update' => [
      'title' => 'Executivo de atualização',
      'desc' => 'Modifique as informações do executivo de entrega.',
    ],
    'delivery_exec_delete' => [
      'title' => 'Excluir executivo',
      'desc' => 'Remova ou desative o executivo de entrega.',
    ],
    'delivery_exec_status' => [
      'title' => 'Status Executivo',
      'desc' => 'Disponibilidade de atualização (disponível/on_delivery/inactive).',
    ],
    'delivery_assign' => [
      'title' => 'Atribuir entrega',
      'desc' => 'Atribuir executivo/plataforma ao pedido.',
    ],
    'delivery_order_status' => [
      'title' => 'Status de entrega',
      'desc' => 'Atualizar o status de entrega do pedido (preparando, out_for_delivery, entregue).',
    ],
    'delivery_orders' => [
      'title' => 'Pedidos de entrega',
      'desc' => 'Obtenha uma lista filtrada de pedidos de entrega.',
    ],
    'multipos_reg' => [
      'title' => 'Registrar dispositivo',
      'desc' => 'Vincule hardware físico.',
    ],
    'multipos_check' => [
      'title' => 'Verifique o dispositivo',
      'desc' => 'Verifique o registro.',
    ],
    'notif_token' => [
      'title' => 'Registrar FCM',
      'desc' => 'Salve o token de envio.',
    ],
    'notif_list' => [
      'title' => 'Notificações',
      'desc' => 'Receba alertas no aplicativo.',
    ],
    'notif_read' => [
      'title' => 'Marcar como lido',
      'desc' => 'Ignorar notificação.',
    ],
    'pusher_settings' => [
      'title' => 'Obter configurações do empurrador',
      'desc' => 'Recuperar a configuração completa do Pusher. Acessível a todos os usuários autenticados (superadmin, admin, staff).',
    ],
    'pusher_broadcast' => [
      'title' => 'Obtenha configurações de transmissão',
      'desc' => 'Obtenha a configuração de transmissão em tempo real do Pusher. Configurações de todo o sistema para todos os usuários.',
    ],
    'pusher_beams' => [
      'title' => 'Obter configurações de vigas',
      'desc' => 'Obtenha a configuração de notificação push do Pusher Beams. Acessível a todos os usuários autenticados.',
    ],
    'pusher_status' => [
      'title' => 'Verifique o status do empurrador',
      'desc' => 'Verificação rápida de status para verificar se os serviços Pusher estão habilitados. Disponível para todos os usuários.',
    ],
    'pusher_authorize' => [
      'title' => 'Autorizar canal',
      'desc' => 'Autorize o acesso do usuário a canais privados e de presença. Requer autenticação válida.',
    ],
    'pusher_presence' => [
      'title' => 'Obtenha membros presentes',
      'desc' => 'Recuperar lista de usuários atualmente conectados a um canal de presença. Dados de todo o sistema.',
    ],
  ],
];