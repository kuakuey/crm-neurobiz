<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'CRM Neurobiz' }} · Neurobiz</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-slate-50 font-sans text-slate-800 antialiased">
    <div class="flex min-h-screen">
        <aside class="hidden w-64 shrink-0 bg-navy text-white lg:flex lg:flex-col">
            <div class="px-6 py-6">
                <div class="text-xs uppercase tracking-[0.2em] text-teal-300">Neurobiz</div>
                <div class="mt-1 text-lg font-semibold">CRM comercial</div>
            </div>
            <nav class="flex-1 space-y-1 px-3">
                @php
                    $links = [
                        ['dashboard', 'Inicio', 'M3 12l9-9 9 9M4 10v10h6v-6h4v6h6V10'],
                        ['contactos.index', 'Contactos', 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
                        ['organizations.index', 'Empresas', 'M3 21h18M9 8h6m-9 4h12M5 21V5a2 2 0 012-2h10a2 2 0 012 2v16'],
                        ['deals.index', 'Pipeline', 'M3 7h6v10H3zM10 4h6v13h-6zM17 10h4v7h-4z'],
                        ['activities.index', 'Actividades', 'M8 7V3m8 4V3M3 11h18M5 5h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z'],
                        ['reports.index', 'Dirección', 'M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z'],
                    ];
                @endphp
                @foreach ($links as [$route, $label])
                    @php
                        $active = $route === 'contactos.index'
                            ? request()->routeIs('contactos.*', 'people.*')
                            : request()->routeIs(Str::before($route, '.').'.*') || request()->routeIs($route);
                    @endphp
                    <a href="{{ route($route) }}" wire:navigate
                       class="block rounded-lg px-3 py-2 text-sm {{ $active ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                        {{ $label }}
                    </a>
                @endforeach
                @if(auth()->user()?->isAdmin())
                    <a href="{{ route('settings.users') }}" wire:navigate
                       class="block rounded-lg px-3 py-2 text-sm {{ request()->routeIs('settings.users') ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                        Usuarios
                    </a>
                @endif
                @if(auth()->user()?->canManageSettings())
                    <a href="{{ route('settings.integrations') }}" wire:navigate
                       class="block rounded-lg px-3 py-2 text-sm {{ request()->routeIs('settings.integrations') ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                        Integraciones
                    </a>
                @endif
            </nav>
            <div class="border-t border-white/10 p-4 text-sm">
                <div class="font-medium">{{ auth()->user()->name }}</div>
                <div class="text-xs text-slate-300">{{ auth()->user()->role?->label() }}</div>
                <form method="POST" action="{{ route('logout') }}" class="mt-3">
                    @csrf
                    <button class="text-xs text-teal-200 hover:text-white">Cerrar sesión</button>
                </form>
            </div>
        </aside>
        <div class="flex min-w-0 flex-1 flex-col">
            <header class="flex items-center justify-between border-b border-slate-200 bg-white px-4 py-3 lg:px-8">
                <div>
                    <h1 class="text-lg font-semibold text-navy">{{ $title ?? 'CRM Neurobiz' }}</h1>
                </div>
                <a href="{{ route('deals.create') }}" wire:navigate class="rounded-lg bg-teal-600 px-3 py-2 text-sm font-medium text-white hover:bg-teal-700">Nuevo deal</a>
            </header>
            <main class="flex-1 px-4 py-6 lg:px-8">
                @if (session('status'))
                    <div class="mb-4 rounded-lg border border-teal-200 bg-teal-50 px-4 py-3 text-sm text-teal-900">{{ session('status') }}</div>
                @endif
                {{ $slot }}
            </main>
        </div>
    </div>
    @livewireScripts
</body>
</html>
