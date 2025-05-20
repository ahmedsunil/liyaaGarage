<?php

namespace App\Filament\Resources\ReportResource\Pages;

use Carbon\Carbon;
use App\Filament\Resources\ReportResource;
use Filament\Resources\Pages\CreateRecord;

class CreateReport extends CreateRecord
{
    protected static string $resource = ReportResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Generate the report name in the format SalesReport(Date)
        $fromDate = Carbon::parse($data['from_date'])->format('Y-m-d');
        $data['name'] = "SalesReport - [{$fromDate}]";

        return $data;
    }
}
