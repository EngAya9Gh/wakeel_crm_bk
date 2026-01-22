<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        body { font-family: 'Amiri', sans-serif; text-align: right; }
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
        <h1>ملف العميل: {{ $client->name }}</h1>
        <p>تاريخ التقرير: {{ now()->format('Y-m-d') }}</p>
    </div>

    <div class="section">
        <h3>معلومات أساسية</h3>
        <p><span class="label">البريد الإلكتروني:</span> {{ $client->email }}</p>
        <p><span class="label">الجوال:</span> {{ $client->phone }}</p>
        <p><span class="label">الحالة:</span> {{ $client->status->name ?? 'غير محدد' }}</p>
        <p><span class="label">المدينة:</span> {{ $client->city->name ?? 'غير محدد' }}</p>
    </div>

    <div class="section">
        <h3>آخر التعليقات</h3>
        <table>
            <thead>
                <tr>
                    <th>المستخدم</th>
                    <th>النوع</th>
                    <th>المحتوى</th>
                    <th>التاريخ</th>
                </tr>
            </thead>
            <tbody>
                @foreach($client->comments()->take(5)->get() as $comment)
                <tr>
                    <td>{{ $comment->user->name ?? '' }}</td>
                    <td>{{ $comment->type->name ?? '' }}</td>
                    <td>{{ Str::limit($comment->content, 50) }}</td>
                    <td>{{ $comment->created_at->format('Y-m-d') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</body>
</html>
