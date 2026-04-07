<?php

namespace Modules\FontControl\Livewire\Restaurant;

use App\Models\LanguageSetting;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\FontControl\Entities\FontControlSetting;
use Modules\FontControl\Services\FontDownloader;
use Modules\FontControl\Services\TableQrRegenerator;
use App\Models\FileStorage;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Label\LabelAlignment;
use Endroid\QrCode\Label\Margin\Margin;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use App\Helper\Files;
use Symfony\Component\HttpFoundation\File\File;
use Illuminate\Support\Facades\Storage;

class Setting extends Component
{
    use WithFileUploads;

    public array $fonts = [];
    public $settings;
    public ?int $restaurantId = null;
    public array $qr = [];
    public array $activeLanguages = [];
    public $qr_logo_upload;
    public bool $advancedAvailable = true;
    public bool $justRegenerated = false;
    public ?string $qrPreviewData = null;

    public function mount($settings = null): void
    {
        $this->settings = $settings;
        // Super admin (no restaurant) manages global/landing fonts only.
        // Restaurant admins scope to their restaurant.
        $this->restaurantId = auth()->user()?->restaurant_id;
        $this->loadFonts();
        $this->loadQr();
    }

    public function updatedQrLogoUpload(): void
    {
        $this->resetErrorBag('qr_logo_upload');
        $this->validateOnly('qr_logo_upload', [
            'qr_logo_upload' => ['nullable', 'mimes:png,jpg,jpeg,svg,webp', 'max:20480'],
        ]);
    }

    public function render()
    {
        return view('fontcontrol::livewire.restaurant.setting');
    }

    // regenerateNow() method removed - button removed from UI to prevent regenerating entire server

    public function previewQr(): void
    {
        $data = $this->prepareQrConfig();
        $label = $this->renderLabel('{table_code}', '{table_hash}', '{area_name}');
        $isAdvanced = (bool) ($data['advanced_qr_enabled'] ?? false);
        $binary = $this->buildQrBinary($data, $label, $isAdvanced, 'https://example.com/qr-preview');
        $this->qrPreviewData = 'data:image/png;base64,' . base64_encode($binary);
    }

    public function generateStoreQr(): void
    {
        $data = $this->prepareQrConfig();
        $label = $this->renderLabel('{store}', '{store}', '{store}');
        $isAdvanced = (bool) ($data['advanced_qr_enabled'] ?? false);
        $url = config('app.url');
        $binary = $this->buildQrBinary($data, $label, $isAdvanced, $url);

        Files::createDirectoryIfNotExist('qrcodes');
        $fileName = 'store-qr.png';
        $filePath = public_path(Files::UPLOAD_FOLDER . '/qrcodes/' . $fileName);
        if (file_exists($filePath)) {
            @unlink($filePath);
        }
        file_put_contents($filePath, $binary);

        // ⚠️ Delete old file_storage entry used by store-qr.png to prevent bloat
        if ($this->restaurantId) {
             FileStorage::where('filename', $fileName)
                ->where('restaurant_id', $this->restaurantId)
                ->delete();
        } else {
             // Fallback if no restaurant context, though usually store qr has one
             FileStorage::where('filename', $fileName)->delete();
        }

        Files::fileStore(new File($filePath), 'qrcodes', $fileName, uploaded: false, restaurantId: $this->restaurantId);

        if (config('filesystems.default') !== 'local') {
            $contents = file_get_contents($filePath);
            Storage::disk(config('filesystems.default'))->put('qrcodes/' . $fileName, $contents);
            // keep local copy to avoid missing asset on CDN delays
        }

        session()->flash('qr_status', __('fontcontrol::messages.store_qr_generated'));
    }

    public function save(): void
    {
        $this->validate([
            'fonts.*.font_family' => ['required', 'string', 'max:190'],
            'fonts.*.font_size' => ['required', 'integer', 'min:10', 'max:30'],
            'fonts.*.font_url' => ['nullable', 'url', 'max:255'],
            'qr.label_format' => ['required', 'string', 'max:255'],
            'qr.font_family' => ['required', 'string', 'max:190'],
            'qr.font_size' => ['required', 'integer', 'min:10', 'max:48'],
            'qr.font_url' => ['nullable', 'url', 'max:255'],
            'qr.qr_size' => ['required', 'integer', 'min:120', 'max:800'],
            'qr.qr_margin' => ['required', 'integer', 'min:0', 'max:50'],
            'qr.qr_foreground_color' => ['required', 'regex:/^#?[0-9A-Fa-f]{6}$/'],
            'qr.qr_background_color' => ['required', 'regex:/^#?[0-9A-Fa-f]{6}$/'],
            'qr.qr_round_block_size' => ['boolean'],
            'qr.label_color' => ['nullable', 'regex:/^#?[0-9A-Fa-f]{6}$/'],
            'qr.advanced_qr_enabled' => ['boolean'],
            'qr.qr_logo_size' => ['nullable', 'integer', 'min:10', 'max:80'],
            'qr.qr_ecc_level' => ['nullable', 'in:NONE,L,M,Q,H'],
            'qr_logo_upload' => ['nullable', 'image', 'max:10240'],
        ]);

        foreach ($this->fonts as $font) {
            $model = FontControlSetting::updateOrCreate(
                array_filter([
                    'language_code' => strtolower($font['language_code']),
                    // Only set restaurant_id when column exists
                    ...(\Schema::hasColumn('font_control_settings', 'restaurant_id') ? ['restaurant_id' => $this->restaurantId] : []),
                ]),
                [
                    'font_family' => trim($font['font_family']),
                    'font_size' => (int) $font['font_size'],
                    'font_url' => $font['font_url'] ?: null,
                    ...(\Schema::hasColumn('font_control_settings', 'restaurant_id') ? ['restaurant_id' => $this->restaurantId] : []),
                ]
            );

            // Download selected font locally for QR usage
            if ($model->font_url) {
                FontDownloader::ensureLocal($model);
            }
        }

        $this->saveQr();

        session()->flash('fonts_status', __('fontcontrol::messages.font_preferences_updated'));
        session()->flash('qr_status', __('fontcontrol::messages.qr_preferences_updated'));
    }

    private function loadFonts(): void
    {
        $existing = FontControlSetting::when(\Schema::hasColumn('font_control_settings', 'restaurant_id'), function ($q) {
            return $q->when(! is_null($this->restaurantId), function ($qq) {
                $qq->where('restaurant_id', $this->restaurantId);
            }, function ($qq) {
                $qq->whereNull('restaurant_id');
            });
        })->get()->keyBy('language_code');
        $languages = LanguageSetting::where('active', 1)->get();

        $defaults = [
            'font_family' => 'Figtree',
            'font_size' => 14,
            'font_url' => null,
        ];

        $fonts = [];
        $this->activeLanguages = [];

        // Fallback / default entry
        $fonts[] = [
            'language_code' => 'default',
            'language_name' => __('fontcontrol::messages.default_label'),
            'font_family' => $existing['default']->font_family ?? $defaults['font_family'],
            'font_size' => $existing['default']->font_size ?? $defaults['font_size'],
            'font_url' => $existing['default']->font_url ?? $defaults['font_url'],
            'is_default' => true,
        ];

        foreach ($languages as $language) {
            $code = $language->language_code;
            $this->activeLanguages[] = [
                'code' => $code,
                'name' => $language->language_name,
            ];
            $fonts[] = [
                'language_code' => $code,
                'language_name' => $language->language_name,
                'font_family' => $existing[$code]->font_family ?? $defaults['font_family'],
                'font_size' => $existing[$code]->font_size ?? $defaults['font_size'],
                'font_url' => $existing[$code]->font_url ?? ($code === 'ar' ? 'https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap' : null),
                'is_default' => false,
            ];
        }

        $this->fonts = $fonts;
    }

    private function loadQr(): void
    {
        $defaults = [
            'label_format' => '{table_code}',
            'font_family' => 'Noto Sans',
            'font_size' => 16,
            'font_url' => null,
            'font_local_path' => null,
            'qr_size' => 300,
            'qr_margin' => 10,
            'qr_foreground_color' => '#000000',
            'qr_background_color' => '#FFFFFF',
            'qr_round_block_size' => true,
            'label_color' => '#000000',
            'advanced_qr_enabled' => false,
            'qr_logo_path' => null,
            'qr_logo_size' => 20,
            'qr_ecc_level' => 'H',
        ];

        $setting = \Modules\FontControl\Entities\FontControlQrSetting::when(\Schema::hasTable('font_control_qr_settings'), function ($q) {
            return $q->when(! is_null($this->restaurantId), function ($qq) {
                $qq->where('restaurant_id', $this->restaurantId);
            }, function ($qq) {
                $qq->whereNull('restaurant_id');
            });
        })->first();

        $this->qr = array_merge($defaults, $setting ? $setting->toArray() : []);
    }

    private function saveQr(): void
    {
        if (! \Schema::hasTable('font_control_qr_settings')) {
            return;
        }

        $data = $this->qr;
        $data['qr_foreground_color'] = '#' . ltrim($data['qr_foreground_color'], '#');
        $data['qr_background_color'] = '#' . ltrim($data['qr_background_color'], '#');
        if (! empty($data['label_color'])) {
            $data['label_color'] = '#' . ltrim($data['label_color'], '#');
        }
        if (! empty($data['qr_ecc_level'])) {
            $data['qr_ecc_level'] = strtoupper($data['qr_ecc_level']);
        }

        // Handle logo upload for advanced mode
        if ($this->qr_logo_upload) {
            $disk = config('filesystems.default', 'public');
            $folder = 'qrcodes/logos';
            try {
                Storage::disk($disk)->makeDirectory($folder);
            } catch (\Throwable $e) {
                // ignore
            }

            try {
                $stored = $this->qr_logo_upload->store($folder, $disk);
                try {
                    Storage::disk($disk)->setVisibility($stored, 'public');
                } catch (\Throwable $e) {
                    // ignore visibility issues for disks that do not support it
                }
                $data['qr_logo_path'] = $stored;
                // allow immediate re-upload
                $this->reset('qr_logo_upload');
                $this->resetErrorBag('qr_logo_upload');
            } catch (\Throwable $e) {
                $this->addError('qr_logo_upload', __('fontcontrol::messages.qr_logo_failed'));
                return;
            }
        }

        $model = \Modules\FontControl\Entities\FontControlQrSetting::updateOrCreate(
            [
                'restaurant_id' => $this->restaurantId,
            ],
            [
                'label_format' => $data['label_format'],
                'font_family' => $data['font_family'],
                'font_size' => (int) $data['font_size'],
                'font_url' => $data['font_url'] ?: null,
                'qr_size' => (int) $data['qr_size'],
                'qr_margin' => (int) $data['qr_margin'],
                'qr_foreground_color' => $data['qr_foreground_color'],
                'qr_background_color' => $data['qr_background_color'],
                'qr_round_block_size' => (bool) $data['qr_round_block_size'],
                'label_color' => $data['label_color'] ?? null,
                'advanced_qr_enabled' => (bool) ($data['advanced_qr_enabled'] ?? false),
                'qr_logo_path' => $data['qr_logo_path'] ?? null,
                'qr_logo_size' => $data['qr_logo_size'] ?? null,
                'qr_ecc_level' => $data['qr_ecc_level'] ?? 'H',
            ]
        );

        if ($model->font_url) {
            $fakeSetting = new FontControlSetting([
                'font_family' => $model->font_family,
                'font_url' => $model->font_url,
                'font_local_path' => $model->font_local_path,
                'language_code' => 'qr',
                'restaurant_id' => $this->restaurantId,
            ]);
            $path = FontDownloader::ensureLocal($fakeSetting);
            if ($path) {
                $model->font_local_path = $path;
                $model->saveQuietly();
            }
        }

        TableQrRegenerator::forceRun();
    }

    private function prepareQrConfig(): array
    {
        $data = $this->qr;
        $data['qr_foreground_color'] = '#' . ltrim($data['qr_foreground_color'], '#');
        $data['qr_background_color'] = '#' . ltrim($data['qr_background_color'], '#');
        if (! empty($data['label_color'])) {
            $data['label_color'] = '#' . ltrim($data['label_color'], '#');
        }
        if (! empty($data['qr_ecc_level'])) {
            $data['qr_ecc_level'] = strtoupper($data['qr_ecc_level']);
        }
        return $data;
    }

    private function renderLabel(string $tableCode, string $tableHash, string $areaName): string
    {
        $format = $this->qr['label_format'] ?? '{table_code}';
        return str_replace(
            ['{table_code}', '{table_hash}', '{area_name}', '{store}'],
            [$tableCode, $tableHash, $areaName, __('fontcontrol::messages.store_label')],
            $format
        );
    }

    private function buildQrBinary(array $config, string $label, bool $advanced, string $url): string
    {
        $fg = $this->hexToRgb($config['qr_foreground_color']);
        $bg = $this->hexToRgb($config['qr_background_color']);
        $labelColor = $this->hexToRgb($config['label_color'] ?? '#000000');
        $fontFamily = $config['font_family'] ?? 'Noto Sans';
        $fontSize = (int) ($config['font_size'] ?? 16);
        $ecc = strtoupper($config['qr_ecc_level'] ?? 'H');
        $size = (int) ($config['qr_size'] ?? 300);
        $margin = (int) ($config['qr_margin'] ?? 10);
        $round = (bool) ($config['qr_round_block_size'] ?? true);
        $logoPath = $config['qr_logo_path'] ?? null;
        $logoSize = $config['qr_logo_size'] ?? null;

        // Use High ECC if logo is present for better readability
        $eccLevel = $logoPath ? ErrorCorrectionLevel::High : $this->mapEcc($ecc);

        $builder = Builder::create()
            ->writer(new PngWriter())
            ->writerOptions([])
            ->data($url)
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel($eccLevel)
            ->size($size)
            ->margin($margin)
            ->foregroundColor(new Color($fg['r'], $fg['g'], $fg['b']))
            ->backgroundColor(new Color($bg['r'], $bg['g'], $bg['b']))
            ->roundBlockSizeMode($round ? RoundBlockSizeMode::Margin : RoundBlockSizeMode::None)
            ->validateResult(false)
            ->labelText($label)
            ->labelTextColor(new Color($labelColor['r'], $labelColor['g'], $labelColor['b']))
            ->labelAlignment(LabelAlignment::Center)
            ->labelMargin(new Margin(12, 12, 12, 12));

        if ($advanced && class_exists(\Modules\FontControl\Services\QrStyleManager::class)) {
            $font = \Modules\FontControl\Services\QrStyleManager::labelFont($this->restaurantId, $config);
            $builder->labelFont($font);
        }

        $result = $builder->build();
        $binary = $result->getString();

        if ($advanced && $logoPath && extension_loaded('gd')) {
            $binary = $this->mergeLogo($binary, $logoPath, $logoSize ? (int) $logoSize : null);
        }

        return $binary;
    }

    private function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = preg_replace('/(.)/', '$1$1', $hex);
        }
        $int = hexdec($hex);
        return [
            'r' => ($int >> 16) & 255,
            'g' => ($int >> 8) & 255,
            'b' => $int & 255,
        ];
    }

    private function mapEcc(string $level): ErrorCorrectionLevel
    {
        return match (strtoupper($level)) {
            'NONE', 'N' => ErrorCorrectionLevel::Low,
            'L' => ErrorCorrectionLevel::Low,
            'M' => ErrorCorrectionLevel::Medium,
            'Q' => ErrorCorrectionLevel::Quartile,
            default => ErrorCorrectionLevel::High,
        };
    }

    private function mergeLogo(string $pngBinary, string $logoPath, ?int $logoWidth): string
    {
        try {
            $qr = @imagecreatefromstring($pngBinary);
            $logoData = @file_get_contents($logoPath);
            if ($logoData === false) {
                return $pngBinary;
            }
            $logo = @imagecreatefromstring($logoData);
            if (! $qr || ! $logo) {
                return $pngBinary;
            }

            $qrW = imagesx($qr);
            $qrH = imagesy($qr);
            $lw = imagesx($logo);
            $lh = imagesy($logo);

            $targetW = $logoWidth ?: (int) ($qrW * 0.2);
            $targetW = max(1, min($targetW, (int) ($qrW * 0.25)));
            $ratio = $lw > 0 ? ($targetW / $lw) : 1;
            $targetH = max(1, (int) ($lh * $ratio));

            $resized = imagecreatetruecolor($targetW, $targetH);
            imagesavealpha($resized, true);
            $trans = imagecolorallocatealpha($resized, 0, 0, 0, 127);
            imagefill($resized, 0, 0, $trans);
            imagecopyresampled($resized, $logo, 0, 0, 0, 0, $targetW, $targetH, $lw, $lh);

            $dstX = (int) (($qrW - $targetW) / 2);
            $dstY = (int) (($qrH - $targetH) / 2);
            imagecopy($qr, $resized, $dstX, $dstY, 0, 0, $targetW, $targetH);

            ob_start();
            imagepng($qr);
            $out = ob_get_clean();

            imagedestroy($qr);
            imagedestroy($logo);
            imagedestroy($resized);

            return $out ?: $pngBinary;
        } catch (\Throwable $e) {
            return $pngBinary;
        }
    }
}
