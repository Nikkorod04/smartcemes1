<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: dejavusans, sans-serif; font-size: 8.5pt; color: #222; }
        h2 { font-size: 9pt; color: #0a2a66; text-transform: uppercase; letter-spacing: .2px; border-bottom: 1px solid #cbd5e1; padding-bottom: 2px; margin: 0 0 4px; }
        h3 { font-size: 8.5pt; color: #0a2a66; margin: 0 0 3px; }
        p { margin: 2px 0; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #eef2f8; font-size: 6.8pt; text-transform: uppercase; color: #475569; padding: 3px; border-bottom: 1px solid #cbd5e1; text-align: left; }
        td { font-size: 7.2pt; padding: 3px; border-bottom: 1px solid #e5e7eb; vertical-align: top; }
        tr { page-break-inside: avoid; }
        thead { display: table-header-group; }
        .report-section { margin-bottom: 8px; page-break-inside: avoid; }

        /* Summary tiles: the web view uses a grid; the PDF uses inline blocks. */
        .pdf-tiles { margin-bottom: 8px; }
        .pdf-tiles > div {
            display: inline-block; width: 22%; vertical-align: top;
            border: 1px solid #dbe1ea; padding: 4px; margin: 0 1% 4px 0;
        }
        .pdf-tiles > div p { margin: 0; }
        .pdf-tiles > div p:first-child { font-size: 6.2pt; text-transform: uppercase; color: #64748b; }
        .pdf-tiles > div p:nth-child(2) { font-size: 12pt; font-weight: bold; color: #111; }
        .pdf-tiles > div p:last-child { font-size: 6.2pt; color: #64748b; }

        .badge { display: inline-block; padding: 1px 4px; font-size: 6.2pt; border: 1px solid #cbd5e1; border-radius: 2px; }
        .kv-wrap { width: 100%; margin-bottom: 6px; }
        .kv .k { width: 22%; font-weight: bold; background: #f8fafc; }
        .kv .v { width: 78%; }

        .pdf-signatures { width: 100%; margin-top: 16px; page-break-inside: avoid; }
        .pdf-signatures td { width: 33%; text-align: center; font-size: 7.5pt; padding-top: 10px; border: 0; }
    </style>
</head>
<body>
    @yield('content')

    <table class="pdf-signatures">
        <tr>
            <td>
                <div style="font-size:6.5pt; font-weight:bold; text-transform:uppercase; color:#475569;">Prepared by</div>
                <div style="margin-top:18px; border-top:1px solid #333; width:80%; margin-left:auto; margin-right:auto;"></div>
                <div style="font-size:7pt; color:#555;">Name and Signature</div>
            </td>
            <td>
                <div style="font-size:6.5pt; font-weight:bold; text-transform:uppercase; color:#475569;">Reviewed by</div>
                <div style="margin-top:18px; border-top:1px solid #333; width:80%; margin-left:auto; margin-right:auto;"></div>
                <div style="font-size:7pt; color:#555;">Name and Signature</div>
            </td>
            <td>
                <div style="font-size:6.5pt; font-weight:bold; text-transform:uppercase; color:#475569;">Approved by</div>
                <div style="margin-top:18px; border-top:1px solid #333; width:80%; margin-left:auto; margin-right:auto;"></div>
                <div style="font-size:7pt; color:#555;">Name and Signature</div>
            </td>
        </tr>
    </table>
</body>
</html>
