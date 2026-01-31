<!DOCTYPE html>
<html dir="rtl">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>فاتورة #{{ $invoice->invoice_number }}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700&display=swap');
        body { font-family: 'Tajawal', sans-serif; direction: rtl; text-align: right; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: right; }
        .header { text-align: center; margin-bottom: 20px; }
        .total { font-weight: bold; text-align: left; } /* Total label usually on left if amount is on right, or vice versa depending on layout preference. User said "texts on left", likely meant the whole page alignment */
    </style>
</head>
<body>
    <div class="header">
        <h1>@ar('فاتورة ضريبية')</h1>
        <p>{{ $invoice->invoice_number }} :@ar('رقم الفاتورة')</p>
        <p>{{ $invoice->created_at->format('Y-m-d') }} :@ar('التاريخ')</p>
    </div>

    <div class="client-info">
        <h3>:@ar('بيانات العميل')</h3>
        <p>@ar($invoice->client->name) :@ar('الاسم')</p>
        <p>{{ $invoice->client->phone }} :@ar('الهاتف')</p>
    </div>

    <h3>:@ar('التفاصيل')</h3>
    <table>
        <thead>
            <tr>
                <th>@ar('المجموع')</th>
                <th>@ar('السعر')</th>
                <th>@ar('الكمية')</th>
                <th>@ar('المنتج/الخدمة')</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $item)
            <tr>
                <td>{{ number_format($item->price * $item->quantity, 2) }}</td>
                <td>{{ number_format($item->price, 2) }}</td>
                <td>{{ $item->quantity }}</td>
                <td>@ar($item->product->name ?? 'منتج')</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td class="total">{{ number_format($invoice->total, 2) }}</td>
                <td colspan="3" class="total">:@ar('المجموع الكلي')</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
