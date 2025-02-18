@php
//    $defaultState = '$wire.' . $applyStateBindingModifiers("\$entangle('{$statePath}')");
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div
        x-ignore
        ax-load
        ax-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('permissions-selector') }}"
        x-data='permissionsSelector({
            state: $wire.$entangle("{{ $getStatePath() }}"),
            modelPermissions: @json($getOptions()),
            selectedModelPermissions: @json($getSelectedOptions()),
        })'
    >
        <div class="border-b border-gray-200 pb-6">
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-1">
                    <label class="" for="data.description">
                        <span class="text-sm font-medium leading-6 text-gray-950 dark:text-white">
                            Search by Models
                        </span>
                    </label>
                    <div class="relative">
                        <div class="absolute left-[10px] top-[10px] text-gray-500">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                            </svg>
                        </div>
                        <input class="flex w-full !pl-[30px] pr-3 pt-1.5 pb-2 rounded-lg border text-sm border-gray-200"
                               maxlength="255"
                               type="text"
                               x-model="searchByModels"
                               @keyup.prevent="searchModels"
                        />
                        <div x-show="searchByModels.length != 0"
                             @click="clearSearchByModels"
                             class="absolute right-[5px] top-[10px] cursor-pointer text-gray-500">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                 stroke-width="1.5" stroke="currentColor"
                                 class="w-4 h-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                        </div>
                    </div>
                </div>
                <div class="col-span-1">
                    <label class="" for="data.description">
                        <span class="text-sm font-medium leading-6 text-gray-950 dark:text-white">
                            Search by Permissions
                        </span>
                    </label>
                    <div class="relative">
                        <div class="absolute left-[10px] top-[10px] text-gray-500">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                            </svg>
                        </div>
                        <input class="flex w-full !pl-[30px] pr-3 pt-1.5 pb-2 rounded-lg border text-sm border-gray-200"
                               maxlength="255"
                               type="text"
                               x-model="searchByPermissions"
                               @keyup.prevent="searchPermissions"
                        />
                        <div x-show="searchByPermissions.length != 0"
                             @click="clearSearchByPermissions"
                             class="absolute right-[5px] top-[10px] cursor-pointer text-gray-500">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                 stroke-width="1.5" stroke="currentColor"
                                 class="w-4 h-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mt-2">
                <div class="text-sm font-semibold text-primary-600 cursor-pointer" x-show="! allPermissionsAreSelected()" @click="selectAllPermissions()">Select all permissions</div>
                <div class="text-sm font-semibold text-primary-600 cursor-pointer" x-show="allPermissionsAreSelected()" @click="deselectAllPermissions()">Deselect all permissions</div>
            </div>
        </div>
        <div class="flex flex-col space-y-4">
            <template x-for="(modelPermission, index) in displayedModels" :key="index">
                <div class="mt-4">
                    <div class="text-sm font-medium leading-6 text-gray-800 dark:text-white mb-2" x-text="modelPermission.model"></div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                        <template x-for="permission in modelPermission.permissions">
                            <label class="fi-fo-checkbox-list-option-label flex gap-x-3 items-center">
                                <x-filament::input.checkbox
                                    :valid="! $errors->has($statePath)"
                                    x-bind:checked="isChecked(permission.id)"
                                    @change="updatePermission(permission.id)"
                                />
                                <span class="fi-fo-checkbox-list-option-label text-sm font-medium text-gray-950 dark:text-white"
                                      x-text="permission.name"></span>
                            </label>
                        </template>
                        <template x-if="modelPermission.permissions.length === 0">
                            <div class="text-gray-500">Couldn't find any permissions matching your search.</div>
                        </template>
                    </div>
                </div>
            </template>
            <template x-if="displayedModels.length === 0">
                <div class="text-gray-500">Couldn't find any model permissions matching your search.</div>
            </template>
        </div>
    </div>
</x-dynamic-component>
