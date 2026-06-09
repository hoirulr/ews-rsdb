@props([
    'name',
    'show' => false,
    'maxWidth' => '2xl',
    'center' => false,
    'sidebarAdjust' => false
])

@php
$maxWidth = [
    'sm' => 'sm:max-w-sm',
    'md' => 'sm:max-w-md',
    'lg' => 'sm:max-w-lg',
    'xl' => 'sm:max-w-xl',
    '2xl' => 'sm:max-w-2xl',
][$maxWidth];
@endphp

<div
    wire:ignore.self
    x-data="{
        show: @js($show),
        focusables() {
            // All focusable element types...
            let selector = 'a, button, input:not([type=\'hidden\']), textarea, select, details, [tabindex]:not([tabindex=\'-1\'])'
            return [...$el.querySelectorAll(selector)]
                // All non-disabled elements...
                .filter(el => ! el.hasAttribute('disabled'))
        },
        firstFocusable() { return this.focusables()[0] },
        lastFocusable() { return this.focusables().slice(-1)[0] },
        nextFocusable() { return this.focusables()[this.nextFocusableIndex()] || this.firstFocusable() },
        prevFocusable() { return this.focusables()[this.prevFocusableIndex()] || this.lastFocusable() },
        nextFocusableIndex() { return (this.focusables().indexOf(document.activeElement) + 1) % (this.focusables().length + 1) },
        prevFocusableIndex() { return Math.max(0, this.focusables().indexOf(document.activeElement)) -1 },
    }"
    x-init="$watch('show', value => {
        if (value) {
            document.body.classList.add('overflow-y-hidden');
            {{ $attributes->has('focusable') ? 'setTimeout(() => firstFocusable().focus(), 100)' : '' }}
        } else {
            document.body.classList.remove('overflow-y-hidden');
        }
    })"
    x-on:open-modal.window="($event.detail == '{{ $name }}' || $event.detail.name == '{{ $name }}') ? show = true : null"
    x-on:close-modal.window="($event.detail == '{{ $name }}' || $event.detail.name == '{{ $name }}') ? show = false : null"
    x-on:close.stop="show = false"
    x-on:keydown.escape.window="show = false"
    x-on:keydown.tab.prevent="$event.shiftKey || nextFocusable().focus()"
    x-on:keydown.shift.tab.prevent="prevFocusable().focus()"
    x-show="show"
    @class([
        'fixed inset-0 overflow-y-auto px-4 py-6 sm:px-0 z-[150000] transition-all duration-300 ease-in-out',
        'flex items-center justify-center' => $center
    ])
    :class="{
        'xl:pl-[290px]': {{ $sidebarAdjust ? 'true' : 'false' }} && ($store.sidebar.isExpanded || $store.sidebar.isHovered),
        'xl:pl-[90px]': {{ $sidebarAdjust ? 'true' : 'false' }} && !($store.sidebar.isExpanded || $store.sidebar.isHovered),
        'pl-0': {{ $sidebarAdjust ? 'true' : 'false' }} && $store.sidebar.isMobileOpen
    }"
    style="display: {{ $show ? 'block' : 'none' }};"
>
    <div
        wire:ignore
        x-show="show"
        class="fixed inset-0 transform transition-all"
        x-on:click="show = false"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
    >
        <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
    </div>

    <div
        wire:ignore.self
        x-show="show"
        @class([
            'relative bg-white rounded-lg overflow-hidden shadow-xl transform transition-all sm:w-full',
            $maxWidth,
            'sm:mx-auto mb-6' => !$center,
            'my-auto sm:mx-auto' => $center
        ])
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
    >
        {{ $slot }}
    </div>
</div>
