<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wakeel CRM - تسجيل الدخول</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;800&display=swap" rel="stylesheet">
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <style>
        body { 
            font-family: 'Tajawal', sans-serif;
            background-color: #fafafa;
        }
        
        [data-theme="dark"] body {
            background-color: #121212;
            color: #ffffff;
        }

        .bg-gradient-radial {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: -1;
            pointer-events: none;
            background-image: radial-gradient(circle at top right, rgba(255, 102, 0, 0.05), transparent 40%),
                              radial-gradient(circle at bottom left, rgba(255, 102, 0, 0.05), transparent 40%);
        }
        
        [data-theme="dark"] .bg-gradient-radial {
            background-image: radial-gradient(circle at top right, rgba(255, 102, 0, 0.1), transparent 40%),
                              radial-gradient(circle at bottom left, rgba(255, 102, 0, 0.1), transparent 40%);
        }

        /* Floating toggle button */
        .theme-toggle-btn {
            position: fixed;
            bottom: 2rem;
            left: 2rem;
            width: 3rem;
            height: 3rem;
            border-radius: 50%;
            background-color: #2d3748;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-family: sans-serif;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            z-index: 50;
            cursor: pointer;
            transition: transform 0.2s, background-color 0.2s;
        }
        
        .theme-toggle-btn:hover {
            transform: scale(1.05);
        }

        [data-theme="dark"] .theme-toggle-btn {
            background-color: #fbd38d;
            color: #1a202c;
        }
    </style>
</head>
<body 
    x-data="{
        darkMode: localStorage.getItem('wakeel_theme') === 'dark',
        initTheme() {
            this.$watch('darkMode', val => {
                localStorage.setItem('wakeel_theme', val ? 'dark' : 'light');
                document.body.setAttribute('data-theme', val ? 'dark' : 'light');
            });
            document.body.setAttribute('data-theme', this.darkMode ? 'dark' : 'light');
        }
    }"
    x-init="initTheme()"
    class="flex items-center justify-center min-h-screen relative"
>

    <div class="bg-gradient-radial"></div>

    <!-- Floating Dark Mode Toggle -->
    <button 
        @click="darkMode = !darkMode" 
        class="theme-toggle-btn"
        :title="darkMode ? 'الوضع النهاري' : 'الوضع الليلي'"
    >
        <span x-text="darkMode ? 'D' : 'N'"></span>
    </button>

    <div class="w-full max-w-[400px] flex flex-col items-center z-10 px-4">
        
        <!-- Logo Area -->
        <div class="mb-8 flex flex-col items-center">
            <div class="w-20 h-20 bg-gray-900 rounded-3xl mb-4 flex items-center justify-center shadow-lg relative overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-tr from-gray-800 to-gray-900"></div>
                <img src="/logo_dark.png" alt="Wakeel Logo" class="w-12 h-12 object-contain relative z-10 opacity-0" />
                <div class="relative z-10 grid grid-cols-2 gap-1">
                    <div class="w-3 h-3 bg-orange-500 rounded-full"></div>
                    <div class="w-3 h-3 bg-gray-600 rounded-full"></div>
                    <div class="w-3 h-3 bg-gray-600 rounded-full"></div>
                    <div class="w-3 h-3 bg-orange-500 rounded-full"></div>
                </div>
            </div>
            <img x-show="!darkMode" src="/logo_dark.png" alt="Wakeel" class="h-6 mb-1 object-contain">
            <img x-show="darkMode" x-cloak src="/logo_light.png" alt="Wakeel" class="h-6 mb-1 object-contain">
            <div class="text-gray-400 text-sm font-medium">لوحة الإدارة العليا</div>
        </div>

        <!-- Login Box -->
        <div class="w-full bg-white dark:bg-[#1a1d27] rounded-3xl shadow-sm border border-gray-100 dark:border-gray-800 p-8 relative overflow-hidden">
            <!-- Orange top border -->
            <div class="absolute top-0 left-0 right-0 h-1 bg-[#ff6600]"></div>
            
            <h1 class="text-xl font-bold text-gray-900 dark:text-white text-center mb-8">تسجيل الدخول</h1>
            
            @if ($errors->any())
                <div class="bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 p-4 rounded-xl mb-6 text-sm border border-red-200 dark:border-red-800 text-right">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('super.login') }}" class="space-y-5">
                @csrf
                
                <div class="text-right flex items-center justify-between">
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                        class="w-full bg-indigo-50/50 dark:bg-gray-800/50 border border-indigo-100/50 dark:border-gray-700 text-gray-900 dark:text-gray-100 rounded-xl px-4 py-3 outline-none text-left dir-ltr text-sm focus:border-indigo-300 dark:focus:border-indigo-500 transition-colors"
                        placeholder="superadmin@wakeel.system">
                    <label for="email" class="text-xs font-bold text-gray-500 dark:text-gray-400 absolute right-12 bg-transparent pointer-events-none -translate-y-8">البريد الإلكتروني</label>
                </div>
                
                <div class="text-right flex items-center justify-between relative mt-8">
                    <input type="password" id="password" name="password" required
                        class="w-full bg-indigo-50/50 dark:bg-gray-800/50 border border-indigo-100/50 dark:border-gray-700 text-gray-900 dark:text-gray-100 rounded-xl px-4 py-3 outline-none text-left dir-ltr text-sm focus:border-indigo-300 dark:focus:border-indigo-500 transition-colors"
                        placeholder="••••••••••••••••">
                    <label for="password" class="text-xs font-bold text-gray-500 dark:text-gray-400 absolute right-4 bg-transparent pointer-events-none -translate-y-10">كلمة المرور</label>
                </div>
                
                <button type="submit" 
                    class="w-full bg-[#ea580c] hover:bg-[#c2410c] text-white font-bold py-3.5 px-4 rounded-xl transition-colors duration-200 flex justify-center items-center gap-2 shadow-sm mt-4 text-sm">
                    دخول
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path></svg>
                </button>
            </form>
        </div>
    </div>
</body>
</html>
