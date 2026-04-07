POS backend (Laravel) – architecture and integration guide
==========================================================

Scope
-----
- Module: `Modules/ApplicationIntegration`
- API prefix: `/api/application-integration`
- Auth: Sanctum (`auth:sanctum`)
- Feature gate: `EnsurePosFeatureEnabled` (requires POS/Order module; superadmin with no restaurant_id bypasses)
- Default branch/restaurant resolution in controllers: user.branch_id -> first branch of user.restaurant -> first restaurant/branch fallback (never null to keep API usable).

Route map (high level)
----------------------
- `/auth/login`, `/auth/me`
- `/platform/config`, `/platform/permissions`, `/platform/printers`, `/platform/receipt-settings`, `/platform/switch-branch`
- `/languages`, `/currencies`, `/payment-gateways`, `/staff`, `/roles`, `/areas`
- `/customer-addresses` (list/create/update/delete)
- `/customer/catalog`, `/customer/orders` (place/list)
- POS catalog: `/pos/menus`, `/pos/categories`, `/pos/items`, `/pos/items/category/{id}`, `/pos/items/menu/{id}`, `/pos/items/{id}/variations`, `/pos/items/{id}/modifier-groups`
- POS helpers: `/pos/extra-charges/{orderType}`, `/pos/tables`, `/pos/tables/{id}/unlock`, `/pos/reservations/today`, `/pos/order-types`, `/pos/actions`, `/pos/delivery-platforms`, `/pos/get-order-number`, `/pos/waiters`, `/pos/customers`, `/pos/phone-codes`, `/pos/customers` (POST)
- Orders: `/pos/orders` (list/create), `/pos/orders/{id}`, `/pos/orders/{id}/status`, `/pos/orders/{id}/pay`, `/pos/orders/{id}/kot` (create KOT), `/pos/orders/{id}/kots` (list order KOTs)
- KOT Management: `/pos/kots` (list), `/pos/kots/{id}` (detail), `/pos/kots/{id}/status` (update), `/pos/kot-items/{id}/status` (update item), `/pos/kot-places`, `/pos/kot-cancel-reasons`
- Delivery Management: `/pos/delivery-settings`, `/pos/delivery-fee/calculate`, `/pos/delivery-fee-tiers`, `/pos/delivery-platforms` (CRUD), `/pos/delivery-executives` (CRUD), `/pos/delivery-executives/{id}/status`, `/pos/orders/{id}/assign-delivery`, `/pos/orders/{id}/delivery-status`, `/pos/delivery-orders`
- Taxes/branches/restaurants/reservations: `/pos/taxes`, `/pos/restaurants`, `/pos/branches`, `/pos/reservations`, `/pos/reservations` (POST), `/pos/reservations/{id}/status`

Auth and session
----------------
- `POST /auth/login`: email/password -> bearer token + user payload (id, name, email, restaurant_id, branch_id, roles).
- `GET /auth/me`: current user + enabled modules (restaurant_modules()).

Platform bootstrap
------------------
- `GET /platform/config`: returns user, restaurant (id/name/hash/logo/currency), branch (id/name/hash/address/timezone), feature flags (pos/order/delivery/customer_app/waiter_app/theme/payment_gateway_integration), modules list, payment gateway meta (status/mode/currency/qr_code_image_url), languages.
- `GET /platform/permissions`: user roles + permissions.
- `GET /platform/printers`: printers for current branch.
- `GET /platform/receipt-settings`: restaurant receipt settings.
- `POST /platform/switch-branch`: sets user.branch_id; client must reload branch-scoped data (menus, categories, items, tables, reservations, orders, waiters, currencies).

Shared/app-wide data
--------------------
- `GET /languages`, `GET /currencies`, `GET /payment-gateways`, `GET /staff` (id,name,email,branch_id), `GET /roles` (names), `GET /areas` (branch areas).
- Customer addresses: `GET/POST/PUT/DELETE /customer-addresses` (by customer_id).

Customer-facing subset
----------------------
- `GET /customer/catalog`: menus + categories + items (same payload as POS catalog).
- `POST /customer/orders`: forwards to POS submitOrder with `placed_via=app`.
- `GET /customer/orders`: paginated orders for a given customer_id.

POS catalog
-----------
Branch-scoped; cached 60 minutes per branch key where noted.
- `GET /pos/menus`: id, translated menu_name, sort_order (cached).
- `GET /pos/categories`: id, item count, translated category_name, sort_order (cached).
- `GET /pos/items`: all menu items with prices (per order type), variations, modifier groups + counts (cached).
- `GET /pos/items/category/{id}` or `/menu/{id}`: filtered items with prices/variations/modifier groups.
- `GET /pos/items/{id}/variations`: variations for an item.
- `GET /pos/items/{id}/modifier-groups`: modifier groups + modifiers for an item.
- `GET /pos/extra-charges/{orderType}`: enabled restaurant charges for order type.
- `GET /pos/tables`: active tables (not running), with lock info (locked_by_user_id/name, is_locked, locked_at), area info, seating, available_status; also returns `is_admin`. Cleans expired locks.
- `GET /pos/tables?include_running=1`: include running tables; adds `summary` counts (total/running/available/locked_by_me/locked_by_other) and `is_running` per table.
- `POST /pos/tables/{id}/unlock`: admin or locker can unlock; otherwise 403.
- `GET /pos/reservations/today`: today’s reservations with table_code/time/party_size/status.
- `GET /pos/order-types`: active order types (id, slug, order_type_name, type).
- `GET /pos/actions`: action semantics (draft/kot/bill/cancel/pay with effects).
- `GET /pos/delivery-platforms`: active delivery platforms (id/name/logo/url).
- `GET /pos/get-order-number`: returns `[order_number, formatted_order_number]` (prefix-aware).
- `GET /pos/waiters`: users for the restaurant (cached). Supports `?role=` filter (matches role/type/job text, e.g., delivery/driver/courier/waiter) and `?include_permissions=1` to return permissions. Returns role list and `is_delivery_staff` flag.

POS customer helpers
--------------------
- `GET /pos/customers?search=`: search by name/phone/email (min length 2, limit 10).
- `GET /pos/phone-codes`: distinct phone codes, optional search filter.
- `POST /pos/customers`: upsert customer by email/phone; stores name/email/phone/delivery_address; clears cache.

Taxes, branches, restaurants
----------------------------
- `GET /pos/taxes`: taxes, optionally filtered via restaurant_taxes mapping if present.
- `GET /pos/restaurants`: id + restaurant_name.
- `GET /pos/branches`: branches for current restaurant; best-effort columns (id/name/branch_name/hash/unique_hash).

Order lifecycle (submitOrder)
-----------------------------
Endpoint: `POST /pos/orders`.
Key inputs:
- `items` (required): each `{id, price, quantity, variation_id?, note?, tax_amount?, tax_percentage?, tax_breakup?, modifiers?}`; subtotal = sum(price + modifiers_total) * qty.
- `customer`: `{name, phone, email}` (creates/updates customer for restaurant).
- `order_type`: display string; normalized (lower/underscored) and matched against active OrderType by slug/type/name; fallback to default active; else uses provided slug/name.
- `pax`, `waiter_id`, `table_id` (locks table), `delivery_address`, `delivery_time`, `delivery_fee`, `delivery_executive_id`, `delivery_app_id`, `customer_address_id`.
- `actions` (array): first element drives status: `bill/billed` -> status billed, order_status confirmed, table running; `kot` -> status kot, order_status confirmed, table running, creates KOT; `cancel` -> status/order_status canceled, table available; default -> status draft, order_status placed.
- `discount_type`, `discount_value`, `discount_amount`, `extra_charges` (IDs).
- `taxes`: list `{id, amount}` (saved to order_taxes).
- `note`, `placed_via` (defaults to `pos`).

Processing:
1) Guard branch/plan; 400 with plan_not_allowed if missing.
2) Require items else 422 orderItemRequired.
3) Resolve order type model; capture id/slug/name.
4) Dine-in with table_id: check lock; if locked by other user and cannot access, 403 with tableHandledByUser message.
5) Upsert customer if provided; capture customer_id.
6) Subtotal = sum(price + modifiers_total) * qty. Apply discount_amount. Add taxes amounts. Add extra charges via RestaurantCharge::getAmount on discounted subtotal. Clamp total >= 0.
7) Generate order number (with prefix if enabled).
8) Derive statuses from action (above) and table_status (running/available).
9) Create Order with totals, order_type fields, delivery fields, waiter_id, pax, table_id, customer_id, placed_via, tax_mode=order; if billed sets added_by current user.
10) Attach extra charges (order_charges).
11) Insert order items (variation_id, tax info, note, amount; adds total column if schema has it).
    - If schema has `modifiers`/`modifiers_total`/`modifier_ids`, they are populated from `modifiers` array; amount includes modifiers_total even if columns don’t exist (for correct totals).
12) Insert order taxes.
13) Update table availability; if running, create/update TableSession locked by current user.
14) If KOT, create KOT and KOT items.
15) Return success + OrderResource (with items/charges/taxes) + kot_ids. On exception logs and returns 500 orderSaveError.

Orders query and payment
------------------------
- `GET /pos/orders`: paginated orders for branch; filters status/date_from/date_to/search (order number).
- `GET /pos/orders/{id}`: order with items/charges/taxes/customer/table.
- `POST /pos/orders/{id}/status`: update status/order_status.
- `POST /pos/orders/{id}/pay`: mark paid/billed; frees table and deletes TableSession if present.

Reservations (POS side)
-----------------------
- `GET /pos/reservations`: paginated by status.
- `POST /pos/reservations`: create pending reservation (table_id, reservation_date_time, party_size, name?, phone?).
- `POST /pos/reservations/{id}/status`: update reservation_status.

Table locking rules
-------------------
- `GET /pos/tables` shows lock flags per table (`is_locked`, `is_locked_by_current_user`, `is_locked_by_other_user`, `locked_by_user_id/name`, `locked_at`, `is_running`).
- `POST /pos/tables/{id}/unlock` allowed for admin or the user who locked it; otherwise 403.
- `submitOrder` blocks dine-in on locked table not owned/allowed; `TableSession::updateOrCreate` locks when running; `payOrder` frees table and deletes TableSession.

Plan/role enforcement
---------------------
- `EnsurePosFeatureEnabled`: denies 403 if restaurant plan lacks POS/Order modules (case-insensitive list).
- Table access check: if `canBeAccessedByUser` exists and current user is not allowed, returns 403 on submitOrder.

Caching
-------
- Cached 60 minutes per branch: menus (`menus_{branchId}`), categories (`categories_{branchId}`), menu items (`menu_items_{branchId}`), waiters (`waiters_{branchId}`).
- Clients should drop caches after branch switch or relevant updates.

Payload hints for clients (system/Flutter POS)
----------------------------------------------
- Auth then call `/platform/config`; verify `features.pos` and modules.
- After branch switch, reload menus/categories/items/tables/reservations/orders/waiters/currencies.
- Build order payload:
  * `order_type`: human string (e.g., "Dine In", "Take Away", "Delivery"); backend normalizes to slug.
  * Dine-in: include `table_id`, `pax`; handle 403 lock responses; use `/pos/tables/{id}/unlock` only if permitted.
  * Delivery: include `delivery_address`, optionally `delivery_executive_id` (waiter_id) and `delivery_app_id`, `delivery_fee`.
  * Items: `id`, `price`, `quantity`, optional `menu_item_variation_id`, `note`, `tax_amount/tax_percentage/tax_breakup`, `modifiers` (id/name/price) rolled into price for totals.
  * Charges/discount/taxes: use `extra_charges` IDs, `discount_amount/value/type`, `taxes` list if computed client-side; backend also supports `getExtraCharges` and `getTaxes`.
  * Actions: `kot`, `bill`, `cancel`, or empty for draft/placed; affects table status and KOT creation. `pay` via separate endpoint.
- Use `/pos/order-types` to align UI labels to slugs, `/pos/actions` for semantics, `/pos/delivery-platforms` for external delivery sources, `/pos/phone-codes` and `/pos/customers` for quick customer entry, `/pos/reservations/today` for table availability context. Use `/pos/waiters?role=delivery&include_permissions=1` for delivery staff.

Error behaviors to expect
-------------------------
- 400 plan_not_allowed when POS not enabled.
- 401 unauthorized when token missing/expired.
- 403 on table locked by another user or unlock not permitted.
- 422 orderItemRequired when items empty.
- 500 orderSaveError with message logged server side on unexpected failures.

Models touched (read/created)
-----------------------------
- Restaurant, Branch (+BranchDeliverySetting), Menu, MenuItem (+variations, prices, modifierGroups.modifiers), ItemCategory, Order, OrderItem, OrderTax, RestaurantCharge, OrderType, Table, TableSession, Reservation, DeliveryPlatform, DeliveryExecutive, DeliveryFeeTier, Customer (+Address), Country (phone codes), Tax, Kot (+KotItem, +KotPlace, +KotCancelReason, +KotItemModifierOption), Printer, ReceiptSetting, PaymentGatewayCredential, LanguageSetting, User, Role.

End-to-end logic and workflows (system POS)
-------------------------------------------
This section describes how the POS behaves across all order types and states, combining backend rules with expected frontend flows (the “system POS”, not the Flutter client).

Order types and state machine
- Types: dine_in, take_away, delivery (plus any custom active OrderType in DB). Each type can have its own price rows (`prices.order_type_id`) and extra charges.
- Actions map to states:
  * No action / default: Order status placed, table available.
  * bill/billed: Order status confirmed/billed; table set to running; TableSession locked by current user.
  * kot: Order status confirmed; KOT created with token/kot number; table running; TableSession locked.
  * cancel: Order status canceled; table set to available; no lock.
  * pay (API `/pay`): Marks paid/billed and frees table + deletes TableSession.
- Table lock rules: Tables marked running are excluded from `/pos/tables` results unless `include_running=1`. If a table has a TableSession locked by another user and `canBeAccessedByUser` denies access, submitOrder returns 403. Unlock requires admin or locker (`/pos/tables/{id}/unlock`).

Core workflows (happy paths)
- Dine-in:
  1) Operator selects branch (optional) and table from `/pos/tables`; sees lock status.
  2) Adds items (with variation, modifiers, price) to cart; sets pax.
  3) Choose action:
     - Save as draft/placed (no action): keeps table available.
     - KOT: creates order + KOT, table running/locked.
     - Bill: creates order billed, table running/locked.
  4) Payment: `/pos/orders/{id}/pay` frees table.
- Take-away:
  1) No table/pax needed; add items.
  2) Submit with desired action (draft/kot/bill). No table locking.
- Delivery:
  1) Collect customer + address (or `customer_address_id`), optionally delivery platform/fee/time and delivery staff (`delivery_executive_id` or waiter_id).
  2) Add items; submit with action (typically draft/bill). No table interaction. Extra charges can come from `extra-charges/delivery` if configured.
- Reservations:
  - Today view: `/pos/reservations/today` shows upcoming tables with times/status.
  - Create/update: `/pos/reservations` (POST) and `/pos/reservations/{id}/status` to manage lifecycle (pending/confirmed/canceled).
  - Reservations do not auto-lock tables; locking occurs when an order is placed on that table.

Pricing, taxes, charges, discounts
- Item price resolution: client supplies price per item; backend trusts given price (prices also available via catalog; variations/prices include order_type-specific rows). Subtotal = sum(price + modifiers_total) * qty.
- Discounts: `discount_amount` is subtracted from subtotal; `discount_value/type` accepted but core math uses amount before taxes/charges.
- Extra charges: backend computes amounts via `RestaurantCharge::getAmount(discountedSubtotal)` for IDs provided in `extra_charges`.
- Taxes: accepted as list with `amount` per tax ID; persisted to order_taxes and added to total.
- Total clamped to >= 0 after charges/taxes/discounts.

Customers and addresses
- Customer upsert on submit if name/phone/email provided; matching by phone then email for restaurant. Delivery address can be sent inline (`delivery_address`) or via `customer_address_id`; separate address CRUD endpoints exist.

KOT handling
- If action = kot, a Kot record is created with token and kot_number for the branch/order_type, and KOT items mirror order items. Order status becomes confirmed; table locks if dine-in.

KOT Management Endpoints (Kitchen Display)
------------------------------------------
Full KOT workflow for kitchen displays and ticket management:

List KOTs
- `GET /pos/kots`: List KOTs for kitchen display.
  * Query params: `status` (pending_confirmation|in_kitchen|food_ready|served|cancelled), `kitchen_place_id`, `date` (YYYY-MM-DD, defaults to today), `limit` (max 100), `offset`.
  * Returns: array of KOTs with items, order info, table name, kitchen place.
  * Use for kitchen display screens; filter by status for workflow stages.

Get KOT Detail
- `GET /pos/kots/{id}`: Get single KOT with full details.
  * Returns: KOT with items (menu item name, variation, modifiers, quantity, status, note), order info, cancel reason if cancelled.

Create KOT for Existing Order
- `POST /pos/orders/{id}/kot`: Create new KOT for an order.
  * Body: `order_item_ids` (optional, defaults to all items), `note`, `kitchen_place_id`.
  * Returns: kot_id, kot_number, token_number, items_count.
  * Use when adding items to running order or re-sending to kitchen.

Get Order's KOTs
- `GET /pos/orders/{id}/kots`: Get all KOTs for a specific order.
  * Returns: list of KOTs with items, useful for order history view.

Update KOT Status
- `PUT /pos/kots/{id}/status`: Change KOT status.
  * Body: `status` (pending_confirmation|in_kitchen|food_ready|served|cancelled), `cancel_reason_id`, `cancel_reason_text` (when cancelling).
  * Status flow: pending_confirmation -> in_kitchen -> food_ready -> served.
  * When cancelled, all items are also cancelled.
  * When served, all non-cancelled items marked ready.

Update KOT Item Status
- `PUT /pos/kot-items/{id}/status`: Change individual item status.
  * Body: `status` (pending|cooking|ready|cancelled), `cancel_reason_id`, `cancel_reason_text`.
  * Status flow: pending -> cooking -> ready.
  * When all items ready, KOT auto-updates to food_ready if in_kitchen.

Kitchen Places
- `GET /pos/kot-places`: Get kitchen stations/places.
  * Returns: id, name, type (food/beverage/etc), is_default, printer_id.
  * Use to route KOTs to specific kitchens.

Cancel Reasons
- `GET /pos/kot-cancel-reasons`: Get predefined cancellation reasons.
  * Returns: id, reason, cancel_order (bool), cancel_kot (bool).
  * Use in cancel dialogs for consistent tracking.

KOT Status Values
- KOT statuses: pending_confirmation, in_kitchen, food_ready, served, cancelled
- KOT item statuses: pending, cooking, ready, cancelled

Kitchen Display Workflow
1. Fetch KOTs: `GET /pos/kots?status=in_kitchen` for active tickets.
2. Display items with modifiers and notes.
3. Update item status as cooked: `PUT /pos/kot-items/{id}/status` with status=cooking then ready.
4. When all items ready, bump KOT: `PUT /pos/kots/{id}/status` with status=food_ready.
5. Waiter serves and marks: `PUT /pos/kots/{id}/status` with status=served.

Delivery Management Endpoints
-----------------------------
Complete delivery platform and order management for third-party delivery apps and in-house delivery.

Delivery Settings
- `GET /pos/delivery-settings`: Get branch delivery configuration.
  * Returns: is_enabled, max_radius, unit (km/miles), fee_type (fixed/tiered/per_distance), fixed_fee, per_distance_rate, free_delivery_over_amount, free_delivery_within_radius, delivery_schedule_start/end, prep_time_minutes, avg_delivery_speed_kmh, branch_lat/lng, fee_tiers array.
  * Use to configure delivery UI (radius map, fee display, schedule).

Calculate Delivery Fee
- `POST /pos/delivery-fee/calculate`: Calculate fee based on customer location.
  * Body: `lat`, `lng`, `order_amount` (optional, for free delivery threshold).
  * Returns: available (bool), distance (km), fee, is_free_delivery, eta_min, eta_max, message.
  * Use when customer enters address to show delivery availability and cost.

Fee Types:
- `fixed`: Same fee for all distances within radius.
- `per_distance`: Rate per km/mile (rounded up).
- `tiered`: Distance-based tiers with specific fees for ranges.

Delivery Fee Tiers
- `GET /pos/delivery-fee-tiers`: Get distance-based fee tiers.
  * Returns: array of {id, min_distance, max_distance, fee}.
  * Only relevant when fee_type is 'tiered'.

Delivery Platforms (Third-Party Apps)
- `GET /pos/delivery-platforms`: List active platforms (Uber Eats, DoorDash, etc.).
  * Returns: id, name, logo_url for each platform.
- `GET /pos/delivery-platforms/{id}`: Get platform with commission details.
  * Returns: id, name, logo, logo_url, commission_type (percent/fixed), commission_value, formatted_commission, is_active.
- `POST /pos/delivery-platforms`: Create new platform.
  * Body: `name` (required), `commission_type`, `commission_value`, `is_active`.
- `PUT /pos/delivery-platforms/{id}`: Update platform.
  * Body: any of name, commission_type, commission_value, is_active.
- `DELETE /pos/delivery-platforms/{id}`: Delete or deactivate platform.
  * If platform has orders, it's deactivated instead of deleted.

Delivery Executives (Drivers)
- `GET /pos/delivery-executives`: List delivery staff.
  * Query params: `status` (available/on_delivery/inactive), `search`.
  * Returns: id, name, phone, phone_code, status, photo.
- `POST /pos/delivery-executives`: Create new executive.
  * Body: `name` (required), `phone`, `phone_code`, `status`.
- `PUT /pos/delivery-executives/{id}`: Update executive.
  * Body: any of name, phone, phone_code, status.
- `DELETE /pos/delivery-executives/{id}`: Delete or set inactive.
  * If executive has orders, status is set to inactive.
- `PUT /pos/delivery-executives/{id}/status`: Quick status update.
  * Body: `status` (available/on_delivery/inactive).

Delivery Order Management
- `PUT /pos/orders/{id}/assign-delivery`: Assign executive/platform to order.
  * Body: `delivery_executive_id`, `delivery_app_id` (one or both).
  * Automatically sets executive status to 'on_delivery'.
- `PUT /pos/orders/{id}/delivery-status`: Update delivery tracking status.
  * Body: `status` (preparing/ready_for_pickup/out_for_delivery/delivered/failed).
  * When delivered/failed, executive status auto-resets to 'available'.
- `GET /pos/delivery-orders`: Get filtered delivery orders.
  * Query params: `status`, `delivery_executive_id`, `delivery_app_id`, `date`, `limit`, `offset`.
  * Returns: order list with customer, delivery info, items_count.

Delivery Order Workflow
1. Order placed with order_type='delivery' and delivery_address.
2. Calculate fee: `POST /pos/delivery-fee/calculate` with customer lat/lng.
3. Assign driver: `PUT /pos/orders/{id}/assign-delivery`.
4. Track status: `PUT /pos/orders/{id}/delivery-status` through preparing → ready_for_pickup → out_for_delivery → delivered.
5. Monitor active deliveries: `GET /pos/delivery-orders?status=out_for_delivery`.

Delivery Status Values
- Order delivery statuses: preparing, ready_for_pickup, out_for_delivery, delivered, failed
- Executive statuses: available, on_delivery, inactive

Third-Party Platform Integration
- When order placed via delivery app (Uber Eats, etc.), set `delivery_app_id` in submitOrder.
- Commission calculated via `DeliveryPlatform::getCommissionAmount($amount)`.
- Price markup for platform menu via `DeliveryPlatform::getPriceWithCommission($basePrice)`.

Branch and feature gating
- All POS endpoints are branch-scoped; branch is derived from the authenticated user and can be changed via `/platform/switch-branch`.
- Middleware ensures the restaurant has POS/Order module; otherwise 403.
- Feature flags from `/platform/config` inform frontend whether delivery/customer_app/waiter_app/theme/payment_gateway modules are available.

Caching and freshness
- Menus/categories/items/waiters cached 60 minutes per branch. After branch switch or data changes, client should invalidate and refetch.
- Tables/reservations/orders are live queries (no cache) and should be refreshed after critical actions (submitOrder, pay, unlock).

Failure scenarios and handling
- Missing items: 422 with `orderItemRequired`.
- Locked table owned by another user: 403 with human-readable message; frontend should offer unlock only if user is admin/locker.
- Plan not allowed: 400 plan_not_allowed (feature gate).
- Unauthorized: 401 on missing/expired token.
- Pay on dine-in frees table; if pay fails, table remains locked/running.
- Unlock attempts by non-admin/non-locker: 403.

Frontend (system POS) expectations
- Always load config/permissions first, then branch-scoped data.
- Respect order_type price/extra charge differences; fetch order types and extra charges per type.
- When dine-in, enforce table selection before KOT/Bill; surface lock owner/time in UI.
- For delivery, enforce address and delivery staff/platform as needed; show delivery fee/time if provided.
- Support multiple actions: draft-save, KOT, Bill, Cancel, Pay; each maps to backend action/state above.
- Keep carts isolated per order intent (order type, table, delivery) so payload sent matches the intended flow.
