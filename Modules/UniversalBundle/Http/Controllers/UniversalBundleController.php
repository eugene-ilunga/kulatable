<?php

namespace Modules\UniversalBundle\Http\Controllers;

use App\Helper\Reply;
use App\Http\Controllers\AccountBaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Modules\UniversalBundle\Entities\UniversalBundleSetting;
use Modules\UniversalBundle\Entities\UniversalModuleInstall;
use \Nwidart\Modules\Facades\Module;
use App\Http\Controllers\Controller;

class UniversalBundleController extends Controller
{

    public function installUniversalBundleModule(Request $request)
    {
        $modulePath = getUniversalBundleModulesPath() . '/' . $request->module;

        if (!file_exists($modulePath)) {
            return Reply::error(__('universalbundle::app.moduleIsNotAvailable', ['module' => $request->module]));
        }

        $universalBundleSetting = UniversalBundleSetting::first();

        if (!$universalBundleSetting?->purchase_code) {
            return Reply::error(__('universalbundle::app.purchaseCodeRequired'));
        }

        $moduleInstallationPath = base_path() . '/Modules/' . $request->module;

        File::copyDirectory($modulePath, $moduleInstallationPath);

        // Clear the static modules cache in FileRepository
        $reflection = new \ReflectionClass(\Nwidart\Modules\FileRepository::class);
        $property = $reflection->getProperty('modules');
        $property->setAccessible(true);
        $property->setValue(null, []);

        cache()->forget('laravel-modules');

        $appModule = Module::findOrFail($request->module);
        $appModule->enable();

        Artisan::call('module:migrate', ['module' => $request->module, '--force' => true]);

        $this->setSessionForSubdomainModal($request->module);

        return Reply::success(__('universalbundle::app.moduleIsInstalling', ['module' => $request->module]));
    }

    public function addUniversalModulePurchaseCode(Request $request)
    {

        $universalBundleSetting = UniversalBundleSetting::first();

        if (!$universalBundleSetting?->purchase_code) {
            return Reply::error(__('universalbundle::app.purchaseCodeRequired'));
        }

        $appModule = Module::findOrFail($request->module);

        // UniversalModuleInstall for the module check is installed from universal bundle
        UniversalModuleInstall::updateOrCreate([
            'module_name' => $request->module,
        ], [
            'version' => File::get($appModule->getPath() . '/version.txt'),
        ]);

        if (config(strtolower($request->module) . '.setting')) {
            $fetchSetting = config(strtolower($request->module) . '.setting')::first();

            if ($fetchSetting && !$fetchSetting->purchase_code) {
                $fetchSetting->purchase_code = $universalBundleSetting->purchase_code;
                $fetchSetting->supported_until = $universalBundleSetting->supported_until;
                $fetchSetting->save();
            }
        }

        $userId = auth()->user()->id;
        // clear cache
        cache()->flush();
        // clear session
        session()->flush();
        // login user
        auth()->loginUsingId($userId);

        $module = $request->module;

        $this->setSessionForSubdomainModal($module);


        return Reply::success(__('universalbundle::app.moduleIsInstalled', ['module' => $module]));
    }

    public function setSessionForSubdomainModal(string $module)
    {
        if (strtolower($module) == 'subdomain') {
            session(['subdomain_module_activated' => true]);
        }
        if (strtolower($module) == 'languagepack') {
            session(['languagepack_module_activated' => true]);
        }
    }
}
