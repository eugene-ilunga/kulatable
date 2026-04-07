<?php

namespace Modules\RestApi\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\RestApi\Entities\ApplicationIntegrationSetting;
use Illuminate\Support\Str;
use App\Models\LanguageSetting;

class DocumentationController extends Controller
{
    public function index(Request $request): View
    {
        return $this->renderDocumentation($request, false);
    }

    public function public(string $token, Request $request): View
    {
        $settings = ApplicationIntegrationSetting::instance();
        if (! $settings->public_token || $settings->public_token !== $token) {
            abort(404);
        }

        return $this->renderDocumentation($request, true);
    }

    protected function renderDocumentation(Request $request, bool $isPublic): View
    {
        $languages = $this->languageOptions();
        $requested = $request->get('lang');
        $activeLocale = $this->resolveLocale($languages, $requested);
        app()->setLocale($activeLocale);

        $baseUrl = url('/api/application-integration');
        $selectedLang = collect($languages)->firstWhere('language_code', $activeLocale);
        $direction = $selectedLang['text_direction'] ?? (in_array($activeLocale, ['ar', 'fa', 'ur']) ? 'rtl' : 'ltr');
        $sections = $this->sections($baseUrl);
        $toc = collect($sections)->map(fn($section) => ['id' => $section['id'], 'title' => $section['title']])->all();

        // Get logo URL
        $logoUrl = global_setting()->logo_url;


        // Get system font
        $fontFamily = 'Sans-Serif';
        if (function_exists('restaurant') && restaurant() && !empty(restaurant()->font_family)) {
            $fontFamily = restaurant()->font_family;
        } elseif (function_exists('global_setting') && global_setting() && !empty(global_setting()->font_family)) {
            $fontFamily = global_setting()->font_family;
        }

        // Get module version from version.txt
        $moduleVersion = '1.0.0';
        $versionTxtPath = base_path('Modules/RestApi/version.txt');
        if (file_exists($versionTxtPath)) {
            $moduleVersion = trim(file_get_contents($versionTxtPath));
        }

        return view('applicationintegration::documentation.index', [
            'baseUrl' => $baseUrl,
            'sections' => $sections,
            'languages' => $languages,
            'toc' => $toc,
            'isPublic' => $isPublic,
            'locale' => $activeLocale,
            'direction' => $direction,
            'logoUrl' => $logoUrl,
            'fontFamily' => $fontFamily,
            'moduleVersion' => $moduleVersion,
        ]);
    }

    protected function languageOptions(): array
    {
        try {
            $langs = LanguageSetting::where('active', 1)->get(['language_name', 'language_code', 'text_direction'])->toArray();
            if (! empty($langs)) {
                return $langs;
            }
        } catch (\Throwable $e) {
            // ignore
        }

        return collect(LanguageSetting::LANGUAGES)
            ->map(function ($label, $code) {
                return [
                    'language_name' => $label,
                    'language_code' => $code,
                    'text_direction' => in_array($code, ['ar', 'fa', 'ur']) ? 'rtl' : 'ltr',
                ];
            })
            ->values()
            ->all();
    }

    protected function resolveLocale(array $languages, ?string $requested): string
    {
        $current = $requested ?: app()->getLocale();
        $lookup = collect($languages)
            ->mapWithKeys(fn($lang) => [strtolower($lang['language_code']) => $lang['language_code']]);

        $aliases = [
            'eng' => 'en',
            'gr' => 'el',
        ];

        $key = strtolower(str_replace('_', '-', $current));
        $key = $aliases[$key] ?? $key;
        if ($lookup->has($key)) {
            return $lookup->get($key);
        }

        // fall back to default app locale if provided
        $fallback = config('app.locale');
        $fallbackKey = strtolower(str_replace('_', '-', $fallback));
        $fallbackKey = $aliases[$fallbackKey] ?? $fallbackKey;
        return $lookup->get($fallbackKey, 'en');
    }

    protected function sections(string $baseUrl): array
    {
        return [
            [
                'id' => 'overview',
                'title' => __('applicationintegration-docs::doc.overview_title'),
                'description' => __('applicationintegration-docs::doc.overview_desc'),
                'quick' => [
                    ['label' => __('applicationintegration-docs::doc.base_url'), 'value' => $baseUrl],
                    ['label' => __('applicationintegration-docs::doc.auth'), 'value' => 'Bearer'],
                    ['label' => __('applicationintegration-docs::doc.formats'), 'value' => 'JSON'],
                ],
            ],
            [
                'id' => 'auth',
                'title' => __('applicationintegration-docs::doc.auth_title'),
                'description' => __('applicationintegration-docs::doc.auth_desc'),
                'endpoints' => [
                    [
                        'name' => 'POST /auth/login',
                        'method' => 'POST',
                        'path' => '/auth/login',
                        'auth' => false,
                        'summary' => __('applicationintegration-docs::doc.login_summary'),
                        'headers' => [
                            ['name' => 'Accept', 'value' => 'application/json'],
                            ['name' => 'Content-Type', 'value' => 'application/json'],
                        ],
                        'body' => ['email' => 'admin@example.com', 'password' => 'secret'],
                        'response' => [
                            'status' => true,
                            'message' => 'success',
                            'data' => [
                                'token_type' => 'Bearer',
                                'access_token' => 'eyJhbGciOi...',
                            ],
                        ],
                        'notes' => [
                            __('applicationintegration-docs::doc.login_note_token'),
                            __('applicationintegration-docs::doc.login_note_permissions'),
                            __('applicationintegration-docs::doc.login_note_rate_limit'),
                        ],
                    ],
                    [
                        'name' => 'GET /auth/me',
                        'method' => 'GET',
                        'path' => '/auth/me',
                        'auth' => true,
                        'summary' => __('applicationintegration-docs::doc.me_summary'),
                        'headers' => [
                            ['name' => 'Authorization', 'value' => 'Bearer <token>'],
                            ['name' => 'Accept', 'value' => 'application/json'],
                        ],
                        'response' => [
                            'user' => [
                                'id' => 12,
                                'name' => 'API Admin',
                                'email' => 'admin@example.com',
                                'roles' => ['owner', 'manager'],
                            ],
                            'modules' => ['POS', 'Order', 'Delivery'],
                        ],
                        'notes' => [
                            __('applicationintegration-docs::doc.me_note_roles'),
                        ],
                    ],
                ],
            ],
            [
                'id' => 'platform',
                'title' => __('applicationintegration-docs::doc.platform_title'),
                'description' => __('applicationintegration-docs::doc.platform_desc'),
                'endpoints' => [
                    [
                        'name' => 'GET /platform/config',
                        'method' => 'GET',
                        'path' => '/platform/config',
                        'auth' => true,
                        'summary' => __('applicationintegration-docs::doc.platform_config'),
                        'response' => [
                            'restaurant' => ['id' => 2, 'name' => 'Demo', 'currency' => ['code' => 'USD']],
                            'branch' => ['id' => 1, 'name' => 'HQ'],
                            'features' => ['pos' => true, 'order' => true],
                            'modules' => ['POS', 'Order', 'Delivery'],
                            'languages' => [['language_name' => 'English', 'language_code' => 'en']],
                        ],
                        'notes' => [
                            __('applicationintegration-docs::doc.platform_note_cache'),
                        ],
                    ],
                    [
                        'name' => 'GET /languages',
                        'method' => 'GET',
                        'path' => '/languages',
                        'auth' => true,
                        'summary' => __('applicationintegration-docs::doc.languages_summary'),
                        'response' => [
                            ['language_name' => 'English', 'language_code' => 'en', 'status' => 1],
                        ],
                        'notes' => [
                            __('applicationintegration-docs::doc.languages_note'),
                        ],
                    ],
                    [
                        'name' => 'GET /platform/permissions',
                        'method' => 'GET',
                        'path' => '/platform/permissions',
                        'auth' => true,
                        'summary' => __('applicationintegration-docs::doc.permissions_summary'),
                        'response' => [
                            'roles' => ['owner', 'manager'],
                            'permissions' => ['orders.view', 'orders.create'],
                        ],
                        'notes' => [],
                    ],
                    [
                        'name' => 'GET /platform/printers',
                        'method' => 'GET',
                        'path' => '/platform/printers',
                        'auth' => true,
                        'summary' => __('applicationintegration-docs::doc.printers_summary'),
                        'response' => [
                            ['id' => 2, 'name' => 'Kitchen Printer', 'branch_id' => 1],
                        ],
                        'notes' => [],
                    ],
                    [
                        'name' => 'GET /platform/receipt-settings',
                        'method' => 'GET',
                        'path' => '/platform/receipt-settings',
                        'auth' => true,
                        'summary' => __('applicationintegration-docs::doc.receipt_summary'),
                        'response' => [
                            'title' => 'Demo Restaurant',
                            'footer' => 'Thanks for visiting',
                        ],
                        'notes' => [],
                    ],
                    [
                        'name' => 'POST /platform/switch-branch',
                        'method' => 'POST',
                        'path' => '/platform/switch-branch',
                        'auth' => true,
                        'summary' => __('applicationintegration-docs::doc.switch_branch'),
                        'body' => ['branch_id' => 1],
                        'response' => [
                            'success' => true,
                            'branch' => ['id' => 1, 'name' => 'HQ'],
                        ],
                        'notes' => [],
                    ],
                ],
            ],
            [
                'id' => 'pos',
                'title' => __('applicationintegration-docs::doc.pos_title'),
                'description' => __('applicationintegration-docs::doc.pos_desc'),
                'endpoints' => [
                    [
                        'name' => 'GET /pos/menus',
                        'method' => 'GET',
                        'path' => '/pos/menus',
                        'auth' => true,
                        'summary' => __('applicationintegration-docs::doc.menus_summary'),
                        'response' => [
                            ['id' => 3, 'name' => 'Main Menu', 'branch_id' => 1],
                        ],
                        'notes' => [],
                    ],
                    [
                        'name' => 'GET /pos/categories',
                        'method' => 'GET',
                        'path' => '/pos/categories',
                        'auth' => true,
                        'summary' => __('applicationintegration-docs::doc.categories_summary'),
                        'response' => [
                            ['id' => 5, 'name' => 'Coffee'],
                        ],
                        'notes' => [],
                    ],
                    [
                        'name' => 'GET /pos/items',
                        'method' => 'GET',
                        'path' => '/pos/items',
                        'auth' => true,
                        'summary' => __('applicationintegration-docs::doc.items_summary'),
                        'response' => [
                            ['id' => 10, 'name' => 'Espresso', 'price' => 3.5, 'menu_id' => 3],
                        ],
                        'notes' => [
                            __('applicationintegration-docs::doc.items_note_filters'),
                        ],
                    ],
                    [
                        'name' => 'GET /pos/items/category/{categoryId}',
                        'method' => 'GET',
                        'path' => '/pos/items/category/{categoryId}',
                        'auth' => true,
                        'summary' => __('applicationintegration-docs::doc.items_by_category'),
                        'response' => [
                            ['id' => 11, 'name' => 'Latte'],
                        ],
                        'notes' => [],
                    ],
                    [
                        'name' => 'GET /pos/items/menu/{menuId}',
                        'method' => 'GET',
                        'path' => '/pos/items/menu/{menuId}',
                        'auth' => true,
                        'summary' => __('applicationintegration-docs::doc.items_by_menu'),
                        'response' => [
                            ['id' => 10, 'name' => 'Espresso'],
                        ],
                        'notes' => [],
                    ],
                    [
                        'name' => 'GET /pos/items/{itemId}/variations',
                        'method' => 'GET',
                        'path' => '/pos/items/{itemId}/variations',
                        'auth' => true,
                        'summary' => __('applicationintegration-docs::doc.variations'),
                        'response' => [
                            ['id' => 101, 'name' => 'Large', 'price' => 4.5],
                        ],
                        'notes' => [],
                    ],
                    [
                        'name' => 'GET /pos/items/{itemId}/modifier-groups',
                        'method' => 'GET',
                        'path' => '/pos/items/{itemId}/modifier-groups',
                        'auth' => true,
                        'summary' => __('applicationintegration-docs::doc.modifiers'),
                        'response' => [
                            ['id' => 55, 'name' => 'Milk'],
                        ],
                        'notes' => [],
                    ],
                    [
                        'name' => 'GET /pos/extra-charges/{orderType}',
                        'method' => 'GET',
                        'path' => '/pos/extra-charges/dine-in',
                        'auth' => true,
                        'summary' => __('applicationintegration-docs::doc.extra_charges'),
                        'response' => [
                            ['name' => 'Service', 'amount' => 5.00],
                        ],
                        'notes' => [],
                    ],
                    [
                        'name' => 'POST /pos/orders',
                        'method' => 'POST',
                        'path' => '/pos/orders',
                        'auth' => true,
                        'summary' => __('applicationintegration-docs::doc.orders_create'),
                        'body' => [
                            'order_type' => 'Dine In',
                            'table_id' => 12,
                            'actions' => ['kot'],
                            'items' => [
                                ['id' => 10, 'quantity' => 1, 'price' => 15.00],
                            ],
                        ],
                        'response' => [
                            'success' => true,
                            'order' => ['id' => 9901, 'order_number' => 'POS-9901', 'status' => 'placed'],
                            'cart' => [
                                'items' => [
                                    ['menu_item_id' => 10, 'quantity' => 1, 'amount' => 15.00],
                                ],
                                'charges' => [
                                    ['name' => 'Service', 'amount' => 1.50],
                                ],
                                'taxes' => [
                                    ['name' => 'VAT', 'amount' => 2.10],
                                ],
                                'summary' => [
                                    'sub_total' => 15.00,
                                    'discount_amount' => 0,
                                    'tax_total' => 2.10,
                                    'charges_total' => 1.50,
                                    'delivery_fee' => 0,
                                    'grand_total' => 18.60,
                                ],
                            ],
                        ],
                        'notes' => [
                            __('applicationintegration-docs::doc.idempotency'),
                            'Response includes cart-level items, charges, taxes, and totals.',
                            __('applicationintegration-docs::doc.price_fallback_note'),
                        ],
                    ],
                    [
                        'name' => 'PUT /pos/orders/{id}',
                        'method' => 'PUT',
                        'path' => '/pos/orders/{id}',
                        'auth' => true,
                        'summary' => 'Update an existing order (cancel, modify status, update items)',
                        'body' => [
                            'actions' => ['cancel'],  // or ['bill'], ['kot'], ['draft']
                            'items' => [  // Optional: update items
                                ['id' => 10, 'quantity' => 2, 'price' => 15.00],
                            ],
                        ],
                        'response' => [
                            'success' => true,
                            'message' => 'Order canceled',
                            'order_id' => 9901,
                        ],
                        'notes' => [
                            'actions=["cancel"]: Deletes order and frees table (matching Laravel POS behavior)',
                            'actions=["bill"]: Updates status to billed, sets table to running',
                            'actions=["kot"]: Updates status to kot, sets table to running',
                            'actions=["draft"]: Updates status to draft, sets table to available',
                            'If items array provided, existing items are replaced with new ones',
                            'This endpoint is used by Flutter POS app for order cancellation',
                        ],
                    ],
                    [

                        'name' => 'GET /pos/orders',
                        'method' => 'GET',
                        'path' => '/pos/orders',
                        'auth' => true,
                        'summary' => __('applicationintegration-docs::doc.orders_list'),
                        'response' => [
                            'data' => [
                                [
                                    'id' => 4123,
                                    'status' => 'open',
                                    'order_type' => 'Dine In',
                                    'total' => 82.50,
                                    'items' => [
                                        ['menu_item_id' => 10, 'quantity' => 1, 'amount' => 15.00],
                                    ],
                                    'charges' => [
                                        ['name' => 'Service', 'amount' => 5.00],
                                    ],
                                    'taxes' => [
                                        ['name' => 'VAT', 'amount' => 2.50],
                                    ],
                                    'cart' => [
                                        'summary' => [
                                            'sub_total' => 70.00,
                                            'tax_total' => 2.50,
                                            'charges_total' => 5.00,
                                            'grand_total' => 82.50,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'notes' => [
                            __('applicationintegration-docs::doc.orders_filters'),
                            'Each order now includes items, charges, taxes, and cart summary for direct consumption.',
                            __('applicationintegration-docs::doc.orders_resilient'),
                            __('applicationintegration-docs::doc.orders_status_flow'),
                        ],
                    ],
                    [
                        'name' => 'GET /pos/orders/{id}',
                        'method' => 'GET',
                        'path' => '/pos/orders/{id}',
                        'auth' => true,
                        'summary' => __('applicationintegration-docs::doc.orders_detail'),
                        'response' => [
                            'id' => 4123,
                            'order_number' => 'POS-4123',
                            'order_status' => 'confirmed',
                            'items' => [['menu_item_id' => 10, 'quantity' => 1, 'amount' => 15.00]],
                            'charges' => [['name' => 'Service', 'amount' => 1.50]],
                            'taxes' => [['name' => 'VAT', 'amount' => 2.10]],
                            'cart' => [
                                'summary' => [
                                    'sub_total' => 15.00,
                                    'discount_amount' => 0,
                                    'tax_total' => 2.10,
                                    'charges_total' => 1.50,
                                    'grand_total' => 18.60,
                                ],
                            ],
                        ],
                        'notes' => [
                            __('applicationintegration-docs::doc.orders_detail_dinein'),
                            __('applicationintegration-docs::doc.orders_invoice'),
                            __('applicationintegration-docs::doc.orders_discount_note'),
                            __('applicationintegration-docs::doc.orders_print_note'),
                            __('applicationintegration-docs::doc.orders_customer_note'),
                            __('applicationintegration-docs::doc.orders_table_waiter'),
                        ],
                    ],
                    [
                        'name' => 'POST /pos/orders/{id}/status',
                        'method' => 'POST',
                        'path' => '/pos/orders/{id}/status',
                        'auth' => true,
                        'summary' => __('applicationintegration-docs::doc.orders_status'),
                        'body' => ['status' => 'paid'],
                        'response' => ['status' => true],
                        'notes' => [
                            __('applicationintegration-docs::doc.orders_status_flow'),
                        ],
                    ],
                    [
                        'name' => 'POST /pos/orders/{id}/tip',
                        'method' => 'POST',
                        'path' => '/pos/orders/{id}/tip',
                        'auth' => true,
                        'summary' => 'Add or update a tip on the order.',
                        'body' => ['amount' => 5.00, 'note' => 'Thanks'],
                        'response' => ['success' => true, 'order_id' => 1, 'tip_amount' => 5.00],
                        'notes' => [],
                    ],
                    [
                        'name' => 'POST /pos/orders/{id}/split-payments',
                        'method' => 'POST',
                        'path' => '/pos/orders/{id}/split-payments',
                        'auth' => true,
                        'summary' => 'Create a split payment (optional linked items).',
                        'body' => [
                            'amount' => 20.00,
                            'payment_method' => 'card',
                            'status' => 'pending',
                            'items' => [
                                ['order_item_id' => 123, 'quantity' => 1],
                            ],
                        ],
                        'response' => [
                            'success' => true,
                            'order_id' => 1,
                            'split_order_id' => 10,
                            'amount' => 20.00,
                            'payment_method' => 'card',
                            'status' => 'pending',
                        ],
                        'notes' => [],
                    ],
                    [
                        'name' => 'POST /pos/orders/{id}/tip',
                        'method' => 'POST',
                        'path' => '/pos/orders/{id}/tip',
                        'auth' => true,
                        'summary' => __('applicationintegration-docs::doc.add_tip'),
                        'body' => [
                            'amount' => 5.00,
                            'note' => 'Great service',
                        ],
                        'response' => [
                            'success' => true,
                            'tip_amount' => 5.00,
                        ],
                        'notes' => [
                            __('applicationintegration-docs::doc.add_tip_note'),
                        ],
                    ],
                    [
                        'name' => 'POST /pos/orders/{id}/split-payments',
                        'method' => 'POST',
                        'path' => '/pos/orders/{id}/split-payments',
                        'auth' => true,
                        'summary' => __('applicationintegration-docs::doc.split_payments'),
                        'body' => [
                            'payments' => [
                                ['amount' => 25.00, 'method' => 'cash'],
                                ['amount' => 25.00, 'method' => 'card'],
                            ],
                        ],
                        'response' => [
                            'success' => true,
                            'payments' => [
                                ['id' => 1, 'amount' => 25.00, 'method' => 'cash'],
                                ['id' => 2, 'amount' => 25.00, 'method' => 'card'],
                            ],
                        ],
                        'notes' => [
                            __('applicationintegration-docs::doc.split_payments_note'),
                        ],
                    ],
                    [
                        'name' => 'POST /pos/orders/{id}/pay',
                        'method' => 'POST',
                        'path' => '/pos/orders/{id}/pay',
                        'auth' => true,
                        'summary' => __('applicationintegration-docs::doc.orders_pay'),
                        'body' => ['payments' => [['amount' => 10, 'method' => 'cash']]],
                        'response' => ['status' => true],
                        'notes' => [
                            __('applicationintegration-docs::doc.orders_pay_note'),
                            __('applicationintegration-docs::doc.orders_invoice'),
                        ],
                    ],
                    [
                        'name' => 'PUT /pos/orders/{id}/items',
                        'method' => 'PUT',
                        'path' => '/pos/orders/{id}/items',
                        'auth' => true,
                        'summary' => __('applicationintegration-docs::doc.update_order_items'),
                        'body' => [
                            'items' => [
                                ['action' => 'add', 'menu_item_id' => 15, 'quantity' => 2, 'price' => 25.00],
                                ['action' => 'update', 'order_item_id' => 123, 'quantity' => 3],
                                ['action' => 'remove', 'order_item_id' => 124],
                            ],
                            'recalculate_totals' => true,
                        ],
                        'response' => [
                            'success' => true,
                            'data' => [
                                'added_items' => [125],
                                'updated_items' => [123],
                                'removed_items' => [124],
                                'sub_total' => 75.00,
                                'total' => 82.50,
                            ],
                        ],
                        'notes' => [
                            __('applicationintegration-docs::doc.update_items_note'),
                        ],
                    ],
                    [
                        'name' => 'POST /pos/orders/{id}/kot',
                        'method' => 'POST',
                        'path' => '/pos/orders/{id}/kot',
                        'auth' => true,
                        'summary' => __('applicationintegration-docs::doc.create_kot'),
                        'body' => [
                            'order_item_ids' => [123, 124],
                            'note' => 'Kitchen note',
                            'kitchen_place_id' => 1,
                        ],
                        'response' => [
                            'success' => true,
                            'data' => [
                                'kot_id' => 45,
                                'kot_number' => '123',
                                'token_number' => 5,
                                'items_count' => 2,
                            ],
                        ],
                        'notes' => [
                            __('applicationintegration-docs::doc.create_kot_note'),
                        ],
                    ],
                    [
                        'name' => 'GET /pos/customers',
                        'method' => 'GET',
                        'path' => '/pos/customers',
                        'auth' => true,
                        'summary' => __('applicationintegration-docs::doc.customers'),
                        'response' => [['id' => 2, 'name' => 'John Doe']],
                        'notes' => [],
                    ],
                    [
                        'name' => 'POST /pos/customers',
                        'method' => 'POST',
                        'path' => '/pos/customers',
                        'auth' => true,
                        'summary' => __('applicationintegration-docs::doc.customers_create'),
                        'body' => ['name' => 'John', 'phone' => '+1'],
                        'response' => ['id' => 22, 'name' => 'John'],
                        'notes' => [],
                    ],
                    [
                        'name' => 'GET /pos/phone-codes',
                        'method' => 'GET',
                        'path' => '/pos/phone-codes',
                        'auth' => true,
                        'summary' => __('applicationintegration-docs::doc.phone_codes'),
                        'response' => [['code' => '+1', 'name' => 'US']],
                        'notes' => [],
                    ],
                    [
                        'name' => 'GET /pos/taxes',
                        'method' => 'GET',
                        'path' => '/pos/taxes',
                        'auth' => true,
                        'summary' => __('applicationintegration-docs::doc.taxes'),
                        'response' => [['name' => 'VAT', 'rate' => 5]],
                        'notes' => [],
                    ],
                    [
                        'name' => 'GET /pos/restaurants',
                        'method' => 'GET',
                        'path' => '/pos/restaurants',
                        'auth' => true,
                        'summary' => __('applicationintegration-docs::doc.restaurants'),
                        'response' => [['id' => 2, 'name' => 'Demo']],
                        'notes' => [],
                    ],
                    [
                        'name' => 'GET /pos/branches',
                        'method' => 'GET',
                        'path' => '/pos/branches',
                        'auth' => true,
                        'summary' => __('applicationintegration-docs::doc.branches'),
                        'response' => [['id' => 1, 'name' => 'HQ']],
                        'notes' => [],
                    ],
                    [
                        'name' => 'GET /pos/reservations',
                        'method' => 'GET',
                        'path' => '/pos/reservations',
                        'auth' => true,
                        'summary' => __('applicationintegration-docs::doc.reservations'),
                        'response' => [['id' => 8, 'status' => 'confirmed']],
                        'notes' => [],
                    ],
                    [
                        'name' => 'POST /pos/reservations',
                        'method' => 'POST',
                        'path' => '/pos/reservations',
                        'auth' => true,
                        'summary' => __('applicationintegration-docs::doc.reservations_create'),
                        'body' => ['table_id' => 1, 'guest_name' => 'Sam', 'guest_count' => 2],
                        'response' => ['id' => 9],
                        'notes' => [],
                    ],
                    [
                        'name' => 'POST /pos/reservations/{id}/status',
                        'method' => 'POST',
                        'path' => '/pos/reservations/{id}/status',
                        'auth' => true,
                        'summary' => __('applicationintegration-docs::doc.reservations_status'),
                        'body' => ['status' => 'cancelled'],
                        'response' => ['status' => true],
                        'notes' => [],
                    ],
                    [
                        'name' => 'GET /pos/reservations/today',
                        'method' => 'GET',
                        'path' => '/pos/reservations/today',
                        'auth' => true,
                        'summary' => __('applicationintegration-docs::doc.reservations_today'),
                        'response' => [['id' => 8, 'status' => 'confirmed']],
                        'notes' => [],
                    ],
                    [
                        'name' => 'GET /pos/tables',
                        'method' => 'GET',
                        'path' => '/pos/tables',
                        'auth' => true,
                        'summary' => __('applicationintegration-docs::doc.tables'),
                        'response' => [['id' => 6, 'name' => 'T-6']],
                        'notes' => [],
                    ],
                    [
                        'name' => 'POST /pos/tables/{tableId}/unlock',
                        'method' => 'POST',
                        'path' => '/pos/tables/{tableId}/unlock',
                        'auth' => true,
                        'summary' => __('applicationintegration-docs::doc.tables_unlock'),
                        'response' => ['unlocked' => true],
                        'notes' => [],
                    ],
                    [
                        'name' => 'GET /pos/order-types',
                        'method' => 'GET',
                        'path' => '/pos/order-types',
                        'auth' => true,
                        'summary' => __('applicationintegration-docs::doc.order_types'),
                        'response' => [['code' => 'dine-in', 'label' => 'Dine In']],
                        'notes' => [],
                    ],
                    [
                        'name' => 'GET /pos/actions',
                        'method' => 'GET',
                        'path' => '/pos/actions',
                        'auth' => true,
                        'summary' => __('applicationintegration-docs::doc.actions'),
                        'response' => ['kot', 'bill'],
                        'notes' => [],
                    ],
                    [
                        'name' => 'GET /pos/delivery-platforms',
                        'method' => 'GET',
                        'path' => '/pos/delivery-platforms',
                        'auth' => true,
                        'summary' => __('applicationintegration-docs::doc.delivery_platforms'),
                        'response' => ['Talabat', 'Careem'],
                        'notes' => [],
                    ],
                    [
                        'name' => 'GET /pos/get-order-number',
                        'method' => 'GET',
                        'path' => '/pos/get-order-number',
                        'auth' => true,
                        'summary' => __('applicationintegration-docs::doc.order_number'),
                        'response' => [
                            'order_number' => 157,
                            'formatted_order_number' => 'POS-2026-0157',
                        ],
                        'notes' => [
                            __('applicationintegration-docs::doc.order_number_lock_note'),
                        ],
                    ],
                    [
                        'name' => 'GET /pos/waiters',
                        'method' => 'GET',
                        'path' => '/pos/waiters',
                        'auth' => true,
                        'summary' => __('applicationintegration-docs::doc.waiters'),
                        'response' => [['id' => 4, 'name' => 'Waiter 1']],
                        'notes' => [],
                    ],
                    [
                        'name' => 'GET /pos/delivery-executives',
                        'method' => 'GET',
                        'path' => '/pos/delivery-executives',
                        'auth' => true,
                        'summary' => __('applicationintegration-docs::doc.delivery_executives'),
                        'response' => [
                            ['id' => 1, 'name' => 'Driver 1', 'phone' => '+123456789', 'status' => 'available'],
                        ],
                        'notes' => [
                            __('applicationintegration-docs::doc.delivery_executives_note'),
                        ],
                    ],
                ],
            ],
            [
                'id' => 'customer',
                'title' => __('applicationintegration-docs::doc.customer_title'),
                'description' => __('applicationintegration-docs::doc.customer_desc'),
                'endpoints' => [
                    [
                        'name' => 'GET /customer/catalog',
                        'method' => 'GET',
                        'path' => '/customer/catalog',
                        'auth' => true,
                        'summary' => __('applicationintegration-docs::doc.catalog_summary'),
                        'response' => [
                            'menus' => [
                                ['id' => 3, 'name' => 'Main Menu', 'categories' => []],
                            ],
                        ],
                        'notes' => [],
                    ],
                    [
                        'name' => 'POST /customer/orders',
                        'method' => 'POST',
                        'path' => '/customer/orders',
                        'auth' => true,
                        'summary' => __('applicationintegration-docs::doc.customer_order'),
                        'body' => [
                            'order_type' => 'Delivery',
                            'customer_address_id' => 9,
                            'items' => [
                                ['id' => 10, 'quantity' => 1],
                            ],
                        ],
                        'response' => [
                            'status' => true,
                            'order_id' => 551,
                        ],
                        'notes' => [
                            __('applicationintegration-docs::doc.customer_order_note'),
                        ],
                    ],
                    [
                        'name' => 'GET /customer/orders',
                        'method' => 'GET',
                        'path' => '/customer/orders',
                        'auth' => true,
                        'summary' => __('applicationintegration-docs::doc.customer_orders'),
                        'response' => [
                            ['id' => 551, 'status' => 'open', 'total' => 22.5],
                        ],
                        'notes' => [],
                    ],
                ],
            ],
            [
                'id' => 'notifications',
                'title' => __('applicationintegration-docs::doc.notifications_title'),
                'description' => __('applicationintegration-docs::doc.notifications_desc'),
                'endpoints' => [
                    [
                        'name' => 'POST /pos/notifications/register-token',
                        'method' => 'POST',
                        'path' => '/pos/notifications/register-token',
                        'auth' => true,
                        'summary' => __('applicationintegration-docs::doc.notifications_register'),
                        'body' => [
                            'device_token' => Str::uuid()->toString(),
                            'platform' => 'ios',
                        ],
                        'response' => ['saved' => true],
                        'notes' => [],
                    ],
                    [
                        'name' => 'GET /pos/notifications',
                        'method' => 'GET',
                        'path' => '/pos/notifications',
                        'auth' => true,
                        'summary' => __('applicationintegration-docs::doc.notifications_list'),
                        'response' => [
                            ['id' => 1, 'title' => 'Order ready', 'read_at' => null],
                        ],
                        'notes' => [],
                    ],
                    [
                        'name' => 'POST /pos/notifications/{id}/read',
                        'method' => 'POST',
                        'path' => '/pos/notifications/{id}/read',
                        'auth' => true,
                        'summary' => __('applicationintegration-docs::doc.notifications_read'),
                        'response' => ['read' => true],
                        'notes' => [],
                    ],
                    [
                        'name' => 'POST /pos/notifications/test',
                        'method' => 'POST',
                        'path' => '/pos/notifications/test',
                        'auth' => true,
                        'summary' => __('applicationintegration-docs::doc.notifications_test'),
                        'response' => ['sent' => true],
                        'notes' => [],
                    ],
                ],
            ],
            [
                'id' => 'shared',
                'title' => __('applicationintegration-docs::doc.shared_title'),
                'description' => __('applicationintegration-docs::doc.shared_desc'),
                'endpoints' => [
                    [
                        'name' => 'GET /languages',
                        'method' => 'GET',
                        'path' => '/languages',
                        'auth' => true,
                        'summary' => __('applicationintegration-docs::doc.languages_summary'),
                        'response' => [
                            ['language_name' => 'English', 'language_code' => 'en', 'status' => 1],
                        ],
                        'notes' => [],
                    ],
                    [
                        'name' => 'GET /currencies',
                        'method' => 'GET',
                        'path' => '/currencies',
                        'auth' => true,
                        'summary' => __('applicationintegration-docs::doc.currencies_summary'),
                        'response' => [
                            ['id' => 1, 'currency_code' => 'USD'],
                        ],
                        'notes' => [],
                    ],
                    [
                        'name' => 'GET /payment-gateways',
                        'method' => 'GET',
                        'path' => '/payment-gateways',
                        'auth' => true,
                        'summary' => __('applicationintegration-docs::doc.gateways_summary'),
                        'response' => ['stripe_status' => 'active'],
                        'notes' => [],
                    ],
                    [
                        'name' => 'GET /staff',
                        'method' => 'GET',
                        'path' => '/staff',
                        'auth' => true,
                        'summary' => __('applicationintegration-docs::doc.staff_summary'),
                        'response' => [['id' => 5, 'name' => 'Manager']],
                        'notes' => [],
                    ],
                    [
                        'name' => 'GET /roles',
                        'method' => 'GET',
                        'path' => '/roles',
                        'auth' => true,
                        'summary' => __('applicationintegration-docs::doc.roles_summary'),
                        'response' => ['Owner_1', 'Manager_1'],
                        'notes' => [],
                    ],
                    [
                        'name' => 'GET /areas',
                        'method' => 'GET',
                        'path' => '/areas',
                        'auth' => true,
                        'summary' => __('applicationintegration-docs::doc.areas_summary'),
                        'response' => [['id' => 3, 'name' => 'Zone A']],
                        'notes' => [],
                    ],
                    [
                        'name' => 'GET /customer-addresses',
                        'method' => 'GET',
                        'path' => '/customer-addresses?customer_id=1',
                        'auth' => true,
                        'summary' => __('applicationintegration-docs::doc.customer_addresses'),
                        'response' => [['id' => 7, 'address' => 'Main St']],
                        'notes' => [],
                    ],
                    [
                        'name' => 'POST /customer-addresses',
                        'method' => 'POST',
                        'path' => '/customer-addresses',
                        'auth' => true,
                        'summary' => __('applicationintegration-docs::doc.customer_addresses_create'),
                        'body' => [
                            'customer_id' => 1,
                            'address' => '123 Main St',
                            'area_id' => 3,
                            'is_default' => true,
                        ],
                        'response' => [
                            'success' => true,
                            'data' => ['id' => 8, 'address' => '123 Main St'],
                        ],
                        'notes' => [],
                    ],
                    [
                        'name' => 'PUT /customer-addresses/{id}',
                        'method' => 'PUT',
                        'path' => '/customer-addresses/{id}',
                        'auth' => true,
                        'summary' => __('applicationintegration-docs::doc.customer_addresses_update'),
                        'body' => [
                            'address' => '456 Oak Ave',
                            'is_default' => false,
                        ],
                        'response' => [
                            'success' => true,
                            'data' => ['id' => 8, 'address' => '456 Oak Ave'],
                        ],
                        'notes' => [],
                    ],
                    [
                        'name' => 'DELETE /customer-addresses/{id}',
                        'method' => 'DELETE',
                        'path' => '/customer-addresses/{id}',
                        'auth' => true,
                        'summary' => __('applicationintegration-docs::doc.customer_addresses_delete'),
                        'response' => [
                            'success' => true,
                            'message' => 'Address deleted',
                        ],
                        'notes' => [],
                    ],
                ],
            ],
            [
                'id' => 'cashregister',
                'title' => __('applicationintegration-docs::doc.cashregister_title'),
                'description' => __('applicationintegration-docs::doc.cashregister_desc'),
                'endpoints' => [
                    // Registers
                    [
                        'name' => 'GET /pos/cash-register/registers',
                        'method' => 'GET',
                        'path' => '/pos/cash-register/registers',
                        'auth' => true,
                        'summary' => __('applicationintegration-docs::doc.cashregister_list'),
                        'response' => [
                            'success' => true,
                            'data' => [
                                ['id' => 1, 'name' => 'Main Register', 'is_active' => true],
                            ],
                        ],
                        'notes' => [],
                    ],
                    [
                        'name' => 'GET /pos/cash-register/registers/{id}',
                        'method' => 'GET',
                        'path' => '/pos/cash-register/registers/{id}',
                        'auth' => true,
                        'summary' => __('applicationintegration-docs::doc.cashregister_get'),
                        'response' => [
                            'success' => true,
                            'data' => ['id' => 1, 'name' => 'Main Register', 'is_active' => true],
                        ],
                        'notes' => [],
                    ],
                    [
                        'name' => 'POST /pos/cash-register/registers',
                        'method' => 'POST',
                        'path' => '/pos/cash-register/registers',
                        'auth' => true,
                        'summary' => __('applicationintegration-docs::doc.cashregister_create'),
                        'body' => ['name' => 'New Register', 'is_active' => true],
                        'response' => [
                            'success' => true,
                            'data' => ['id' => 2, 'name' => 'New Register'],
                        ],
                        'notes' => [],
                    ],
                    [
                        'name' => 'PUT /pos/cash-register/registers/{id}',
                        'method' => 'PUT',
                        'path' => '/pos/cash-register/registers/{id}',
                        'auth' => true,
                        'summary' => __('applicationintegration-docs::doc.cashregister_update'),
                        'body' => ['name' => 'Updated Register'],
                        'response' => [
                            'success' => true,
                            'data' => ['id' => 1, 'name' => 'Updated Register'],
                        ],
                        'notes' => [],
                    ],
                    [
                        'name' => 'DELETE /pos/cash-register/registers/{id}',
                        'method' => 'DELETE',
                        'path' => '/pos/cash-register/registers/{id}',
                        'auth' => true,
                        'summary' => __('applicationintegration-docs::doc.cashregister_delete'),
                        'response' => [
                            'success' => true,
                            'message' => 'Cash register deactivated',
                        ],
                        'notes' => [],
                    ],
                    // Sessions
                    [
                        'name' => 'GET /pos/cash-register/sessions',
                        'method' => 'GET',
                        'path' => '/pos/cash-register/sessions',
                        'auth' => true,
                        'summary' => __('applicationintegration-docs::doc.cashregister_sessions'),
                        'response' => [
                            'success' => true,
                            'data' => [
                                ['id' => 1, 'status' => 'open', 'opening_float' => 500.00],
                            ],
                        ],
                        'notes' => [],
                    ],
                    [
                        'name' => 'GET /pos/cash-register/sessions/active',
                        'method' => 'GET',
                        'path' => '/pos/cash-register/sessions/active',
                        'auth' => true,
                        'summary' => __('applicationintegration-docs::doc.cashregister_session_active'),
                        'response' => [
                            'success' => true,
                            'data' => [
                                'id' => 1,
                                'status' => 'open',
                                'opening_float' => 500.00,
                                'running_total' => 1250.00,
                            ],
                        ],
                        'notes' => [],
                    ],
                    [
                        'name' => 'GET /pos/cash-register/sessions/{id}',
                        'method' => 'GET',
                        'path' => '/pos/cash-register/sessions/{id}',
                        'auth' => true,
                        'summary' => __('applicationintegration-docs::doc.cashregister_session_get'),
                        'response' => [
                            'success' => true,
                            'data' => [
                                'id' => 1,
                                'status' => 'open',
                                'transactions' => [],
                            ],
                        ],
                        'notes' => [],
                    ],
                    [
                        'name' => 'POST /pos/cash-register/sessions/open',
                        'method' => 'POST',
                        'path' => '/pos/cash-register/sessions/open',
                        'auth' => true,
                        'summary' => __('applicationintegration-docs::doc.cashregister_session_open'),
                        'body' => [
                            'cash_register_id' => 1,
                            'opening_float' => 500.00,
                            'note' => 'Opening shift',
                        ],
                        'response' => [
                            'success' => true,
                            'data' => ['id' => 1, 'status' => 'open'],
                        ],
                        'notes' => [
                            __('applicationintegration-docs::doc.cashregister_session_note'),
                        ],
                    ],
                    [
                        'name' => 'POST /pos/cash-register/sessions/{id}/close',
                        'method' => 'POST',
                        'path' => '/pos/cash-register/sessions/{id}/close',
                        'auth' => true,
                        'summary' => __('applicationintegration-docs::doc.cashregister_session_close'),
                        'body' => [
                            'expected_cash' => 1250.00,
                            'counted_cash' => 1248.50,
                            'closing_note' => 'End of shift',
                            'denomination_counts' => [
                                ['denomination_id' => 1, 'count' => 10],
                            ],
                        ],
                        'response' => [
                            'success' => true,
                            'data' => [
                                'discrepancy' => -1.50,
                                'status' => 'closed',
                            ],
                        ],
                        'notes' => [
                            __('applicationintegration-docs::doc.cashregister_close_note'),
                        ],
                    ],
                    [
                        'name' => 'GET /pos/cash-register/sessions/{id}/summary',
                        'method' => 'GET',
                        'path' => '/pos/cash-register/sessions/{id}/summary',
                        'auth' => true,
                        'summary' => __('applicationintegration-docs::doc.cashregister_session_summary'),
                        'response' => [
                            'success' => true,
                            'data' => [
                                'totals' => [
                                    'cash_sales' => 750.00,
                                    'cash_in' => 100.00,
                                    'cash_out' => 50.00,
                                ],
                            ],
                        ],
                        'notes' => [],
                    ],
                    [
                        'name' => 'GET /pos/cash-register/sessions/{id}/transactions',
                        'method' => 'GET',
                        'path' => '/pos/cash-register/sessions/{id}/transactions',
                        'auth' => true,
                        'summary' => __('applicationintegration-docs::doc.cashregister_transactions'),
                        'response' => [
                            'success' => true,
                            'data' => [
                                ['id' => 1, 'type' => 'cash_sale', 'amount' => 50.00],
                            ],
                        ],
                        'notes' => [],
                    ],
                    // Transactions
                    [
                        'name' => 'POST /pos/cash-register/transactions/cash-in',
                        'method' => 'POST',
                        'path' => '/pos/cash-register/transactions/cash-in',
                        'auth' => true,
                        'summary' => __('applicationintegration-docs::doc.cashregister_cash_in'),
                        'body' => [
                            'amount' => 100.00,
                            'reason' => 'Petty cash for supplies',
                        ],
                        'response' => [
                            'success' => true,
                            'data' => ['id' => 5, 'type' => 'cash_in', 'amount' => 100.00],
                        ],
                        'notes' => [],
                    ],
                    [
                        'name' => 'POST /pos/cash-register/transactions/cash-out',
                        'method' => 'POST',
                        'path' => '/pos/cash-register/transactions/cash-out',
                        'auth' => true,
                        'summary' => __('applicationintegration-docs::doc.cashregister_cash_out'),
                        'body' => [
                            'amount' => 50.00,
                            'reason' => 'Change for customer',
                        ],
                        'response' => [
                            'success' => true,
                            'data' => ['id' => 6, 'type' => 'cash_out', 'amount' => 50.00],
                        ],
                        'notes' => [],
                    ],
                    [
                        'name' => 'POST /pos/cash-register/transactions/safe-drop',
                        'method' => 'POST',
                        'path' => '/pos/cash-register/transactions/safe-drop',
                        'auth' => true,
                        'summary' => __('applicationintegration-docs::doc.cashregister_safe_drop'),
                        'body' => [
                            'amount' => 500.00,
                            'reason' => 'Midday safe drop',
                        ],
                        'response' => [
                            'success' => true,
                            'data' => ['id' => 7, 'type' => 'safe_drop', 'amount' => 500.00],
                        ],
                        'notes' => [],
                    ],
                    // Denominations
                    [
                        'name' => 'GET /pos/cash-register/denominations',
                        'method' => 'GET',
                        'path' => '/pos/cash-register/denominations',
                        'auth' => true,
                        'summary' => __('applicationintegration-docs::doc.cashregister_denominations'),
                        'response' => [
                            'success' => true,
                            'data' => [
                                ['id' => 1, 'name' => '$1', 'value' => 1.00, 'type' => 'bill'],
                            ],
                        ],
                        'notes' => [],
                    ],
                    [
                        'name' => 'GET /pos/cash-register/denominations/{uuid}',
                        'method' => 'GET',
                        'path' => '/pos/cash-register/denominations/{uuid}',
                        'auth' => true,
                        'summary' => __('applicationintegration-docs::doc.cashregister_denomination_get'),
                        'response' => [
                            'success' => true,
                            'data' => ['id' => 1, 'name' => '$1', 'value' => 1.00],
                        ],
                        'notes' => [],
                    ],
                    [
                        'name' => 'POST /pos/cash-register/denominations',
                        'method' => 'POST',
                        'path' => '/pos/cash-register/denominations',
                        'auth' => true,
                        'summary' => __('applicationintegration-docs::doc.cashregister_denomination_create'),
                        'body' => ['name' => '$5', 'value' => 5.00, 'type' => 'bill'],
                        'response' => [
                            'success' => true,
                            'data' => ['id' => 2, 'name' => '$5', 'value' => 5.00],
                        ],
                        'notes' => [],
                    ],
                    [
                        'name' => 'PUT /pos/cash-register/denominations/{uuid}',
                        'method' => 'PUT',
                        'path' => '/pos/cash-register/denominations/{uuid}',
                        'auth' => true,
                        'summary' => __('applicationintegration-docs::doc.cashregister_denomination_update'),
                        'body' => ['name' => '$5 Bill', 'is_active' => true],
                        'response' => [
                            'success' => true,
                            'data' => ['id' => 2, 'name' => '$5 Bill'],
                        ],
                        'notes' => [],
                    ],
                    [
                        'name' => 'DELETE /pos/cash-register/denominations/{uuid}',
                        'method' => 'DELETE',
                        'path' => '/pos/cash-register/denominations/{uuid}',
                        'auth' => true,
                        'summary' => __('applicationintegration-docs::doc.cashregister_denomination_delete'),
                        'response' => [
                            'success' => true,
                            'message' => 'Denomination deleted',
                        ],
                        'notes' => [],
                    ],
                    // Customer delete endpoint
                    [
                        'name' => 'DELETE /pos/customers/{id}',
                        'method' => 'DELETE',
                        'path' => '/pos/customers/{id}',
                        'auth' => true,
                        'summary' => __('applicationintegration-docs::doc.customers_delete'),
                        'response' => [
                            'success' => true,
                            'message' => 'Customer deleted',
                        ],
                        'notes' => [
                            __('applicationintegration-docs::doc.customers_delete_note'),
                        ],
                    ],
                ],
            ],
            [
                'id' => 'sdks',
                'title' => __('applicationintegration-docs::doc.sdks_title'),
                'description' => __('applicationintegration-docs::doc.sdks_desc'),
                'samples' => [
                    'method' => 'GET',
                    'path' => '/pos/items',
                    'body' => null,
                    'response' => [
                        'status' => true,
                        'data' => [
                            'items' => [
                                ['id' => 1, 'name' => 'Espresso'],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
