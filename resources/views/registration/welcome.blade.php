@extends('layouts.registration')

@section('title', 'Welcome')

@section('content')
    <section class="mx-auto max-w-3xl">
        <div class="overflow-hidden rounded-3xl border border-white/10 bg-white/5 shadow-2xl backdrop-blur">
            <div
                id="reminder-carousel"
                class="relative"
                data-carousel
                data-interval="6500"
            >
                @php
                    $slides = [
                        ['title' => 'Dink after dark holloween open play', 'body' => 'Please complete all registration steps carefully.'],
                        ['title' => 'Personal Information', 'body' => 'Make sure that all information provided is accurate and updated.'],
                        ['title' => 'Email Verification', 'body' => 'Please provide an active and correct email address because your tournament confirmation will be sent there.'],
                        ['title' => 'Payment', 'body' => 'Please complete the required GCash payment and upload a clear proof of payment.'],
                    ];
                @endphp

                <div class="relative min-h-[280px] overflow-hidden sm:min-h-[320px]">
                    @foreach ($slides as $index => $slide)
                        <article
                            class="carousel-slide absolute inset-0 flex flex-col justify-center px-6 py-8 text-center sm:px-12 {{ $index === 0 ? 'opacity-100' : 'pointer-events-none opacity-0' }}"
                            data-slide="{{ $index }}"
                            @if ($index === 0) data-active="true" @endif
                        >
                            <p class="mb-3 text-xs font-bold uppercase tracking-[0.25em] text-ktg-lime">Reminder {{ $index + 1 }} of 4</p>
                            <h1 class="font-display text-3xl uppercase italic tracking-tight text-white sm:text-5xl">{{ $slide['title'] }}</h1>
                            <p class="mx-auto mt-4 max-w-xl text-base leading-relaxed text-white/75 sm:text-lg">{{ $slide['body'] }}</p>
                        </article>
                    @endforeach
                </div>

                <div class="flex items-center justify-center gap-4 px-4 pb-4">
                    <button type="button" class="carousel-prev flex h-10 w-10 items-center justify-center rounded-full bg-ktg-black/70 text-xl text-ktg-lime ring-1 ring-white/20 hover:bg-ktg-green" aria-label="Previous reminder">
                        ‹
                    </button>
                    <div class="flex justify-center gap-2">
                        @foreach ($slides as $index => $slide)
                            <button
                                type="button"
                                class="carousel-dot h-2.5 w-2.5 rounded-full {{ $index === 0 ? 'bg-ktg-lime' : 'bg-white/30' }}"
                                data-dot="{{ $index }}"
                                aria-label="Go to reminder {{ $index + 1 }}"
                            ></button>
                        @endforeach
                    </div>
                    <button type="button" class="carousel-next flex h-10 w-10 items-center justify-center rounded-full bg-ktg-black/70 text-xl text-ktg-lime ring-1 ring-white/20 hover:bg-ktg-green" aria-label="Next reminder">
                        ›
                    </button>
                </div>
            </div>

            <form method="POST" action="{{ route('register.welcome.continue') }}" class="px-6 pb-6">
                @csrf
                <button type="submit" class="btn-primary w-full">
                    Let's Go
                </button>
            </form>
        </div>
    </section>
@endsection
