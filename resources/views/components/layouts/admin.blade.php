@props(['title' => null, 'crumb' => null])
<x-layouts.shell :title="$title" :crumb="$crumb" area="Administration" :admin="true"
    :nav="[
        ['admin.dashboard', 'Dashboard', 'dashboard', 'admin.dashboard'],
        ['admin.reports.index', 'Reports', 'file-text', 'admin.reports.*'],
        ['admin.matches.index', 'Potential matches', 'compare', 'admin.matches.*'],
        ['admin.claims.index', 'Claims', 'shield-check', 'admin.claims.*'],
        ['admin.users.index', 'Users', 'users', 'admin.users.*'],
        ['admin.activity.index', 'Activity log', 'activity', 'admin.activity.*'],
    ]"
    :secondary="[[route('home'), 'Public site', 'home'], [route('dashboard'), 'Member view', 'user']]">
    {{ $slot }}
</x-layouts.shell>
