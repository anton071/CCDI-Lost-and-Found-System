@php $errors ??= new \Illuminate\Support\ViewErrorBag; @endphp
@php $outcome = session('match_outcome'); @endphp
<div class="space-y-3 empty:hidden" aria-live="polite">
    @if (session('status'))
        <div class="callout callout-success" role="status">
            <x-icon name="check-circle" class="mt-0.5 h-5 w-5 shrink-0 text-emerald-600" /><p>{{ session('status') }}</p>
        </div>
    @endif
    @if ($outcome)
        @php $bad = $outcome['status'] === 'unavailable'; @endphp
        <div class="callout {{ $bad ? 'callout-warn' : '' }}" role="status">
            <x-icon :name="$bad ? 'alert' : 'compare'" class="mt-0.5 h-5 w-5 shrink-0 {{ $bad ? 'text-amber-600' : 'text-navy-600' }}" />
            <p><span class="font-semibold">Image matching:</span> {{ $outcome['message'] }}</p>
        </div>
    @endif
    @foreach (['claim', 'user', 'status'] as $key)
        @error($key)<div class="callout callout-danger" role="alert"><x-icon name="alert" class="mt-0.5 h-5 w-5 shrink-0 text-red-600" /><p>{{ $message }}</p></div>@enderror
    @endforeach
</div>
