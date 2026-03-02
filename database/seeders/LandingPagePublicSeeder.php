<?php

namespace Database\Seeders;

use App\Enums\PackageType;
use App\Models\Contact;
use App\Models\CustomMenu;
use App\Models\FrontDetail;
use App\Models\FrontFaq;
use App\Models\FrontReviewSetting;
use App\Models\GlobalCurrency;
use App\Models\LanguageSetting;
use App\Models\Module;
use App\Models\Package;
use Illuminate\Database\Seeder;

class LandingPagePublicSeeder extends Seeder
{
    public function run(): void
    {
        $languages = LanguageSetting::query()->where('active', 1)->get();

        if ($languages->isEmpty()) {
            return;
        }

        $this->seedCustomMenus();
        $this->seedPublicPackages();

        foreach ($languages as $language) {
            $this->seedLanguageLandingContent((int) $language->id);
        }
    }

    private function seedLanguageLandingContent(int $languageId): void
    {
        // Ne pas ecraser l'existant: cree uniquement si absent.
        FrontDetail::query()->firstOrCreate(
            ['language_setting_id' => $languageId],
            [
                'header_title' => 'Logiciel de caisse restaurant simple et complet',
                'header_description' => 'Gerez vos commandes, menus, tables et paiements depuis une seule plateforme.',
                'feature_with_image_heading' => 'Pilotez votre restaurant facilement',
                'feature_with_icon_heading' => 'Des fonctionnalites utiles au quotidien',
                'review_heading' => 'Ils utilisent deja la solution',
                'price_heading' => 'Tarifs clairs et sans surprise',
                'price_description' => 'Choisissez une formule adaptee a la taille de votre etablissement.',
                'faq_heading' => 'Questions frequentes',
                'faq_description' => 'Retrouvez les reponses aux questions les plus courantes.',
                'contact_heading' => 'Contact',
                'footer_copyright_text' => '© ' . now()->year . ' ' . config('app.name', 'TableTrack') . '.',
            ]
        );

        // Demande utilisateur: ne pas toucher aux front_features.

        if (!FrontReviewSetting::query()->where('language_setting_id', $languageId)->exists()) {
            FrontReviewSetting::query()->insert([
                [
                    'language_setting_id' => $languageId,
                    'reviewer_name' => 'Sophie Martin',
                    'reviewer_designation' => 'Gerante - Brasserie du Centre',
                    'reviews' => '"Interface claire, prise en main rapide et vrai gain de temps en service."',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'language_setting_id' => $languageId,
                    'reviewer_name' => 'Karim Diallo',
                    'reviewer_designation' => 'Proprietaire - Le Comptoir',
                    'reviews' => '"Le suivi des commandes et des tables nous a permis de reduire les erreurs."',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'language_setting_id' => $languageId,
                    'reviewer_name' => 'Nadia K.',
                    'reviewer_designation' => 'Responsable de salle',
                    'reviews' => '"Le tableau de bord est pratique et les rapports sont utiles pour decider vite."',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }

        if (!FrontFaq::query()->where('language_setting_id', $languageId)->exists()) {
            FrontFaq::query()->insert([
                [
                    'language_setting_id' => $languageId,
                    'question' => 'Est-ce que je peux tester la plateforme gratuitement ?',
                    'answer' => 'Oui, une periode d essai est disponible selon le plan configure.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'language_setting_id' => $languageId,
                    'question' => 'Le logiciel fonctionne-t-il sur tablette ?',
                    'answer' => 'Oui, l application est utilisable sur ordinateur, tablette et mobile.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'language_setting_id' => $languageId,
                    'question' => 'Puis-je gerer plusieurs points de vente ?',
                    'answer' => 'Oui, selon le plan souscrit, vous pouvez gerer plusieurs terminaux et equipes.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'language_setting_id' => $languageId,
                    'question' => 'Les paiements en ligne sont-ils pris en charge ?',
                    'answer' => 'Oui, plusieurs passerelles de paiement peuvent etre activees.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'language_setting_id' => $languageId,
                    'question' => 'Puis-je modifier mon menu facilement ?',
                    'answer' => 'Oui, vous pouvez ajouter, editer ou retirer des articles en quelques clics.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'language_setting_id' => $languageId,
                    'question' => 'Comment contacter le support ?',
                    'answer' => 'Le support est joignable par email via la section contact.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }

        Contact::query()->firstOrCreate(
            ['language_setting_id' => $languageId],
            [
                'contact_company' => 'Service Client ' . config('app.name', 'TableTrack'),
                'address' => '12 Avenue des Saveurs, Kinshasa',
                'email' => 'support@tabletrack.test',
                'image' => null,
            ]
        );
    }

    private function seedCustomMenus(): void
    {
        $menus = [
            [
                'menu_slug' => 'a-propos',
                'menu_name' => 'A propos',
                'menu_content' => '<p>Nous aidons les restaurants a mieux gerer leurs operations au quotidien.</p>',
                'position' => 'header',
                'sort_order' => 10,
            ],
            [
                'menu_slug' => 'mentions-legales',
                'menu_name' => 'Mentions legales',
                'menu_content' => '<p>Consultez nos informations legales et nos conditions de service.</p>',
                'position' => 'footer',
                'sort_order' => 20,
            ],
        ];

        foreach ($menus as $menu) {
            CustomMenu::query()->firstOrCreate(
                ['menu_slug' => $menu['menu_slug']],
                [
                    'menu_name' => $menu['menu_name'],
                    'menu_content' => $menu['menu_content'],
                    'is_active' => true,
                    'position' => $menu['position'],
                    'sort_order' => $menu['sort_order'],
                ]
            );
        }
    }

    private function seedPublicPackages(): void
    {
        $currencyId = GlobalCurrency::query()->value('id');

        if (is_null($currencyId)) {
            return;
        }

        // Ne pas modifier les plans deja existants.
        $publicPackagesExist = Package::query()
            ->where('package_type', '!=', PackageType::DEFAULT->value)
            ->where('package_type', '!=', PackageType::TRIAL->value)
            ->where('is_private', false)
            ->exists();

        if ($publicPackagesExist) {
            return;
        }

        $moduleIds = Module::query()->where('is_superadmin', 0)->pluck('id')->all();

        $plans = [
            [
                'package_name' => 'Essentiel',
                'description' => 'Pour les petits restaurants qui veulent demarrer rapidement.',
                'package_type' => PackageType::STANDARD->value,
                'monthly_status' => true,
                'annual_status' => true,
                'monthly_price' => 19,
                'annual_price' => 190,
                'price' => 0,
                'billing_cycle' => 12,
                'is_recommended' => false,
                'sort_order' => 2,
                'additional_features' => json_encode(['Table Reservation', 'Export Report']),
            ],
            [
                'package_name' => 'Pro',
                'description' => 'Pour les etablissements en croissance avec besoins avances.',
                'package_type' => PackageType::STANDARD->value,
                'monthly_status' => true,
                'annual_status' => true,
                'monthly_price' => 39,
                'annual_price' => 390,
                'price' => 0,
                'billing_cycle' => 12,
                'is_recommended' => true,
                'sort_order' => 3,
                'additional_features' => json_encode(['Table Reservation', 'Export Report', 'Customer Display']),
            ],
            [
                'package_name' => 'Entreprise a vie',
                'description' => 'Paiement unique pour un acces illimite dans la duree.',
                'package_type' => PackageType::LIFETIME->value,
                'monthly_status' => false,
                'annual_status' => false,
                'monthly_price' => null,
                'annual_price' => null,
                'price' => 799,
                'billing_cycle' => 0,
                'is_recommended' => false,
                'sort_order' => 4,
                'additional_features' => json_encode(Package::ADDITIONAL_FEATURES),
            ],
        ];

        foreach ($plans as $plan) {
            $package = Package::query()->create(array_merge($plan, [
                'currency_id' => $currencyId,
                'is_private' => false,
                'is_free' => false,
                'branch_limit' => 5,
                'menu_items_limit' => 500,
                'order_limit' => 250,
                'staff_limit' => 15,
                'multipos_limit' => 2,
            ]));

            if (!empty($moduleIds)) {
                $package->modules()->sync($moduleIds);
            }
        }
    }
}
