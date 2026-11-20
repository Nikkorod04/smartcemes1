<?php

namespace App\Services;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;

/**
 * Server-side PDF rendering for the institutional reports (2026-11-20).
 *
 * mPDF is pure PHP, so the Azure VM needs no Chromium or Node for PDFs. The
 * report body reuses the same Blade content as the HTML report; the PDF layout
 * (layouts.pdf) supplies print-friendly CSS, and mPDF owns the repeated
 * letterhead and the "Page X of Y" footer.
 *
 * The PDF is the ACTUAL A4 landscape document; the reports workspace embeds it
 * directly so the preview cannot drift from the download.
 */
class ReportPdfService
{
    /**
     * @param  array<string, mixed>  $data  Same data the HTML report receives.
     */
    public function stream(
        string $view,
        array $data,
        string $title,
        string $subtitle,
        string $filename,
        bool $download = false,
    ): Response {
        $data['pdfLayout'] = 'layouts.pdf';

        // The filename comes from controller data (year/project code); keep it
        // safe for Content-Disposition.
        $filename = preg_replace('/[^A-Za-z0-9._-]+/', '-', $filename) ?: 'report.pdf';

        $html = view($view, $data)->render();

        $tempDir = storage_path('app/mpdf');
        File::ensureDirectoryExists($tempDir);

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4-L',
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 23,
            'margin_bottom' => 15,
            'margin_header' => 6,
            'margin_footer' => 6,
            'default_font' => 'dejavusans',
            'default_font_size' => 8.5,
            'tempDir' => $tempDir,
        ]);

        $mpdf->SetTitle($title);
        $mpdf->SetAuthor('SmartCEMES');
        $mpdf->SetCreator('SmartCEMES');
        $mpdf->SetDisplayMode('fullpage');
        $mpdf->shrink_tables_to_fit = 1;
        $mpdf->SetHTMLHeader($this->header($title, $subtitle, $data['scopeLabel'] ?? null));
        $mpdf->SetHTMLFooter($this->footer());
        $mpdf->WriteHTML($html);

        $pdf = $mpdf->Output($filename, Destination::STRING_RETURN);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => ($download ? 'attachment' : 'inline').'; filename="'.$filename.'"',
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }

    private function header(string $title, string $subtitle, ?string $scope): string
    {
        $logo = public_path('ceso_logobig.png');
        $logoTag = is_file($logo)
            ? '<img src="'.e($logo).'" style="height:26px; width:26px;">'
            : '';

        return '
            <table style="width:100%; border-bottom:1px solid #0a2a66; padding-bottom:3px;">
                <tr>
                    <td style="width:30px; vertical-align:middle;">'.$logoTag.'</td>
                    <td style="vertical-align:middle;">
                        <div style="font-size:8.5pt; font-weight:bold; color:#0a2a66;">LEYTE NORMAL UNIVERSITY</div>
                        <div style="font-size:6.5pt; color:#555;">Community Extension Services Office</div>
                    </td>
                    <td style="text-align:right; vertical-align:middle;">
                        <div style="font-size:8.5pt; font-weight:bold; color:#111;">'.e($title).'</div>
                        <div style="font-size:6.5pt; color:#555;">'.e($subtitle).'</div>
                        '.($scope ? '<div style="font-size:6pt; color:#777;">'.e($scope).'</div>' : '').'
                        <div style="font-size:5.8pt; color:#999;">Generated '.e(now()->format('M j, Y g:i A')).'</div>
                    </td>
                </tr>
            </table>';
    }

    private function footer(): string
    {
        return '
            <div style="border-top:1px solid #cbd5e1; padding-top:2px; font-size:6pt; color:#777; text-align:center;">
                SmartCEMES &middot; Community Extension Services Office &middot; Leyte Normal University
                &nbsp;&nbsp;|&nbsp;&nbsp; Page {PAGENO} of {nbpg}
            </div>';
    }
}
