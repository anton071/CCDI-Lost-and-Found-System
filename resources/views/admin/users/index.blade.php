<x-layouts.admin title="Users" crumb="Users">
    <x-page-header kicker="Administration" title="Users" text="Change roles or deactivate accounts. Changes apply when you press Save on that row." />
    <form method="GET" class="card" role="search">
        <div class="grid gap-4 p-4 sm:grid-cols-2 sm:p-5 lg:grid-cols-12">
            <div class="sm:col-span-2 lg:col-span-7"><label for="q" class="label">Search</label><input id="q" name="q" value="{{ $filters['q'] ?? '' }}" class="input" placeholder="Name or e-mail"></div>
            <div class="sm:col-span-2 lg:col-span-5"><label for="role" class="label">Role</label><select id="role" name="role" class="input"><option value="">All</option>@foreach ($roles as $r)<option value="{{ $r->value }}" @selected(($filters['role'] ?? '') === $r->value)>{{ $r->label() }}</option>@endforeach</select></div>
        </div>
        <div class="flex gap-2 border-t border-slate-200 bg-slate-50 px-4 py-3 sm:px-5"><button class="btn btn-primary btn-sm"><x-icon name="search" class="h-4 w-4" />Filter</button>@if (array_filter($filters))<a href="{{ route('admin.users.index') }}" class="btn btn-secondary btn-sm">Clear filters</a>@endif</div>
    </form>
    <div class="card table-card"><table class="data-table table-stack">
        <thead><tr><th>User</th><th>Role</th><th>Account</th><th>Joined</th><th><span class="sr-only">Save</span></th></tr></thead>
        <tbody>@foreach ($users as $u)
            <tr>
                <td class="cell-main"><p class="font-semibold text-navy-900">{{ $u->name }}</p><p class="text-xs text-slate-500">{{ $u->email }}</p></td>
                <td data-label="Role"><label class="sr-only" for="role-{{ $u->id }}">Role for {{ $u->name }}</label><select id="role-{{ $u->id }}" name="role" form="user-form-{{ $u->id }}" class="input !min-h-10 !w-auto !py-1.5">@foreach ($roles as $r)<option value="{{ $r->value }}" @selected($u->role === $r)>{{ $r->label() }}</option>@endforeach</select></td>
                <td data-label="Account"><label class="sr-only" for="active-{{ $u->id }}">Account status for {{ $u->name }}</label><select id="active-{{ $u->id }}" name="is_active" form="user-form-{{ $u->id }}" class="input !min-h-10 !w-auto !py-1.5"><option value="1" @selected($u->is_active)>Active</option><option value="0" @selected(! $u->is_active)>Deactivated</option></select></td>
                <td data-label="Joined" class="whitespace-nowrap text-slate-600">{{ $u->created_at?->format('M j, Y') }}</td>
                <td data-label="" class="text-right">
                    <form id="user-form-{{ $u->id }}" method="POST" action="{{ route('admin.users.update', $u) }}" class="inline">@csrf @method('PATCH')<button class="btn btn-secondary btn-sm"><x-icon name="check" class="h-4 w-4" />Save changes</button></form>
                </td>
            </tr>
        @endforeach</tbody></table></div>
    <div>{{ $users->links() }}</div>
</x-layouts.admin>
