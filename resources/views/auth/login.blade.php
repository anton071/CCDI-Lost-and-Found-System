<x-layouts.app title="Sign in">
    <div class="mx-auto grid max-w-[1600px] gap-8 px-4 py-8 sm:px-6 sm:py-12 lg:grid-cols-[1fr_28rem] lg:px-8 lg:items-stretch">
        <aside class="hidden rounded-lg border-b-4 border-red-500 bg-navy-800 p-8 text-white lg:flex lg:flex-col lg:justify-between">
            <div><x-brand size="h-16" plate /><h2 class="mt-8 text-2xl leading-tight font-bold">Lost something at CCDI?<br>Found something?</h2><p class="mt-3 text-navy-200">Sign in to report items, track your reports and claims, and see possible matches suggested by the system.</p></div>
            <ul class="mt-8 space-y-3 text-sm text-navy-100">
                <li class="flex gap-3"><x-icon name="shield-check" class="mt-0.5 h-5 w-5 shrink-0 text-red-300" />Every claim is verified by authorized personnel.</li>
                <li class="flex gap-3"><x-icon name="lock" class="mt-0.5 h-5 w-5 shrink-0 text-red-300" />Private item details stay private.</li>
            </ul>
        </aside>
        <div class="card card-pad">
            <div class="mb-6 flex items-center gap-3 lg:hidden"><x-brand size="h-12" /><span class="text-sm font-bold text-navy-900">CCDI Lost &amp; Found</span></div>
            <span class="kicker">Account</span>
            <h1 class="page-title !text-2xl">Sign in</h1>
            <p class="mt-1 text-sm text-slate-500">Use your CCDI Lost and Found account.</p>
            <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4" data-loading="Signing in…">
                @csrf
                <div>
                    <label for="email" class="label">E-mail address <span class="req" aria-hidden="true">*</span></label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" class="input @error('email') input-error @enderror" required autofocus autocomplete="username" inputmode="email">
                    @error('email')<p class="field-error" role="alert"><x-icon name="alert" class="mt-0.5 h-4 w-4 shrink-0" />{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="password" class="label">Password <span class="req" aria-hidden="true">*</span></label>
                    <input id="password" name="password" type="password" class="input" required autocomplete="current-password">
                </div>
                <label class="flex min-h-10 items-center gap-2.5 text-sm text-slate-600"><input type="checkbox" name="remember" class="check"> Keep me signed in</label>
                <button type="submit" class="btn btn-primary btn-block">Sign in</button>
            </form>
            <p class="mt-6 border-t border-slate-200 pt-5 text-center text-sm text-slate-500">No account yet? <a href="{{ route('register') }}" class="link">Create one</a></p>
        </div>
    </div>
</x-layouts.app>
