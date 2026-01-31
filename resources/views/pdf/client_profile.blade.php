<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700&display=swap');
        body { font-family: 'Tajawal', sans-serif; text-align: right; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 1px solid #ddd; padding-bottom: 10px; }
        .section { margin-bottom: 15px; }
        .label { font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: right; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <div class="header">
        <h1>@ar('ملف العميل'): @ar($client->name)</h1>
        <p>@ar('تاريخ التقرير'): {{ now()->format('Y-m-d') }}</p>
    </div>

    <div class="section">
        <h3>@ar('معلومات أساسية')</h3>
        <p>{{ $client->email }} <span class="label">:@ar('البريد الإلكتروني')</span></p>
        <p>{{ $client->phone }} <span class="label">:@ar('الجوال')</span></p>
        <p>@ar($client->status->name ?? 'غير محدد') <span class="label">:@ar('الحالة')</span></p>
        <p>@ar($client->city->name ?? 'غير محدد') <span class="label">:@ar('المدينة')</span></p>
    </div>

    <div class="section">
        <h3>@ar('آخر التعليقات')</h3>
        <table>
            <thead>
                <tr>
                    <th>@ar('التاريخ')</th>
                    <th>@ar('المحتوى')</th>
                    <th>@ar('النوع')</th>
                    <th>@ar('المستخدم')</th>
                </tr>
            </thead>
            <tbody>
                @foreach($client->comments()->take(5)->get() as $comment)
                <tr>
                    <td>{{ $comment->created_at->format('Y-m-d') }}</td>
                    <td>@ar(Str::limit($comment->content, 50))</td>
                    <td>@ar($comment->type->name ?? '')</td>
                    <td>@ar($comment->user->name ?? '')</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</body>
</html>
