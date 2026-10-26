<?php

namespace App\Helpers;

use App\Models\Period;
use App\Models\MasterSatker;
use App\Models\Submission;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SAQImportHelper
{
    /**
     * Generate pre-filled Excel template (.xlsx) for SAQ Operator questionnaire.
     * All columns (No, Status, Indikator, Sub Indikator, Pertanyaan) are pre-filled.
     * Operator only needs to fill: Jawaban Operator (Ya/Tidak), Link PPID, Link Drive.
     */
    public static function generateTemplate(
        Period $period,
        Collection $indicators,
        Collection $answers,
        ?Submission $submission = null,
        ?MasterSatker $satker = null
    ): StreamedResponse {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template SAQ');

        // Style definitions
        $styleTitle = [
            'font'      => ['bold' => true, 'size' => 13, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '184D77']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ];
        $styleInfoLabel = [
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F3F4F6']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '9E9E9E']]],
        ];
        $styleInfoValue = [
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '9E9E9E']]],
        ];
        $styleColHeader = [
            'font'      => ['bold' => true],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E8F0FE']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '9E9E9E']]],
        ];
        $styleColHeaderAction = [
            'font'      => ['bold' => true, 'color' => ['rgb' => '1E40AF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DBEAFE']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '9E9E9E']]],
        ];
        $styleCellCenter = [
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_TOP, 'wrapText' => true],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E0E0E0']]],
        ];
        $styleCellLeft = [
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_TOP, 'wrapText' => true],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E0E0E0']]],
        ];
        $styleCellAnswer = [
            'font'      => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_TOP, 'wrapText' => true],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E0E0E0']]],
        ];

        // Row 1: Title
        $sheet->mergeCells('A1:I1');
        $titleText = ($submission || $satker)
            ? 'Hasil Pengisian Kuesioner SAQ - KIP'
            : 'Daftar Pertanyaan dan Persyaratan Dokumen Kuesioner SAQ - KIP';
        $sheet->setCellValue('A1', $titleText);
        $sheet->getStyle('A1')->applyFromArray($styleTitle);
        $sheet->getRowDimension(1)->setRowHeight(24);

        // Rows 2-4: Info metadata
        $r = 2;
        if ($submission || $satker) {
            $satkerName = $satker->nama_satker ?? $submission->result?->satker?->nama_satker ?? '-';
            $infoRows = [
                ['Unit Kerja / Satker', (string) $satkerName],
                ['Tahun',               (string) ($period->tahun ?? date('Y'))],
                ['Status Kuesioner',    $submission ? ($submission->status_label ?? 'Draft Pengisian') : 'Draft Pengisian'],
            ];
        } else {
            $infoRows = [
                ['Tahun',      (string) ($period->tahun ?? date('Y'))],
                ['Keterangan', 'Dokumen Persiapan Kuesioner SAQ (Format Publik & Contoh Bukti Dukung)'],
            ];
        }

        foreach ($infoRows as [$label, $val]) {
            $sheet->mergeCells('A' . $r . ':B' . $r);
            $sheet->mergeCells('C' . $r . ':I' . $r);
            $sheet->setCellValue('A' . $r, $label);
            $sheet->setCellValueExplicit('C' . $r, $val, DataType::TYPE_STRING);
            $sheet->getStyle('A' . $r . ':B' . $r)->applyFromArray($styleInfoLabel);
            $sheet->getStyle('C' . $r . ':I' . $r)->applyFromArray($styleInfoValue);
            $r++;
        }

        $r++; // Spacer row

        // Column Headers (Sama persis antara login publik dan operator)
        $cols = [
            'A' => 'No',
            'B' => 'Indikator',
            'C' => 'Sub Indikator',
            'D' => 'Pertanyaan',
            'E' => 'Deskripsi',
            'F' => "Dokumen yang Diperlukan\n(Beserta Contoh Bukti)",
            'G' => "Jawaban Operator\n(Ya / Tidak)",
            'H' => "Link PPID\n(Jika Ya)",
            'I' => "Link Drive\n(Jika Ya)",
        ];

        foreach ($cols as $col => $label) {
            $sheet->setCellValue($col . $r, $label);
            if (in_array($col, ['G', 'H', 'I'])) {
                $sheet->getStyle($col . $r)->applyFromArray($styleColHeaderAction);
            } else {
                $sheet->getStyle($col . $r)->applyFromArray($styleColHeader);
            }
        }
        $sheet->getRowDimension($r)->setRowHeight(34);
        $r++;

        // Pre-fill data rows
        $dataRows = [];
        $qCounter = 1;

        foreach ($indicators as $ind) {
            foreach ($ind->subIndikators as $subInd) {
                foreach ($subInd->pertanyaans as $q) {
                    $ans = $answers->get($q->id_master_pertanyaan);

                    $ansText = '';
                    $ppidVal = '';
                    $driveVal = '';

                    // Jika operator sudah login dan sudah mengisi jawaban, isikan nilai yang ada
                    if ($ans && $ans->answer !== null) {
                        $ansText = (bool) $ans->answer ? 'Ya' : 'Tidak';
                        if ((bool) $ans->answer) {
                            $ppidVal = $ans->link_ppid ?? '';
                            $driveVal = $ans->link_drive ?? '';
                        }
                    }

                    $docText = trim((string)($q->doc_requirement ?? ''));
                    $contohLinks = [];
                    if (!empty(trim((string)($q->link_drive ?? '')))) {
                        $contohLinks[] = "Link Drive: " . trim((string)$q->link_drive);
                    }
                    if (!empty(trim((string)($q->link_ppid ?? '')))) {
                        $contohLinks[] = "Link PPID: " . trim((string)$q->link_ppid);
                    }

                    $cellParts = [];
                    if (!empty($docText)) {
                        $cellParts[] = $docText;
                    }
                    if (!empty($contohLinks)) {
                        $cellParts[] = "Contoh Link Bukti Dukung:\n" . implode("\n", $contohLinks);
                    }

                    $dokumenValue = !empty($cellParts) ? implode("\n\n", $cellParts) : '-';

                    $dataRows[] = [
                        $qCounter++,
                        $ind->nama_indikator,
                        $subInd->nama_sub_indikator,
                        $q->ques_text,
                        $q->ques_description ?? '-',
                        $dokumenValue,
                        $ansText,
                        $ppidVal,
                        $driveVal,
                    ];
                }
            }
        }

        $startDataRow = $r;
        if (!empty($dataRows)) {
            $sheet->fromArray($dataRows, null, 'A' . $startDataRow);
            $endDataRow = $startDataRow + count($dataRows) - 1;

            // Apply column-level batch styling
            $sheet->getStyle("A{$startDataRow}:A{$endDataRow}")->applyFromArray($styleCellCenter);
            $sheet->getStyle("B{$startDataRow}:F{$endDataRow}")->applyFromArray($styleCellLeft);
            $sheet->getStyle("G{$startDataRow}:G{$endDataRow}")->applyFromArray($styleCellAnswer);
            $sheet->getStyle("H{$startDataRow}:I{$endDataRow}")->applyFromArray($styleCellLeft);
        }

        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(6);
        $sheet->getColumnDimension('B')->setWidth(24);
        $sheet->getColumnDimension('C')->setWidth(26);
        $sheet->getColumnDimension('D')->setWidth(45);
        $sheet->getColumnDimension('E')->setWidth(40);
        $sheet->getColumnDimension('F')->setWidth(45);
        $sheet->getColumnDimension('G')->setWidth(20);
        $sheet->getColumnDimension('H')->setWidth(34);
        $sheet->getColumnDimension('I')->setWidth(34);

        if ($satker) {
            $filename = 'template_kuesioner_saq_' . str_replace(' ', '_', strtolower($satker->nama_satker ?? 'satker')) . '_' . ($period->tahun ?? date('Y')) . '.xlsx';
        } else {
            $filename = 'template_kuesioner_saq_' . ($period->tahun ?? date('Y')) . '.xlsx';
        }

        $writer = new Xlsx($spreadsheet);

        return response()->stream(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control'       => 'max-age=0',
            'Pragma'              => 'no-cache',
            'Expires'             => '0',
        ]);
    }

    /**
     * Generate Excel template for public download (Halaman Login).
     * Memanggil generateTemplate dengan koleksi jawaban kosong (kosongan sebelum login).
     */
    public static function generatePublicTemplate(
        Period $period,
        Collection $indicators
    ): StreamedResponse {
        return self::generateTemplate($period, $indicators, collect(), null, null);
    }

    /**
     * Parse uploaded Excel file and map rows to questions.
     *
     * @param string $filePath
     * @param Collection $indicators
     * @return array
     */
    public static function parseAnswers(string $filePath, Collection $indicators): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, false);

        if (count($rows) < 2) {
            throw new \Exception('File Excel kosong atau format tidak sesuai.');
        }

        // Build questions lookup map
        $allQuestions = collect();
        $orderNoCounter = 1;
        foreach ($indicators as $ind) {
            foreach ($ind->subIndikators as $subInd) {
                foreach ($subInd->pertanyaans as $q) {
                    $q->calculated_order_no = $orderNoCounter++;
                    $allQuestions->push($q);
                }
            }
        }

        $questionsByOrder = $allQuestions->keyBy('calculated_order_no');
        $questionsByText = $allQuestions->keyBy(fn($q) => self::normalizeText($q->ques_text));

        // Detect header row (look for "Pertanyaan" or "Jawaban" or default to row 7/index 6)
        $headerRowIndex = 6;
        for ($i = 0; $i < min(15, count($rows)); $i++) {
            $rowString = strtolower(implode(' ', array_filter(array_map('strval', $rows[$i]))));
            if (str_contains($rowString, 'pertanyaan') && str_contains($rowString, 'jawaban')) {
                $headerRowIndex = $i;
                break;
            }
        }

        // Dynamic column index detection with fallback to standard layout
        $colNo = 0;
        $colText = 3;
        $colJawaban = 6;
        $colPpid = 7;
        $colDrive = 8;

        if (isset($rows[$headerRowIndex])) {
            foreach ($rows[$headerRowIndex] as $idx => $cellVal) {
                $cellLower = strtolower(trim((string)$cellVal));
                if ($cellLower === 'no') {
                    $colNo = $idx;
                } elseif (str_contains($cellLower, 'pertanyaan') && !str_contains($cellLower, 'deskripsi')) {
                    $colText = $idx;
                } elseif (str_contains($cellLower, 'jawaban')) {
                    $colJawaban = $idx;
                } elseif (str_contains($cellLower, 'ppid')) {
                    $colPpid = $idx;
                } elseif (str_contains($cellLower, 'drive')) {
                    $colDrive = $idx;
                }
            }
        }

        $validAnswers = [];
        $totalDetected = 0;
        $answeredYa = 0;
        $answeredTidak = 0;
        $unanswered = 0;
        $warnings = [];

        for ($i = $headerRowIndex + 1; $i < count($rows); $i++) {
            $row = $rows[$i];

            // Check if row is empty
            if (empty(array_filter($row, fn($v) => !is_null($v) && trim((string)$v) !== ''))) {
                continue;
            }

            $rawNo = isset($row[$colNo]) ? trim((string)$row[$colNo]) : '';
            $rawText = isset($row[$colText]) ? trim((string)$row[$colText]) : '';
            $rawJawaban = isset($row[$colJawaban]) ? trim((string)$row[$colJawaban]) : '';
            $rawPpid = isset($row[$colPpid]) ? trim((string)$row[$colPpid]) : '';
            $rawDrive = isset($row[$colDrive]) ? trim((string)$row[$colDrive]) : '';

            // Map to question
            $matchedQuestion = null;
            if (is_numeric($rawNo) && $questionsByOrder->has((int)$rawNo)) {
                $matchedQuestion = $questionsByOrder->get((int)$rawNo);
            } elseif (!empty($rawText)) {
                $matchedQuestion = $questionsByText->get(self::normalizeText($rawText));
            }

            if (!$matchedQuestion) {
                continue;
            }

            $totalDetected++;
            $normJawaban = strtolower(trim($rawJawaban));

            $isYa = in_array($normJawaban, ['ya', 'y', '1', 'true', 'yes']);
            $isTidak = in_array($normJawaban, ['tidak', 't', '0', 'false', 'no']);

            if ($isYa) {
                $answeredYa++;
                $ppidVal = !empty($rawPpid) && $rawPpid !== '-' ? $rawPpid : null;
                $driveVal = !empty($rawDrive) && $rawDrive !== '-' ? $rawDrive : null;

                // Validate URL format
                if ($ppidVal && !preg_match('/^https?:\/\//i', $ppidVal)) {
                    $warnings[] = "No. {$matchedQuestion->calculated_order_no}: Isikan Link PPID dengan benar";
                }
                if ($driveVal && !preg_match('/^https?:\/\//i', $driveVal)) {
                    $warnings[] = "No. {$matchedQuestion->calculated_order_no}: Isikan Link Drive dengan benar";
                }

                $validAnswers[] = [
                    'id_question'   => $matchedQuestion->id_master_pertanyaan,
                    'id_indicators' => $matchedQuestion->id_indicators,
                    'answer'        => true,
                    'link_ppid'     => $ppidVal,
                    'link_drive'    => $driveVal,
                ];
            } elseif ($isTidak) {
                $answeredTidak++;
                if (!empty($rawPpid) || !empty($rawDrive)) {
                    $warnings[] = "No. {$matchedQuestion->calculated_order_no}: Jawaban 'Tidak' tetapi kolom link bukti terisi. Kolom link dikosongkan (pastikan jawaban bukan seharusnya 'Ya').";
                }
                $validAnswers[] = [
                    'id_question'   => $matchedQuestion->id_master_pertanyaan,
                    'id_indicators' => $matchedQuestion->id_indicators,
                    'answer'        => false,
                    'link_ppid'     => null,
                    'link_drive'    => null,
                ];
            } else {
                $unanswered++;
            }
        }

        return [
            'answers'        => $validAnswers,
            'total_detected' => $totalDetected,
            'answered_ya'    => $answeredYa,
            'answered_tidak' => $answeredTidak,
            'unanswered'     => $unanswered,
            'warnings'       => $warnings,
        ];
    }

    /**
     * Normalize string for fuzzy question matching.
     */
    private static function normalizeText(string $text): string
    {
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9]/', '', $text);
        return trim($text);
    }
}
