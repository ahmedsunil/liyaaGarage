<x-filament-panels::page
    @print-invoice.window="setTimeout(() => window.print(), 100)"
>
    <style>
        @media print {
            body * {
                visibility: hidden;
            }

            .fi-layout, .fi-layout *, .fi-infolist, .fi-infolist * {
                visibility: visible;
            }

            .fi-header, .fi-header *, .fi-topbar, .fi-topbar * {
                visibility: hidden;
            }

            .fi-infolist {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }
        }
    </style>

    {{ $slot }}
</x-filament-panels::page>
