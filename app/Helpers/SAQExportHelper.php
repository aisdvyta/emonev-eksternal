<?php

namespace App\Helpers;

use App\Models\Period;
use App\Models\Submission;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

class SAQExportHelper
{
    /**
     * Download single SAQ submission details as Excel (.xls).
     */
    public static function downloadSingle(Submission $submission, Collection $indicators, Collection $answers)
    {
        $evaluatorNames = '-';
        if ($submission->kuesionerEvaluators->isNotEmpty()) {
            $evaluatorNames = $submission->kuesionerEvaluators->pluck('nama_user')->implode(', ');
        } elseif ($submission->evaluators->isNotEmpty()) {
            $evaluatorNames = $submission->evaluators->pluck('nama_user')->implode(', ');
        } else {
            $evaluatorNames = $submission->evaluator->nama_user ?? '-';
        }

        $satkerName = $submission->user->masterSatker->nama_satker ?? 'BPS Kab/Kota';
        $tahun = $submission->result->period->tahun ?? date('Y');
        $filename = 'kuesioner_saq_' . str_replace(' ', '_', strtolower($satkerName)) . '_' . $tahun . '_' . now()->format('Ymd_His') . '.xls';

        $html = '<table border="1" style="font-family:Arial;font-size:11pt;border-collapse:collapse;">';

        // Header Info
        $html .= '<tr><td colspan="12" style="font-weight:bold;font-size:14pt;text-align:center;background-color:#184D77;color:#ffffff;padding:10px;">Hasil Pengisian Kuesioner SAQ - KIP</td></tr>';
        $html .= '<tr><td colspan="2" style="font-weight:bold;background-color:#F3F4F6;border:1px solid #9e9e9e;">Kabupaten/Kota:</td><td colspan="10" style="border:1px solid #9e9e9e;">' . htmlspecialchars($satkerName) . '</td></tr>';
        $html .= '<tr><td colspan="2" style="font-weight:bold;background-color:#F3F4F6;border:1px solid #9e9e9e;">Tahun:</td><td colspan="10" style="border:1px solid #9e9e9e;">' . $tahun . '</td></tr>';
        $html .= '<tr><td colspan="2" style="font-weight:bold;background-color:#F3F4F6;border:1px solid #9e9e9e;">Petugas Evaluator:</td><td colspan="10" style="border:1px solid #9e9e9e;">' . htmlspecialchars($evaluatorNames) . '</td></tr>';
        $html .= '<tr><td colspan="2" style="font-weight:bold;background-color:#F3F4F6;border:1px solid #9e9e9e;">Nilai SAQ:</td><td colspan="10" style="border:1px solid #9e9e9e;">' . ($submission->result?->ques_score !== null ? number_format($submission->result->ques_score, 2) : '-') . '</td></tr>';
        $html .= '<tr><td colspan="12" style="padding:4px;border:none;"></td></tr>';

        // Column Titles
        $cols = ['No', 'Indikator', 'Sub Indikator', 'Pertanyaan', 'Deskripsi', 'Dokumen yang Diperlukan', 'Jawaban Operator', 'Link PPID', 'Link Drive', 'Nilai Evaluator', 'Catatan Evaluator', 'Nilai Maksimal'];
        $html .= '<tr>';
        foreach ($cols as $col) {
            $html .= '<td style="font-weight:bold;background-color:#E8F0FE;text-align:center;padding:6px;border:1px solid #9e9e9e;">' . $col . '</td>';
        }
        $html .= '</tr>';

        $qCounter = 1;
        foreach ($indicators as $ind) {
            foreach ($ind->subIndikators as $subInd) {
                foreach ($subInd->pertanyaans as $q) {
                    $ans = $answers->get($q->id_master_pertanyaan);
                    $val = $ans ? ($ans->answer ? 'Ya' : 'Tidak') : '-';
                    $ppidVal = $ans ? $ans->link_ppid : '';
                    $driveVal = $ans ? $ans->link_drive : '';
                    $earnedScore = $ans ? ($ans->answer_score !== null ? number_format($ans->answer_score, 2) : '-') : '-';
                    $evaluatorNote = $ans ? $ans->evaluator_note : '';

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
                        $cellParts[] = nl2br(htmlspecialchars($docText));
                    }
                    if (!empty($contohLinks)) {
                        $cellParts[] = "<b>Contoh Link:</b><br>" . implode("<br>", array_map('htmlspecialchars', $contohLinks));
                    }
                    $dokumenHtml = !empty($cellParts) ? implode("<br><br>", $cellParts) : '-';

                    $html .= '<tr>';
                    $html .= '<td style="text-align:center;padding:5px;border:1px solid #e0e0e0;">' . $qCounter++ . '</td>';
                    $html .= '<td style="padding:5px;border:1px solid #e0e0e0;">' . htmlspecialchars($ind->nama_indikator) . '</td>';
                    $html .= '<td style="padding:5px;border:1px solid #e0e0e0;">' . htmlspecialchars($subInd->nama_sub_indikator) . '</td>';
                    $html .= '<td style="padding:5px;border:1px solid #e0e0e0;">' . htmlspecialchars($q->ques_text) . '</td>';
                    $html .= '<td style="padding:5px;border:1px solid #e0e0e0;">' . htmlspecialchars($q->ques_description ?? '-') . '</td>';
                    $html .= '<td style="padding:5px;border:1px solid #e0e0e0;">' . $dokumenHtml . '</td>';
                    $html .= '<td style="text-align:center;padding:5px;border:1px solid #e0e0e0;">' . $val . '</td>';

                    // Link PPID
                    $html .= '<td style="padding:5px;border:1px solid #e0e0e0;word-break:break-all;">';
                    if ($ppidVal) {
                        $html .= '<a href="' . htmlspecialchars($ppidVal) . '">' . htmlspecialchars($ppidVal) . '</a>';
                    } else {
                        $html .= '-';
                    }
                    $html .= '</td>';

                    // Link Drive
                    $html .= '<td style="padding:5px;border:1px solid #e0e0e0;word-break:break-all;">';
                    if ($driveVal) {
                        $html .= '<a href="' . htmlspecialchars($driveVal) . '">' . htmlspecialchars($driveVal) . '</a>';
                    } else {
                        $html .= '-';
                    }
                    $html .= '</td>';

                    $html .= '<td style="text-align:center;padding:5px;border:1px solid #e0e0e0;font-weight:bold;">' . $earnedScore . '</td>';
                    $html .= '<td style="padding:5px;border:1px solid #e0e0e0;">' . htmlspecialchars($evaluatorNote ?: '-') . '</td>';
                    $html .= '<td style="text-align:center;padding:5px;border:1px solid #e0e0e0;">' . number_format($q->bobot_pertanyaan, 2) . '</td>';
                    $html .= '</tr>';
                }
            }
        }

        $html .= '</table>';

        return ExcelExportHelper::download($filename, 'Hasil Kuesioner SAQ', $html);
    }

    /**
     * Download multi-sheet SAQ batch report as XLSX.
     */
    public static function downloadBatch(Period $period, Collection $records, Collection $indicators, array $statusLabels)
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);

        // Reusable style definitions
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
        $styleCellCenter = [
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_TOP, 'wrapText' => true],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E0E0E0']]],
        ];
        $styleCellLeft = [
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_TOP, 'wrapText' => true],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E0E0E0']]],
        ];
        $styleCellScore = [
            'font'      => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_TOP, 'wrapText' => true],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E0E0E0']]],
        ];

        $sanitizeSheetName = function (string $name): string {
            $name = preg_replace('/[\\\\\/?\*\[\]:]/u', '', $name);
            $name = str_replace('BPS ', '', $name);
            return mb_substr(trim($name), 0, 31);
        };

        $sheetNameCount = [];

        foreach ($records as $record) {
            $satker      = $record->satker;
            $sub         = $record->submission;
            $statusLabel = $statusLabels[$record->status_val] ?? $record->status_val;
            $answers     = $sub ? $sub->answers->keyBy('id_question') : collect();

            $evaluatorNames = '-';
            if ($sub) {
                if ($sub->kuesionerEvaluators && $sub->kuesionerEvaluators->isNotEmpty()) {
                    $evaluatorNames = $sub->kuesionerEvaluators->pluck('nama_user')->implode(', ');
                } elseif ($sub->evaluators && $sub->evaluators->isNotEmpty()) {
                    $evaluatorNames = $sub->evaluators->pluck('nama_user')->implode(', ');
                }
            }

            $statusVal    = $record->status_val;
            $showSaqScore = in_array($statusVal, ['selesai', 'gugur']);
            $saqScore     = ($showSaqScore && $sub && $sub->result?->ques_score !== null)
                ? number_format($sub->result->ques_score, 2)
                : '-';

            $tahunStr = $period->tahun ? (string) $period->tahun : (string) date('Y');

            $rawName   = $sanitizeSheetName($satker->nama_satker ?? 'Satker');
            $sheetNameCount[$rawName] = ($sheetNameCount[$rawName] ?? 0) + 1;
            $sheetName = $sheetNameCount[$rawName] > 1
                ? mb_substr($rawName, 0, 28) . ' ' . $sheetNameCount[$rawName]
                : $rawName;

            $sheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, $sheetName);
            $spreadsheet->addSheet($sheet);

            // Title
            $sheet->mergeCells('A1:M1');
            $sheet->setCellValue('A1', 'Hasil Pengisian Kuesioner SAQ - KIP');
            $sheet->getStyle('A1')->applyFromArray($styleTitle);
            $sheet->getRowDimension(1)->setRowHeight(22);

            // Info header
            $infoRows = [
                ['Kabupaten/Kota', $satker->nama_satker ?? '-'],
                ['Tahun',          $tahunStr],
                ['Evaluator',      $evaluatorNames],
                ['Nilai SAQ',      $saqScore],
            ];
            $r = 2;
            foreach ($infoRows as [$label, $value]) {
                $sheet->mergeCells('A' . $r . ':B' . $r);
                $sheet->mergeCells('C' . $r . ':M' . $r);
                $sheet->setCellValue('A' . $r, $label);
                $sheet->setCellValueExplicit('C' . $r, $value, DataType::TYPE_STRING);
                $sheet->getStyle('A' . $r . ':B' . $r)->applyFromArray($styleInfoLabel);
                $sheet->getStyle('C' . $r . ':M' . $r)->applyFromArray($styleInfoValue);
                $r++;
            }

            $r++; // spacer

            // Column Headers
            $cols = [
                'A' => 'No',
                'B' => 'Status Kuesioner',
                'C' => 'Indikator',
                'D' => 'Sub Indikator',
                'E' => 'Pertanyaan',
                'F' => 'Deskripsi',
                'G' => "Dokumen yang Diperlukan\n(Beserta Contoh Bukti)",
                'H' => 'Jawaban Operator',
                'I' => 'Link PPID',
                'J' => 'Link Drive',
                'K' => 'Nilai Evaluator',
                'L' => 'Catatan Evaluator',
                'M' => 'Nilai Maksimal',
            ];
            foreach ($cols as $col => $label) {
                $sheet->setCellValue($col . $r, $label);
                $sheet->getStyle($col . $r)->applyFromArray($styleColHeader);
            }
            $sheet->getRowDimension($r)->setRowHeight(30);
            $r++;

            // Data rows
            $qCounter = 1;
            $dataRows = [];
            foreach ($indicators as $ind) {
                foreach ($ind->subIndikators as $subInd) {
                    foreach ($subInd->pertanyaans as $q) {
                        $ans           = $answers->get($q->id_master_pertanyaan);
                        $val           = $ans ? ($ans->answer ? 'Ya' : 'Tidak') : '-';
                        $ppidVal       = $ans ? ($ans->link_ppid ?: '-') : '-';
                        $driveVal      = $ans ? ($ans->link_drive ?: '-') : '-';
                        $earnedScore   = $ans && $ans->answer_score !== null ? number_format($ans->answer_score, 2) : '-';
                        $evaluatorNote = $ans ? ($ans->evaluator_note ?: '-') : '-';

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
                            $statusLabel,
                            $ind->nama_indikator,
                            $subInd->nama_sub_indikator,
                            $q->ques_text,
                            $q->ques_description ?? '-',
                            $dokumenValue,
                            $val,
                            $ppidVal,
                            $driveVal,
                            $earnedScore,
                            $evaluatorNote,
                            number_format($q->bobot_pertanyaan, 2),
                        ];
                    }
                }
            }

            $startDataRow = $r;
            if (!empty($dataRows)) {
                $sheet->fromArray($dataRows, null, 'A' . $startDataRow);
                $endDataRow = $startDataRow + count($dataRows) - 1;

                // High-performance batch range styling
                $sheet->getStyle("A{$startDataRow}:B{$endDataRow}")->applyFromArray($styleCellCenter);
                $sheet->getStyle("C{$startDataRow}:G{$endDataRow}")->applyFromArray($styleCellLeft);
                $sheet->getStyle("H{$startDataRow}:H{$endDataRow}")->applyFromArray($styleCellCenter);
                $sheet->getStyle("I{$startDataRow}:J{$endDataRow}")->applyFromArray($styleCellLeft);
                $sheet->getStyle("K{$startDataRow}:K{$endDataRow}")->applyFromArray($styleCellScore);
                $sheet->getStyle("L{$startDataRow}:L{$endDataRow}")->applyFromArray($styleCellLeft);
                $sheet->getStyle("M{$startDataRow}:M{$endDataRow}")->applyFromArray($styleCellCenter);
            }

            // Column widths
            $sheet->getColumnDimension('A')->setWidth(5);
            $sheet->getColumnDimension('B')->setWidth(18);
            $sheet->getColumnDimension('C')->setWidth(22);
            $sheet->getColumnDimension('D')->setWidth(26);
            $sheet->getColumnDimension('E')->setWidth(40);
            $sheet->getColumnDimension('F')->setWidth(35);
            $sheet->getColumnDimension('G')->setWidth(40);
            $sheet->getColumnDimension('H')->setWidth(16);
            $sheet->getColumnDimension('I')->setWidth(30);
            $sheet->getColumnDimension('J')->setWidth(30);
            $sheet->getColumnDimension('K')->setWidth(14);
            $sheet->getColumnDimension('L')->setWidth(35);
            $sheet->getColumnDimension('M')->setWidth(14);
        }

        $filename = 'rekap_kuesioner_saq_' . $period->tahun . '_' . now()->format('Ymd_His') . '.xlsx';
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
}
