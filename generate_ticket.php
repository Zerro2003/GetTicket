<?php
require_once('config.php');
require_once('libs/TCPDF/tcpdf.php');
require_once('libs/phpqrcode/qrlib.php');

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$ticket_code = $_GET['code'] ?? null;

if (!$ticket_code) {
    die("No ticket code provided.");
}

// Verify the ticket belongs to the user or the user is an admin/organizer
$user_id = $_SESSION['id'] ?? null;
$user_role = $_SESSION['role'] ?? null;

try {
    // Fetch ticket and event details
    $sql = "SELECT 
                t.id, t.status, t.ticket_code,
                e.name AS event_name, e.date AS event_date, e.location AS event_location,
                c.name AS category_name,
                u.id AS user_id
            FROM tickets t
            JOIN events e ON t.event_id = e.id
            LEFT JOIN event_ticket_categories c ON t.category_id = c.id
            LEFT JOIN users u ON t.user_id = u.id
            WHERE t.ticket_code = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$ticket_code]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ticket) {
        die("Invalid ticket code.");
    }

    // Security check: Ensure the user has the right to view this ticket
    if ($user_role !== 'organizer' && $user_role !== 'admin' && $ticket['user_id'] != $user_id) {
        die("Access denied. You do not have permission to view this ticket.");
    }

    // If the ticket was just reserved, finalize it.
    if ($ticket['status'] === 'reserved') {
        // In a real-world scenario, this is where payment processing would happen.
        // For this project, we'll just confirm the booking.
        $new_status = 'confirmed';
        $updateStmt = $pdo->prepare("UPDATE tickets SET status = ? WHERE id = ?");
        $updateStmt->execute([$new_status, $ticket['id']]);
        $ticket['status'] = $new_status;
    }

    // --- PDF and QR Code Generation ---

    // Create a QR code using phpqrcode
    ob_start();
    QRcode::png($ticket['ticket_code'], null, QR_ECLEVEL_L, 4, 2);
    $qrCodeImage = ob_get_clean();

    // Create a new PDF document
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, 'A5', true, 'UTF-8', false);

    // Set document information
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Event Ticket Generator');
    $pdf->SetTitle('Event Ticket - ' . $ticket['event_name']);
    $pdf->SetSubject('Your Ticket');
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);

    // Add a page
    $pdf->AddPage();

    // Set font
    $pdf->SetFont('helvetica', '', 12);

    // --- Ticket Content ---
    $categoryLabel = $ticket['category_name'] ?? 'General Admission';
    $eventDate = date('F j, Y, g:i a', strtotime($ticket['event_date']));

    $html = '
    <style>
        .ticket-container {
            border: 2px dashed #ccc;
            padding: 15px;
            font-family: helvetica, sans-serif;
        }
        h1 {
            font-size: 20px;
            color: #333;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        p {
            font-size: 12px;
            line-height: 1.6;
        }
        .ticket-code {
            font-weight: bold;
            font-size: 14px;
            color: #000;
        }
    </style>
    <div class="ticket-container">
        <h1>'.htmlspecialchars($ticket['event_name']).'</h1>
        <p><strong>Date:</strong> '.htmlspecialchars($eventDate).'</p>
        <p><strong>Location:</strong> '.htmlspecialchars($ticket['event_location']).'</p>
        <p><strong>Category:</strong> '.htmlspecialchars($categoryLabel).'</p>
        <hr>
        <p>Present this ticket at the entrance. Your unique ticket code is:</p>
        <p class="ticket-code">'.htmlspecialchars($ticket['ticket_code']).'</p>
    </div>';

    $pdf->writeHTML($html, true, false, true, false, '');

    // Add the QR code image to the PDF
    // The '@' symbol tells TCPDF that the data is an image stream
    $pdf->Image('@' . $qrCodeImage, 95, 40, 40, 40, 'PNG');

    // Close and output PDF document
    // 'I' means inline display, 'D' means force download
    $pdf->Output('event_ticket.pdf', 'I');

} catch (Exception $e) {
    error_log($e->getMessage());
    die("An error occurred while generating your ticket. Please try again later.");
}
?>
