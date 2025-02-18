<x-dynamic-component :component="$getEntryWrapperView()"
                     class="w-full"
                     :entry="$entry">

    @php
        $old = $getState()['old'] ?? [];
        $attributes = $getState()['attributes'] ?? [];
        $ids = array_unique(array_merge(array_keys($old), array_keys($attributes)));
    @endphp
    <div class="overflow-hidden border border-gray-200 sm:rounded-lg w-full">
        <div class="bg-white p-4 font-semibold border-b border-gray-200">{{ str($entry->getName())->title()->toString() }}</div>
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
            <tr>
                <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">Attribute</th>
                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Old</th>
                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">New</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
            @foreach ($ids as $id)
                <tr>
                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">{{ $id }}</td>
                    <td class="whitespace-nowrap bg-red-50 px-3 py-4 text-sm text-gray-500">{{ $old[$id] ?? '' }}</td>
                    <td class="whitespace-nowrap bg-green-50 px-3 py-4 text-sm text-gray-500">{{ $attributes[$id] ?? '' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</x-dynamic-component>
