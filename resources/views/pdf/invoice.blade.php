<!DOCTYPE html>
<html dir="rtl">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>فاتورة #{{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: right; }
        .header { text-align: center; margin-bottom: 20px; }
        .total { font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h1>فاتورة ضريبية</h1>
        <p>رقم الفاتورة: {{ $invoice->invoice_number }}</p>
        <p>التاريخ: {{ $invoice->created_at->format('Y-m-d') }}</p>
    </div>

    <div class="client-info">
        <h3>بيانات العميل:</h3>
        <p>الاسم: {{ $invoice->client->name }}</p>
        <p>الهاتف: {{ $invoice->client->phone }}</p>
    </div>

    <h3>التفاصيل:</h3>
    <table>
        <thead>
            <tr>
                <th>المنتج/الخدمة</th>
                <th>السعر</th>
                <th>الكمية</th>
                <th>المجموع</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $item)
            <tr>
                <td>{{ $item->product->name ?? 'منتج' }}</td>
                <td>{{ number_format($item->price, 2) }}</td>
                <td>{{ $item->quantity }}</td>
                <td>{{ number_format($item->price * $item->quantity, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" class="total">المجموع الكلي</td>
                <td class="total">{{ number_format($invoice->total, 2) }}</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
