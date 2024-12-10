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
        ->minWidth(900)
        ->height(800)
        ->minHeight(600)
        ->showDevTools(false)
        ->hideMenu();

        MenuBar::create()
        ->url(route('menubar'))
        ->icon(public_path('icon.png'))
        ->width(500)
        ->height(200)
        ->label('MiniMangene')
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
