<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - Plant Sphere Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg dashboard-nav shadow-sm">
        <div class="container">
            <a class="navbar-brand text-white fw-bold" href="#">PlantSphere</a>
            <div class="d-flex">
                <span class="navbar-text text-white me-3">
                    Welcome, <?php echo htmlspecialchars($_SESSION['user']['firstname']); ?>!
                </span>
                <a href="index.php?action=logout" class="btn btn-outline-light btn-sm">Logout</a>
            </div>
        </div>
    </nav>

    <main class="container welcome-section">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <?php if(isset($success)): ?>
                    <div class="alert alert-success shadow-sm mb-4" role="alert">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-check-circle-fill me-2" viewBox="0 0 16 16">
                            <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
                        </svg>
                        <?php echo $success; ?>
                    </div>
                <?php endif; ?>

                <div class="welcome-card text-center">
                    <div class="mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="#2d5a27" class="bi bi-tree" viewBox="0 0 16 16">
                            <path d="M8.416.223a.5.5 0 0 0-.832 0l-3 4.5A.5.5 0 0 0 5 5.5h.382l-2.486 3.73a.5.5 0 0 0 .416.77h1.124l-2.14 3.21a.5.5 0 0 0 .416.77h11.416a.5.5 0 0 0 .416-.77l-2.14-3.21h1.124a.5.5 0 0 0 .416-.77L10.618 5.5H11a.5.5 0 0 0 .416-.77l-3-4.5zM6.017 5h3.966L8 2.019 6.017 5zm-1.242 4h6.45L8 5.019 4.775 9zM3.535 13h8.93L8 9.019 3.535 13zM8 14c.966 0 1.75-.784 1.75-1.75H6.25c0 .966.784 1.75 1.75 1.75z"/>
                        </svg>
                    </div>
                    <h1 class="display-5 fw-bold mb-3">Hello, <?php echo htmlspecialchars($_SESSION['user']['firstname'] . ' ' . $_SESSION['user']['lastname']); ?>!</h1>
                    <p class="lead text-muted mb-4">You have successfully accessed the Plant Sphere Capstone Project system. This dashboard serves as the foundation for your inventory, synthesis, and management modules.</p>
                    
                    <div class="row mt-5 text-start">
                        <div class="col-md-4 mb-3">
                            <div class="p-3 border rounded shadow-sm bg-white">
                                <h5 class="fw-bold">Inventory</h5>
                                <p class="small text-muted">Manage your plant collection and resources.</p>
                                <span class="badge bg-success">Ready</span>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="p-3 border rounded shadow-sm bg-white">
                                <h5 class="fw-bold">Synthesis</h5>
                                <p class="small text-muted">Online system for botanical synthesis.</p>
                                <span class="badge bg-secondary">Coming Soon</span>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="p-3 border rounded shadow-sm bg-white">
                                <h5 class="fw-bold">Reports</h5>
                                <p class="small text-muted">Generate detailed analytical reports.</p>
                                <span class="badge bg-secondary">Coming Soon</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="text-center py-4 text-muted">
        &copy; <?php echo date('Y'); ?> Plant Sphere Capstone Project. All rights reserved.
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
