@php $isLost = $type->value === 'lost'; @endphp
<x-layouts.app :title="$isLost ? 'Report a lost item' : 'Report a found item'">
    <div class="mx-auto max-w-[1600px] px-4 py-8 sm:px-6 lg:px-8">
        <a href="{{ auth()->check() ? route('dashboard') : route('home') }}" class="mb-2 inline-flex min-h-11 items-center gap-1.5 text-sm font-semibold text-slate-500 hover:text-navy-800"><x-icon name="arrow-left" class="h-4 w-4" />Back</a>
        <x-page-header :kicker="$isLost ? 'Lost item report' : 'Found item report'" :title="$isLost ? 'Report a lost item' : 'Report a found item'"
            :text="$isLost ? 'The more precisely you describe it, the easier it is to match.' : 'Found items are reviewed by an administrator before they are listed. Keep the item safe until you hear back.'" />

        <form method="POST" action="{{ route('reports.store', $type->value) }}" enctype="multipart/form-data" class="card card-pad mt-6 space-y-8" data-loading="Submitting report…" novalidate>
            @csrf
            <p class="text-sm text-slate-500">Fields marked <span class="req">*</span> are required.</p>

            <fieldset class="form-section grid gap-5 sm:grid-cols-2">
                <legend class="form-section-title mb-5">1 &middot; The item</legend>
                <div class="sm:col-span-2">
                    <label for="item_name" class="label">Item name <span class="req" aria-hidden="true">*</span></label>
                    <input id="item_name" name="item_name" value="{{ old('item_name') }}" class="input @error('item_name') input-error @enderror" required maxlength="180" placeholder="e.g. Black umbrella with wooden handle">
                    @error('item_name')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="category_id" class="label">Category <span class="req" aria-hidden="true">*</span></label>
                    <select id="category_id" name="category_id" class="input @error('category_id') input-error @enderror" required>
                        <option value="">Select a category</option>
                        @foreach ($categories as $c)<option value="{{ $c->id }}" @selected((string) old('category_id') === (string) $c->id)>{{ $c->name }}</option>@endforeach
                    </select>
                    @error('category_id')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div><label for="brand" class="label">Brand <span class="opt">(optional)</span></label><input id="brand" name="brand" value="{{ old('brand') }}" class="input" maxlength="120"></div>
                    <div><label for="color" class="label">Colour <span class="opt">(optional)</span></label><input id="color" name="color" value="{{ old('color') }}" class="input" maxlength="100"></div>
                </div>
                <div class="sm:col-span-2">
                    <label for="description" class="label">Description <span class="req" aria-hidden="true">*</span></label>
                    <textarea id="description" name="description" rows="4" class="input @error('description') input-error @enderror" required maxlength="3000" placeholder="Size, condition, stickers, what it looked like…">{{ old('description') }}</textarea>
                    @error('description')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div class="sm:col-span-2">
                    <label for="identifying_features" class="label">{{ $isLost ? 'Private identifying details' : 'Private verification details' }} <span class="opt">(optional)</span></label>
                    <textarea id="identifying_features" name="identifying_features" rows="3" class="input" maxlength="2000" placeholder="{{ $isLost ? 'Serial number, contents, unique marks…' : 'Contents, marks or details only the true owner could describe…' }}">{{ old('identifying_features') }}</textarea>
                    <p class="hint flex items-center gap-1.5"><x-icon name="lock" class="h-3.5 w-3.5" />Shown only to you and administrators. Never listed publicly and not searchable.</p>
                </div>
            </fieldset>

            <fieldset class="form-section grid gap-5 sm:grid-cols-2">
                <legend class="form-section-title mb-5">2 &middot; Where and when</legend>
                <div class="sm:col-span-2">
                    <label for="location_description" class="label">{{ $isLost ? 'Where did you lose it?' : 'Where did you find it?' }} <span class="req" aria-hidden="true">*</span></label>
                    <input id="location_description" name="location_description" value="{{ old('location_description') }}" class="input @error('location_description') input-error @enderror" required maxlength="255" placeholder="e.g. Library, second floor, near the printers">
                    @error('location_description')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="incident_date" class="label">Date <span class="req" aria-hidden="true">*</span></label>
                    <input id="incident_date" name="incident_date" type="date" max="{{ now()->toDateString() }}" value="{{ old('incident_date', now()->toDateString()) }}" class="input @error('incident_date') input-error @enderror" required>
                    @error('incident_date')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="incident_time" class="label">Approximate time <span class="opt">(optional)</span></label>
                    <input id="incident_time" name="incident_time" type="time" value="{{ old('incident_time') }}" class="input @error('incident_time') input-error @enderror">
                    @error('incident_time')<p class="field-error">{{ $message }}</p>@enderror
                </div>
            </fieldset>

            <fieldset class="form-section">
                <legend class="form-section-title mb-5">3 &middot; Photograph <span class="opt !text-xs !normal-case !tracking-normal">(optional)</span></legend>
                <div class="grid gap-5 lg:grid-cols-[1fr_16rem]">
                    <div>
                        <div id="photo-zone" class="dropzone {{ $errors->has('photo') ? 'has-error' : '' }}">
                            <input id="photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp" data-max-bytes="{{ config('ccdi.uploads.max_kb') * 1024 }}" class="absolute inset-0 z-10 h-full w-full cursor-pointer opacity-0" aria-describedby="photo-help photo-note" aria-label="Choose a photograph">
                            <div id="photo-empty" class="pointer-events-none flex flex-col items-center gap-2">
                                <span class="flex h-12 w-12 items-center justify-center rounded-md bg-navy-100 text-navy-700"><x-icon name="upload" class="h-6 w-6" /></span>
                                <p class="text-sm font-semibold text-navy-900"><span class="text-navy-600 underline underline-offset-2">Choose a photo</span> <span class="hidden sm:inline">or drag it here</span></p>
                                <p id="photo-help" class="text-xs text-slate-500">JPEG, PNG or WebP &middot; up to {{ config('ccdi.uploads.max_kb') / 1024 }} MB</p>
                            </div>
                            <div id="photo-filled" class="pointer-events-none relative z-20 hidden w-full">
                                <img id="photo-preview" class="mx-auto max-h-64 rounded-md border border-slate-200 bg-white object-contain" alt="Preview of the selected photograph">
                                <p class="mt-3 truncate text-sm font-semibold text-navy-900" id="photo-name"></p>
                                <p class="text-xs text-slate-500" id="photo-size"></p>
                                <div class="pointer-events-auto mt-3 flex justify-center gap-2">
                                    <button type="button" id="photo-replace" class="btn btn-secondary btn-sm"><x-icon name="refresh" class="h-4 w-4" />Replace</button>
                                    <button type="button" id="photo-remove" class="btn btn-danger btn-sm"><x-icon name="trash" class="h-4 w-4" />Remove</button>
                                </div>
                            </div>
                        </div>
                        <p id="photo-note" class="field-error" role="alert">@error('photo'){{ $message }}@enderror</p>
                    </div>
                    <aside class="callout h-fit" aria-label="About image matching">
                        <x-icon name="compare" class="mt-0.5 h-5 w-5 shrink-0 text-navy-600" />
                        <div class="space-y-2 text-[0.8125rem] leading-relaxed">
                            <p class="font-bold">Photo tips</p>
                            <ul class="list-disc space-y-1 pl-4"><li>One item, plain background, good light.</li><li>Show logos, scratches or stickers.</li></ul>
                            <p class="border-t border-navy-200 pt-2 text-navy-700">A photo lets the system <strong>suggest possible matches</strong>. Suggestions are never proof; staff verify every claim.</p>
                        </div>
                    </aside>
                </div>
            </fieldset>

            <div class="flex flex-col-reverse gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:items-center sm:justify-end">
                <a href="{{ auth()->check() ? route('dashboard') : route('home') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary sm:min-w-44"><x-icon name="check" class="h-4 w-4" />Submit report</button>
            </div>
        </form>
    </div>
</x-layouts.app>
