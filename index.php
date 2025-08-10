<?php
// index.php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/utils.php';

$pdo = getDbConnection();
$stmt = $pdo->query("SELECT * FROM domains ORDER BY updatedAt DESC");
$domains = $stmt->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['domain'])) {
        $domain = trim($_POST['domain']);
        if (isValidDomain($domain)) {
            $normalizedDomain = normalizeDomain($domain);
            header('Location: add_domain.php?domain=' . urlencode($normalizedDomain));
            exit;
        } else {
            header('Location: index.php?error=' . urlencode('Please enter a valid domain name'));
            exit;
        }
    }
}

// Include header
include_once __DIR__ . '/includes/header.php';

// Display error message if present
if (isset($_GET['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($_GET['error']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="container">
    <!-- Stats Cards -->
    <div class="row mb-4 g-4">
        <div class="col-md-4">
            <div class="card bg-primary bg-opacity-10 border-0 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase text-muted mb-1">Total Domains</h6>
                            <h2 class="mb-0"><?php echo count($domains); ?></h2>
                        </div>
                        <div class="icon-shape bg-primary text-white rounded-circle p-3">
                            <i class="fas fa-globe fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success bg-opacity-10 border-0 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase text-muted mb-1">Available</h6>
                            <h2 class="mb-0"><?php echo count(array_filter($domains, fn($d) => $d['available'])); ?></h2>
                        </div>
                        <div class="icon-shape bg-success text-white rounded-circle p-3">
                            <i class="fas fa-check-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-info bg-opacity-10 border-0 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase text-muted mb-1">Expiring Soon</h6>
                            <h2 class="mb-0"><?php 
                                $expiringSoon = array_filter($domains, function($d) {
                                    if (!$d['expiryDate']) return false;
                                    $expiry = new DateTime($d['expiryDate']);
                                    $now = new DateTime();
                                    $diff = $now->diff($expiry);
                                    return $diff->days <= 30 && $diff->invert === 0;
                                });
                                echo count($expiringSoon);
                            ?></h2>
                        </div>
                        <div class="icon-shape bg-info text-white rounded-circle p-3">
                            <i class="fas fa-clock fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Domain Card -->
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-body p-4">
            <h5 class="card-title mb-4">Check Domain Availability</h5>
            <form method="post" class="row g-3">
                <div class="col-md-10">
                    <div class="input-group">
                        <span class="input-group-text bg-dark border-secondary text-muted">https://</span>
                        <input type="text" 
                               name="domain" 
                               class="form-control form-control-lg" 
                               placeholder="example.com" 
                               required
                               pattern="^([a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,}$"
                               title="Please enter a valid domain name">
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-lg w-100">
                        <i class="fas fa-search me-2"></i> Check
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Domains List -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="bg-dark bg-opacity-25">
                        <tr>
                            <th class="ps-4">Domain</th>
                            <th>Status</th>
                            <th>Expiry Date</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($domains as $domain): ?>
                            <tr>
                                <td><?= htmlspecialchars($domain['name']) ?></td>
                                <td>
                                    <span class="badge bg-<?= $domain['available'] ? 'success' : 'danger' ?>">
                                        <?= $domain['available'] ? 'Available' : 'Registered' ?>
                                    </span>
                                </td>
                                <td>
                                    <?= $domain['expiryDate'] ? date('Y-m-d', strtotime($domain['expiryDate'])) : 'N/A' ?>
                                </td>
                                <td>
                                    <a href="delete_domain.php?id=<?= $domain['id'] ?>" 
                                       class="btn btn-sm btn-danger" 
                                       onclick="return confirm('Are you sure?')">Delete</a>
                                    <a href="add_domain.php?domain=<?= urlencode($domain['name']) ?>&check=1" 
                                       class="btn btn-sm btn-info">Recheck</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/includes/footer.php'; ?>