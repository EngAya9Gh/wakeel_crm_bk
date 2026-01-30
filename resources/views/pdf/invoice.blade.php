<!DOCTYPE html>
<html dir="rtl">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>فاتورة #{{ $invoice->invoice_number }}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700&display=swap');
        body { font-family: 'Tajawal', sans-serif; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: right; }
        .header { text-align: center; margin-bottom: 20px; }
        .total { font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h1>@ar('فاتورة ضريبية')</h1>
        <p>@ar('رقم الفاتورة'): {{ $invoice->invoice_number }}</p>
        <p>@ar('التاريخ'): {{ $invoice->created_at->format('Y-m-d') }}</p>
    </div>

    <div class="client-info">
        <h3>@ar('بيانات العميل'):</h3>
        <p>@ar('الاسم'): @ar($invoice->client->name)</p>
        <p>@ar('الهاتف'): {{ $invoice->client->phone }}</p>
    </div>

    <h3>@ar('التفاصيل'):</h3>
    <table>
        <thead>
            <tr>
                <th>@ar('المنتج/الخدمة')</th>
                <th>@ar('السعر')</th>
                <th>@ar('الكمية')</th>
                <th>@ar('المجموع')</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $item)
            <tr>
                <td>@ar($item->product->name ?? 'منتج')</td>
                <td>{{ number_format($item->price, 2) }}</td>
                <td>{{ $item->quantity }}</td>
                <td>{{ number_format($item->price * $item->quantity, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" class="total">@ar('المجموع الكلي')</td>
                <td class="total">{{ number_format($invoice->total, 2) }}</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
