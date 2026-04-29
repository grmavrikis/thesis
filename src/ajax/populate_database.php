<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../includes/init.php';

$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Execution order to satisfy Foreign Key constraints
// Execution order to satisfy Foreign Key constraints
$order = [
    'language',
    'user',
    'tax',
    'appointment_status',
    'appointment_status_description',
    'questionnaire_type',
    'questionnaire_type_description',
    'invoice_settings',
    'service',
    'service_description',
    'dietitian',
    'client',
    'client_relationships',
    'invoice',
    'invoice_charge',
    'appointment',
    'nutrition_plan',
    'questionnaire'
];

$id_registry = [];
$inserted_stats = [];

try
{
    foreach ($order as $table)
    {
        if (!isset($data[$table])) continue;

        $inserted_stats[$table] = 0;

        foreach ($data[$table] as $index => $record)
        {
            // Resolve Dependencies: Replace {{table:index}} or {{table}} with real ID
            foreach ($record as $key => $value)
            {
                if (is_string($value) && preg_match('/^{{([^:]+)(?::(\d+))?}}$/', $value, $matches))
                {
                    $ref_table = $matches[1];
                    $ref_index = $matches[2] ?? null;

                    if ($ref_index !== null)
                    {
                        // Specific index provided: {{user:0}}
                        if (isset($id_registry[$ref_table][$ref_index]))
                        {
                            $record[$key] = $id_registry[$ref_table][$ref_index];
                        }
                        else
                        {
                            throw new Exception("Missing dependency index: $value for table $table");
                        }
                    }
                    else
                    {
                        // No index provided: {{user}}. Use the last inserted ID for this table.
                        if (!empty($id_registry[$ref_table]))
                        {
                            $record[$key] = end($id_registry[$ref_table]);
                        }
                        else
                        {
                            throw new Exception("No previous records found for table $ref_table to resolve $value");
                        }
                    }
                }
            }

            // Insert and register the new ID
            $inserted_ids = $db->insert($table, [$record]);
            if (!$inserted_ids)
            {
                throw new Exception("Database insert failed for table: $table");
            }

            $id_registry[$table][$index] = $inserted_ids[0];
            $inserted_stats[$table]++;
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Database populated successfully',
        'stats' => $inserted_stats
    ]);
}
catch (Exception $e)
{
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
exit;
