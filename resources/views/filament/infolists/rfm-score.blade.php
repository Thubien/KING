<div class="text-center">
    <div class="mb-2">
        <span class="inline-flex items-center justify-center w-16 h-16 rounded-full text-2xl font-bold
            @if($color === 'success') bg-success-100 text-success-700 dark:bg-success-700 dark:text-success-100
            @elseif($color === 'warning') bg-warning-100 text-warning-700 dark:bg-warning-700 dark:text-warning-100
            @else bg-danger-100 text-danger-700 dark:bg-danger-700 dark:text-danger-100
            @endif">
            {{ $score }}/5
        </span>
    </div>
    <p class="text-sm text-gray-600 dark:text-gray-400">{{ $label }}</p>
</div>