<x-layouts.member title="Profile" crumb="Profile">
    <x-page-header kicker="My account" title="My profile" text="Keep your contact details current so personnel can reach you about claims." />
    <div class="grid gap-6 lg:grid-cols-2 lg:items-start">
        <form method="POST" action="{{ route('profile.update') }}" class="card card-pad grid gap-4 sm:grid-cols-2" data-loading="Saving…">
            @csrf @method('PUT')
            <h2 class="form-section-title sm:col-span-2">Personal details</h2>
            <div class="sm:col-span-2"><label for="name" class="label">Full name <span class="req" aria-hidden="true">*</span></label><input id="name" name="name" value="{{ old('name', $user->name) }}" class="input @error('name') input-error @enderror" required>@error('name')<p class="field-error">{{ $message }}</p>@enderror</div>
            <div class="sm:col-span-2"><label for="email" class="label">E-mail address <span class="req" aria-hidden="true">*</span></label><input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" class="input @error('email') input-error @enderror" required>@error('email')<p class="field-error">{{ $message }}</p>@enderror</div>
            <div><label for="student_number" class="label">Student / ID number</label><input id="student_number" name="student_number" value="{{ old('student_number', $user->student_number) }}" class="input @error('student_number') input-error @enderror">@error('student_number')<p class="field-error">{{ $message }}</p>@enderror</div>
            <div><label for="contact_number" class="label">Contact number</label><input id="contact_number" name="contact_number" value="{{ old('contact_number', $user->contact_number) }}" class="input @error('contact_number') input-error @enderror" inputmode="tel">@error('contact_number')<p class="field-error">{{ $message }}</p>@enderror</div>
            <div class="sm:col-span-2"><label for="department" class="label">Department / course</label><input id="department" name="department" value="{{ old('department', $user->department) }}" class="input"></div>
            <div class="flex flex-col gap-3 border-t border-slate-200 pt-4 sm:col-span-2 sm:flex-row sm:items-center sm:justify-between"><span class="text-sm text-slate-500">Account type: <strong class="text-navy-900">{{ $user->role->label() }}</strong></span><button class="btn btn-primary" type="submit">Save changes</button></div>
        </form>
        <form method="POST" action="{{ route('profile.password') }}" class="card card-pad space-y-4" data-loading="Updating…">
            @csrf @method('PUT')
            <h2 class="form-section-title">Change password</h2>
            <div><label for="current_password" class="label">Current password</label><input id="current_password" name="current_password" type="password" class="input @error('current_password') input-error @enderror" autocomplete="current-password" required>@error('current_password')<p class="field-error">{{ $message }}</p>@enderror</div>
            <div><label for="new_password" class="label">New password</label><input id="new_password" name="password" type="password" class="input @error('password') input-error @enderror" autocomplete="new-password" required><p class="hint">At least 10 characters with letters and numbers.</p>@error('password')<p class="field-error">{{ $message }}</p>@enderror</div>
            <div><label for="new_password_confirmation" class="label">Confirm new password</label><input id="new_password_confirmation" name="password_confirmation" type="password" class="input" autocomplete="new-password" required></div>
            <div class="border-t border-slate-200 pt-4"><button class="btn btn-secondary" type="submit">Update password</button></div>
        </form>
    </div>
</x-layouts.member>
