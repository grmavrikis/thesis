<?php
class Invoice
{
    private $db;
    private $language;

    public function __construct($invoice_language = '')
    {
        global $db, $language;

        $this->db = $db;

        if (!empty($invoice_language))
        {
            $this->language = $invoice_language;
        }
        else
        {
            $this->language = $language;
        }
    }

    /**
     * Calculates tax_amount based on clean_amount and tax_id
     * @param float $clean_amount
     * @param int $tax_id
     * @return array tax_amount
     */
    private function calculateTaxAmount($clean_amount, $tax_id)
    {
        $sql = "SELECT factor FROM tax WHERE tax_id = :tax_id LIMIT 1";
        $tax_row = $this->db->query($sql, [':tax_id' => $tax_id])->fetch();

        $factor = ($tax_row) ? (float)$tax_row['factor'] : 0;

        $tax_amount = $clean_amount * $factor;

        return (float)$tax_amount;
    }

    /**
     * Retrieves the next available incremental serial number for a new invoice.
     * This method queries the maximum existing serial number in the 'invoice' table 
     * and increments it by one. If no invoices exist, it initializes the sequence at 1.
     *
     * @return int The next serial number in the sequence.
     */
    private function getNextSerialNumber()
    {
        $sql = "SELECT MAX(serial_number) AS last_sn FROM invoice";
        $result = $this->db->query($sql, [])->fetch();
        return (!$result || $result['last_sn'] === null) ? 1 : (int)$result['last_sn'] + 1;
    }

    /**
     * Creates a new invoice record along with its associated line items (charges).
     *
     * This method executes the following logic:
     * - Issuer Snapshot: Retrieves current configuration from 'invoice_settings' and embeds 
     * it directly into the invoice to maintain historical accuracy against future changes.
     * - Sequential Numbering: Automatically calculates and assigns the next available serial number.
     * - Financial Integrity: Processes each line item using a strict rounding strategy 
     * (PHP_ROUND_HALF_UP) to ensure clean, tax, and total amounts remain mathematically consistent.
     *
     * @param array $data {
     * @type int    $client_id               The unique ID of the client (receiver).
     * @type int    $dietitian_id            The unique ID of the dietitian (issuer).
     * @type array  $charges                 A collection of line item arrays:
     * @type string $charges[].description  The service or product description.
     * @type float  $charges[].clean_amount The net value (before tax).
     * @type float  $charges[].tax_amount   The initial tax value (recalculated internally).
     * @type int    $charges[].tax_id       The foreign key referencing the tax rate.
     * }
     *
     * @return int|bool Returns the generated invoice_id on success, or false on failure.
     */
    public function createInvoice($data)
    {

        $sql = "SELECT * FROM invoice_settings WHERE invoice_settings_id = 1";
        $invoice_settings = $this->db->query($sql, [])->fetch();

        $invoice_data = [
            'issuer_title'  => $invoice_settings['company_title'],
            'issuer_name'  => $invoice_settings['company_name'],
            'issuer_vat'  => $invoice_settings['vat_number'],
            'issuer_tax_office'  => $invoice_settings['tax_office'],
            'issuer_address_street'  => $invoice_settings['address_street'],
            'issuer_address_number'  => $invoice_settings['address_number'],
            'issuer_city'  => $invoice_settings['city'],
            'issuer_postal_code'  => $invoice_settings['postal_code'],
            'issuer_phone'  => $invoice_settings['phone'],
            'issuer_email'  => $invoice_settings['email'],
            'serial_number'  => $this->getNextSerialNumber(),
            'issue_date'     => date('Y-m-d H:i:s'),
            'canceled'       => 0,
            'client_id'      => $data['client_id'],
            'dietitian_id'   => $data['dietitian_id']
        ];

        $inserted_invoice = $this->db->insert('invoice', $invoice_data);
        $invoice_id = $inserted_invoice[0] ?? null;

        if (!$invoice_id) return false;

        if (!empty($data['charges']) && is_array($data['charges']))
        {
            foreach ($data['charges'] as $charge)
            {
                $clean = (float)$charge['clean_amount'];
                $tax   = (float)$charge['tax_amount'];
                $total = $clean + $tax;

                $clean_final = round($clean, 2, PHP_ROUND_HALF_UP);
                $total_final = round($total, 2, PHP_ROUND_HALF_UP);

                // Tax occurs after the clean and total are rounded
                $tax_final = $total_final - $clean_final;

                $charge_data = [
                    'invoice_id'   => $invoice_id,
                    'description'  => $charge['description'],
                    'clean_amount' => $clean_final,
                    'tax_amount'   => $tax_final,
                    'tax_id'       => $charge['tax_id']
                ];

                $this->db->insert('invoice_charge', $charge_data);
            }
        }

        return $invoice_id;
    }

    /**
     * Automatically generates an invoice based on an existing appointment's data.
     *
     * This method executes the following logic:
     * - Data Retrieval: Fetches client, dietitian, and service cost details by joining 
     * appointment, service, and service_description tables.
     * - Localization: Uses the current language context to retrieve the localized service title.
     * - Tax Calculation: Determines the tax amount for the service cost.
     * - Invoice Creation: Packages the data and invokes the createInvoice method.
     * - Linkage: Updates the original appointment record with the newly generated invoice_id.
     *
     * @param int $appointment_id The unique ID of the appointment to be invoiced.
     * @return int|bool Returns the generated invoice_id on success, or false if the 
     * appointment is not found or the creation fails.
     */
    public function createAppointmentInvoice($appointment_id)
    {
        if (empty($appointment_id) || !is_numeric($appointment_id))
        {
            return false;
        }

        $sql = "SELECT a.client_id, a.dietitian_id, s.clean_cost, s.tax_id, sd.title
                FROM appointment a
                JOIN service s ON a.service_id = s.service_id
                JOIN service_description sd ON s.service_id = sd.service_id AND sd.language_id = :language_id
                WHERE a.appointment_id = :id LIMIT 1";

        $params = [':language_id' => $this->language['id'], ':id' => $appointment_id];
        $appointment_data = $this->db->query($sql, $params)->fetch();

        if (!$appointment_data) return false;

        $tax_amount = $this->calculateTaxAmount($appointment_data['clean_cost'], $appointment_data['tax_id']);

        $invoice_payload = [
            'client_id'      => $appointment_data['client_id'],
            'dietitian_id'   => $appointment_data['dietitian_id'],
            'charges' => [
                [
                    'description' => $appointment_data['title'],
                    'clean_amount' => $appointment_data['clean_cost'],
                    'tax_amount'   => $tax_amount,
                    'tax_id'       => $appointment_data['tax_id']
                ]
            ]
        ];

        $invoice_id = $this->createInvoice($invoice_payload);
        if (!$invoice_id) return false;

        $records = [
            'invoice_id' => $invoice_id
        ];
        $this->db->update('appointment', $records, 'appointment_id = ?', [$appointment_id]);

        return $invoice_id;
    }

    /**
     * Cancels an invoice
     * @param int $invoice_id
     * @return bool
     */
    public function cancelInvoice($invoice_id)
    {
        if (empty($invoice_id) || !is_numeric($invoice_id))
        {
            return false;
        }

        $update_data = [
            'canceled' => 1
        ];

        $result = $this->db->update('invoice', $update_data, 'invoice_id = ?', [$invoice_id]);

        return (bool)$result;
    }

    /**
     * Retrieves all data associated with a specific invoice.
     * The returned array is structured to be directly compatible with the InvoicePDF class.
     *
     * @param int $invoice_id The ID of the invoice to retrieve.
     * @return array|bool The structured invoice data, or false if not found.
     */
    public function getInvoiceData($invoice_id)
    {
        if (empty($invoice_id) || !is_numeric($invoice_id))
        {
            return false;
        }

        // Main invoice data, client and dietitian basic info
        $sql = "SELECT 
                    i.*,
                    c.first_name AS client_fname, c.last_name AS client_lname,
                    d.first_name AS dietitian_fname, d.last_name AS dietitian_lname
                FROM invoice i
                LEFT JOIN client c ON i.client_id = c.client_id
                LEFT JOIN dietitian d ON i.dietitian_id = d.dietitian_id
                WHERE i.invoice_id = :invoice_id LIMIT 1";

        $invoice_row = $this->db->query($sql, [':invoice_id' => $invoice_id])->fetch();

        if (!$invoice_row)
        {
            return false;
        }

        // Fetch logo
        $sql_settings = "SELECT logo_path FROM invoice_settings WHERE invoice_settings_id = 1 LIMIT 1";
        $settings_row = $this->db->query($sql_settings, [])->fetch();
        $logo_path = $settings_row ? $settings_row['logo_path'] : '';

        // Fetch charges
        $sql_charges = "SELECT 
                            ic.description, ic.clean_amount, ic.tax_amount, ic.tax_id,
                            t.factor
                        FROM invoice_charge ic
                        LEFT JOIN tax t ON ic.tax_id = t.tax_id
                        WHERE ic.invoice_id = :invoice_id";

        $charges_rows = $this->db->query($sql_charges, [':invoice_id' => $invoice_id])->fetchAll();

        // Format charges for the PDF
        $formatted_charges = [];
        $totals = ['clean' => 0, 'taxes' => [], 'grand' => 0];

        foreach ($charges_rows as $charge)
        {
            $tax_rate_percentage = (floatval($charge['factor']) * 100);
            $clean = (float)$charge['clean_amount'];
            $tax_v = (float)$charge['tax_amount'];
            $total = $clean + $tax_v;

            $formatted_charges[] = [
                'description'  => $charge['description'],
                'clean_amount' => $clean,
                'tax_rate'     => (int)$tax_rate_percentage,
                'tax_amount'   => $tax_v,
                'total_amount' => $total,
                'tax_id'       => $charge['tax_id']
            ];

            // Update totals
            $totals['clean'] += $clean;
            $totals['grand'] += $total;
            $rate_key = (int)$tax_rate_percentage . '%';
            $totals['taxes'][$rate_key] = ($totals['taxes'][$rate_key] ?? 0) + $tax_v;
        }

        // Match InvoicePDF requirements
        $data = [
            'issuer' => [
                'company_title'  => $invoice_row['issuer_title'],
                'company_name'   => $invoice_row['issuer_name'],
                'vat_number'     => $invoice_row['issuer_vat'],
                'tax_office'     => $invoice_row['issuer_tax_office'],
                'address_street' => $invoice_row['issuer_address_street'],
                'address_number' => $invoice_row['issuer_address_number'],
                'city'           => $invoice_row['issuer_city'],
                'postal_code'    => $invoice_row['issuer_postal_code'],
                'phone'          => $invoice_row['issuer_phone'],
                'email'          => $invoice_row['issuer_email'],
                'logo'           => $logo_path
            ],
            'invoice' => [
                'title'         => TEXT_INVOICE_PDF_TITLE,
                'is_canceled'   => (bool)$invoice_row['canceled'],
                'serial_number' => $invoice_row['serial_number'],
                'issue_date'    => date('d/m/Y', strtotime($invoice_row['issue_date']))
            ],
            'client' => [
                'name' => trim($invoice_row['client_fname'] . ' ' . $invoice_row['client_lname'])
            ],
            'dietitian' => [
                'name' => trim($invoice_row['dietitian_fname'] . ' ' . $invoice_row['dietitian_lname'])
            ],
            'charges' => $formatted_charges,
            'totals'  => $totals
        ];

        return $data;
    }
}
