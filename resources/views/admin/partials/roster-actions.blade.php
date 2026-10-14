<div class="mb-4 flex flex-col gap-2 sm:flex-row sm:flex-wrap">
    <details class="relative">
        <summary class="btn-primary !px-5 !py-2.5 text-xs cursor-pointer list-none [&::-webkit-details-marker]:hidden">Export Data</summary>
        <div class="absolute left-0 z-20 mt-2 min-w-48 rounded-2xl border border-white/10 bg-[#161616] p-2 shadow-2xl">
            @foreach (\App\Enums\EntryLevel::cases() as $level)
                <a href="{{ route('admin.confirmed-applicants.export', $level) }}" data-loading="download" data-loading-message="Preparing {{ strtolower($level->label()) }} export" class="block rounded-xl px-4 py-2.5 text-xs font-extrabold uppercase tracking-widest text-white/80 hover:bg-white/10 hover:text-ktg-lime">{{ $level->label() }}</a>
            @endforeach
        </div>
    </details>
    <a href="{{ route('admin.categories.index') }}" class="btn-ghost !px-5 !py-2.5 text-xs">Open / Close Categories</a>
    <details class="relative">
        <summary class="btn-ghost !px-5 !py-2.5 text-xs cursor-pointer list-none [&::-webkit-details-marker]:hidden">Refresh Data</summary>
        <div class="absolute left-0 z-20 mt-2 min-w-48 rounded-2xl border border-white/10 bg-[#161616] p-2 shadow-2xl">
            @foreach (\App\Enums\EntryLevel::cases() as $level)
                <a href="{{ route('admin.confirmed-slots.refresh', $level) }}" data-loading data-loading-message="Opening {{ strtolower($level->label()) }} refresh" class="block rounded-xl px-4 py-2.5 text-xs font-extrabold uppercase tracking-widest text-white/80 hover:bg-white/10 hover:text-ktg-lime">{{ $level->label() }}</a>
            @endforeach
        </div>
    </details>
</div>
