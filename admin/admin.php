<?php
/**
 * admin.php - Secure Owner Dashboard for Techfest 2.0 (AWS EC2 Optimized)
 * Features: PIN-based login, Metrics, Data Management, Proof Verification, WhatsApp Integration
 */

// --- CONFIGURATION ---
$ADMIN_PIN = "aditya"; 
// On AWS EC2, if MariaDB is on the same instance, 'localhost' is correct.
// If using RDS, replace 'localhost' with your RDS Endpoint.
$host = "localhost";
$username = 'techfest_user';
$password = 'StrongPassword123';
$dbname = "techfest_db";

// Dynamically detect the current script name and path for EC2 environments
$self = htmlspecialchars(basename($_SERVER['PHP_SELF']));

session_start();

// Database Connection using MySQLi
$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) { 
    die("<div style='color:red; text-align:center; padding:20px; font-family:sans-serif;'>
            <h3>SERVER CONNECTION ERROR</h3>
            <p>Database Connection Failed. Ensure MariaDB is running on your EC2 instance.</p>
         </div>"); 
}

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
        $redir = "$self?view=registrations";
        header("Location: $redir");
        exit();
    }
    
    if (isset($_GET['delete_query_id'])) {
        $id = intval($_GET['delete_query_id']);
        $conn->query("DELETE FROM queries WHERE id = $id");
        header("Location: $self?view=queries");
        exit();
    }

    // 2. Update Payment Status Logic
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

// --- CSV EXPORT LOGIC ---
if (isset($_SESSION['authenticated_owner']) && isset($_GET['export'])) {
    header('Content-Type: text/csv; charset=utf-8');
    if ($view == 'registrations') {
        header('Content-Disposition: attachment; filename=Techfest_Registrations_' . date('Y-m-d') . '.csv');
        $output = fopen('php://output', 'w');
        fputcsv($output, array('ID', 'Lead Name', 'Phone', 'Event', 'Fee', 'Status', 'Date'));
        $export_result = $conn->query("SELECT id, lead_name, lead_phone, event_name, total_fee, payment_status, registration_date FROM registrations ORDER BY id DESC");
        while ($row = $export_result->fetch_assoc()) { fputcsv($output, $row); }
    } else {
        header('Content-Disposition: attachment; filename=User_Queries_' . date('Y-m-d') . '.csv');
        $output = fopen('php://output', 'w');
        fputcsv($output, array('ID', 'Name', 'Email', 'Subject', 'Message', 'Date'));
        $export_result = $conn->query("SELECT * FROM queries ORDER BY id DESC");
        while ($row = $export_result->fetch_assoc()) { fputcsv($output, $row); }
    }
    fclose($output);
    exit();
}

// Global Metrics
$total_participants = $conn->query("SELECT count(*) as count FROM registrations")->fetch_assoc()['count'] ?? 0;
$total_queries = $conn->query("SELECT count(*) as count FROM queries")->fetch_assoc()['count'] ?? 0;
$revenue_res = $conn->query("SELECT SUM(total_fee) as total FROM registrations WHERE payment_status = 'Verified'");
$total_revenue = ($revenue_res && $row = $revenue_res->fetch_assoc()) ? $row['total'] : 0;

// Fetch Main Data
if ($view == 'registrations') {
    $result = $conn->query("SELECT * FROM registrations ORDER BY id DESC");
} else {
    $result = $conn->query("SELECT * FROM queries ORDER BY id DESC");
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
        h1 { font-family: 'Orbitron'; color: var(--primary); text-align: center; letter-spacing: 2px; }
        .metrics-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 30px 0; }
        .metric-card { background: var(--bg-card); border: 1px solid var(--border); padding: 25px; border-radius: 15px; text-align: center; }
        .metric-label { font-family: 'Orbitron'; font-size: 0.7rem; color: var(--text-dim); text-transform: uppercase; margin-bottom: 5px; display: block; }
        .metric-value { font-size: 2.2rem; font-weight: 800; font-family: 'Orbitron'; }
        .login-overlay { position: fixed; inset: 0; background: var(--bg-dark); display: flex; align-items: center; justify-content: center; z-index: 9999; }
        .login-card { background: var(--bg-card); padding: 40px; border-radius: 20px; border: 1px solid var(--primary); text-align: center; width: 350px; }
        .pin-input { width: 100%; padding: 12px; background: #000; border: 1px solid var(--primary); color: var(--primary); border-radius: 8px; margin-bottom: 20px; text-align: center; font-size: 1.5rem; letter-spacing: 5px; outline: none; }
        .btn-primary { background: var(--primary); color: #000; border: none; padding: 12px; width: 100%; border-radius: 8px; font-weight: bold; cursor: pointer; text-transform: uppercase; }
        .view-tabs { display: flex; justify-content: center; gap: 10px; margin-bottom: 25px; }
        .tab-link { text-decoration: none; padding: 10px 25px; border-radius: 25px; font-family: 'Orbitron'; font-size: 0.75rem; color: var(--text-dim); border: 1px solid var(--border); transition: 0.3s; }
        .tab-link.active { background: var(--primary); color: #000; border-color: var(--primary); }
        .table-wrapper { background: var(--bg-card); border-radius: 15px; overflow-x: auto; border: 1px solid var(--border); }
        table { width: 100%; border-collapse: collapse; min-width: 800px; }
        th { background: rgba(255, 255, 255, 0.05); text-align: left; padding: 15px; font-family: 'Orbitron'; font-size: 0.65rem; color: var(--primary); text-transform: uppercase; }
        td { padding: 15px; border-bottom: 1px solid rgba(255, 255, 255, 0.03); font-size: 0.9rem; }
        .status-badge { padding: 4px 10px; border-radius: 5px; font-size: 0.6rem; font-weight: 800; text-transform: uppercase; font-family: 'Orbitron'; }
        .status-verified { background: rgba(0, 255, 136, 0.1); color: var(--success); border: 1px solid var(--success); }
        .status-pending { background: rgba(255, 170, 0, 0.1); color: var(--warning); border: 1px solid var(--warning); }
        .btn-action { text-decoration: none; font-size: 0.7rem; padding: 6px 12px; border-radius: 5px; font-family: 'Orbitron'; font-weight: bold; display: inline-block; }
        .btn-verify { background: var(--success); color: #000; }
        .btn-delete { color: var(--danger); border: 1px solid var(--danger); margin-left: 5px; }
        .btn-wa { background: #25D366; color: #fff; border-radius: 50%; width: 30px; height: 30px; display: inline-flex; align-items: center; justify-content: center; }
        .modal { position: fixed; inset: 0; background: rgba(0,0,0,0.9); display: none; align-items: center; justify-content: center; z-index: 10000; }
        .modal img { max-width: 90%; max-height: 90%; border: 1px solid var(--primary); }
    </style>
</head>
<body>

<?php if (!isset($_SESSION['authenticated_owner'])): ?>
    <div class="login-overlay">
        <div class="login-card">
            <h2 style="font-family: Orbitron; color: var(--primary);">ADMIN PORTAL</h2>
            <form method="POST">
                <input type="password" name="pin_input" class="pin-input" placeholder="PIN" required autofocus>
                <button type="submit" class="btn-primary">Authenticate</button>
            </form>
            <?php if ($error_msg): ?><p style="color:var(--danger); font-size:0.8rem; margin-top:10px;"><?php echo $error_msg; ?></p><?php endif; ?>
        </div>
    </div>
<?php else: ?>
    <div class="container">
        <div style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <span style="font-family:Orbitron; color:var(--primary); font-size:0.8rem;">AWS-EC2 NODE ACTIVE</span>
            <a href="?logout=1" style="color:var(--text-dim); text-decoration:none; font-size:0.7rem; font-family:Orbitron;">[ LOGOUT ]</a>
        </div>
        
        <h1>DASHBOARD ANALYTICS</h1>

        <div class="metrics-grid">
            <div class="metric-card"><span class="metric-label">Users</span><div class="metric-value"><?php echo $total_participants; ?></div></div>
            <div class="metric-card"><span class="metric-label">Revenue</span><div class="metric-value" style="color:var(--success);">₹<?php echo number_format($total_revenue); ?></div></div>
            <div class="metric-card"><span class="metric-label">Queries</span><div class="metric-value" style="color:var(--primary);"><?php echo $total_queries; ?></div></div>
        </div>

        <div class="view-tabs">
            <a href="?view=registrations" class="tab-link <?php echo ($view == 'registrations') ? 'active' : ''; ?>">REGISTRATIONS</a>
            <a href="?view=queries" class="tab-link <?php echo ($view == 'queries') ? 'active' : ''; ?>">QUERIES</a>
        </div>

        <div class="table-wrapper">
            <?php if ($view == 'registrations'): ?>
                <table>
                    <thead><tr><th>Lead</th><th>Event</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php while($row = $result->fetch_assoc()): 
                            $s_class = ($row['payment_status'] == 'Verified') ? 'status-verified' : 'status-pending';
                            $wa_msg = urlencode("Hello " . $row['lead_name'] . ", your Techfest registration is VERIFIED!");
                            $wa_url = "https://wa.me/" . preg_replace('/[^0-9]/', '', $row['lead_phone']) . "?text=" . $wa_msg;
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($row["lead_name"]); ?></strong><br><small><?php echo htmlspecialchars($row["lead_phone"]); ?></small></td>
                            <td><?php echo htmlspecialchars($row["event_name"]); ?><br><span style="color:var(--success);">₹<?php echo $row['total_fee']; ?></span></td>
                            <td><span class="status-badge <?php echo $s_class; ?>"><?php echo $row['payment_status']; ?></span></td>
                            <td>
                                <div style="display:flex; gap:8px; align-items:center;">
                                    <button onclick="showImg('../uploads/<?php echo $row['transaction_proof']; ?>')" class="btn-primary" style="padding:4px 10px; font-size:0.6rem; width:auto;">PROOF</button>
                                    <?php if($row['payment_status'] == 'Pending'): ?>
                                        <a href="?toggle_payment=Verified&id=<?php echo $row['id']; ?>" class="btn-action btn-verify">VERIFY</a>
                                    <?php else: ?>
                                        <a href="<?php echo $wa_url; ?>" target="_blank" class="btn-wa">WA</a>
                                    <?php endif; ?>
                                    <a href="?delete_reg_id=<?php echo $row['id']; ?>" class="btn-action btn-delete" onclick="return confirm('Delete record?')">X</a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <table>
                    <thead><tr><th>User</th><th>Message</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($row["name"]); ?></strong><br><small><?php echo htmlspecialchars($row["email"]); ?></small></td>
                            <td style="color:var(--text-dim);"><?php echo nl2br(htmlspecialchars($row["message"])); ?></td>
                            <td><a href="?delete_query_id=<?php echo $row['id']; ?>" class="btn-action btn-delete">DELETE</a></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <div style="text-align:center; margin-top:20px;">
            <a href="?export=csv&view=<?php echo $view; ?>" style="color:var(--primary); font-size:0.7rem; text-decoration:none; font-family:Orbitron;">[ EXPORT DATA TO CSV ]</a>
        </div>
    </div>

    <div id="imgModal" class="modal" onclick="this.style.display='none'">
        <div class="modal-content" onclick="event.stopPropagation()"><img id="modalImg" src=""></div>
    </div>

    <script>
        function showImg(url) {
            document.getElementById('modalImg').src = url;
            document.getElementById('imgModal').style.display = 'flex';
        }
    </script>
<?php endif; ?>
</body>
</html>
<?php $conn->close(); ?>
