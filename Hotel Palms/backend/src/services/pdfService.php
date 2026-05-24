<?php
/**
 * Servicio de exportación de PDF.
 * Genera informes en formato PDF utilizando la biblioteca FPDF.
 *
 * @author Beatriz Lara Solana
 * @version 1.0.0
 */

// FPDF está incluido en el directorio de proveedores.
require_once __DIR__ . '/../../vendor/fpdf/fpdf.php';

class PdfService
{
    /** @var FPDF */
    private FPDF $pdf;

    /**
     * Constructor: inicializa FPDF.
     */
    public function __construct()
    {
        $this->pdf = new FPDF();
        $this->pdf->SetAutoPageBreak(true, 20);
    }

    /**
     * Generar un informe de reservas en formato PDF.
     *
     * @param array $bookings Datos de reservas.
     * @return void Envía el PDF al navegador.
     */
    public function generateBookingsPdf(array $bookings): void
    {
        $this->pdf->AddPage();

        // Header
        $this->pdf->SetFont('Helvetica', 'B', 18);
        $this->pdf->Cell(0, 15, 'Hotel Palms - Informe de reservas', 0, 1, 'C');
        $this->pdf->Ln(5);

        $this->pdf->SetFont('Helvetica', '', 10);
        $this->pdf->Cell(0, 8, 'Generated: ' . date('Y-m-d H:i:s'), 0, 1, 'C');
        $this->pdf->Ln(10);

        // Table header
        $this->pdf->SetFont('Helvetica', 'B', 8);
        $this->pdf->SetFillColor(41, 128, 185);
        $this->pdf->SetTextColor(255);

        $headers = ['ID', 'Confirmacion', 'Cliente', 'Habitacion', 'Check-in', 'Check-out', 'Noches', 'Total', 'Estado'];
        $widths  = [15, 22, 35, 15, 22, 22, 12, 18, 18];

        foreach ($headers as $i => $header) {
            $this->pdf->Cell($widths[$i], 7, $header, 1, 0, 'C', true);
        }
        $this->pdf->Ln();

        // Filas de la tabla.
        $this->pdf->SetFont('Helvetica', '', 7);
        $this->pdf->SetTextColor(0);

        $fill = false;
        foreach ($bookings as $booking) {
            if ($fill) {
                $this->pdf->SetFillColor(235, 241, 250);
            } else {
                $this->pdf->SetFillColor(255, 255, 255);
            }

            $this->pdf->Cell($widths[0], 6, (string)$booking['id'], 1, 0, 'C', $fill);
            $this->pdf->Cell($widths[1], 6, $booking['codigo_confirmacion'], 1, 0, 'C', $fill);
            $this->pdf->Cell($widths[2], 6, ($booking['nombre_cliente'] ?? 'N/A'), 1, 0, 'L', $fill);
            $this->pdf->Cell($widths[3], 6, $booking['numero_habitacion'], 1, 0, 'C', $fill);
            $this->pdf->Cell($widths[4], 6, $booking['check_in'], 1, 0, 'C', $fill);
            $this->pdf->Cell($widths[5], 6, $booking['check_out'], 1, 0, 'C', $fill);
            $this->pdf->Cell($widths[6], 6, (string)($booking['noches'] ?? ''), 1, 0, 'C', $fill);
            $this->pdf->Cell($widths[7], 6, '€' . number_format($booking['precio_total'], 2), 1, 0, 'R', $fill);
            $this->pdf->Cell($widths[8], 6, ucfirst($booking['estado']), 1, 0, 'C', $fill);
            $this->pdf->Ln();

            $fill = !$fill;
        }

        // Summary
        $this->pdf->Ln(10);
        $total = array_sum(array_column($bookings, 'precio_total'));
        $this->pdf->SetFont('Helvetica', 'B', 10);
        $this->pdf->Cell(0, 8, 'Ingresos Totales: €' . number_format($total, 2), 0, 1, 'R');
        $this->pdf->Cell(0, 8, 'Reservas Totales: ' . count($bookings), 0, 1, 'R');

        // Send PDF
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="bookings_report_' . date('Y-m-d') . '.pdf"');
        $this->pdf->Output('D');
        exit;
    }
}