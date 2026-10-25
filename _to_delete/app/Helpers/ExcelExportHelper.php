<?php

namespace App\Helpers;

use Symfony\Component\HttpFoundation\Response;

/**
 * Helper untuk meng-generate response XLS berbasis HTML-as-Excel.
 *
 * Mencegah duplikasi boilerplate XML/HTML Excel header & HTTP response header
 * yang sebelumnya terulang di 7 method controller berbeda.
 */
class ExcelExportHelper
{
    /**
     * Wrap isi <table> HTML ke dalam dokumen XML/HTML yang kompatibel dengan MS Excel.
     */
    public static function wrapTable(string $sheetName, string $tableHtml): string
    {
        $html  = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
        $html .= '<head><meta charset="UTF-8"><!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet>';
        $html .= '<x:Name>' . htmlspecialchars($sheetName, ENT_QUOTES, 'UTF-8') . '</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions>';
        $html .= '</x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]--></head><body>';
        $html .= $tableHtml;
        $html .= '</body></html>';

        return $html;
    }

    /**
     * Hasilkan HTTP Response untuk mendownload file .xls (HTML-as-Excel).
     *
     * @param string $filename Contoh: "rekap_penilaian_2026.xls"
     * @param string $sheetName Contoh: "Hasil Penilaian"
     * @param string $tableHtml Konten tag <table>...</table>
     */
    public static function download(string $filename, string $sheetName, string $tableHtml): Response
    {
        $fullHtml = self::wrapTable($sheetName, $tableHtml);

        return response($fullHtml, 200, [
            'Content-Type'        => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Pragma'              => 'no-cache',
            'Expires'             => '0',
        ]);
    }
}
