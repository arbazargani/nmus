<?php

namespace App\Providers;

use Native\Laravel\Facades\Window;
use Native\Laravel\Facades\Menubar;
use Native\Laravel\Contracts\ProvidesPhpIni;

class NativeAppServiceProvider implements ProvidesPhpIni
{
    /**
     * Executed once the native application has been booted.
     * Use this method to open windows, register global shortcuts, etc.
     */
    public function boot(): void
    {
        Window::open('main')
        ->width(1200)
        ->minWidth(1000)
        ->height(800)
        ->minHeight(600)
        ->showDevTools(false);
        // ->rememberState();
        

        MenuBar::create()
        ->url(route('menubar'))
        ->icon(storage_path('app/primer-min.png'))
        ->width(500)
        ->height(300)
        ->label('Mangene')
        // ->alwaysOnTop()
        ->showDockIcon();
    }

    /**
     * Return an array of php.ini directives to be set.
     */
    public function phpIni(): array
    {
        return [
        ];
    }
}
