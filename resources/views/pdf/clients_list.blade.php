<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>قائمة العملاء</title>
    <style>
        body {
            font-family: 'Tajawal', sans-serif;
            direction: rtl;
            text-align: right;
            font-size: 14px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table, th, td {
            border: 1px solid #000;
        }
        th, td {
            padding: 8px;
            text-align: right;
        }
        th {
            background-color: #f2f2f2;
        }
        h2 {
            text-align: center;
        }
    </style>
</head>
<body>
    <h2>قائمة العملاء</h2>
    <table>
        <thead>
            <tr>
                <th>الرقم</th>
                <th>الاسم</th>
                <th>رقم الهاتف</th>
                <th>البريد الإلكتروني</th>
                <th>الحالة</th>
                <th>المدينة</th>
            </tr>
        </thead>
        <tbody>
            @foreach($clients as $client)
                <tr>
                    <td>{{ $client->id }}</td>
                    <td>{{ $client->name }}</td>
                    <td dir="ltr">{{ $client->phone }}</td>
                    <td>{{ $client->email }}</td>
                    <td>{{ $client->status->name ?? '' }}</td>
                    <td>{{ $client->city->name ?? '' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
