<?php
include './convert_currency.php';
header('Content-Type: application/json');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
$host = "localhost";
$dbname = "Nessun_DormaDB";
$user = "root";
$password = "";
$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Error de conexión: ' . $conn->connect_error]);
    exit();
}


function fetchBalanceHistory($defaultCurrency, $walletId) {
    global $conn;

    // Fetch all entries for the wallet, ordered by date
    $stmt = $conn->prepare("SELECT currency, date, amount FROM activos_hist WHERE walletId = ? ORDER BY date ASC");
    $stmt->bind_param("i", $walletId);
    $stmt->execute();
    $result = $stmt->get_result();
    $entries = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Fetch historical currency values (e.g., BTC/USD, EUR/USD, etc.)
    $table = strtolower($defaultCurrency) . "_values_hist";
    $column = strtoupper($defaultCurrency) . "XVAL";

    $stmt = $conn->prepare("SELECT currency, date_reg, $column FROM $table ORDER BY date_reg ASC");
    $stmt->execute();
    $result = $stmt->get_result();
    $currencyValues = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Organize currency values by date and currency
    $valueHistory = [];
    foreach ($currencyValues as $value) {
        $currency = $value['currency'];
        $date = $value['date_reg'];
        $rate = (double)$value[$column];

        if (!isset($valueHistory[$currency])) {
            $valueHistory[$currency] = [];
        }
        $valueHistory[$currency][$date] = $rate;
    }

    // Initialize variables
    $balanceHistory = [];
    $runningTotals = []; // Keeps track of cumulative totals per currency

    // Process each entry
    foreach ($entries as $entry) {
        $currency = $entry['currency'];
        $date = $entry['date'];
        $amount = (double)$entry['amount'];

        // Update the running total for this currency
        if (!isset($runningTotals[$currency])) {
            $runningTotals[$currency] = 0;
        }
        $runningTotals[$currency] += $amount;

        // Ensure the balance history for this date includes all currencies
        if (!isset($balanceHistory[$date])) {
            $balanceHistory[$date] = [];
        }
        $balanceHistory[$date] = array_merge($balanceHistory[$date], $runningTotals);
    }

    // Fill in missing dates with the previous day's totals for all currencies
    if (count($balanceHistory) <= 0) {
        echo json_encode(['success' => true, 'error' => 'No historic values']);
        exit();
    }
    $allDates = array_keys($balanceHistory);
    $startDate = new DateTime($allDates[0]);
    $endDate = new DateTime(); // Today
    $interval = new DateInterval('P1D');
    $dateRange = new DatePeriod($startDate, $interval, $endDate);

    $previousDayTotals = [];
    foreach ($dateRange as $date) {
        $formattedDate = $date->format('Y-m-d');
        if (isset($balanceHistory[$formattedDate])) {
            // Update the previous day's totals with the current day's balances
            foreach ($balanceHistory[$formattedDate] as $currency => $amount) {
                $previousDayTotals[$currency] = $amount;
            }
        } else {
            // Carry forward the previous day's totals
            $balanceHistory[$formattedDate] = $previousDayTotals;
        }
    }

    // Calculate the total value for each currency on each day
    $finalBalanceHistory = [];
    foreach ($balanceHistory as $date => $balances) {
        foreach ($balances as $currency => $amount) {
            // Find the most recent value for the currency on or before this date
            $rate = 1; // Default to 1 if no conversion is needed
            if (isset($valueHistory[$currency])) {
                foreach ($valueHistory[$currency] as $rateDate => $rateValue) {
                    if ($rateDate <= $date) {
                        $rate = $rateValue;
                    } else {
                        break;
                    }
                }
            }

            // Calculate the value for the currency on this date
            if (!isset($finalBalanceHistory[$date])) {
                $finalBalanceHistory[$date] = [];
            }
            $finalBalanceHistory[$date][$currency] = $amount * $rate;
        }
    }

    // Sort the balance history by date
    ksort($finalBalanceHistory);

    // Return the final balance history as JSON
    echo json_encode(['success' => true, 'balanceHistory' => $finalBalanceHistory]);
    exit();
}

// Example usage
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['defaultCurrency']) && isset($_GET['walletId'])) {
    $defaultCurrency = $_GET['defaultCurrency'];
    $walletId = $_GET['walletId'];
    fetchBalanceHistory($defaultCurrency, $walletId);
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit();
}
?>