@if ($status === 'active')
    <span
        class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">
        <svg class="-ms-0.5 me-1.5 size-2 fill-green-500" viewBox="0 0 6 6">
            <circle cx="3" cy="3" r="3" />
        </svg>
        Active
    </span>
@elseif ($status === 'trial')
    <span
        class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-600/20">
        <svg class="-ms-0.5 me-1.5 size-2 fill-blue-500" viewBox="0 0 6 6">
            <circle cx="3" cy="3" r="3" />
        </svg>
        Trial
    </span>
@elseif ($status === 'suspended')
    <span
        class="inline-flex items-center rounded-md bg-yellow-50 px-2 py-1 text-xs font-medium text-yellow-700 ring-1 ring-inset ring-yellow-600/20">
        <svg class="-ms-0.5 me-1.5 size-2 fill-yellow-500" viewBox="0 0 6 6">
            <circle cx="3" cy="3" r="3" />
        </svg>
        Suspended
    </span>
@elseif ($status === 'expired')
    <span
        class="inline-flex items-center rounded-md bg-red-50 px-2 py-1 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/10">
        <svg class="-ms-0.5 me-1.5 size-2 fill-red-500" viewBox="0 0 6 6">
            <circle cx="3" cy="3" r="3" />
        </svg>
        Expired
    </span>
@else
    <span
        class="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10">
        {{ ucfirst($status) }}
    </span>
@endif
