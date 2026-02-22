<?php
/**
 * admin.php - Secure Owner Dashboard for Techfest 2.0 (AWS EC2 Optimized)
 * Path: /admin/admin.php
 */

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
    die("<div style='color:red; text-align:center; padding:20px; font-family:sans-serif; background:#111; border:1px solid red; margin:20px; border-radius:10px;'>
            <h3 style='margin-top:0;'>DATABASE CONNECTION ERROR</h3>
            <p>The admin panel cannot connect to MariaDB.</p>
            <p style='font-size:0.8rem; color:#888;'>Error: " . $conn->connect_error . "</p>
         </div>"); 
}

$self = htmlspecialchars(basename($_SERVER['PHP_SELF']));

// Handle Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: $self");
    exit();
}

// Handle Login
$error_msg = "";
if (isset($_POST['pin_input'])) {
    if ($_POST['pin_input'] === $ADMIN_PIN) {
        $_SESSION['authenticated_owner'] = true;
        header("Location: $self"); 
        exit();
    } else {
        $error_msg = "ACCESS DENIED: INVALID SECURITY PIN.";
    }
}

// Determine Current View
$view = (isset($_GET['view'])) ? $_GET['view'] : 'registrations';

// --- ACTION LOGIC ---
if (isset($_SESSION['authenticated_owner'])) {
    if (isset($_GET['delete_reg_id'])) {
        $id = intval($_GET['delete_reg_id']);
        $conn->query("DELETE FROM registrations WHERE id = $id");
        header("Location: $self?view=registrations");
        exit();
    }
    
    if (isset($_GET['delete_query_id'])) {
        $id = intval($_GET['delete_query_id']);
        $conn->query("DELETE FROM queries WHERE id = $id");
        header("Location: $self?view=queries");
        exit();
    }

    if (isset($_GET['delete_stay_id'])) {
        $id = intval($_GET['delete_stay_id']);
        $conn->query("DELETE FROM stay_requests WHERE id = $id");
        header("Location: $self?view=stay");
        exit();
    }

    if (isset($_GET['toggle_payment']) && isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $new_status = ($_GET['toggle_payment'] === 'Verified') ? 'Verified' : 'Pending';
        $stmt = $conn->prepare("UPDATE registrations SET payment_status = ? WHERE id = ?");
        $stmt->bind_param("si", $new_status, $id);
        $stmt->execute();
        header("Location: $self?view=registrations");
        exit();
    }
}

// --- CSV EXPORT ---
if (isset($_SESSION['authenticated_owner']) && isset($_GET['export'])) {
    header('Content-Type: text/csv; charset=utf-8');
    $filename = 'Techfest_Data_' . ucfirst($view) . '_';
    header('Content-Disposition: attachment; filename=' . $filename . date('Y-m-d_H-i') . '.csv');
    $output = fopen('php://output', 'w');
    
    if ($view == 'registrations') {
        fputcsv($output, array('ID', 'Event', 'Category', 'Team Name', 'College', 'Lead Name', 'Email', 'Phone', 'Members', 'Fee', 'UTR', 'Status', 'Date'));
        $res = $conn->query("SELECT id, event_name, category, team_name, college, lead_name, lead_email, lead_phone, additional_members, total_fee, utr, payment_status, registration_date FROM registrations ORDER BY id DESC");
    } elseif ($view == 'queries') {
        fputcsv($output, array('ID', 'Name', 'Email', 'Subject', 'Message', 'Date'));
        $res = $conn->query("SELECT id, name, email, subject, message, created_at FROM queries ORDER BY id DESC");
    } elseif ($view == 'stay') {
        fputcsv($output, array('ID', 'Full Name', 'College Name', 'Gender', 'Nights', 'Check-in', 'Phone', 'Requirements', 'Date'));
        $res = $conn->query("SELECT id, full_name, college_name, gender, number_of_nights, check_in_date, phone_number, special_requirements, submitted_at FROM stay_requests ORDER BY id DESC");
    }
    
    if(isset($res)){ while ($row = $res->fetch_assoc()) { fputcsv($output, $row); } }
    fclose($output);
    exit();
}

// Metrics
$total_participants = $conn->query("SELECT count(*) as count FROM registrations")->fetch_assoc()['count'] ?? 0;
$total_queries = $conn->query("SELECT count(*) as count FROM queries")->fetch_assoc()['count'] ?? 0;
$revenue_res = $conn->query("SELECT SUM(total_fee) as total FROM registrations WHERE payment_status = 'Verified'");
$total_revenue = ($revenue_res && $row = $revenue_res->fetch_assoc()) ? $row['total'] : 0;
$event_stats = $conn->query("SELECT event_name, COUNT(*) as count FROM registrations GROUP BY event_name ORDER BY count DESC");

// Data Fetching
if ($view == 'registrations') {
    $result = $conn->query("SELECT * FROM registrations ORDER BY id DESC");
} elseif ($view == 'queries') {
    $result = $conn->query("SELECT * FROM queries ORDER BY id DESC");
} else {
    $result = $conn->query("SELECT * FROM stay_requests ORDER BY id DESC");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Owner Dashboard - Techfest 2.0</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #00f2ff; --bg-dark: #030305; --bg-card: #0a0a12; --border: rgba(255, 255, 255, 0.1); --text-dim: #94a3b8; --success: #00ff88; --warning: #ffaa00; --danger: #ff4444; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: var(--bg-dark); color: white; margin: 0; padding: 20px; }
        .container { max-width: 1400px; margin: 0 auto; }
        h1 { font-family: 'Orbitron'; color: var(--primary); text-align: center; letter-spacing: 2px; text-shadow: 0 0 20px rgba(0, 242, 255, 0.2); }
        .metrics-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 30px 0; }
        .metric-card { background: var(--bg-card); border: 1px solid var(--border); padding: 20px; border-radius: 12px; text-align: center; }
        .metric-label { font-family: 'Orbitron'; font-size: 0.6rem; color: var(--text-dim); text-transform: uppercase; margin-bottom: 5px; display: block; }
        .metric-value { font-size: 1.8rem; font-weight: 800; font-family: 'Orbitron'; }
        
        .analytics-section { background: rgba(255,255,255,0.02); border: 1px solid var(--border); border-radius: 15px; padding: 20px; margin-bottom: 30px; }
        .analytics-title { font-family: 'Orbitron'; color: var(--primary); font-size: 0.8rem; margin-bottom: 15px; display: block; text-align: center; }
        .stats-grid { display: flex; flex-wrap: wrap; gap: 10px; justify-content: center; }
        .stat-tag { background: #000; border: 1px solid var(--primary); padding: 5px 12px; border-radius: 20px; font-size: 0.7rem; font-family: 'Orbitron'; }
        .stat-tag span { color: var(--primary); margin-left: 5px; }

        .login-overlay { position: fixed; inset: 0; background: var(--bg-dark); display: flex; align-items: center; justify-content: center; z-index: 9999; }
        .login-card { background: var(--bg-card); padding: 40px; border-radius: 20px; border: 1px solid var(--primary); text-align: center; width: 350px; box-shadow: 0 0 50px rgba(0, 242, 255, 0.1); }
        .pin-input { width: 100%; padding: 12px; background: #000; border: 1px solid var(--primary); color: var(--primary); border-radius: 8px; margin-bottom: 20px; text-align: center; font-size: 1.5rem; letter-spacing: 5px; outline: none; font-family: Orbitron; }
        .btn-primary { background: var(--primary); color: #000; border: none; padding: 12px; width: 100%; border-radius: 8px; font-weight: bold; cursor: pointer; text-transform: uppercase; font-family: Orbitron; }
        
        .view-tabs { display: flex; justify-content: center; gap: 10px; margin-bottom: 25px; flex-wrap: wrap; }
        .tab-link { text-decoration: none; padding: 10px 20px; border-radius: 25px; font-family: 'Orbitron'; font-size: 0.7rem; color: var(--text-dim); border: 1px solid var(--border); transition: 0.3s; }
        .tab-link.active { background: var(--primary); color: #000; border-color: var(--primary); box-shadow: 0 0 15px rgba(0, 242, 255, 0.3); }
        
        .table-wrapper { background: var(--bg-card); border-radius: 15px; overflow-x: auto; border: 1px solid var(--border); }
        table { width: 100%; border-collapse: collapse; min-width: 1000px; }
        th { background: rgba(255, 255, 255, 0.05); text-align: left; padding: 15px; font-family: 'Orbitron'; font-size: 0.6rem; color: var(--primary); text-transform: uppercase; border-bottom: 1px solid var(--border); }
        td { padding: 15px; border-bottom: 1px solid rgba(255, 255, 255, 0.03); font-size: 0.8rem; }
        
        .status-badge { padding: 4px 10px; border-radius: 5px; font-size: 0.55rem; font-weight: 800; text-transform: uppercase; font-family: 'Orbitron'; }
        .status-verified { background: rgba(0, 255, 136, 0.1); color: var(--success); border: 1px solid var(--success); }
        .status-pending { background: rgba(255, 170, 0, 0.1); color: var(--warning); border: 1px solid var(--warning); }
        
        .btn-action { text-decoration: none; font-size: 0.65rem; padding: 6px 10px; border-radius: 5px; font-family: 'Orbitron'; font-weight: bold; display: inline-block; text-align: center; }
        .btn-verify { background: var(--success); color: #000; }
        .btn-revoke { background: rgba(255, 170, 0, 0.1); color: var(--warning); border: 1px solid var(--warning); margin-right: 5px; }
        .btn-delete { color: var(--danger); border: 1px solid var(--danger); }
        .btn-wa { background: #25D366; color: #fff; border-radius: 50%; width: 30px; height: 30px; display: inline-flex; align-items: center; justify-content: center; }
    </style>
</head>
<body>

<?php if (!isset($_SESSION['authenticated_owner'])): ?>
    <div class="login-overlay">
        <div class="login-card">
            <h2 style="font-family: Orbitron; color: var(--primary); margin-bottom: 5px;">ROOT ACCESS</h2>
            <form method="POST">
                <input type="password" name="pin_input" class="pin-input" placeholder="••••" required autofocus>
                <button type="submit" class="btn-primary">INITIALIZE UPLINK</button>
            </form>
            <?php if ($error_msg): ?><p style="color:var(--danger); font-size:0.8rem; margin-top:15px;"><?php echo $error_msg; ?></p><?php endif; ?>
        </div>
    </div>
<?php else: ?>
    <div class="container">
        <div style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <span style="font-family:Orbitron; color:var(--primary); font-size:0.6rem;">AWS NODE: <?php echo $_SERVER['SERVER_ADDR']; ?></span>
            <a href="?logout=1" style="color:var(--text-dim); text-decoration:none; font-size:0.6rem; font-family:Orbitron;">[ TERMINATE SESSION ]</a>
        </div>
        
        <h1>DASHBOARD ANALYTICS</h1>

        <div class="metrics-grid">
            <div class="metric-card"><span class="metric-label">Total Participants</span><div class="metric-value"><?php echo $total_participants; ?></div></div>
            <div class="metric-card"><span class="metric-label">Verified Revenue</span><div class="metric-value" style="color:var(--success);">₹<?php echo number_format($total_revenue); ?></div></div>
            <div class="metric-card"><span class="metric-label">Support Queries</span><div class="metric-value" style="color:var(--primary);"><?php echo $total_queries; ?></div></div>
        </div>

        <div class="analytics-section">
            <span class="analytics-title">EVENT PARTICIPATION COUNTS</span>
            <div class="stats-grid">
                <?php while($stat = $event_stats->fetch_assoc()): ?>
                    <div class="stat-tag"><?php echo strtoupper(htmlspecialchars($stat['event_name'])); ?>: <span><?php echo $stat['count']; ?></span></div>
                <?php endwhile; ?>
            </div>
        </div>

        <div class="view-tabs">
            <a href="?view=registrations" class="tab-link <?php echo ($view == 'registrations') ? 'active' : ''; ?>">REGISTRATIONS</a>
            <a href="?view=queries" class="tab-link <?php echo ($view == 'queries') ? 'active' : ''; ?>">QUERIES</a>
            <a href="?view=stay" class="tab-link <?php echo ($view == 'stay') ? 'active' : ''; ?>">STAY/HOSTEL</a>
        </div>

        <div class="table-wrapper">
            <?php if ($view == 'registrations'): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Lead & Team</th>
                            <th>College & Email</th>
                            <th>Event Details</th>
                            <th>UTR / Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($result && $result->num_rows > 0): while($row = $result->fetch_assoc()): 
                            $s_class = ($row['payment_status'] == 'Verified') ? 'status-verified' : 'status-pending';
                            // Updated WhatsApp message with the specific ?mode=gi_t parameter
                            $wa_msg = urlencode("Hello " . $row['lead_name'] . ", your registration for expo at Techfest 2.0 has been VERIFIED. We look forward to seeing you! Stay updated with the latest announcements, schedules, and registration links. Join our official Techfest WhatsApp Group now! https://chat.whatsapp.com/CDIWyEyGRFsHJzLhvOHYhO?mode=gi_t");
                            $wa_url = "https://wa.me/" . preg_replace('/[^0-9]/', '', $row['lead_phone']) . "?text=" . $wa_msg;
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($row["lead_name"]); ?></strong><br>
                                <small style="color:var(--primary);"><?php echo htmlspecialchars($row["lead_phone"]); ?></small><br>
                                <small style="color:var(--text-dim);">Team: <?php echo htmlspecialchars($row["team_name"] ?? 'N/A'); ?></small>
                            </td>
                            <td>
                                <small><?php echo htmlspecialchars($row["college"] ?? 'N/A'); ?></small><br>
                                <small style="color:var(--text-dim);"><?php echo htmlspecialchars($row["lead_email"] ?? 'N/A'); ?></small>
                            </td>
                            <td>
                                <span style="color:var(--primary);"><?php echo htmlspecialchars($row["event_name"]); ?></span><br>
                                <small>Fee: ₹<?php echo $row['total_fee']; ?></small><br>
                                <small style="font-size:0.6rem;">Members: <?php echo htmlspecialchars($row['additional_members'] ?? 'None'); ?></small>
                            </td>
                            <td>
                                <code style="font-size:0.75rem; color:var(--primary);"><?php echo htmlspecialchars($row["utr"] ?? 'NO UTR'); ?></code><br>
                                <span class="status-badge <?php echo $s_class; ?>"><?php echo $row['payment_status']; ?></span>
                            </td>
                            <td>
                                <div style="display:flex; gap:5px; align-items:center;">
                                    <?php if($row['payment_status'] == 'Pending'): ?>
                                        <a href="?toggle_payment=Verified&id=<?php echo $row['id']; ?>" class="btn-action btn-verify">VERIFY</a>
                                    <?php else: ?>
                                        <a href="?toggle_payment=Pending&id=<?php echo $row['id']; ?>" class="btn-action btn-revoke" onclick="return confirm('Revoke verification?')">REVOKE</a>
                                        <a href="<?php echo $wa_url; ?>" target="_blank" class="btn-wa">
                                            <svg width="14" height="14" fill="white" viewBox="0 0 24 24"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766 0-3.18-2.587-5.771-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.512-2.96-2.626-.087-.114-.694-.922-.694-1.758 0-.837.434-1.246.587-1.412.144-.17.315-.21.424-.21.109 0 .21.002.302.007.098.005.23-.037.35.25.132.317.456 1.112.496 1.192.04.08.066.17.013.272-.053.102-.08.175-.162.27-.08.093-.173.205-.246.27-.087.08-.178.166-.076.337.103.17.458.753.985 1.22.68.606 1.254.796 1.431.884.178.088.283.074.388-.047.106-.123.456-.532.578-.714.122-.182.245-.153.413-.091.166.062 1.06.502 1.242.593.182.091.303.136.348.215.045.079.045.457-.1.862z"/></svg>
                                        </a>
                                    <?php endif; ?>
                                    <a href="?delete_reg_id=<?php echo $row['id']; ?>" class="btn-action btn-delete" onclick="return confirm('Delete record?')">X</a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr><td colspan="5" style="text-align:center; padding:50px; color:var(--text-dim);">No registrations found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            <?php elseif ($view == 'queries'): ?>
                <table>
                    <thead><tr><th>User Info</th><th>Subject</th><th>Message Payload</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php if($result && $result->num_rows > 0): while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($row["name"]); ?></strong><br><small style="color:var(--primary);"><?php echo htmlspecialchars($row["email"]); ?></small></td>
                            <td style="font-weight:bold; color:var(--primary);"><?php echo htmlspecialchars($row["subject"] ?? 'N/A'); ?></td>
                            <td style="color:var(--text-dim); font-size:0.75rem; max-width:400px;"><?php echo nl2br(htmlspecialchars($row["message"])); ?></td>
                            <td>
                                <div style="display:flex; flex-direction:column; gap:5px;">
                                    <small style="font-size:0.6rem; color:var(--text-dim);"><?php echo $row['created_at']; ?></small>
                                    <a href="?delete_query_id=<?php echo $row['id']; ?>" class="btn-action btn-delete" onclick="return confirm('Delete query?')">DELETE</a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr><td colspan="4" style="text-align:center; padding:50px; color:var(--text-dim);">Inbox is empty.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            <?php elseif ($view == 'stay'): ?>
                <table>
                    <thead><tr><th>Guest & College</th><th>Contact</th><th>Stay Details</th><th>Requirements</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php if($result && $result->num_rows > 0): while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($row["full_name"]); ?></strong><br><small style="color:var(--text-dim);"><?php echo htmlspecialchars($row["college_name"]); ?></small></td>
                            <td><?php echo htmlspecialchars($row["phone_number"]); ?></td>
                            <td>
                                <span style="color:var(--primary); font-weight:bold;"><?php echo htmlspecialchars($row["gender"]); ?></span><br>
                                <small><?php echo htmlspecialchars($row["number_of_nights"]); ?> Night(s)</small><br>
                                <small>Check-in: <?php echo htmlspecialchars($row["check_in_date"]); ?></small>
                            </td>
                            <td style="font-size:0.7rem; color:var(--text-dim); max-width:250px;"><?php echo nl2br(htmlspecialchars($row["special_requirements"])); ?></td>
                            <td>
                                <div style="display:flex; flex-direction:column; gap:5px;">
                                    <small style="font-size:0.6rem; color:var(--text-dim);"><?php echo $row['submitted_at']; ?></small>
                                    <a href="?delete_stay_id=<?php echo $row['id']; ?>" class="btn-action btn-delete" onclick="return confirm('Delete stay request?')">DELETE</a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr><td colspan="5" style="text-align:center; padding:50px; color:var(--text-dim);">No stay requests found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <div style="text-align:center; margin-top:30px;">
            <a href="?export=csv&view=<?php echo $view; ?>" style="color:var(--primary); font-size:0.7rem; text-decoration:none; font-family:Orbitron; border: 1px solid var(--primary); padding: 10px 25px; border-radius: 20px;">[ EXPORT <?php echo strtoupper($view); ?> TO CSV ]</a>
        </div>
    </div>
<?php endif; ?>
</body>
</html>
<?php $conn->close(); ?>
