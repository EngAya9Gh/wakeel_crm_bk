<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تقييم الخدمة</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Tajawal', sans-serif; background-color: #f8fafc; }
        .star-rating {
            display: flex;
            flex-direction: row-reverse;
            justify-content: center;
            gap: 0.5rem;
        }
        .star-rating input {
            display: none;
        }
        .star-rating label {
            font-size: 3rem;
            color: #d1d5db;
            cursor: pointer;
            transition: color 0.2s;
        }
        .star-rating input:checked ~ label,
        .star-rating label:hover,
        .star-rating label:hover ~ label {
            color: #fbbf24;
        }
    </style>
</head>
<body class="antialiased min-h-screen flex flex-col justify-center items-center p-4">

    <div class="max-w-md w-full bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-100">
        <!-- Header -->
        <div class="bg-orange-500 p-8 text-center text-white relative">
            <!-- Logo -->
            <div class="mb-4 flex justify-center">
                <img src="{{ asset('logo_dark.png') }}" alt="Logo" class="h-16 w-auto object-contain bg-white rounded-lg p-1 shadow" onerror="this.style.display='none'">
            </div>
            <h1 class="text-2xl font-bold mb-2">{{ $link->tenant->name ?? 'الشركة' }}</h1>
            <p class="text-orange-100 opacity-90 text-sm">رأيك يهمنا لنرتقي بخدمتكم</p>
        </div>

        <div class="p-8">
            @if(session('success'))
                <div class="text-center py-8">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-green-100 text-green-500 mb-6">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-800 mb-2">شكراً لك!</h2>
                    <p class="text-gray-600">{{ session('success') }}</p>
                </div>
            @else
                @if($link->note_for_client)
                    <div class="bg-orange-50 border-r-4 border-orange-500 p-4 rounded-l text-sm text-orange-800 mb-6">
                        {{ $link->note_for_client }}
                    </div>
                @endif
                
                @if($link->assignedUser)
                    <div class="text-center mb-6">
                        <p class="text-sm text-gray-500">مقدم الخدمة:</p>
                        <p class="font-semibold text-gray-800">{{ $link->assignedUser->name }}</p>
                    </div>
                @endif

                <form action="{{ url('/rate/' . $token) }}" method="POST">
                    @csrf
                    
                    <div class="mb-8">
                        <label class="block text-center text-gray-700 font-semibold mb-4">ما هو تقييمك؟</label>
                        <div class="star-rating">
                            <input type="radio" id="star5" name="rating" value="5" required />
                            <label for="star5" title="5 نجوم">★</label>
                            
                            <input type="radio" id="star4" name="rating" value="4" />
                            <label for="star4" title="4 نجوم">★</label>
                            
                            <input type="radio" id="star3" name="rating" value="3" />
                            <label for="star3" title="3 نجوم">★</label>
                            
                            <input type="radio" id="star2" name="rating" value="2" />
                            <label for="star2" title="نجمتين">★</label>
                            
                            <input type="radio" id="star1" name="rating" value="1" />
                            <label for="star1" title="نجمة واحدة">★</label>
                        </div>
                        @error('rating')
                            <p class="text-red-500 text-sm text-center mt-2">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-6">
                        <label for="notes" class="block text-gray-700 text-sm font-medium mb-2">ملاحظات إضافية (اختياري)</label>
                        <textarea id="notes" name="notes" rows="3" class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-colors resize-none" placeholder="اكتب ملاحظاتك هنا..."></textarea>
                    </div>

                    <button type="submit" class="w-full bg-orange-500 hover:bg-orange-600 text-white font-bold py-3 px-4 rounded-xl shadow-lg shadow-orange-200 transition-all transform hover:-translate-y-0.5">
                        إرسال التقييم
                    </button>
                </form>
            @endif
        </div>
        
        <!-- Footer -->
        <div class="bg-gray-50 p-4 text-center border-t border-gray-100">
            <p class="text-xs text-gray-400">Powered by Wakeel CRM</p>
        </div>
    </div>
</body>
</html>
