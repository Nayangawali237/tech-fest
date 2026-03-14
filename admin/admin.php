<?php
/**
 * admin.php - Secure Cyberpunk Owner Dashboard for Techfest 2.0
 * Features: Event Filtering, WhatsApp Integration, and Filtered CSV Export.
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
    die("<div style='color:#ff4444; text-align:center; padding:40px; font-family:sans-serif; background:#050505; border:2px solid #ff4444; margin:50px; border-radius:15px;'>
            <h2>SYSTEM OFFLINE</h2>
            <p>Database connection failed.</p>
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
        $error_msg = "ACCESS DENIED.";
    }
}

// Determine View & Filter
$view = (isset($_GET['view'])) ? $_GET['view'] : 'registrations';
$event_filter = (isset($_GET['event'])) ? $_GET['event'] : '';

// --- ACTIONS ---
if (isset($_SESSION['authenticated_owner'])) {
    if (isset($_GET['delete_reg_id'])) {
        $id = intval($_GET['delete_reg_id']);
        $conn->query("DELETE FROM registrations WHERE id = $id");
        $redirect = "$self?view=registrations" . ($event_filter ? "&event=".urlencode($event_filter) : "");
        header("Location: $redirect");
        exit();
    }
    
    if (isset($_GET['toggle_payment']) && isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $new_status = ($_GET['toggle_payment'] === 'Verified') ? 'Verified' : 'Pending';
        $stmt = $conn->prepare("UPDATE registrations SET payment_status = ? WHERE id = ?");
        if($stmt) {
            $stmt->bind_param("si", $new_status, $id);
            $stmt->execute();
        }
        $redirect = "$self?view=registrations" . ($event_filter ? "&event=".urlencode($event_filter) : "");
        header("Location: $redirect");
        exit();
    }
}

// --- CSV EXPORT ---
if (isset($_SESSION['authenticated_owner']) && isset($_GET['export'])) {
    if (ob_get_level()) ob_end_clean();
    
    $prefix = $event_filter ? preg_replace('/[^a-zA-Z0-9]/', '_', $event_filter) : ucfirst($view);
    $filename = 'Techfest_' . $prefix . '_' . date('Ymd_Hi') . '.csv';
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    $sql = "SELECT * FROM registrations";
    if ($event_filter) {
        $sql .= " WHERE event_name = '" . $conn->real_escape_string($event_filter) . "'";
    }
    $sql .= " ORDER BY id DESC";
    
    $res = $conn->query($sql);
    if($res && $row = $res->fetch_assoc()){
        fputcsv($output, array_keys($row));
        $res->data_seek(0);
        while($r = $res->fetch_assoc()) fputcsv($output, $r);
    }
    fclose($output);
    exit();
}

// --- METRICS ---
$total_participants = $conn->query("SELECT count(*) as count FROM registrations")->fetch_assoc()['count'] ?? 0;
$total_revenue = $conn->query("SELECT SUM(total_fee) as total FROM registrations WHERE payment_status = 'Verified'")->fetch_assoc()['total'] ?? 0;
$query_count = $conn->query("SELECT count(*) as count FROM queries")->fetch_assoc()['count'] ?? 0;
$event_stats = $conn->query("SELECT event_name, COUNT(*) as count FROM registrations GROUP BY event_name ORDER BY count DESC");

// Fetch Results with filter logic
if ($view == 'registrations') {
    $sql = "SELECT * FROM registrations";
    if ($event_filter) {
        $sql .= " WHERE event_name = '" . $conn->real_escape_string($event_filter) . "'";
    }
    $sql .= " ORDER BY id DESC";
    $result = $conn->query($sql);
} else {
    $result = null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DASHBOARD ANALYTICS</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&family=Plus+Jakarta+Sans:wght@400;600&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #00f2ff; --bg: #000; --card: #0a0a0f; --border: rgba(0,242,255,0.2); --text: #e2e8f0; --dim: #64748b; --success: #00ff88; --danger: #ff4444; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: var(--bg); color: var(--text); margin: 0; padding: 20px; }
        .container { max-width: 1400px; margin: 0 auto; }
        h1 { font-family: 'Orbitron'; color: var(--primary); text-align: center; letter-spacing: 4px; text-shadow: 0 0 15px rgba(0,242,255,0.3); margin-bottom: 10px; }
        
        .metrics { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin: 30px 0; }
        .m-card { background: var(--card); border: 1px solid var(--border); padding: 20px; border-radius: 12px; text-align: center; }
        .m-label { font-family: 'Orbitron'; font-size: 0.6rem; color: var(--dim); display: block; margin-bottom: 5px; }
        .m-value { font-family: 'Orbitron'; font-size: 1.8rem; font-weight: 700; }

        .tags { display: flex; flex-wrap: wrap; gap: 10px; justify-content: center; margin-bottom: 30px; padding: 20px; background: rgba(255,255,255,0.02); border-radius: 15px; border: 1px solid var(--border); }
        .tag { background: #000; border: 1px solid var(--border); padding: 6px 15px; border-radius: 50px; font-family: 'Orbitron'; font-size: 0.65rem; text-decoration: none; color: inherit; transition: 0.3s; }
        .tag:hover, .tag.active { border-color: var(--primary); box-shadow: 0 0 10px rgba(0,242,255,0.3); transform: translateY(-2px); }
        .tag span { color: var(--primary); margin-left: 5px; }

        .tabs { display: flex; justify-content: center; gap: 15px; margin-bottom: 25px; }
        .tab { text-decoration: none; padding: 10px 25px; border-radius: 50px; font-family: 'Orbitron'; font-size: 0.7rem; color: var(--dim); border: 1px solid var(--border); transition: 0.3s; }
        .tab.active { background: var(--primary); color: #000; border-color: var(--primary); box-shadow: 0 0 15px rgba(0,242,255,0.4); }

        .table-wrap { background: var(--card); border-radius: 12px; overflow: hidden; border: 1px solid var(--border); }
        table { width: 100%; border-collapse: collapse; }
        th { background: rgba(0,242,255,0.05); text-align: left; padding: 15px 20px; font-family: 'Orbitron'; font-size: 0.65rem; color: var(--primary); border-bottom: 1px solid var(--border); }
        td { padding: 15px 20px; border-bottom: 1px solid rgba(255,255,255,0.05); }

        .name { font-weight: 700; font-size: 1rem; display: block; }
        .phone { color: var(--primary); font-size: 0.8rem; font-family: 'Orbitron'; }
        .team { color: var(--dim); font-size: 0.75rem; }
        .ev-name { color: var(--primary); font-weight: 600; display: block; }
        .ev-fee { font-size: 0.75rem; color: #fff; }
        .ev-mem { font-size: 0.65rem; color: var(--dim); display: block; }

        .badge { padding: 3px 8px; border-radius: 4px; font-family: 'Orbitron'; font-size: 0.55rem; font-weight: 700; }
        .verified { background: rgba(0,255,136,0.1); color: var(--success); border: 1px solid var(--success); }
        .pending { background: rgba(255,170,0,0.1); color: #ffaa00; border: 1px solid #ffaa00; }

        .actions { display: flex; gap: 8px; align-items: center; }
        .btn { text-decoration: none; font-family: 'Orbitron'; font-size: 0.6rem; font-weight: 700; padding: 6px 12px; border-radius: 4px; transition: 0.2s; }
        .btn-v { background: var(--success); color: #000; }
        .btn-r { border: 1px solid #ffaa00; color: #ffaa00; }
        .btn-d { border: 1px solid var(--danger); color: var(--danger); }
        .btn-wa { background: #25D366; color: white; border-radius: 50%; width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; }

        .login-screen { position: fixed; inset: 0; background: #000; display: flex; align-items: center; justify-content: center; z-index: 100; }
        .login-box { background: var(--card); padding: 40px; border-radius: 15px; border: 1px solid var(--primary); text-align: center; width: 350px; }
        .pin { width: 100%; padding: 12px; background: #000; border: 1px solid var(--primary); color: var(--primary); border-radius: 8px; font-family: 'Orbitron'; font-size: 1.5rem; text-align: center; letter-spacing: 5px; margin: 20px 0; }
        
        .filter-header { display: flex; justify-content: center; align-items: center; gap: 10px; margin-bottom: 20px; font-family: 'Orbitron'; font-size: 0.8rem; }
        .clear-filter { color: var(--danger); text-decoration: none; font-size: 0.6rem; border-bottom: 1px solid var(--danger); }
    </style>
</head>
<body>

<?php if (!isset($_SESSION['authenticated_owner'])): ?>
    <div class="login-screen">
        <div class="login-box">
            <h2 style="font-family: 'Orbitron'; color: var(--primary);">ROOT ACCESS</h2>
            <form method="POST"><input type="password" name="pin_input" class="pin" required autofocus><button type="submit" style="background:var(--primary); width:100%; padding:12px; border:none; border-radius:8px; font-family:'Orbitron'; font-weight:700;">INITIALIZE</button></form>
            <?php if($error_msg) echo "<p style='color:red; font-size:0.8rem;'>$error_msg</p>"; ?>
        </div>
    </div>
<?php else: ?>
    <div class="container">
        <div style="display:flex; justify-content: space-between; font-family: 'Orbitron'; font-size: 0.6rem; margin-bottom: 10px;">
            <span style="color: var(--primary);">AWS_NODE: <?php echo $_SERVER['SERVER_ADDR']; ?></span>
            <a href="?logout=1" style="color: var(--dim); text-decoration: none;">[ TERMINATE ]</a>
        </div>

        <h1>DASHBOARD ANALYTICS</h1>
        
        <?php if($event_filter): ?>
            <div class="filter-header">
                FILTERING BY: <span style="color:var(--primary);"><?php echo strtoupper(htmlspecialchars($event_filter)); ?></span>
                <a href="?view=registrations" class="clear-filter">[ CLEAR ]</a>
            </div>
        <?php endif; ?>

        <div class="metrics">
            <div class="m-card"><span class="m-label">TOTAL PARTICIPANTS</span><div class="m-value"><?php echo $total_participants; ?></div></div>
            <div class="m-card"><span class="m-label">VERIFIED REVENUE</span><div class="m-value" style="color: var(--success);">₹<?php echo number_format($total_revenue); ?></div></div>
            <div class="m-card"><span class="m-label">SUPPORT QUERIES</span><div class="m-value" style="color: var(--primary);"><?php echo $query_count; ?></div></div>
        </div>

        <div class="tags">
            <?php while($s = $event_stats->fetch_assoc()): 
                $activeClass = ($event_filter == $s['event_name']) ? 'active' : '';
            ?>
                <a href="?view=registrations&event=<?php echo urlencode($s['event_name']); ?>" class="tag <?php echo $activeClass; ?>">
                    <?php echo strtoupper(htmlspecialchars($s['event_name'])); ?>: <span><?php echo $s['count']; ?></span>
                </a>
            <?php endwhile; ?>
        </div>

        <div class="tabs">
            <a href="?view=registrations" class="tab <?php echo $view == 'registrations' ? 'active' : ''; ?>">REGISTRATIONS</a>
            <a href="?view=queries" class="tab <?php echo $view == 'queries' ? 'active' : ''; ?>">QUERIES</a>
            <a href="?view=stay" class="tab <?php echo $view == 'stay' ? 'active' : ''; ?>">STAY/HOSTEL</a>
        </div>

        <div class="table-wrap">
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
                        $isV = ($row['payment_status'] == 'Verified');
                        $wa_msg = urlencode("Hello " . $row['lead_name'] . ", your registration for " . $row['event_name'] . " at Techfest 2.0 has been VERIFIED. We look forward to seeing you!");
                        $wa_url = "https://wa.me/" . preg_replace('/[^0-9]/', '', $row['lead_phone']) . "?text=" . $wa_msg;
                        
                        // Maintain filter in links
                        $filterParams = $event_filter ? "&event=".urlencode($event_filter) : "";
                    ?>
                    <tr>
                        <td>
                            <span class="name"><?php echo htmlspecialchars($row['lead_name']); ?></span>
                            <span class="phone"><?php echo htmlspecialchars($row['lead_phone']); ?></span><br>
                            <span class="team">Team: <?php echo htmlspecialchars($row['team_name'] ?: 'Solo'); ?></span>
                        </td>
                        <td>
                            <span style="font-size:0.75rem;"><?php echo htmlspecialchars($row['college'] ?? 'N/A'); ?></span><br>
                            <span style="color:var(--dim); font-size:0.7rem;"><?php echo htmlspecialchars($row['lead_email'] ?? 'N/A'); ?></span>
                        </td>
                        <td>
                            <span class="ev-name"><?php echo htmlspecialchars($row['event_name']); ?></span>
                            <span class="ev-fee">Fee: ₹<?php echo number_format($row['total_fee']); ?></span>
                            <span class="ev-mem">Members: <?php echo htmlspecialchars($row['additional_members'] ?: 'None'); ?></span>
                        </td>
                        <td>
                            <code style="font-size:0.7rem; color:var(--primary);"><?php echo htmlspecialchars($row['utr'] ?: 'NO UTR'); ?></code><br>
                            <span class="badge <?php echo $isV ? 'verified' : 'pending'; ?>"><?php echo strtoupper($row['payment_status'] ?: 'PENDING'); ?></span>
                        </td>
                        <td>
                            <div class="actions">
                                <?php if(!$isV): ?>
                                    <a href="?toggle_payment=Verified&id=<?php echo $row['id']; ?><?php echo $filterParams; ?>" class="btn btn-v">VERIFY</a>
                                <?php else: ?>
                                    <a href="?toggle_payment=Pending&id=<?php echo $row['id']; ?><?php echo $filterParams; ?>" class="btn btn-r" onclick="return confirm('Revoke?')">REVOKE</a>
                                    <a href="<?php echo $wa_url; ?>" target="_blank" class="btn-wa">
                                        <svg width="14" height="14" fill="currentColor" viewBox="0 0 24 24"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766 0-3.18-2.587-5.771-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.512-2.96-2.626-.087-.114-.694-.922-.694-1.758 0-.837.434-1.246.587-1.412.144-.17.315-.21.424-.21.109 0 .21.002.302.007.098.005.23-.037.35.25.132.317.456 1.112.496 1.192.04.08.066.17.013.272-.053.102-.08.175-.162.27-.08.093-.173.205-.246.27-.087.08-.178.166-.076.337.103.17.458.753.985 1.22.68.606 1.254.796 1.431.884.178.088.283.074.388-.047.106-.123.456-.532.578-.714.122-.182.245-.153.413-.091.166.062 1.06.502 1.242.593.182.091.303.136.348.215.045.079.045.457-.1.862z"/></svg>
                                    </a>
                                <?php endif; ?>
                                <a href="?delete_reg_id=<?php echo $row['id']; ?><?php echo $filterParams; ?>" class="btn btn-d" onclick="return confirm('Delete?')">X</a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; else: ?>
                    <tr><td colspan="5" style="text-align:center; padding:50px; color:var(--dim);">No records found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div style="text-align:center; margin-top:30px; margin-bottom:50px;">
            <a href="?export=csv&view=<?php echo $view; ?><?php echo $event_filter ? '&event='.urlencode($event_filter) : ''; ?>" class="tab" style="border-color:var(--primary); color:var(--primary);">
                DOWNLOAD <?php echo $event_filter ? strtoupper(htmlspecialchars($event_filter)) : 'ALL'; ?> CSV REPORT
            </a>
        </div>
    </div>
<?php endif; ?>

</body>
</html>
<?php $conn->close(); ?>
