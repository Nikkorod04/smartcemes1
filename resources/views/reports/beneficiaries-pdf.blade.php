@extends('layouts.pdf', ['reportTitle' => 'Beneficiary List'])

@section('content')
    {{-- University letterhead: mPDF needs a local path, not a public URL. --}}
    <table style="width:100%; border:0; margin-bottom:8px;">
        <tr>
            <td style="width:52px; border:0; padding:0;">
                <img src="{{ public_path('ceso_logobig.png') }}" alt="Leyte Normal University seal" style="width:44px; height:44px;">
            </td>
            <td style="border:0; padding:0 0 0 8px; vertical-align:middle;">
                <div style="font-size:11pt; font-weight:bold; text-transform:uppercase; color:#0a2a66;">Leyte Normal University</div>
                <div style="font-size:7.5pt; font-weight:bold; text-transform:uppercase; letter-spacing:.4px; color:#475569;">Community Extension Services Office</div>
                <div style="font-size:7pt; color:#64748b;">Tacloban City, Leyte &middot; Republic of the Philippines</div>
            </td>
            <td style="border:0; padding:0; text-align:right; vertical-align:middle;">
                <div style="font-size:7pt; font-weight:bold; text-transform:uppercase; letter-spacing:.4px; color:#64748b;">SmartCEMES</div>
                <div style="font-size:7pt; color:#64748b;">Generated {{ now()->format('M j, Y g:i A') }}</div>
            </td>
        </tr>
    </table>

    <div style="border-bottom:2px solid #0a2a66; margin-bottom:8px;"></div>

    <div style="margin-bottom:6px;">
        <div style="font-size:14pt; font-weight:bold; color:#111;">Beneficiary List</div>
        <div style="font-size:9pt; font-weight:bold; color:#0a2a66;">{{ $project->code }} &middot; {{ $project->title }}</div>
        <div style="font-size:7.5pt; color:#475569; margin-top:2px;">
            {{ $project->communities->pluck('name')->implode(', ') ?: 'No community linked' }}
            &middot; {{ $project->planned_start_date?->format('M j, Y') ?? '—' }} to {{ $project->planned_end_date?->format('M j, Y') ?? '—' }}
            &middot; {{ $project->programLead?->user?->name ?? 'No project lead' }}
        </div>
        <div style="font-size:7.5pt; color:#475569; margin-top:1px;">{{ $scopeLabel }}</div>
    </div>

    <table class="sc-table" style="margin-top:8px;">
        <thead>
            <tr>
                <th style="width:4%;">No.</th>
                <th style="width:27%;">Name</th>
                <th style="width:24%;">Community / Partner School</th>
                <th style="width:18%;">Category</th>
                <th style="width:11%;">Age / Sex</th>
                <th style="width:16%;">Contact</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($beneficiaries as $index => $beneficiary)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $beneficiary->fullName() }}</td>
                    <td>{{ $beneficiary->community?->name ?? $beneficiary->barangay ?? '—' }}</td>
                    <td>{{ $beneficiary->beneficiary_category }}</td>
                    <td>{{ $beneficiary->age ?? '—' }} / {{ $beneficiary->gender ?? '—' }}</td>
                    <td>{{ $beneficiary->phone ?: 'No contact provided' }}</td>
                </tr>
            @empty
                <tr><td colspan="6">No active beneficiaries are recorded for this project.</td></tr>
            @endforelse
        </tbody>
    </table>

    <p style="margin-top:8px; font-size:7pt; color:#666;">
        Archived beneficiaries are excluded from this list. Attendance history is retained in the system.
    </p>
@endsection
