<?php
if (isset($_POST['export'])) {
    // Database connection details
    require_once 'database.php';

    // Get list of all tables
    $tables = [];
    $result = $conn->query("SHOW TABLES");
    while ($row = $result->fetch_array()) {
        $tables[] = $row[0];
    }
    $date=date("d/m/Y");
    // Set header for CSV file download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename=database_export_'.$date.'.csv');

    // Open file in write mode
    $output = fopen('php://output', 'w');

    foreach ($tables as $table) {
        // Write table name as section header in CSV
        fputcsv($output, [$table]);

        // Fetch table data
        $query = "SELECT * FROM $table";
        $result = $conn->query($query);

        if ($result->num_rows > 0) {
            // Get column headers
            $row = $result->fetch_assoc();
            fputcsv($output, array_keys($row));

            // Write rows to the CSV file
            do {
                fputcsv($output, $row);
            } while ($row = $result->fetch_assoc());
        }

        // Add empty line for separation between tables
        fputcsv($output, []);
    }

    fclose($output);
    $conn->close();
    exit;
}
?>
