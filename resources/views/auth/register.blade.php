<x-layouts.app title="Create account">
    <div class="mx-auto max-w-4xl px-4 py-8 sm:py-12">
        <div class="card card-pad">
            <div class="flex items-center gap-4"><x-brand size="h-14" /><div><span class="kicker">New account</span><h1 class="page-title !text-2xl">Create your account</h1></div></div>
            <p class="mt-3 text-sm text-slate-500">For students, teachers and staff of CCDI. Fields marked <span class="req">*</span> are required.</p>
            <form method="POST" action="{{ route('register') }}" class="mt-6 space-y-6" data-loading="Creating account…">
                @csrf
                <fieldset class="form-section grid gap-4 sm:grid-cols-2">
                    <legend class="form-section-title mb-4">Personal details</legend>
                    <div class="sm:col-span-2">
                        <label for="name" class="label">Full name <span class="req" aria-hidden="true">*</span></label>
                        <input id="name" name="name" value="{{ old('name') }}" class="input @error('name') input-error @enderror" required autocomplete="name">
                        @error('name')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label for="email" class="label">E-mail address <span class="req" aria-hidden="true">*</span></label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" class="input @error('email') input-error @enderror" required autocomplete="email" inputmode="email">
                        @error('email')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="role" class="label">I am a <span class="req" aria-hidden="true">*</span></label>
                        <select id="role" name="role" class="input @error('role') input-error @enderror" required>
                            @foreach ($roles as $r)<option value="{{ $r->value }}" @selected(old('role', 'student') === $r->value)>{{ $r->label() }}</option>@endforeach
                        </select>
                        @error('role')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="student_number" class="label">Student / ID number <span class="opt">(optional)</span></label>
                        <input id="student_number" name="student_number" value="{{ old('student_number') }}" class="input @error('student_number') input-error @enderror">
                        @error('student_number')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="department" class="label">Department / course <span class="opt">(optional)</span></label>
                        <input id="department" name="department" value="{{ old('department') }}" class="input">
                    </div>
                    <div>
                        <label for="contact_number" class="label">Contact number <span class="opt">(optional)</span></label>
                        <input id="contact_number" name="contact_number" value="{{ old('contact_number') }}" class="input @error('contact_number') input-error @enderror" inputmode="tel">
                        @error('contact_number')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                </fieldset>
                <fieldset class="form-section grid gap-4 sm:grid-cols-2">
                    <legend class="form-section-title mb-4">Security</legend>
                    <div>
                        <label for="password" class="label">Password <span class="req" aria-hidden="true">*</span></label>
                        <input id="password" name="password" type="password" class="input @error('password') input-error @enderror" required autocomplete="new-password">
                        <p class="hint">At least 10 characters with letters and numbers.</p>
                        @error('password')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="password_confirmation" class="label">Confirm password <span class="req" aria-hidden="true">*</span></label>
                        <input id="password_confirmation" name="password_confirmation" type="password" class="input" required autocomplete="new-password">
                    </div>
                </fieldset>
                <div class="flex flex-col-reverse gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm text-slate-500">Already registered? <a href="{{ route('login') }}" class="link">Sign in</a></p>
                    <button type="submit" class="btn btn-primary sm:min-w-44">Create account</button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
