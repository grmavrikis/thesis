<?php
require_once __DIR__ . '/../libs/TCPDF/tcpdf.php';

class InvoicePDF extends TCPDF
{
    private $data;
    private $totals;

    public function __construct($data, $totals)
    {
        parent::__construct(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $this->data = $data;
        $this->totals = $totals;

        $this->SetCreator(PDF_CREATOR);
        $this->SetAuthor($this->data['issuer']['company_title']);
        $this->SetTitle('Παραστατικό #' . $this->data['invoice']['serial_number']);

        $this->SetMargins(15, 15, 15);
        $this->SetAutoPageBreak(TRUE, 20);
        $this->setImageScale(PDF_IMAGE_SCALE_RATIO);
        $this->SetFont('dejavusans', '', 10);
        $this->setPrintHeader(false);
    }

    public function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('dejavusans', 'I', 8);
        $page_string = sprintf(TEXT_INVOICE_PDF_PAGE, $this->getAliasNumPage(), $this->getAliasNbPages());
        $this->Cell(0, 10, $page_string, 0, false, 'C', 0, '', 0, false, 'T', 'M');
    }

    public function generate()
    {
        $this->AddPage();
        $this->renderCompanyHeader();
        $this->renderInvoiceMeta();
        $this->renderChargesTable();
        $this->renderTotals();
    }

    private function renderCompanyHeader()
    {
        if (!empty($this->data['issuer']['logo']))
        {
            $logo_path = __DIR__ . '/../uploads/' . ltrim($this->data['issuer']['logo'], './');
            if (file_exists($logo_path))
            {
                $this->Image($logo_path, 15, 15, 45, '', '', '', 'T', false, 300, '', false, false, 0, false, false, false);
            }
        }
        $html = '
        <table width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td width="35%"></td>
                <td width="65%" align="right">
                    <span style="font-size:12pt; font-weight:bold;">' . htmlspecialchars($this->data['issuer']['company_title']) . '</span><br>
                    <span style="font-size:9pt; line-height:1.4;">' .
            htmlspecialchars($this->data['issuer']['company_name']) . '<br>' .
            htmlspecialchars($this->data['issuer']['address_street'] . ' ' . $this->data['issuer']['address_number'] . ', ' . $this->data['issuer']['city']) . '<br>' .
            LABEL_COMPANY_VAT_NUMBER . ' ' . htmlspecialchars($this->data['issuer']['vat_number']) . ' - ' .
            LABEL_COMPANY_TAX_OFFICE . ' ' . htmlspecialchars($this->data['issuer']['tax_office']) . '<br>' .
            LABEL_PHONE_SHORT . ' ' . htmlspecialchars($this->data['issuer']['phone']) . ' | ' .
            htmlspecialchars($this->data['issuer']['email']) .
            '</span>
                </td>
            </tr>
        </table>
        <div style="border-bottom:1px solid #333; line-height:5px;">&nbsp;</div>';
        $this->writeHTML($html, true, false, false, false, '');
    }

    private function renderInvoiceMeta()
    {
        $status_text = $this->data['invoice']['is_canceled'] ? ' <span style="color:red;">(' . TEXT_INVOICE_PDF_CANCELED . ')</span>' : '';
        $html = '
        <div style="line-height:10px;">&nbsp;</div>
        <div align="center">
            <span style="font-size:14pt; font-weight:bold;">' . mb_strtoupper(htmlspecialchars($this->data['invoice']['title']), 'UTF-8') . $status_text . '</span>
        </div>
        <div style="line-height:15px;">&nbsp;</div>
        <table width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td width="50%" align="left">
                    <strong>' . TEXT_INVOICE_PDF_FOLIO . '</strong> ' . htmlspecialchars($this->data['invoice']['serial_number']) . '
                </td>
                <td width="50%" align="right">
                    <strong>' . TEXT_INVOICE_PDF_ISSUE_DATE . '</strong> ' . htmlspecialchars($this->data['invoice']['issue_date']) . '
                </td>
            </tr>
        </table>
        <div style="line-height:10px; border-bottom:0.5px solid #eee;">&nbsp;</div>
        <div style="line-height:10px;">&nbsp;</div>
        <table width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td width="50%" align="left">
                    <span style="font-size:8pt; color:#666;">' . TEXT_INVOICE_PDF_CUSTOMER . '</span><br>
                    <span style="font-size:11pt; font-weight:bold;">' . htmlspecialchars($this->data['client']['name']) . '</span>
                </td>
                <td width="50%" align="right">
                    <span style="font-size:8pt; color:#666;">' . TEXT_INVOICE_PDF_DIETITIAN . '</span><br>
                    <span style="font-size:11pt; font-weight:bold;">' . htmlspecialchars($this->data['dietitian']['name']) . '</span>
                </td>
            </tr>
        </table>
        <div style="line-height:20px;">&nbsp;</div>';

        $this->writeHTML($html, true, false, false, false, '');
    }

    private function renderChargesTable()
    {
        $html = '
        <table border="1" cellpadding="6" cellspacing="0" style="border-collapse:collapse; border-color:#ccc;">
            <thead>
                <tr style="background-color:#E8F0FE; font-weight:bold; color:#1967D2;">
                    <th width="40%" align="left">' . TEXT_INVOICE_PDF_CHARGE_DESCRIPTION . '</th>
                    <th width="15%" align="right">' . TEXT_INVOICE_PDF_CHARGE_CLEAN . '</th>
                    <th width="15%" align="center">' . TEXT_INVOICE_PDF_CHARGE_VAT_PERCENT . '</th>
                    <th width="15%" align="right">' . TEXT_INVOICE_PDF_CHARGE_VAT_VALUE . '</th>
                    <th width="15%" align="right">' . TEXT_INVOICE_PDF_CHARGE_TOTAL . '</th>
                </tr>
            </thead>
        <tbody>';
        foreach ($this->data['charges'] as $charge)
        {
            $html .= '
            <tr>
                <td width="40%" align="left">' . htmlspecialchars($charge['description']) . '</td>
                <td width="15%" align="right">' . number_format($charge['clean_amount'], 2, ',', '.') . '</td>
                <td width="15%" align="center">' . number_format($charge['tax_rate'], 2, ',', '.') . '%</td>
                <td width="15%" align="right">' . number_format($charge['tax_amount'], 2, ',', '.') . '</td>
                <td width="15%" align="right">' . number_format($charge['total_amount'], 2, ',', '.') . '</td>
            </tr>';
        }
        $html .= '</tbody></table><br><br>';
        $this->writeHTML($html, true, false, false, false, '');
    }

    private function renderTotals()
    {
        $html = '
        <table width="100%" cellpadding="5">
            <tr>
            <td width="40%"></td>
            <td width="60%">
                <table border="1" cellpadding="6" cellspacing="0" style="border-collapse:collapse; border-color:#ccc;">
                    <tr>
                        <td width="60%" align="right">
                            <strong>' . TEXT_INVOICE_PDF_TOTAL_CLEAN . '</strong>
                        </td>
                        <td width="40%" align="right">' . number_format($this->totals['clean'], 2, ',', '.') . ' €</td>
                    </tr>';
        foreach ($this->totals['taxes'] as $rate => $tax_val)
        {
            $html .= '
            <tr>
                <td align="right">
                    <strong>' . TEXT_INVOICE_PDF_TOTAL_VAT . $rate . ':</strong>
                </td>
                <td align="right">' . number_format($tax_val, 2, ',', '.') . ' €</td>
            </tr>';
        }
        $html .= '
            <tr style="background-color:#f0f9f1;">
                <td align="right" style="color:#28a745; font-size:12pt;">
                    <strong>' . TEXT_INVOICE_PDF_TOTAL_VALUE . '</strong>
                </td>
                <td align="right" style="color:#28a745; font-size:12pt;">
                    <strong>' . number_format($this->totals['grand'], 2, ',', '.') . ' €</strong>
                </td>
            </tr>
        </table></td></tr></table>';

        $this->startTransaction();
        $start_page = $this->getPage();
        $this->writeHTML($html, true, false, false, false, '');
        if ($this->getPage() > $start_page)
        {
            $this->rollbackTransaction(true);
            $this->AddPage();
            $this->writeHTML($html, true, false, false, false, '');
        }
        else
        {
            $this->commitTransaction();
        }
    }

    public function save($path)
    {
        $this->Output($path, 'F');
        return file_exists($path);
    }
}
