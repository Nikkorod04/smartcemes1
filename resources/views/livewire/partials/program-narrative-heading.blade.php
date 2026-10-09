{{--
    Project identity block, shared by BOTH card-header branches on
    `/program-narratives` — the collapsible (completed) header and the
    non-collapsible (not-generated / failed / pending) header — so the two cannot
    drift apart.

    Only phrasing content (<span>) is used, because the collapsible branch renders
    this INSIDE a <button>, where a <div> or <p> would be invalid markup.

    Expects $p (App\Models\ExtensionProject).
--}}
<span class="pn-project-identity">
    <span class="pn-project-title block">{{ $p->title }}</span>
    <span class="pn-project-meta flex items-center gap-1.5 flex-wrap">
        <span class="badge badge-gray !text-[10px] font-mono">{{ $p->code }}</span>
        @if ($p->college)
            <span class="badge badge-gray !text-[10px]">{{ $p->college->code }}</span>
        @endif
    </span>
    <span class="pn-project-context flex items-center gap-x-3 gap-y-1 flex-wrap">
        <span><x-sc.icon name="people" class="h-3 w-3" /> Lead: {{ $p->programLead?->user?->name ?? '—' }}</span>
        <span><x-sc.icon name="pin" class="h-3 w-3" /> {{ $p->communities->pluck('name')->implode(', ') ?: 'No community linked' }}</span>
    </span>
</span>
