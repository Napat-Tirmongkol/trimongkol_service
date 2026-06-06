<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#1f47e6">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', __('app.accounting.heading')) — {{ config('app.name') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Noto+Sans+Thai:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <style>[x-cloak]{display:none !important;}</style>
</head>
@php $accountingUser = auth('accounting')->user(); @endphp
<body x-data="{ sidebarOpen: false }" class="min-h-screen bg-slate-50 text-slate-900 antialiased">

    @if ($accountingUser)
        @include('partials.accounting.sidebar')
    @endif

    <div class="flex min-h-screen flex-col {{ $accountingUser ? 'lg:pl-64' : '' }}">

        {{-- Top bar: hamburger on mobile, user/logout on the right --}}
        <nav class="sticky top-0 z-20 border-b border-slate-200 bg-white">
            <div class="flex h-14 items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">

                <div class="flex items-center gap-3 min-w-0">
                    @if ($accountingUser)
                        <button type="button" @click="sidebarOpen = true"
                                class="grid h-9 w-9 place-items-center rounded-lg text-slate-500 hover:bg-slate-100 lg:hidden"
                                aria-label="Open menu">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                        </button>
                    @endif

                    {{-- Brand (only shown when no sidebar — i.e. unauthenticated) --}}
                    @unless ($accountingUser)
                        <a href="{{ route('accounting.dashboard') }}"
                           class="flex shrink-0 items-center gap-2 text-sm font-semibold text-slate-900 hover:text-brand-700">
                            <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-brand-600 text-white">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
                                </svg>
                            </div>
                            <span>{{ __('app.accounting.nav') }}</span>
                        </a>
                    @endunless

                    {{-- Brand label on mobile when sidebar is hidden --}}
                    @if ($accountingUser)
                        <span class="text-sm font-semibold text-slate-900 lg:hidden">{{ __('app.accounting.nav') }}</span>
                    @endif
                </div>

                @if ($accountingUser)
                    <div class="flex items-center gap-2">
                        <span class="hidden text-sm text-slate-500 sm:inline">
                            {{ $accountingUser->name }}
                            @if ($accountingUser->role === 'owner')
                                <span class="ml-1.5 rounded-full bg-brand-50 px-2 py-0.5 text-[10px] font-semibold text-brand-700">{{ __('app.accounting.role_owner') }}</span>
                            @elseif ($accountingUser->role === 'admin')
                                <span class="ml-1.5 rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-600">{{ __('app.accounting.role_admin') }}</span>
                            @else
                                <span class="ml-1.5 rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-600">{{ __('app.accounting.role_staff') }}</span>
                            @endif
                        </span>
                        @unless ($accountingUser->isDemo())
                        <a href="{{ route('accounting.password.edit') }}"
                           class="rounded-md px-3 py-1.5 text-xs font-medium text-slate-500 hover:bg-slate-100 hover:text-slate-700">
                            {{ __('app.accounting.change_password') }}
                        </a>
                        @endunless
                        <form method="POST" action="{{ route('accounting.logout') }}">
                            @csrf
                            <button type="submit"
                                    class="rounded-md px-3 py-1.5 text-xs font-medium text-slate-500 hover:bg-slate-100 hover:text-slate-700">
                                {{ __('app.admin.logout') }}
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        </nav>

        @isset($header)
            <header class="border-b border-slate-200 bg-white">
                <div class="px-4 py-5 sm:px-6 lg:px-8">
                    {{ $header }}
                </div>
            </header>
        @endisset

        <main class="flex-1 pb-16">
            {{ $slot }}
        </main>
    </div>

    @include('partials.sweetalert')
    @livewireScripts
</body>
</html>
