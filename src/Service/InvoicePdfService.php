<?php

namespace App\Service;

use Dompdf\Dompdf;
use Dompdf\Options;

class InvoicePdfService
{
    public function generateFromHtml(string $html): string
    {
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }
}
