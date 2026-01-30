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
        <p><span class="label">@ar('البريد الإلكتروني'):</span> {{ $client->email }}</p>
        <p><span class="label">@ar('الجوال'):</span> {{ $client->phone }}</p>
        <p><span class="label">@ar('الحالة'):</span> @ar($client->status->name ?? 'غير محدد')</p>
        <p><span class="label">@ar('المدينة'):</span> @ar($client->city->name ?? 'غير محدد')</p>
    </div>

    <div class="section">
        <h3>@ar('آخر التعليقات')</h3>
        <table>
            <thead>
                <tr>
                    <th>@ar('المستخدم')</th>
                    <th>@ar('النوع')</th>
                    <th>@ar('المحتوى')</th>
                    <th>@ar('التاريخ')</th>
                </tr>
            </thead>
            <tbody>
                @foreach($client->comments()->take(5)->get() as $comment)
                <tr>
                    <td>@ar($comment->user->name ?? '')</td>
                    <td>@ar($comment->type->name ?? '')</td>
                    <td>@ar(Str::limit($comment->content, 50))</td>
                    <td>{{ $comment->created_at->format('Y-m-d') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</body>
</html>
