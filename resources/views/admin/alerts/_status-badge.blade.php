@if ($status === 'active')
    <span
        class="inline-flex items-center rounded-md bg-red-50 px-2 py-1 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/10">
        <svg class="-ms-0.5 me-1.5 size-2 fill-red-500" viewBox="0 0 6 6">
            <circle cx="3" cy="3" r="3" />
        </svg>
        Active
    </span>
@elseif ($status === 'resolved')
    <span
        class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">
        <svg class="-ms-0.5 me-1.5 size-2 fill-green-500" viewBox="0 0 6 6">
            <circle cx="3" cy="3" r="3" />
        </svg>
        Resolved
    </span>
@else
    <span
        class="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10">
        {{ ucfirst($status) }}
    </span>
@endif
