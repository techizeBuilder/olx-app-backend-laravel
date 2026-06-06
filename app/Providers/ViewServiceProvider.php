<?php

namespace App\Providers;

use App\Models\Language;
use App\Models\Setting;
use App\Services\CachingService;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ViewServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
      View::composer('layouts.topbar', function ($view) {
            $languages = CachingService::getLanguages();

            // Always get the most recent default from DB
            $defaultLangCodeData = CachingService::getSystemSettings('default_language');
            $defaultLangCode = $defaultLangCodeData ?? 'en';
            $defaultLanguage = $languages->where('code', $defaultLangCode)->first();

            // If session is empty, use the database default
            $currentLocale = Session::get('locale', $defaultLangCode);
            $currentLanguage = $languages->where('code', $currentLocale)->first();

            $view->with([
                'languages'       => $languages,
                'defaultLanguage' => $defaultLanguage, // Now correctly shows the DB value
                'currentLanguage' => $currentLanguage,
                'settings'        => CachingService::getSystemSettings()
            ]);
        });




        View::composer('layouts.sidebar', static function (\Illuminate\View\View $view) {
            $settings = CachingService::getSystemSettings('company_logo');
            $view->with('company_logo', $settings ?? '');
        });

        View::composer('layouts.main', static function (\Illuminate\View\View $view) {
            $settings = CachingService::getSystemSettings('favicon_icon');
            $view->with('favicon', $settings ?? '');
            $view->with('lang', Session::get('language'));
        });

        View::composer('auth.login', static function (\Illuminate\View\View $view) {
            // Get Required Settings Data from DB
            $settings = ['favicon_icon','company_logo','login_image','admin_primary_color'];
            $settingData = CachingService::getSystemSettings($settings);

            // Get specific data
            $faviconIcon = isset($settingData['favicon_icon']) && !empty($settingData['favicon_icon']) ? $settingData['favicon_icon'] : null;
            $companyLogo = isset($settingData['company_logo']) && !empty($settingData['company_logo']) ? $settingData['company_logo'] : null;
            $LoginBgImage = isset($settingData['login_image']) && !empty($settingData['login_image']) ? $settingData['login_image'] : null;
            $adminPrimaryColor =  isset($settingData['admin_primary_color']) && !empty($settingData['admin_primary_color']) ? $settingData['admin_primary_color'] : '#00B2CA';


            $view->with('company_logo', $companyLogo);
            $view->with('favicon', $faviconIcon);
            $view->with('login_bg_image', $LoginBgImage);
            $view->with('theme_color', $adminPrimaryColor);
        });

        View::composer('auth.forgot-password', static function (\Illuminate\View\View $view) {
            // Get Required Settings Data from DB
            $settings = ['favicon_icon','company_logo','login_image','admin_primary_color'];
            $settingData = CachingService::getSystemSettings($settings);

            // Get specific data
            $faviconIcon = isset($settingData['favicon_icon']) && !empty($settingData['favicon_icon']) ? $settingData['favicon_icon'] : null;
            $companyLogo = isset($settingData['company_logo']) && !empty($settingData['company_logo']) ? $settingData['company_logo'] : null;
            $LoginBgImage = isset($settingData['login_image']) && !empty($settingData['login_image']) ? $settingData['login_image'] : null;
            $adminPrimaryColor =  isset($settingData['admin_primary_color']) && !empty($settingData['admin_primary_color']) ? $settingData['admin_primary_color'] : '#00B2CA';


            $view->with('company_logo', $companyLogo);
            $view->with('favicon', $faviconIcon);
            $view->with('login_bg_image', $LoginBgImage);
            $view->with('theme_color', $adminPrimaryColor);
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
