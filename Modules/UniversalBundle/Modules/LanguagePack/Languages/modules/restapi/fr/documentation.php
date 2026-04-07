<?php 
return [
  'title' => 'Référence API',
  'subtitle' => 'Documentation du développeur',
  'intro' => 'Bienvenue dans la référence complète de l\'API. Ce système permet une intégration approfondie avec le backend du point de vente, vous permettant de créer des applications de serveur, des kiosques clients ou des tableaux de bord personnalisés.',
  'search_placeholder' => 'Rechercher des points de terminaison...',
  'base_url' => 'URL de base',
  'auth_header' => 'Authentification',
  'auth_desc' => 'Authentifiez-vous via le jeton du porteur. Incluez « Autorisation : Bearer <token> » dans les en-têtes.',
  'sections' => [
    'auth' => 'Authentification',
    'platform' => 'Plate-forme',
    'resources' => 'Ressources',
    'customers' => 'Clients',
    'catalog' => 'Catalogue',
    'sales' => 'Ventes et commandes',
    'kot' => 'Commandes de cuisine (KOT)',
    'delivery' => 'Gestion des livraisons',
    'operations' => 'Opérations',
    'hardware' => 'Matériel et appareils',
    'pusher' => 'En temps réel et Push',
  ],
  'endpoints' => [
    'login' => [
      'title' => 'Se connecter',
      'desc' => 'Obtenez un jeton d\'accès.',
    ],
    'me' => [
      'title' => 'Profil utilisateur',
      'desc' => 'Obtenez l\'utilisateur et les autorisations actuels.',
    ],
    'config' => [
      'title' => 'Configuration et fonctionnalités',
      'desc' => 'Obtenez les paramètres système, les indicateurs de fonctionnalités et les modules actifs.',
    ],
    'permissions' => [
      'title' => 'Autorisations',
      'desc' => 'Répertoriez les rôles et les capacités des utilisateurs.',
    ],
    'printers' => [
      'title' => 'Imprimantes',
      'desc' => 'Obtenez des reçus/imprimantes KOT configurés.',
    ],
    'receipts' => [
      'title' => 'Paramètres du reçu',
      'desc' => 'Obtenez la configuration du style des reçus.',
    ],
    'switch_branch' => [
      'title' => 'Changer de branche',
      'desc' => 'Modifier le contexte de la branche active.',
    ],
    'langs' => [
      'title' => 'Langues',
      'desc' => 'Obtenez les langues disponibles.',
    ],
    'currencies' => [
      'title' => 'Devises',
      'desc' => 'Obtenez les devises du système.',
    ],
    'gateways' => [
      'title' => 'Passerelles de paiement',
      'desc' => 'Obtenez les informations d\'identification de la passerelle publique.',
    ],
    'staff' => [
      'title' => 'Liste du personnel',
      'desc' => 'Obtenez tous les membres du personnel.',
    ],
    'roles' => [
      'title' => 'Rôles',
      'desc' => 'Obtenez les rôles d\'utilisateur disponibles.',
    ],
    'areas' => [
      'title' => 'Domaines',
      'desc' => 'Obtenez les zones du plan d\'étage.',
    ],
    'addr_list' => [
      'title' => 'Liste des adresses',
      'desc' => 'Obtenez les adresses d\'un client.',
    ],
    'addr_create' => [
      'title' => 'Créer une adresse',
      'desc' => 'Ajoutez une nouvelle adresse de livraison.',
    ],
    'addr_update' => [
      'title' => 'Mettre à jour l\'adresse',
      'desc' => 'Modifier une adresse existante.',
    ],
    'addr_delete' => [
      'title' => 'Supprimer l\'adresse',
      'desc' => 'Supprimer une adresse.',
    ],
    'menus' => [
      'title' => 'Menus',
      'desc' => 'Obtenez des menus actifs.',
    ],
    'categories' => [
      'title' => 'Catégories',
      'desc' => 'Obtenez les catégories d\'articles.',
    ],
    'items' => [
      'title' => 'Tous les articles',
      'desc' => 'Obtenez un catalogue d\'articles complet avec les prix et les modificateurs.',
    ],
    'items_filter' => [
      'title' => 'Filtrer les éléments',
      'desc' => 'Obtenez des articles par catégorie ou par menu.',
    ],
    'variations' => [
      'title' => 'Variations d\'articles',
      'desc' => 'Obtenez des variantes pour un article spécifique.',
    ],
    'modifiers' => [
      'title' => 'Modificateurs d\'objet',
      'desc' => 'Obtenez des groupes de modificateurs pour un élément spécifique.',
    ],
    'orders_create' => [
      'title' => 'Soumettre la commande',
      'desc' => 'Créez une nouvelle commande (Dîner sur place/Livraison).',
    ],
    'orders_list' => [
      'title' => 'Liste des commandes',
      'desc' => 'Obtenez l\'historique des commandes.',
    ],
    'orders_detail' => [
      'title' => 'Détail de la commande',
      'desc' => 'Obtenez l\'objet de commande complet.',
    ],
    'orders_status' => [
      'title' => 'Statut de la mise à jour',
      'desc' => 'Modifier le statut de la commande (par exemple préparée).',
    ],
    'orders_pay' => [
      'title' => 'Ordre de paiement',
      'desc' => 'Enregistrez le paiement et clôturez la commande.',
    ],
    'order_number' => [
      'title' => 'Numéro d\'aperçu',
      'desc' => 'Obtenez le prochain numéro de commande.',
    ],
    'order_types' => [
      'title' => 'Types de commandes',
      'desc' => 'Obtenez des types (dîner sur place, à emporter).',
    ],
    'actions' => [
      'title' => 'Actions autorisées',
      'desc' => 'Obtenez des actions de commande valides (kot, facture).',
    ],
    'platforms' => [
      'title' => 'Plateformes de livraison',
      'desc' => 'Obtenez des plateformes tierces.',
    ],
    'charges' => [
      'title' => 'Frais supplémentaires',
      'desc' => 'Obtenez les frais/frais de service.',
    ],
    'taxes' => [
      'title' => 'Impôts',
      'desc' => 'Obtenez les taux de taxe configurés.',
    ],
    'tables' => [
      'title' => 'Tableaux',
      'desc' => 'Obtenez l\'état des tables en temps réel.',
    ],
    'unlock' => [
      'title' => 'Déverrouiller le tableau',
      'desc' => 'Forcer le déverrouillage d\'une table.',
    ],
    'res_today' => [
      'title' => 'Les réservations du jour',
      'desc' => 'Obtenez des réservations pour le tableau de bord.',
    ],
    'res_list' => [
      'title' => 'Toutes les réservations',
      'desc' => 'Obtenez des réservations paginées.',
    ],
    'res_create' => [
      'title' => 'Créer une réservation',
      'desc' => 'Réservez une table.',
    ],
    'res_status' => [
      'title' => 'Mettre à jour la réservation',
      'desc' => 'Changer le statut de la réservation.',
    ],
    'cust_search' => [
      'title' => 'Rechercher des clients',
      'desc' => 'Rechercher par nom/téléphone.',
    ],
    'cust_save' => [
      'title' => 'Enregistrer le client',
      'desc' => 'Créez ou mettez à jour un profil.',
    ],
    'waiters' => [
      'title' => 'Serveurs',
      'desc' => 'Obtenez du personnel avec des rôles de serveur/chauffeur.',
    ],
    'kot_list' => [
      'title' => 'Liste des KOT',
      'desc' => 'Obtenez des tickets de commande de cuisine à afficher.',
    ],
    'kot_detail' => [
      'title' => 'Détail du KOT',
      'desc' => 'Obtenez un seul KOT avec des objets.',
    ],
    'kot_create' => [
      'title' => 'Créer un KOT',
      'desc' => 'Créez un nouveau KOT pour la commande existante.',
    ],
    'kot_status' => [
      'title' => 'Mettre à jour le statut KOT',
      'desc' => 'Changer le statut KOT (in_kitchen, food_ready, servi, annulé).',
    ],
    'kot_item_status' => [
      'title' => 'Mettre à jour le statut de l\'article',
      'desc' => 'Mettre à jour le statut d\'un article individuel (cuisson, prêt, annulé).',
    ],
    'kot_places' => [
      'title' => 'Lieux de cuisine',
      'desc' => 'Obtenez des postes/places de cuisine.',
    ],
    'kot_cancel_reasons' => [
      'title' => 'Raisons d\'annulation',
      'desc' => 'Obtenez les raisons d’annulation de KOT.',
    ],
    'order_kots' => [
      'title' => 'Commander des KOT',
      'desc' => 'Obtenez tous les KOT pour une commande spécifique.',
    ],
    'delivery_settings' => [
      'title' => 'Paramètres de livraison',
      'desc' => 'Obtenez la configuration de la livraison en agence (rayon, frais, calendrier).',
    ],
    'delivery_fee_calc' => [
      'title' => 'Calculer les frais',
      'desc' => 'Calculez les frais de livraison en fonction de l\'emplacement du client.',
    ],
    'delivery_fee_tiers' => [
      'title' => 'Niveaux de frais',
      'desc' => 'Bénéficiez de niveaux de frais basés sur la distance.',
    ],
    'delivery_platforms_list' => [
      'title' => 'Liste des plateformes',
      'desc' => 'Bénéficiez de plateformes de livraison actives (Uber Eats, etc.).',
    ],
    'delivery_platform_get' => [
      'title' => 'Détail de la plateforme',
      'desc' => 'Obtenez une plateforme de livraison unique avec des informations sur les commissions.',
    ],
    'delivery_platform_create' => [
      'title' => 'Créer une plateforme',
      'desc' => 'Ajoutez une nouvelle plateforme de livraison.',
    ],
    'delivery_platform_update' => [
      'title' => 'Plateforme de mise à jour',
      'desc' => 'Modifier les paramètres/commissions de la plateforme.',
    ],
    'delivery_platform_delete' => [
      'title' => 'Supprimer la plateforme',
      'desc' => 'Supprimez ou désactivez la plateforme de livraison.',
    ],
    'delivery_exec_list' => [
      'title' => 'Liste des dirigeants',
      'desc' => 'Obtenez du personnel de livraison avec un filtre de statut.',
    ],
    'delivery_exec_create' => [
      'title' => 'Créer un exécutif',
      'desc' => 'Ajouter un nouveau responsable de livraison.',
    ],
    'delivery_exec_update' => [
      'title' => 'Mettre à jour le directeur',
      'desc' => 'Modifier les informations du responsable de la livraison.',
    ],
    'delivery_exec_delete' => [
      'title' => 'Supprimer le dirigeant',
      'desc' => 'Supprimez ou désactivez le responsable de la livraison.',
    ],
    'delivery_exec_status' => [
      'title' => 'Statut de cadre',
      'desc' => 'Disponibilité de la mise à jour (disponible/on_delivery/inactive).',
    ],
    'delivery_assign' => [
      'title' => 'Attribuer la livraison',
      'desc' => 'Attribuer un exécutif/une plate-forme à la commande.',
    ],
    'delivery_order_status' => [
      'title' => 'Statut de livraison',
      'desc' => 'Mettre à jour le statut de livraison de la commande (préparation, out_for_delivery, livrée).',
    ],
    'delivery_orders' => [
      'title' => 'Bons de livraison',
      'desc' => 'Obtenez la liste filtrée des bons de livraison.',
    ],
    'multipos_reg' => [
      'title' => 'Enregistrer l\'appareil',
      'desc' => 'Reliez le matériel physique.',
    ],
    'multipos_check' => [
      'title' => 'Vérifier l\'appareil',
      'desc' => 'Vérifiez l\'inscription.',
    ],
    'notif_token' => [
      'title' => 'Inscrire FCM',
      'desc' => 'Enregistrez le jeton push.',
    ],
    'notif_list' => [
      'title' => 'Notifications',
      'desc' => 'Recevez des alertes dans l\'application.',
    ],
    'notif_read' => [
      'title' => 'Marquer comme lu',
      'desc' => 'Ignorer la notification.',
    ],
    'pusher_settings' => [
      'title' => 'Obtenir les paramètres du poussoir',
      'desc' => 'Récupérez la configuration complète de Pusher. Accessible à tous les utilisateurs authentifiés (superadmin, admin, staff).',
    ],
    'pusher_broadcast' => [
      'title' => 'Obtenir les paramètres de diffusion',
      'desc' => 'Obtenez la configuration de diffusion en temps réel de Pusher. Paramètres à l’échelle du système pour tous les utilisateurs.',
    ],
    'pusher_beams' => [
      'title' => 'Obtenir les paramètres des poutres',
      'desc' => 'Obtenez la configuration des notifications push Pusher Beams. Accessible à tous les utilisateurs authentifiés.',
    ],
    'pusher_status' => [
      'title' => 'Vérifier l\'état du poussoir',
      'desc' => 'Vérification rapide de l\'état pour vérifier si les services Pusher sont activés. Disponible pour tous les utilisateurs.',
    ],
    'pusher_authorize' => [
      'title' => 'Autoriser la chaîne',
      'desc' => 'Autorisez l’accès des utilisateurs aux canaux privés et de présence. Nécessite une authentification valide.',
    ],
    'pusher_presence' => [
      'title' => 'Obtenez des membres de présence',
      'desc' => 'Récupère la liste des utilisateurs actuellement connectés à un canal de présence. Données à l’échelle du système.',
    ],
  ],
];