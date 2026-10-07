@php
    $steps = [
        1 => 'Welcome',
        2 => 'Experience',
        3 => 'Level',
        4 => 'Personal Data',
        5 => 'Payment',
        6 => 'Confirmation',
    ];
@endphp

<nav aria-label="Registration progress" class="mb-8 overflow-x-auto pb-2">
    <ol class="flex min-w-max items-center justify-between gap-1 sm:gap-2">
        @foreach ($steps as $number => $label)
            @php
                $isCurrent = $step === $number;
                $isDone = $step > $number;
            @endphp
            <li class="flex flex-1 items-center {{ $number < 6 ? 'min-w-0' : '' }}">
                <div class="flex flex-col items-center gap-1 {{ $number < 6 ? 'w-full' : '' }}">
                    <div class="flex w-full items-center">
                        <span @class([
                            'flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-extrabold sm:h-9 sm:w-9 sm:text-sm',
                            'bg-ktg-lime text-ktg-black shadow-[0_0_16px_rgba(200,230,0,0.45)]' => $isCurrent,
                            'bg-ktg-green text-white' => $isDone,
                            'bg-white/10 text-white/50' => ! $isCurrent && ! $isDone,
                        ])>
                            {{ $number }}
                        </span>
                        @if ($number < 6)
                            <span @class([
                                'mx-1 h-0.5 flex-1 rounded-full sm:mx-2',
                                'bg-ktg-green' => $isDone,
                                'bg-white/15' => ! $isDone,
                            ])></span>
                        @endif
                    </div>
                    <span @class([
                        'hidden text-center text-[10px] font-semibold uppercase tracking-wide sm:block sm:text-xs',
                        'text-ktg-lime' => $isCurrent,
                        'text-white/70' => $isDone,
                        'text-white/40' => ! $isCurrent && ! $isDone,
                    ])>{{ $label }}</span>
                </div>
            </li>
        @endforeach
    </ol>
    <p class="mt-3 text-center text-xs font-semibold uppercase tracking-widest text-ktg-lime sm:hidden">
        {{ $step }} {{ $steps[$step] }}
    </p>
</nav>
