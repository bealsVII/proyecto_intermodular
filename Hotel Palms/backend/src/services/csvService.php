<?php
/**
 * Servicio de exportación CSV.
 * Genera archivos CSV para la exportación de datos.
 *
 * @author Beatriz Lara Solana
 * @version 1.0.0
 */

class CsvService
{
    /**
     * Generar un archivo CSV de reservas.
     *
     * @param array $bookings Datos de reservas.
     * @return void Envía el archivo CSV al navegador.
     */
    public function generateBookingsCsv(array $bookings): void
    {
        $filename = 'bookings_export_' . date('Y-m-d_H-i-s') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        // Lista de materiales UTF-8 para compatibilidad con Excel.
        echo "\xEF\xBB\xBF";

        $output = fopen('php://output', 'w');

        // Fila de encabezado.
        fputcsv($output, [
            'ID',
            'Código de Confirmación',
            'Nombre del Cliente',
            'Email del Cliente',
            'Móvil del Cliente',
            'Número de habitación',
            'Tipo de habitación',
            'Check-in',
            'Check-out',
            'Noches',
            'Huéspedes',
            'Precio Total',
            'Estado',
            'Solicitudes Especiales',
            'Creado En',
        ]);

        // Filas de datos.
        foreach ($bookings as $booking) {
            fputcsv($output, [
                $booking['id'],
                $booking['codigo_confimacion'],
                $booking['nombre_cliente'] ?? $booking['nombre'] . ' ' . $booking['apellidos'],
                $booking['email'],
                $booking['movil'] ?? '',
                $booking['numero_habitacion'],
                $booking['tipo_habitacion'],
                $booking['check_in'],
                $booking['check_out'],
                $booking['noches'] ?? '',
                $booking['huespedes'],
                $booking['precio_total'],
                $booking['estado'],
                $booking['solicitudes_especiales'] ?? '',
                $booking['creado_en'],
            ]);
        }

        fclose($output);
        exit;
    }
}