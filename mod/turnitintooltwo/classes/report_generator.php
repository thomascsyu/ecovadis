<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later treatment.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Report generator for PDF and HTML reports
 *
 * @package   turnitintooltwo
 * @copyright 2010 iParadigms LLC
 */

defined('MOODLE_INTERNAL') || die();

class turnitintooltwo_report_generator {

    /**
     * Generate PDF or HTML report based on available libraries
     */
    public static function generate_report($submissiondata, $questions = array(), $answers = array()) {
        global $CFG;

        // Create upload directory if it doesn't exist
        $uploaddir = $CFG->dataroot . '/temp/turnitintooltwo_reports/';
        if (!file_exists($uploaddir)) {
            mkdir($uploaddir, 0755, true);
        }

        // Generate filename
        $filename = 'turnitintooltwo_report_' . $submissiondata['id'] . '_' . date('Y-m-d_H-i-s');
        $pdffilename = $filename . '.pdf';
        $htmlfilename = $filename . '.html';
        
        $pdfpath = $uploaddir . $pdffilename;
        $htmlpath = $uploaddir . $htmlfilename;

        // Build HTML content for the report
        $htmlcontent = self::build_report_html($submissiondata, $questions, $answers);

        // Try to generate PDF using available libraries
        $pdfgenerated = false;
        if (class_exists('Dompdf\Dompdf')) {
            $pdfgenerated = self::generate_pdf_with_dompdf($htmlcontent, $pdfpath);
        } elseif (class_exists('TCPDF')) {
            $pdfgenerated = self::generate_pdf_with_tcpdf($htmlcontent, $pdfpath);
        } elseif (class_exists('mPDF')) {
            $pdfgenerated = self::generate_pdf_with_mpdf($htmlcontent, $pdfpath);
        }

        // If PDF generation failed, at least create the HTML file as fallback
        if (!$pdfgenerated) {
            $htmlbytes = file_put_contents($htmlpath, $htmlcontent);
            if ($htmlbytes === false) {
                throw new moodle_exception('Could not create HTML report file');
            }
        }

        // Return paths for both files (one will be empty depending on what was generated)
        return array(
            'pdf_path' => $pdfgenerated ? $pdfpath : null,
            'html_path' => $pdfgenerated ? null : $htmlpath,
            'html_content' => $htmlcontent
        );
    }

    /**
     * Build HTML report content
     */
    private static function build_report_html($submissiondata, $questions, $answers) {
        global $CFG;

        $title = htmlspecialchars($submissiondata['title'] ?? 'Submission Report');
        $studentname = htmlspecialchars($submissiondata['studentname'] ?? 'Unknown Student');
        $submitteddate = htmlspecialchars($submissiondata['submitteddate'] ?? date('Y-m-d H:i:s'));
        $grade = htmlspecialchars($submissiondata['grade'] ?? 'Not Graded');
        $course = htmlspecialchars($submissiondata['course'] ?? 'Unknown Course');
        
        // Build questions/answers table if available
        $qa_html = '';
        if (!empty($questions) && !empty($answers)) {
            $qa_rows = '';
            foreach ($questions as $idx => $q) {
                $q_text = htmlspecialchars($q['text'] ?? 'Question ' . ($idx + 1));
                $answer = htmlspecialchars($answers[$idx] ?? 'No Answer');
                
                $qa_rows .= '<tr>
                    <td style="padding:8px;border-bottom:1px solid #e5e7eb;font-size:12px;">' . ($idx + 1) . '</td>
                    <td style="padding:8px;border-bottom:1px solid #e5e7eb;font-size:12px;">' . $q_text . '</td>
                    <td style="padding:8px;border-bottom:1px solid #e5e7eb;font-size:12px;">' . $answer . '</td>
                </tr>';
            }
            
            $qa_html = '<div style="margin-top:20px;">
                <h3 style="font-size:14px;font-weight:bold;margin-bottom:10px;">Questions and Answers</h3>
                <table style="width:100%;border-collapse:collapse;">
                    <thead>
                        <tr style="background:#f8fafc;">
                            <th style="padding:8px;text-align:left;font-size:12px;color:#6b7280;border-bottom:2px solid #e5e7eb;">#</th>
                            <th style="padding:8px;text-align:left;font-size:12px;color:#6b7280;border-bottom:2px solid #e5e7eb;">Question</th>
                            <th style="padding:8px;text-align:left;font-size:12px;color:#6b7280;border-bottom:2px solid #e5e7eb;">Answer</th>
                        </tr>
                    </thead>
                    <tbody>' . $qa_rows . '</tbody>
                </table>
            </div>';
        }

        return '<!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>' . $title . '</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; color: #333; }
                .header { border-bottom: 2px solid #007cba; padding-bottom: 10px; margin-bottom: 20px; }
                .title { font-size: 24px; font-weight: bold; margin: 0; }
                .subtitle { font-size: 16px; color: #666; margin: 5px 0; }
                .info-box { background: #f5f5f5; padding: 15px; border-radius: 5px; margin: 15px 0; }
                .info-row { display: flex; margin: 5px 0; }
                .info-label { font-weight: bold; width: 150px; }
                .info-value { flex: 1; }
                table { width: 100%; border-collapse: collapse; margin-top: 10px; }
                th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
                th { background-color: #f2f2f2; }
                tr:hover { background-color: #f5f5f5; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1 class="title">' . $title . '</h1>
                <p class="subtitle">Turnitin Tool Two Report</p>
            </div>
            
            <div class="info-box">
                <div class="info-row">
                    <div class="info-label">Student Name:</div>
                    <div class="info-value">' . $studentname . '</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Course:</div>
                    <div class="info-value">' . $course . '</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Submitted Date:</div>
                    <div class="info-value">' . $submitteddate . '</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Grade:</div>
                    <div class="info-value">' . $grade . '</div>
                </div>
            </div>
            
            ' . $qa_html . '
            
            <div style="margin-top: 30px; padding-top: 10px; border-top: 1px solid #ccc; font-size: 12px; color: #666;">
                <p>Generated by Turnitin Tool Two on ' . date('Y-m-d H:i:s') . '</p>
            </div>
        </body>
        </html>';
    }

    /**
     * Generate PDF using Dompdf library
     */
    private static function generate_pdf_with_dompdf($htmlcontent, $outputpath) {
        try {
            $dompdf = new \Dompdf\Dompdf();
            $dompdf->loadHtml($htmlcontent);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            
            $pdfdata = $dompdf->output();
            $byteswritten = file_put_contents($outputpath, $pdfdata);
            
            return $byteswritten !== false;
        } catch (Exception $e) {
            error_log('Dompdf error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate PDF using TCPDF library
     */
    private static function generate_pdf_with_tcpdf($htmlcontent, $outputpath) {
        try {
            $pdf = new \TCPDF();
            $pdf->SetCreator(PDF_CREATOR);
            $pdf->SetTitle('Turnitin Tool Two Report');
            $pdf->SetHeaderData('', 0, 'Turnitin Tool Two Report', '');
            $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
            $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
            $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
            $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
            $pdf->AddPage();
            $pdf->writeHTML($htmlcontent, true, false, true, false, '');
            $pdf->Output($outputpath, 'F');
            
            return file_exists($outputpath);
        } catch (Exception $e) {
            error_log('TCPDF error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate PDF using mPDF library
     */
    private static function generate_pdf_with_mpdf($htmlcontent, $outputpath) {
        try {
            $mpdf = new \mPDF();
            $mpdf->WriteHTML($htmlcontent);
            $mpdf->Output($outputpath, 'F');
            
            return file_exists($outputpath);
        } catch (Exception $e) {
            error_log('mPDF error: ' . $e->getMessage());
            return false;
        }
    }
}