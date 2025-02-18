<?php

namespace App\Filament\Components\Forms;

use Closure;
use Filament\Forms\Components\Field;
use Illuminate\Contracts\Support\Arrayable;
use Filament\Forms\Components\Concerns\HasExtraInputAttributes;

class PermissionSelector extends Field
{
    use HasExtraInputAttributes;

    protected string $view = 'filament.components.forms.permission-selector';

    protected array | Arrayable | string | Closure | null $options = null;
    protected array | Arrayable | string | Closure | null $selectedOptions = null;

    public function options(array | Arrayable | string | Closure | null $options): static
    {
        $this->options = $options;
        return $this;
    }

    public function selectedOptions(array | Arrayable | string | Closure | null $selectedOptions): static
    {
        $this->selectedOptions = $selectedOptions;
        return $this;
    }

    public function getOptions(): array
    {
        $options = $this->evaluate($this->options) ?? [];
        if ($options instanceof Arrayable) {
            $options = $options->toArray();
        }

        return $options;
    }

    public function getSelectedOptions(): array
    {
        $selectedOptions = $this->evaluate($this->selectedOptions) ?? [];
        if ($selectedOptions instanceof Arrayable) {
            $selectedOptions = $selectedOptions->toArray();
        }

        return $selectedOptions;
    }
}
