<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ClientsExport implements FromCollection, WithHeadings, WithMapping
{
    protected $clients;

    public function __construct($clients)
    {
        $this->clients = $clients;
    }

    public function collection()
    {
        return $this->clients;
    }

    public function headings(): array
    {
        return [
            'الرقم التعريفي (ID)',
            'الاسم',
            'رقم الهاتف',
            'البريد الإلكتروني',
            'الحالة',
            'المدينة',
            'تاريخ الإنشاء',
        ];
    }

    public function map($client): array
    {
        return [
            $client->id,
            $client->name,
            $client->phone,
            $client->email,
            $client->status->name ?? '',
            $client->city->name ?? '',
            $client->created_at ? $client->created_at->format('Y-m-d') : '',
        ];
    }
}
