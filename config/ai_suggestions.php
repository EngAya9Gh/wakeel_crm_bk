<?php

return [
    /*
    |--------------------------------------------------------------------------
    | AI Assistant Predefined Suggestions
    |--------------------------------------------------------------------------
    |
    | Here we define the quick prompts/questions that are suggested to the user
    | to interact with the AI Agent.
    |
    */

    // General questions suitable for system-wide AI assistant
    'general' => [
        [
            'id' => 'sales_summary',
            'question' => 'ملخص المبيعات',
            'prompt' => 'قم بإعداد تقرير موجز عن المبيعات الأخيرة مع التركيز على أعلى المنتجات مبيعًا',
            'icon' => 'bar_chart',
        ],
        [
            'id' => 'client_recommendations',
            'question' => 'توصيات للعملاء',
            'prompt' => 'اقترح استراتيجيات للتواصل مع العملاء غير النشطين وإعادة إشراكهم',
            'icon' => 'people',
        ],
        [
            'id' => 'task_prioritization',
            'question' => 'ترتيب أولويات المهام',
            'prompt' => 'ساعدني في ترتيب أولويات المهام وتحسين إدارة الوقت لفريق المبيعات',
            'icon' => 'task_alt',
        ],
        [
            'id' => 'market_analysis',
            'question' => 'تحليل السوق',
            'prompt' => 'قدم تحليلًا للاتجاهات الحالية في السوق وكيف يمكننا التكيف معها',
            'icon' => 'trending_up',
        ],
        [
            'id' => 'customer_support',
            'question' => 'تحسين دعم العملاء',
            'prompt' => 'اقترح طرقًا لتحسين جودة خدمة العملاء وسرعة الاستجابة',
            'icon' => 'support_agent',
        ],
        // New powerful system-wide questions
        [
            'id' => 'sales_performance_comparison',
            'question' => 'مقارنة أداء المبيعات',
            'prompt' => 'هل أداء المبيعات هذا الشهر أفضل من الشهر الماضي؟ قم بتحليل الأرقام وأعطني الخلاصة.',
            'icon' => 'query_stats',
        ],
        [
            'id' => 'employee_performance',
            'question' => 'أداء الموظفين وتحفيزهم',
            'prompt' => 'من هو الموظف الذي يحقق أفضل النتائج؟ وأي الموظفين يحتاج لدعم أو تحفيز بناءً على نسبة الصفقات المرفوضة أو المتأخرة لديه؟',
            'icon' => 'groups',
        ],
        [
            'id' => 'top_lead_sources',
            'question' => 'أفضل مصادر العملاء',
            'prompt' => 'ما هي أكثر منصات الإعلانات أو المصادر التي تجلب لنا مبيعات ناجحة (صفقات مغلقة) مقارنة بالبقية؟',
            'icon' => 'pie_chart',
        ],
    ],

    // Client-specific questions suitable for client profile AI assistant
    'client_specific' => [
        [
            'id' => 'client_close_analysis',
            'question' => 'تحليل اغلاق الصفقة',
            'prompt' => 'قم بتحليل بيانات هذا العميل والمفاوضات السابقة وقدم توصيات لإغلاق الصفقة بنجاح',
            'icon' => 'payments_outlined',
        ],
        [
            'id' => 'client_payment_analysis',
            'question' => 'تحليل الفواتير',
            'prompt' => 'قم بتحليل نمط المدفوعات والفواتير لهذا العميل وقدم توصيات للتحسين',
            'icon' => 'payment',
        ],
        [
            'id' => 'client_engagement',
            'question' => 'زيادة مشاركة العميل',
            'prompt' => 'اقترح طرقًا لزيادة مشاركة هذا العميل بناءً على تاريخ تعاملاته',
            'icon' => 'emoji_people',
        ],
        [
            'id' => 'client_support_status',
            'question' => 'حالة الدعم الفني',
            'prompt' => 'قدم ملخصًا لمشاكل الدعم الفني الحالية والسابقة للعميل والاقتراحات',
            'icon' => 'support_agent',
        ],
        [
            'id' => 'client_opportunities',
            'question' => 'فرص البيع المتقاطع',
            'prompt' => 'حدد فرص البيع المتقاطع والبيع المتزايد لهذا العميل بناءً على المشتريات السابقة',
            'icon' => 'add_shopping_cart',
        ],
        [
            'id' => 'client_risk_assessment',
            'question' => 'تقييم المخاطر',
            'prompt' => 'قم بتقييم مخاطر خسارة هذا العميل وقدم استراتيجيات للاحتفاظ به',
            'icon' => 'warning',
        ],
        // New custom questions based on our features
        [
            'id' => 'client_comments_summary',
            'question' => 'تلخيص التعليقات والقرارات',
            'prompt' => 'قم بقراءة التعليقات المسجلة على هذا العميل، ولخص أهم النقاط والقرارات التي تم اتخاذها بشأنه',
            'icon' => 'forum',
        ],
        // Existing questions from the current mobile app
        [
            'id' => 'client_history_summary',
            'question' => 'لخص تاريخ العميل',
            'prompt' => 'قم بتلخيص تاريخ المفاوضات والمحادثات مع هذا العميل بأهم النقاط',
            'icon' => '📝',
        ],
        [
            'id' => 'client_followup_message',
            'question' => 'اقتراح رسالة متابعة ودية',
            'prompt' => 'اكتب لي رسالة ودية قصيرة يمكنني إرسالها للعميل لمتابعته حول آخر نقاش بيننا',
            'icon' => '💬',
        ],
        [
            'id' => 'client_initial_quote',
            'question' => 'عرض سعر مبدئي',
            'prompt' => 'بناءً على طلبات العميل، اقترح عرض سعر مبدئي مناسب له',
            'icon' => '💰',
        ],
        [
            'id' => 'client_main_objections',
            'question' => 'أبرز الاعتراضات',
            'prompt' => 'استخرج أبرز الاعتراضات التي ذكرها العميل في تاريخ المحادثات',
            'icon' => '🔍',
        ],
        [
            'id' => 'client_last_contact',
            'question' => 'تاريخ آخر تواصل',
            'prompt' => 'متى كان آخر تواصل مع العميل وماذا كانت النتيجة؟',
            'icon' => '⏰',
        ],
        [
            'id' => 'client_best_discount',
            'question' => 'أفضل عرض/خصم للعميل',
            'prompt' => 'ما هو أفضل عرض أو خصم يمكننا تقديمه للعميل لضمان إغلاق الصفقة؟',
            'icon' => '🎯',
        ],
    ],
];
