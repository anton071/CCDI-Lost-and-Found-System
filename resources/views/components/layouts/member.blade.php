@props(['title' => null, 'crumb' => null])
<x-layouts.shell :title="$title" :crumb="$crumb ?? $title" area="Member area"
    :nav="[
        ['dashboard', 'Dashboard', 'dashboard', 'dashboard'],
        ['reports.mine', 'My reports', 'file-text', 'reports.mine'],
        ['claims.index', 'My claims', 'shield-check', 'claims.*'],
        ['notifications.index', 'Notifications', 'bell', 'notifications.*'],
        ['profile.edit', 'Profile', 'user', 'profile.*'],
    ]"
    :secondary="[[route('reports.create', 'lost'), 'Report lost item', 'plus'], [route('reports.create', 'found'), 'Report found item', 'camera'], [route('items.index'), 'Browse items', 'search']]">
    {{ $slot }}
</x-layouts.shell>
