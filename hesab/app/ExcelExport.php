<?php
declare(strict_types=1);

final class ExcelExport
{
    /** Simple XML Spreadsheet (Excel-compatible) without Composer. */
    public static function download(string $filename, array $headers, array $rows): never
    {
        $filename = preg_replace('/[^\w\-.]+/u', '_', $filename) ?: 'export.xls';
        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-store');
        echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        echo "<?mso-application progid=\"Excel.Sheet\"?>\n";
        echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">';
        echo '<Worksheet ss:Name="Sheet1"><Table>';
        echo '<Row>';
        foreach ($headers as $h) {
            echo '<Cell><Data ss:Type="String">' . self::xml((string) $h) . '</Data></Cell>';
        }
        echo '</Row>';
        foreach ($rows as $row) {
            echo '<Row>';
            foreach ($row as $cell) {
                $type = is_numeric($cell) ? 'Number' : 'String';
                echo '<Cell><Data ss:Type="' . $type . '">' . self::xml((string) $cell) . '</Data></Cell>';
            }
            echo '</Row>';
        }
        echo '</Table></Worksheet></Workbook>';
        exit;
    }

    private static function xml(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}
