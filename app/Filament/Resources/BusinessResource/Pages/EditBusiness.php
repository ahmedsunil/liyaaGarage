<?php

namespace App\Filament\Resources\BusinessResource\Pages;

use App\Models\Business;
use Filament\Forms\Form;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use App\Filament\Resources\BusinessResource;
use Filament\Forms\Concerns\InteractsWithForms;

class EditBusiness extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $model = Business::class;

    protected static string $resource = BusinessResource::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.edit-business';

    protected static ?string $title = 'Business Information';

    protected static ?string $navigationGroup = 'Site Management';

    public ?array $data = [];

    public function mount(): void
    {
        $business = Business::first();
        if (! $business) {
            $business = Business::create([
                'name'                  => 'Default Business',
                'contact'               => 'Default Business Contact',
                'street_address'        => 'Default Business Street Address',
                'email'                 => 'business@mail.com',
                'account_number'        => 'Default Business Account Number',
                'invoice_number_prefix' => 'Default Business Invoice Number Prefix',
                'footer_text'           => 'Default Business Footer Text',
                'copyright'             => 'Default Business Copyright',
                'logo_path'             => 'logo.png',
            ]);
        }
        $this->form->fill($business->toArray());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema(BusinessResource::getFormSchema())
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        if (empty($data['name'])) {
            Notification::make()
                        ->title('Validation Error')
                        ->body('The business name field is required.')
                        ->danger()
                        ->send();

            return;
        }

        $business = Business::first();
        $business->update($data);

        Notification::make()
                    ->title('Success')
                    ->body('Business information updated successfully.')
                    ->success()
                    ->send();
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                  ->label('Save Changes')
                  ->action('save'),
        ];
    }
}
