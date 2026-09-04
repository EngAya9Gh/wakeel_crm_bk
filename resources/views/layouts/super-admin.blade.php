<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wakeel CRM - Super Admin</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body {
            font-family: 'Tajawal', sans-serif;
            background-color: #f8f9fa;
        }
        
        [data-theme="dark"] body {
            background-color: #12131A;
            color: #ffffff;
        }

        /* Smooth transitions */
        body, .bg-white, .text-gray-900, .border-gray-200, aside {
            transition: background-color 0.3s ease, border-color 0.3s ease, color 0.3s ease, width 0.3s ease, transform 0.3s ease;
        }
        
        /* Floating toggle button */
        .theme-toggle-btn {
            position: fixed;
            bottom: 2rem;
            left: 2rem;
            width: 3.5rem;
            height: 3.5rem;
            border-radius: 50%;
            background-color: #1a1d27;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.25rem;
            font-family: sans-serif;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            z-index: 9999;
            cursor: pointer;
            transition: transform 0.2s, background-color 0.2s;
            border: 2px solid rgba(255, 255, 255, 0.1);
        }
        
        .theme-toggle-btn:hover {
            transform: scale(1.05) translateY(-5px);
        }

        [data-theme="dark"] .theme-toggle-btn {
            background-color: #ffffff;
            color: #1a1d27;
        }
        
        /* Top Navbar */
        .top-navbar {
            height: 5rem;
            background-color: #ffffff;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 2rem;
            box-shadow: 0 1px 2px rgba(0,0,0,0.02);
            position: sticky;
            top: 0;
            z-index: 40;
        }
        
        [data-theme="dark"] .top-navbar {
            background-color: #1a1d27;
            border-bottom: 1px solid #2d2d2d;
        }
        
        /* Sidebar */
        .app-sidebar {
            width: 280px;
            background-color: #ffffff;
            border-left: 1px solid #f0f0f0;
            height: 100vh;
            position: fixed;
            right: 0;
            top: 0;
            z-index: 50;
            overflow-y: auto;
            transform: translateX(0);
        }

        .app-sidebar.collapsed {
            transform: translateX(100%);
        }

        [data-theme="dark"] .app-sidebar {
            background-color: #1a1d27;
            border-left: 1px solid #2d2d2d;
        }

        /* Layout wrap */
        .app-wrapper {
            padding-right: 280px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            transition: padding-right 0.3s ease;
        }
        
        .app-wrapper.collapsed {
            padding-right: 0;
        }
        
        /* System status badge */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background-color: #f0fdf4;
            color: #166534;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 700;
        }
        
        [data-theme="dark"] .status-badge {
            background-color: rgba(22, 101, 52, 0.2);
            color: #4ade80;
        }
        
        .status-dot {
            width: 8px;
            height: 8px;
            background-color: #22c55e;
            border-radius: 50%;
            box-shadow: 0 0 8px rgba(34, 197, 94, 0.6);
        }

        /* Content container */
        .main-content {
            flex: 1;
            max-width: 1400px;
            width: 100%;
            margin: 0 auto;
            padding: 2.5rem;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 1.5rem;
            color: #6b7280;
            font-weight: 600;
            border-radius: 1rem;
            margin: 0.5rem 1.5rem;
            transition: all 0.2s;
        }

        .nav-link:hover {
            background-color: #f3f4f6;
            color: #111827;
        }

        .nav-link.active {
            background-color: #fff3ed;
            color: #ff6600;
        }

        [data-theme="dark"] .nav-link {
            color: #9ca3af;
        }

        [data-theme="dark"] .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.05);
            color: #ffffff;
        }

        [data-theme="dark"] .nav-link.active {
            background-color: rgba(255, 102, 0, 0.1);
            color: #ff6600;
        }

        /* Prevent Alpine flash */
        [x-cloak] { display: none !important; }
    </style>
</head>
<body
    x-data="{
        darkMode: localStorage.getItem('wakeel_theme') === 'dark',
        sidebarOpen: true,
        initTheme() {
            this.$watch('darkMode', val => {
                localStorage.setItem('wakeel_theme', val ? 'dark' : 'light');
                document.body.setAttribute('data-theme', val ? 'dark' : 'light');
            });
            document.body.setAttribute('data-theme', this.darkMode ? 'dark' : 'light');
        }
    }"
    x-init="initTheme()"
>

@if(!request()->routeIs('super.login'))
    
    <!-- Sidebar -->
    <aside class="app-sidebar" :class="sidebarOpen ? '' : 'collapsed'">
        <div class="h-20 flex items-center justify-center border-b border-gray-100 dark:border-gray-800">
            <!-- Swapped Logos per user request -->
            <img x-show="!darkMode" src="/logo_dark.png" alt="Wakeel Logo" class="h-8 object-contain">
            <img x-show="darkMode" x-cloak src="/logo_light.png" alt="Wakeel Logo" class="h-8 object-contain">
        </div>

        <div class="py-6">
            <a href="{{ route('super.dashboard') }}" class="nav-link {{ request()->routeIs('super.dashboard') ? 'active' : '' }}">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                الرئيسية
            </a>
            <a href="{{ route('super.tenants.index') }}" class="nav-link {{ request()->routeIs('super.tenants.*') ? 'active' : '' }}">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                المستأجرون
            </a>
            
            <form method="POST" action="{{ route('super.logout') }}" class="mt-8">
                @csrf
                <button type="submit" class="nav-link w-full text-right text-red-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                    تسجيل الخروج
                </button>
            </form>
        </div>
    </aside>

    <div class="app-wrapper" :class="sidebarOpen ? '' : 'collapsed'">
        <!-- Navbar -->
        <header class="top-navbar">
            <div class="flex items-center gap-4">
                <!-- Hamburger Menu -->
                <button @click="sidebarOpen = !sidebarOpen" class="text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white transition-colors bg-gray-100 dark:bg-gray-800 p-2 rounded-xl">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
            </div>
            
            <div>
                <div class="status-badge">
                    <div class="status-dot"></div>
                    النظام يعمل
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="main-content relative z-10">
            @yield('content')
        </main>
    </div>
@else
    <!-- Login Page Wrap -->
    <main class="min-h-screen flex items-center justify-center relative z-10 w-full">
        @yield('content')
    </main>
@endif

<!-- Floating Dark Mode Toggle -->
<button 
    @click="darkMode = !darkMode" 
    class="theme-toggle-btn"
    :title="darkMode ? 'الوضع النهاري' : 'الوضع الليلي'"
>
    <span x-text="darkMode ? 'D' : 'N'"></span>
</button>

</body>
</html>
