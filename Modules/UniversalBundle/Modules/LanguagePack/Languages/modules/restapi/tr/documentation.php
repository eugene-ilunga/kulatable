<?php 
return [
  'title' => 'API Referansı',
  'subtitle' => 'Geliştirici Belgeleri',
  'intro' => 'Tam API referansına hoş geldiniz. Bu sistem, POS arka ucuyla derin entegrasyona olanak tanıyarak garson uygulamaları, müşteri kioskları veya özel kontrol panelleri oluşturmanıza olanak tanır.',
  'search_placeholder' => 'Uç noktaları ara...',
  'base_url' => 'Temel URL',
  'auth_header' => 'Kimlik doğrulama',
  'auth_desc' => 'Taşıyıcı Token aracılığıyla kimlik doğrulaması yapın. Başlıklara `Yetkilendirme: Taşıyıcı <token>`ı ekleyin.',
  'sections' => [
    'auth' => 'Kimlik doğrulama',
    'platform' => 'platformu',
    'resources' => 'Kaynaklar',
    'customers' => 'Müşteriler',
    'catalog' => 'Katalog',
    'sales' => 'Satış ve Siparişler',
    'kot' => 'Mutfak Siparişleri (KOT)',
    'delivery' => 'Teslimat Yönetimi',
    'operations' => 'Operasyonlar',
    'hardware' => 'Donanım ve Cihazlar',
    'pusher' => 'Gerçek Zamanlı ve Anlık',
  ],
  'endpoints' => [
    'login' => [
      'title' => 'Giriş yapmak',
      'desc' => 'Erişim jetonunu edinin.',
    ],
    'me' => [
      'title' => 'Kullanıcı Profili',
      'desc' => 'Geçerli kullanıcıyı ve izinleri alın.',
    ],
    'config' => [
      'title' => 'Yapılandırma ve Özellikler',
      'desc' => 'Sistem ayarlarını, özellik işaretlerini ve etkin modülleri edinin.',
    ],
    'permissions' => [
      'title' => 'İzinler',
      'desc' => 'Kullanıcı rollerini ve yeteneklerini listeleyin.',
    ],
    'printers' => [
      'title' => 'Yazıcılar',
      'desc' => 'Yapılandırılmış makbuzları/KOT yazıcılarını alın.',
    ],
    'receipts' => [
      'title' => 'Makbuz Ayarları',
      'desc' => 'Makbuz stili yapılandırmasını alın.',
    ],
    'switch_branch' => [
      'title' => 'Şubeyi Değiştir',
      'desc' => 'Etkin dal içeriğini değiştirin.',
    ],
    'langs' => [
      'title' => 'Diller',
      'desc' => 'Mevcut dilleri edinin.',
    ],
    'currencies' => [
      'title' => 'Para birimleri',
      'desc' => 'Sistem para birimlerini edinin.',
    ],
    'gateways' => [
      'title' => 'Ödeme Ağ Geçitleri',
      'desc' => 'Genel ağ geçidi kimlik bilgilerini alın.',
    ],
    'staff' => [
      'title' => 'Personel Listesi',
      'desc' => 'Tüm personeli alın.',
    ],
    'roles' => [
      'title' => 'Roller',
      'desc' => 'Kullanılabilir kullanıcı rollerini alın.',
    ],
    'areas' => [
      'title' => 'Alanlar',
      'desc' => 'Kat planı alanlarını alın.',
    ],
    'addr_list' => [
      'title' => 'Adresleri Listele',
      'desc' => 'Bir müşterinin adreslerini alın.',
    ],
    'addr_create' => [
      'title' => 'Adres Oluştur',
      'desc' => 'Yeni bir teslimat adresi ekleyin.',
    ],
    'addr_update' => [
      'title' => 'Adresi Güncelle',
      'desc' => 'Mevcut bir adresi değiştirin.',
    ],
    'addr_delete' => [
      'title' => 'Adresi Sil',
      'desc' => 'Bir adresi kaldırın.',
    ],
    'menus' => [
      'title' => 'Menüler',
      'desc' => 'Aktif menüler alın.',
    ],
    'categories' => [
      'title' => 'Kategoriler',
      'desc' => 'Öğe kategorilerini alın.',
    ],
    'items' => [
      'title' => 'Tüm Ürünler',
      'desc' => 'Fiyatlar ve değiştiriciler içeren tam ürün kataloğunu edinin.',
    ],
    'items_filter' => [
      'title' => 'Öğeleri Filtrele',
      'desc' => 'Öğeleri kategoriye veya menüye göre alın.',
    ],
    'variations' => [
      'title' => 'Öğe Varyasyonları',
      'desc' => 'Belirli bir öğenin varyasyonlarını alın.',
    ],
    'modifiers' => [
      'title' => 'Öğe Değiştiriciler',
      'desc' => 'Belirli bir öğe için değiştirici grupları alın.',
    ],
    'orders_create' => [
      'title' => 'Siparişi Gönder',
      'desc' => 'Yeni bir sipariş oluşturun (İçeride Yemek/Teslimat).',
    ],
    'orders_list' => [
      'title' => 'Siparişleri Listele',
      'desc' => 'Sipariş geçmişini alın.',
    ],
    'orders_detail' => [
      'title' => 'Sipariş Detayı',
      'desc' => 'Tam sipariş nesnesini alın.',
    ],
    'orders_status' => [
      'title' => 'Durumu Güncelle',
      'desc' => 'Sipariş durumunu değiştirin (örn. hazırlandı).',
    ],
    'orders_pay' => [
      'title' => 'Ödeme Emri',
      'desc' => 'Ödemeyi kaydedin ve siparişi kapatın.',
    ],
    'order_number' => [
      'title' => 'Önizleme Numarası',
      'desc' => 'Bir sonraki sipariş numarasını alın.',
    ],
    'order_types' => [
      'title' => 'Sipariş Türleri',
      'desc' => 'Türleri alın (Yemekli, Paket Servis).',
    ],
    'actions' => [
      'title' => 'İzin Verilen Eylemler',
      'desc' => 'Geçerli sipariş işlemlerini (kot, fatura) alın.',
    ],
    'platforms' => [
      'title' => 'Teslimat Platformları',
      'desc' => 'Üçüncü taraf platformları edinin.',
    ],
    'charges' => [
      'title' => 'Ekstra Ücretler',
      'desc' => 'Hizmet ücretlerini/ücretlerini alın.',
    ],
    'taxes' => [
      'title' => 'Vergiler',
      'desc' => 'Yapılandırılmış vergi oranlarını alın.',
    ],
    'tables' => [
      'title' => 'Tablolar',
      'desc' => 'Gerçek zamanlı tablo durumunu alın.',
    ],
    'unlock' => [
      'title' => 'Masanın Kilidini Aç',
      'desc' => 'Bir masanın kilidini açmaya zorla.',
    ],
    'res_today' => [
      'title' => 'Bugünkü Rezervasyonlar',
      'desc' => 'Kontrol paneli için rezervasyon alın.',
    ],
    'res_list' => [
      'title' => 'Tüm Rezervasyonlar',
      'desc' => 'Sayfalandırılmış rezervasyonlar alın.',
    ],
    'res_create' => [
      'title' => 'Rezervasyon Oluştur',
      'desc' => 'Bir masa ayırtın.',
    ],
    'res_status' => [
      'title' => 'Rezervasyonu Güncelle',
      'desc' => 'Rezervasyon durumunu değiştirin.',
    ],
    'cust_search' => [
      'title' => 'Müşterileri Ara',
      'desc' => 'Ada/telefona göre bulun.',
    ],
    'cust_save' => [
      'title' => 'Müşteriyi Kaydet',
      'desc' => 'Profil oluşturun veya güncelleyin.',
    ],
    'waiters' => [
      'title' => 'Garsonlar',
      'desc' => 'Garson/şoför rollerine sahip personel alın.',
    ],
    'kot_list' => [
      'title' => 'KOT\'ları listele',
      'desc' => 'Teşhir için mutfak siparişi biletleri alın.',
    ],
    'kot_detail' => [
      'title' => 'KOT Detayı',
      'desc' => 'Eşyalarla birlikte tek KOT alın.',
    ],
    'kot_create' => [
      'title' => 'KOT oluştur',
      'desc' => 'Mevcut sipariş için yeni KOT oluşturun.',
    ],
    'kot_status' => [
      'title' => 'KOT Durumunu Güncelle',
      'desc' => 'KOT durumunu değiştirin (in_kitchen, food_ready, servis edildi, iptal edildi).',
    ],
    'kot_item_status' => [
      'title' => 'Öğe Durumunu Güncelle',
      'desc' => 'Tek tek öğe durumunu güncelleyin (pişiriliyor, hazır, iptal edildi).',
    ],
    'kot_places' => [
      'title' => 'Mutfak Yerleri',
      'desc' => 'Mutfak istasyonları/yerleri alın.',
    ],
    'kot_cancel_reasons' => [
      'title' => 'İptal Nedenleri',
      'desc' => 'KOT iptal nedenlerini öğrenin.',
    ],
    'order_kots' => [
      'title' => 'KOT siparişi verin',
      'desc' => 'Belirli bir sipariş için tüm KOT\'ları alın.',
    ],
    'delivery_settings' => [
      'title' => 'Teslimat Ayarları',
      'desc' => 'Şube dağıtım yapılandırmasını (yarıçap, ücretler, program) alın.',
    ],
    'delivery_fee_calc' => [
      'title' => 'Ücreti Hesapla',
      'desc' => 'Müşterinin bulunduğu yere göre teslimat ücretini hesaplayın.',
    ],
    'delivery_fee_tiers' => [
      'title' => 'Ücret Kademeleri',
      'desc' => 'Mesafeye dayalı ücret kademeleri alın.',
    ],
    'delivery_platforms_list' => [
      'title' => 'Platformları Listele',
      'desc' => 'Aktif dağıtım platformları edinin (Uber Eats vb.).',
    ],
    'delivery_platform_get' => [
      'title' => 'Platform Detayı',
      'desc' => 'Komisyon bilgileriyle tek teslimat platformuna sahip olun.',
    ],
    'delivery_platform_create' => [
      'title' => 'Platform Oluştur',
      'desc' => 'Yeni teslimat platformu ekleyin.',
    ],
    'delivery_platform_update' => [
      'title' => 'Platformu Güncelle',
      'desc' => 'Platform ayarlarını/komisyonunu değiştirin.',
    ],
    'delivery_platform_delete' => [
      'title' => 'Platformu Sil',
      'desc' => 'Teslimat platformunu kaldırın veya devre dışı bırakın.',
    ],
    'delivery_exec_list' => [
      'title' => 'Yöneticileri Listele',
      'desc' => 'Durum filtreli teslimat personelini edinin.',
    ],
    'delivery_exec_create' => [
      'title' => 'Yönetici Oluştur',
      'desc' => 'Yeni teslimat yöneticisi ekleyin.',
    ],
    'delivery_exec_update' => [
      'title' => 'Yöneticiyi Güncelle',
      'desc' => 'Teslimat yöneticisi bilgilerini değiştirin.',
    ],
    'delivery_exec_delete' => [
      'title' => 'Yöneticiyi Sil',
      'desc' => 'Teslimat yöneticisini kaldırın veya devre dışı bırakın.',
    ],
    'delivery_exec_status' => [
      'title' => 'Yönetici Durumu',
      'desc' => 'Kullanılabilirliği güncelleyin (mevcut/teslimatta/etkin değil).',
    ],
    'delivery_assign' => [
      'title' => 'Teslimatı Ata',
      'desc' => 'Siparişe yönetici/platform atayın.',
    ],
    'delivery_order_status' => [
      'title' => 'Teslimat Durumu',
      'desc' => 'Sipariş teslimat durumunu güncelleyin (hazırlanıyor, teslimat için_ teslim edildi, teslim edildi).',
    ],
    'delivery_orders' => [
      'title' => 'Teslimat Siparişleri',
      'desc' => 'Teslimat siparişlerinin filtrelenmiş listesini alın.',
    ],
    'multipos_reg' => [
      'title' => 'Cihazı Kaydet',
      'desc' => 'Fiziksel donanımı bağlayın.',
    ],
    'multipos_check' => [
      'title' => 'Cihazı Kontrol Et',
      'desc' => 'Kaydı doğrulayın.',
    ],
    'notif_token' => [
      'title' => 'FCM\'yi kaydedin',
      'desc' => 'Push jetonunu kaydedin.',
    ],
    'notif_list' => [
      'title' => 'Bildirimler',
      'desc' => 'Uygulama içi uyarılar alın.',
    ],
    'notif_read' => [
      'title' => 'Okundu Olarak İşaretle',
      'desc' => 'Bildirimi reddet.',
    ],
    'pusher_settings' => [
      'title' => 'İtici Ayarlarını Alın',
      'desc' => 'İtici yapılandırmasının tamamını alın. Kimliği doğrulanmış tüm kullanıcılar (süper yönetici, yönetici, personel) tarafından erişilebilir.',
    ],
    'pusher_broadcast' => [
      'title' => 'Yayın Ayarlarını Alın',
      'desc' => 'Pusher\'ın gerçek zamanlı yayın yapılandırmasını edinin. Tüm kullanıcılar için sistem genelinde ayarlar.',
    ],
    'pusher_beams' => [
      'title' => 'Kiriş Ayarlarını Alma',
      'desc' => 'İtici Kirişler anlık bildirim yapılandırmasını edinin. Kimliği doğrulanmış tüm kullanıcılar tarafından erişilebilir.',
    ],
    'pusher_status' => [
      'title' => 'İtici Durumunu Kontrol Edin',
      'desc' => 'İtici hizmetlerinin etkin olup olmadığını doğrulamak için hızlı durum kontrolü. Tüm kullanıcılara açıktır.',
    ],
    'pusher_authorize' => [
      'title' => 'Kanalı Yetkilendir',
      'desc' => 'Özel kanallara ve iletişim durumu kanallarına kullanıcı erişimine izin verin. Geçerli kimlik doğrulaması gerektirir.',
    ],
    'pusher_presence' => [
      'title' => 'Durum Üyelerini Alın',
      'desc' => 'Şu anda bir varlık kanalına bağlı olan kullanıcıların listesini alın. Sistem çapında veriler.',
    ],
  ],
];