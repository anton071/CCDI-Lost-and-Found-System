{{-- Official CCDI logo (public/images/ccdi-logo.png). Never recoloured or stretched: only height is set, width follows. --}}
@props(['size' => 'h-11', 'plate' => false])
<img src="{{ asset('images/ccdi-logo.png') }}" alt="CCDI logo" width="166" height="147" {{ $attributes->merge(['class' => "$size w-auto shrink-0 object-contain ".($plate ? 'rounded-md bg-white p-1' : '')]) }}>
