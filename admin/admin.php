<?php
// --- CONFIGURATION ---
$ADMIN_PIN = "aditya"; 
$host = "localhost";
$username = 'techfest_user';
$password = 'StrongPassword123';
$dbname = "techfest_db";

session_start();

// Database Connection
$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) { 
    die("<div style='color:red; text-align:center; padding:20px; font-family:Orbitron;'>TERMINAL ERROR: Database Connection Failed. Check XAMPP/MySQL.</div>"); 
}

// Handle Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: admin.php");
    exit();
}

// Handle Login
$error_msg = "";
if (isset($_POST['pin_input'])) {
    if ($_POST['pin_input'] === $ADMIN_PIN) {
        $_SESSION['authenticated_owner'] = true;
    } else {
        $error_msg = "ACCESS DENIED: INVALID SECURITY PIN.";
    }
}

// Determine Current View (registrations or queries)
$view = (isset($_GET['view']) && $_GET['view'] === 'queries') ? 'queries' : 'registrations';

// --- ACTION LOGIC (Only if authenticated) ---
if (isset($_SESSION['authenticated_owner'])) {
    // 1. Delete Logic
    if (isset($_GET['delete_reg_id'])) {
        $id = intval($_GET['delete_reg_id']);
        $conn->query("DELETE FROM registrations WHERE id = $id");
        $redir = "admin.php?view=registrations";
        if (isset($_GET['event_filter'])) $redir .= "&event_filter=" . urlencode($_GET['event_filter']);
        header("Location: $redir");
        exit();
    }
    if (isset($_GET['delete_query_id'])) {
        $id = intval($_GET['delete_query_id']);
        $conn->query("DELETE FROM queries WHERE id = $id");
        header("Location: admin.php?view=queries");
        exit();
    }

    // 2. Update Payment Status Logic + Confirmation Trigger
    if (isset($_GET['toggle_payment']) && isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $new_status = ($_GET['toggle_payment'] === 'Verified') ? 'Verified' : 'Pending';
        
        // Update database
        $stmt = $conn->prepare("UPDATE registrations SET payment_status = ? WHERE id = ?");
        $stmt->bind_param("si", $new_status, $id);
        $stmt->execute();

        // --- AUTOMATED CONFIRMATION LOGIC ---
        if ($new_status === 'Verified') {
            // Fetch participant details for the notification
            $details = $conn->query("SELECT lead_name, lead_email, event_name FROM registrations WHERE id = $id")->fetch_assoc();
            
            if ($details) {
                $to = $details['lead_email'];
                $subject = "Registration Confirmed: " . $details['event_name'] . " - Techfest 2.0";
                $message = "Hello " . $details['lead_name'] . ",\n\n" .
                           "Great news! Your payment has been verified. Your registration for " . $details['event_name'] . " is now CONFIRMED.\n\n" .
                           "Show this email at the registration desk on the day of the event.\n\n" .
                           "Regards,\nTechfest Team";
                $headers = "From: noreply@techfest.com";

                // NOTE: The mail() function requires a configured SMTP server (like SendGrid or local MailHog)
                // @mail($to, $subject, $message, $headers); 
            }
        }

        $redir = "admin.php?view=registrations";
        if (isset($_GET['event_filter'])) $redir .= "&event_filter=" . urlencode($_GET['event_filter']);
        header("Location: $redir");
        exit();
    }
}

// --- CSV EXPORT LOGIC ---
if (isset($_SESSION['authenticated_owner']) && isset($_GET['export'])) {
    header('Content-Type: text/csv; charset=utf-8');
    if ($view == 'registrations') {
        $filter = isset($_GET['event_filter']) ? $conn->real_escape_string($_GET['event_filter']) : '';
        $filename = ($filter == '') ? "Total_Registrations_" : "Event_" . str_replace(' ', '_', $filter) . "_";
        header('Content-Disposition: attachment; filename=' . $filename . date('Y-m-d') . ".csv");
        $output = fopen('php://output', 'w');
        fputcsv($output, array('ID', 'Lead Name', 'Email', 'Event', 'Team Name', 'Members', 'College', 'Phone', 'Fee', 'Status', 'Date'));
        $query = "SELECT * FROM registrations " . ($filter != '' ? "WHERE event_name = '$filter' " : "") . "ORDER BY registration_date DESC";
        $export_result = $conn->query($query);
        while ($row = $export_result->fetch_assoc()) {
            fputcsv($output, array($row['id'], $row['lead_name'], $row['lead_email'], $row['event_name'], $row['team_name'], $row['additional_members'], $row['college'], $row['lead_phone'], $row['total_fee'], $row['payment_status'], $row['registration_date']));
        }
    } else {
        header('Content-Disposition: attachment; filename=User_Queries_' . date('Y-m-d') . '.csv');
        $output = fopen('php://output', 'w');
        fputcsv($output, array('ID', 'Name', 'Email', 'Subject', 'Message', 'Date'));
        $export_result = $conn->query("SELECT * FROM queries ORDER BY created_at DESC");
        while ($row = $export_result->fetch_assoc()) {
            fputcsv($output, array($row['id'], $row['name'], $row['email'], $row['subject'], $row['message'], $row['created_at']));
        }
    }
    fclose($output);
    exit();
}

// Global Metrics Calculation
$total_participants_query = $conn->query("SELECT count(*) as count FROM registrations");
$total_participants = $total_participants_query ? $total_participants_query->fetch_assoc()['count'] : 0;

$total_queries_query = $conn->query("SELECT count(*) as count FROM queries");
$total_queries = $total_queries_query ? $total_queries_query->fetch_assoc()['count'] : 0;

// Dynamic Revenue Calculation (Verified Only)
$revenue_query = $conn->query("SELECT SUM(total_fee) as total FROM registrations WHERE payment_status = 'Verified'");
$total_revenue = ($revenue_query && $row = $revenue_query->fetch_assoc()) ? $row['total'] : 0;

// Event Breakdown for Sidebar
$event_breakdown = [];
$breakdown_res = $conn->query("SELECT event_name, COUNT(*) as count FROM registrations GROUP BY event_name ORDER BY count DESC");
if ($breakdown_res) {
    while($r = $breakdown_res->fetch_assoc()){
        $event_breakdown[$r['event_name']] = $r['count'];
    }
}

// Fetch Data for Display
if ($view == 'registrations') {
    $event_filter = isset($_GET['event_filter']) ? $conn->real_escape_string($_GET['event_filter']) : '';
    $sql = "SELECT * FROM registrations " . ($event_filter != '' ? "WHERE event_name = '$event_filter' " : "") . "ORDER BY registration_date DESC";
    $result = $conn->query($sql);
    $event_list_result = $conn->query("SELECT DISTINCT event_name FROM registrations");
} else {
    $result = $conn->query("SELECT * FROM queries ORDER BY created_at DESC");
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Owner Dashboard Ultra - Techfest 2.0</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #00f2ff;
            --bg-dark: #030305;
            --bg-card: #0a0a12;
            --border: rgba(255, 255, 255, 0.1);
            --text-dim: #94a3b8;
            --accent: #7000ff;
            --success: #00ff88;
            --warning: #ffaa00;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--bg-dark);
            color: white;
            padding: 40px 20px;
            margin: 0;
            line-height: 1.6;
        }

        .container {
            max-width: 1700px;
            margin: 0 auto;
            position: relative;
        }

        h1 {
            font-family: 'Orbitron';
            color: var(--primary);
            margin-bottom: 50px;
            text-align: center;
            letter-spacing: 4px;
            font-size: 2.5rem;
            text-shadow: 0 0 20px rgba(0, 242, 255, 0.3);
        }

        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 50px;
        }

        .metric-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            padding: 35px 25px;
            border-radius: 24px;
            text-align: center;
            position: relative;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }

        .metric-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--primary);
            opacity: 0.6;
            border-radius: 24px 24px 0 0;
        }

        .metric-label {
            font-family: 'Orbitron';
            font-size: 0.75rem;
            color: var(--text-dim);
            text-transform: uppercase;
            letter-spacing: 3px;
            margin-bottom: 12px;
            display: block;
        }

        .metric-value {
            font-size: 3rem;
            font-weight: 900;
            font-family: 'Orbitron';
            color: #fff;
            line-height: 1;
        }

        .rev-value {
            color: var(--success);
        }

        .login-overlay {
            position: fixed;
            inset: 0;
            background: var(--bg-dark);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }

        .login-card {
            background: var(--bg-card);
            padding: 60px;
            border-radius: 32px;
            border: 1px solid var(--primary);
            text-align: center;
            width: 420px;
            box-shadow: 0 0 100px rgba(0, 242, 255, 0.15);
        }

        .pin-input {
            width: 100%;
            padding: 20px;
            background: #000;
            border: 1px solid #333;
            color: var(--primary);
            border-radius: 16px;
            margin-bottom: 30px;
            text-align: center;
            font-size: 2rem;
            font-family: 'Orbitron';
            letter-spacing: 12px;
            outline: none;
            border: 1px solid var(--primary);
        }

        .btn-unlock {
            background: var(--primary);
            color: #000;
            border: none;
            padding: 20px;
            width: 100%;
            border-radius: 16px;
            font-family: 'Orbitron';
            font-weight: 900;
            cursor: pointer;
            font-size: 1rem;
            letter-spacing: 2px;
        }

        .view-tabs {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-bottom: 40px;
        }

        .tab-link {
            text-decoration: none;
            padding: 14px 40px;
            border-radius: 50px;
            font-family: 'Orbitron';
            font-size: 0.9rem;
            font-weight: 700;
            color: var(--text-dim);
            border: 1px solid var(--border);
            transition: 0.3s;
            letter-spacing: 1px;
        }

        .tab-link.active {
            background: var(--primary);
            color: #000;
            border-color: var(--primary);
            box-shadow: 0 0 20px rgba(0, 242, 255, 0.4);
        }

        .dashboard-layout {
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 40px;
        }

        @media (max-width: 1200px) {
            .dashboard-layout {
                grid-template-columns: 1fr;
            }
        }

        .sidebar-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 30px;
            height: fit-content;
        }

        .sidebar-card h3 {
            font-family: 'Orbitron';
            font-size: 0.9rem;
            color: var(--primary);
            margin-bottom: 25px;
            text-transform: uppercase;
            letter-spacing: 2px;
            border-bottom: 1px solid var(--border);
            padding-bottom: 15px;
        }

        .event-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .event-name {
            color: var(--text-dim);
            font-size: 0.95rem;
        }

        .event-count {
            font-family: 'Orbitron';
            font-weight: 900;
            color: var(--primary);
            font-size: 1rem;
        }

        .table-wrapper {
            background: var(--bg-card);
            border-radius: 24px;
            overflow: hidden;
            border: 1px solid var(--border);
            box-shadow: 0 10px 40px rgba(0,0,0,0.4);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: rgba(255, 255, 255, 0.04);
            text-align: left;
            padding: 22px 25px;
            font-family: 'Orbitron';
            font-size: 0.75rem;
            color: var(--primary);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        td {
            padding: 25px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.03);
            font-size: 1rem;
            vertical-align: top;
        }

        .btn-delete {
            color: #ff4444;
            text-decoration: none;
            border: 1px solid rgba(255, 68, 68, 0.3);
            padding: 8px 16px;
            border-radius: 10px;
            font-size: 0.75rem;
            font-weight: bold;
            font-family: 'Orbitron';
            transition: 0.3s;
        }

        .btn-delete:hover { background: rgba(255, 68, 68, 0.1); }

        .badge {
            background: rgba(0, 242, 255, 0.08);
            color: var(--primary);
            padding: 8px 15px;
            border-radius: 10px;
            font-size: 0.75rem;
            font-weight: 800;
            border: 1px solid rgba(0, 242, 255, 0.1);
            display: inline-block;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .status-badge {
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 0.7rem;
            font-weight: 800;
            font-family: 'Orbitron';
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .status-verified { background: rgba(0, 255, 136, 0.15); color: var(--success); border: 1px solid var(--success); }
        .status-pending { background: rgba(255, 170, 0, 0.15); color: var(--warning); border: 1px solid var(--warning); }

        .member-details {
            margin-top: 15px;
            padding: 18px;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 16px;
            border-left: 4px solid var(--primary);
            font-size: 0.85rem;
            color: var(--text-dim);
        }

        .team-label {
            font-family: 'Orbitron';
            font-size: 0.7rem;
            color: var(--primary);
            margin-bottom: 6px;
            display: block;
            letter-spacing: 1px;
        }

        .dashboard-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            gap: 15px;
        }

        .select-filter {
            background: #000;
            border: 1px solid var(--border);
            color: white;
            padding: 12px 20px;
            border-radius: 12px;
            font-family: 'Orbitron';
            font-size: 0.8rem;
            outline: none;
            cursor: pointer;
        }

        .action-btns {
            display: flex;
            gap: 10px;
        }

        .btn-action {
            padding: 12px 25px;
            border-radius: 12px;
            text-decoration: none;
            font-family: 'Orbitron';
            font-size: 0.8rem;
            font-weight: bold;
            transition: 0.3s;
            letter-spacing: 1px;
        }

        .btn-csv { background: #fff; color: #000; }
        .btn-pdf { background: var(--bg-card); color: #fff; border: 1px solid var(--border); cursor: pointer; }

        .btn-util { padding: 10px 18px; border-radius: 10px; text-decoration: none; font-size: 0.75rem; font-weight: bold; border: 1px solid transparent; transition: 0.2s; cursor: pointer; display: inline-block; font-family: 'Orbitron'; letter-spacing: 1px; }
        .btn-view-proof { background: rgba(255,255,255,0.07); color: #fff; border-color: var(--border); margin-right: 5px; }
        .btn-verify { background: var(--success); color: #000; }
        .btn-unverify { background: transparent; color: var(--warning); border-color: var(--warning); }
        .btn-whatsapp { background: #25D366; color: #fff; border-radius: 50%; width: 35px; height: 35px; display: flex; align-items: center; justify-content: center; text-decoration: none; }

        .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.95); z-index: 5000; display: none; align-items: center; justify-content: center; padding: 30px; }
        .modal-content { background: var(--bg-card); border: 1px solid var(--primary); width: 100%; max-width: 1200px; height: 90vh; border-radius: 30px; position: relative; overflow: hidden; display: flex; flex-direction: column; }
        .modal-header { padding: 20px 30px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; }
        .modal-body { flex: 1; padding: 0; background: #000; display: flex; align-items: center; justify-content: center; overflow: auto; }
        .modal-body iframe, .modal-body img { width: 100%; height: 100%; border: none; display: block; object-fit: contain; }
        .btn-close { background: none; border: none; color: white; font-size: 1.5rem; cursor: pointer; font-family: 'Orbitron'; }

        .query-message {
            color: var(--text-dim);
            font-size: 1rem;
            line-height: 1.8;
            padding: 25px;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 20px;
            margin-top: 12px;
            border: 1px solid rgba(255, 255, 255, 0.06);
        }
    </style>
</head>

<body>

    <?php if (!isset($_SESSION['authenticated_owner'])): ?>
    <div class="login-overlay">
        <div class="login-card">
            <h2 style="font-family: Orbitron; color: var(--primary); font-size: 1.8rem; margin-bottom: 10px;">ROOT ACCESS</h2>
            <p style="font-size: 0.7rem; color: var(--text-dim); text-transform: uppercase; letter-spacing: 4px; margin-bottom: 40px;">SECURE OWNER ANALYTICS PORTAL</p>
            <form method="POST">
                <input type="password" name="pin_input" class="pin-input" placeholder="••••" required autofocus>
                <button type="submit" class="btn-unlock">INITIALIZE UPLINK</button>
            </form>
            <?php if ($error_msg): ?>
            <p style="color: var(--danger); margin-top: 25px; font-size: 0.9rem; font-weight: bold;"><?php echo $error_msg; ?></p>
            <?php endif; ?>
        </div>
    </div>
    <?php else: ?>
    <div class="container">
        <a href="?logout=1" style="position: absolute; top: -10px; right: 0; color: var(--text-dim); text-decoration: none; font-size: 0.75rem; font-family: Orbitron; opacity: 0.6; padding: 10px;">[ TERMINATE SESSION ]</a>
        <h1>TECHFEST 2.0 ANALYTICS</h1>

        <div class="metrics-grid">
            <div class="metric-card">
                <span class="metric-label">Total Participants</span>
                <div class="metric-value"><?php echo $total_participants; ?></div>
            </div>
            <div class="metric-card">
                <span class="metric-label">Verified Revenue</span>
                <div class="metric-value rev-value">₹<?php echo number_format($total_revenue); ?></div>
            </div>
            <div class="metric-card">
                <span class="metric-label">Support Inbox</span>
                <div class="metric-value" style="color: var(--accent);"><?php echo $total_queries; ?></div>
            </div>
        </div>

        <div class="dashboard-layout">
            <div class="sidebar-card">
                <h3>Event Distribution</h3>
                <?php if(empty($event_breakdown)): ?>
                <p style="color: #444; font-size: 0.8rem;">No telemetry data available.</p>
                <?php else: ?>
                <?php foreach($event_breakdown as $name => $count): ?>
                <div class="event-row">
                    <span class="event-name"><?php echo htmlspecialchars($name); ?></span>
                    <span class="event-count"><?php echo $count; ?></span>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div>
                <div class="view-tabs">
                    <a href="?view=registrations" class="tab-link <?php echo ($view == 'registrations') ? 'active' : ''; ?>">REGISTRATIONS</a>
                    <a href="?view=queries" class="tab-link <?php echo ($view == 'queries') ? 'active' : ''; ?>">SUPPORT QUERIES</a>
                </div>

                <div class="dashboard-controls">
                    <?php if ($view == 'registrations'): ?>
                    <form method="GET">
                        <input type="hidden" name="view" value="registrations">
                        <select name="event_filter" class="select-filter" onchange="this.form.submit()">
                            <option value="">All Categories</option>
                            <?php if($event_list_result) while($ev = $event_list_result->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($ev['event_name']); ?>" <?php echo ($event_filter==$ev['event_name']) ? 'selected' : '' ; ?>>
                                <?php echo htmlspecialchars($ev['event_name']); ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </form>
                    <?php endif; ?>
                    <div class="action-btns">
                        <a href="?export=csv&view=<?php echo $view; ?><?php echo ($event_filter != '') ? '&event_filter='.urlencode($event_filter) : ''; ?>" class="btn-action btn-csv">📊 DOWNLOAD CSV</a>
                        <button onclick="window.print()" class="btn-action btn-pdf">📄 PRINT / PDF</button>
                    </div>
                </div>

                <div class="table-wrapper">
                    <?php if ($view == 'registrations'): ?>
                    <table>
                        <thead>
                            <tr>
                                <th width="35%">Participant Info</th>
                                <th width="23%">Event & Billing</th>
                                <th width="15%">Payment</th>
                                <th width="27%">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($result && $result->num_rows > 0): while($row = $result->fetch_assoc()): 
                                $status_class = ($row['payment_status'] == 'Verified') ? 'status-verified' : 'status-pending';
                                
                                // Robust proof path handling
                                $db_proof = $row['transaction_proof'];
                                if (empty($db_proof)) {
                                    $proof_file = 'uploads/default_proof.png';
                                } else {
                                    // Check if path already contains 'uploads/' prefix
                                    $proof_file = (strpos($db_proof, 'uploads/') === 0) ? $db_proof : 'uploads/' . $db_proof;
                                }
                                
                                // Generate WhatsApp Link
                                $wa_message = urlencode("Hello " . $row['lead_name'] . ", your registration for " . $row['event_name'] . " at Techfest 2.0 has been VERIFIED. We look forward to seeing you!Stay updated with the latest announcements, schedules, and registration links.
Join our official Techfest WhatsApp Group now!/ Open this link to join my WhatsApp Group: https://chat.whatsapp.com/CDIWyEyGRFsHJzLhvOHYhO?mode=gi_t");
                                $wa_link = "https://wa.me/" . preg_replace('/[^0-9]/', '', $row['lead_phone']) . "?text=" . $wa_message;
                            ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 800; font-size: 1.2rem; color: #fff; margin-bottom: 4px;">
                                        <?php echo htmlspecialchars($row["lead_name"]); ?>
                                    </div>
                                    <div style="color: var(--primary); font-size: 0.9rem; font-family: monospace; margin-bottom: 8px;">
                                        <?php echo htmlspecialchars($row["lead_phone"]); ?>
                                    </div>
                                    
                                    <?php if(!empty($row['team_name']) && $row['team_name'] !== 'Solo' && $row['team_name'] !== 'N/A'): ?>
                                    <div class="member-details">
                                        <span class="team-label">UNIT ID: <?php echo htmlspecialchars($row['team_name']); ?></span>
                                        <?php if(!empty($row['additional_members'])): ?>
                                            <div style="opacity: 0.9; line-height: 1.5;">
                                                <strong style="color: #fff;">SQUAD:</strong> <?php echo htmlspecialchars($row['additional_members']); ?>
                                            </div>
                                        <?php else: ?>
                                            <div style="opacity: 0.4; font-style: italic;">Stand-alone Application</div>
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="background: rgba(0, 242, 255, 0.05); padding: 15px; border-radius: 16px; border: 1px solid rgba(0, 242, 255, 0.1);">
                                        <div style="font-size: 0.9rem; font-weight: 900; color: var(--primary); text-transform: uppercase;"><?php echo htmlspecialchars($row["event_name"]); ?></div>
                                        <div style="color: var(--success); font-family: Orbitron; font-size: 1.1rem; margin-top: 8px; font-weight: 700;">₹<?php echo number_format($row['total_fee']); ?></div>
                                    </div>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $status_class; ?>"><?php echo $row['payment_status']; ?></span>
                                </td>
                                <td>
                                    <div style="display: flex; flex-direction: column; gap: 10px;">
                                        <div style="display: flex; gap: 8px; align-items: center;">
                                            <button onclick="openProof('<?php echo $proof_file; ?>', '<?php echo addslashes($row['lead_name']); ?>')" class="btn-util btn-view-proof" style="flex: 1;">VIEW PROOF</button>
                                            
                                            <?php if($row['payment_status'] == 'Pending'): ?>
                                                <a href="?toggle_payment=Verified&id=<?php echo $row['id']; ?>&view=registrations<?php echo isset($event_filter) ? '&event_filter='.$event_filter : ''; ?>" class="btn-util btn-verify">VERIFY</a>
                                            <?php else: ?>
                                                <a href="<?php echo $wa_link; ?>" target="_blank" class="btn-whatsapp" title="Send WhatsApp Confirmation">
                                                    <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766 0-3.18-2.587-5.771-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.512-2.96-2.626-.087-.114-.694-.922-.694-1.758 0-.837.434-1.246.587-1.412.144-.17.315-.21.424-.21.109 0 .21.002.302.007.098.005.23-.037.35.25.132.317.456 1.112.496 1.192.04.08.066.17.013.272-.053.102-.08.175-.162.27-.08.093-.173.205-.246.27-.087.08-.178.166-.076.337.103.17.458.753.985 1.22.68.606 1.254.796 1.431.884.178.088.283.074.388-.047.106-.123.456-.532.578-.714.122-.182.245-.153.413-.091.166.062 1.06.502 1.242.593.182.091.303.136.348.215.045.079.045.457-.1.862z"/></svg>
                                                </a>
                                                <a href="?toggle_payment=Pending&id=<?php echo $row['id']; ?>&view=registrations<?php echo isset($event_filter) ? '&event_filter='.$event_filter : ''; ?>" class="btn-util btn-unverify">REVOKE</a>
                                            <?php endif; ?>
                                        </div>
                                        <a href="?delete_reg_id=<?php echo $row["id"]; ?>&view=registrations" class="btn-delete" style="text-align: center;" onclick="return confirm('DANGER: Permanent data purge. Continue?')">PURGE RECORD</a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; else: ?>
                            <tr>
                                <td colspan="4" style="text-align:center; padding: 100px; color: var(--text-dim); font-family: Orbitron; font-size: 1.2rem; letter-spacing: 4px;">SYSTEM DATABASE EMPTY</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th width="30%">User Identity</th>
                                <th width="60%">Message Payload</th>
                                <th width="10%">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($result && $result->num_rows > 0): while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 800; color: #fff; font-size: 1.1rem;"><?php echo htmlspecialchars($row["name"]); ?></div>
                                    <div style="font-size: 0.85rem; color: var(--primary); margin-top: 4px;"><?php echo htmlspecialchars($row["email"]); ?></div>
                                    <div style="font-size: 0.7rem; color: var(--text-dim); margin-top: 10px; font-family: Orbitron;"><?php echo date('Y-m-d H:i', strtotime($row['created_at'])); ?></div>
                                </td>
                                <td>
                                    <div style="font-size: 0.75rem; color: var(--primary); margin-bottom: 12px; font-family: Orbitron; text-transform: uppercase; letter-spacing: 1.5px;">[ SUB: <?php echo htmlspecialchars($row["subject"]); ?> ]</div>
                                    <div class="query-message">
                                        <?php echo nl2br(htmlspecialchars($row["message"])); ?>
                                    </div>
                                </td>
                                <td><a href="?delete_query_id=<?php echo $row["id"]; ?>&view=queries" class="btn-delete" style="padding: 12px 20px;">DELETE</a></td>
                            </tr>
                            <?php endwhile; else: ?>
                            <tr>
                                <td colspan="3" style="text-align:center; padding: 100px; color: var(--text-dim); font-family: Orbitron; font-size: 1.2rem; letter-spacing: 4px;">INBOX CLEAR</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div style="margin-top: 40px; text-align: center; color: #222; font-size: 0.6rem; font-family: Orbitron; letter-spacing: 3px; text-transform: uppercase;">
            SECURE ANALYTICS TERMINAL ACTIVE | NODE: <?php echo $_SERVER['REMOTE_ADDR']; ?> | SYSTEM TIME: <?php echo date('H:i:s'); ?>
        </div>
    </div>

    <!-- NEW: TRANSACTION PROOF MODAL -->
    <div id="proofModal" class="modal-overlay" onclick="closeProof(event)">
        <div class="modal-content" onclick="event.stopPropagation()">
            <div class="modal-header">
                <h3 id="modalTitle" style="font-family: Orbitron; font-size: 1rem; margin: 0; color: var(--primary); letter-spacing: 2px;">PAYMENT_VERIFICATION_INTERFACE</h3>
                <button class="btn-close" onclick="closeProof(null)">&times;</button>
            </div>
            <div id="modalBody" class="modal-body">
                <!-- Dynamic Payload -->
            </div>
            <div style="padding: 20px 30px; border-top: 1px solid var(--border); text-align: right; background: rgba(0,0,0,0.7);">
                <button onclick="closeProof(null)" class="btn-util" style="background: var(--primary); color: #000; padding: 15px 30px; font-size: 0.9rem;">DISCONNECT VIEWPORT</button>
            </div>
        </div>
    </div>

    <script>
        function openProof(fileUrl, name) {
            const modal = document.getElementById('proofModal');
            const body = document.getElementById('modalBody');
            const title = document.getElementById('modalTitle');
            
            title.innerText = `IDENTIFYING ASSET: ${name.toUpperCase()}`;
            body.innerHTML = '<div style="display:flex; height:100%; align-items:center; justify-content:center; color:var(--primary); font-family:Orbitron;">INITIALIZING SECURE LINK...</div>';
            
            const extension = fileUrl.split('.').pop().toLowerCase();
            
            // Artificial delay for technical feel
            setTimeout(() => {
                if (extension === 'pdf') {
                    // Optimized Iframe for PDF with fallback link
                    body.innerHTML = `
                        <div style="width:100%; height:100%; display:flex; flex-direction:column;">
                            <iframe src="${fileUrl}#toolbar=0" type="application/pdf" style="width:100%; flex:1; border:none;"></iframe>
                            <div style="padding:10px; text-align:center; background:#111;">
                                <a href="${fileUrl}" target="_blank" style="color:var(--primary); font-size:0.8rem; font-family:Orbitron; text-decoration:none;">[ OPEN PDF IN NEW TAB ]</a>
                            </div>
                        </div>`;
                } else {
                    // Image with responsive fit and centering
                    body.innerHTML = `
                        <div style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; background:#000;">
                            <img src="${fileUrl}" alt="Payment Proof Attachment" style="max-width:95%; max-height:95%; object-fit:contain; box-shadow: 0 0 30px rgba(0,242,255,0.2);">
                        </div>`;
                }
            }, 300);
            
            modal.style.display = 'flex';
        }

        function closeProof(e) {
            if (e && e.target !== e.currentTarget && e !== null) return;
            document.getElementById('proofModal').style.display = 'none';
            document.getElementById('modalBody').innerHTML = '';
        }

        // Close on Escape key
        document.addEventListener('keydown', (e) => { if (e.key === "Escape") closeProof(null); });
    </script>

    <?php endif; ?>

</body>

</html>
<?php $conn->close(); ?>
