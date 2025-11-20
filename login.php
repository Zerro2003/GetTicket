<?php
require_once 'config.php';

// If session is not started, start it
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// If user is already logged in, redirect them
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    if ($_SESSION['role'] === 'organizer') {
        header("location: admin/index.php");
    } else {
        header("location: index.php");
    }
    exit;
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = 'Username and password are required.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, username, password_hash, role FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                // Password is correct, start a new session
                session_regenerate_id(true);
                $_SESSION['loggedin'] = true;
                $_SESSION['id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];

                // Redirect to the appropriate dashboard
                if ($user['role'] == 'organizer') {
                    header("location: admin/index.php");
                } else {
                    header("location: index.php");
                }
                exit;
            } else {
                $error = 'Invalid username or password.';
            }
        } catch (PDOException $e) {
            // In a real app, you'd log this error, not show it to the user
            $error = "A database error occurred. Please try again later.";
        }
    }
}

$pageTitle = "Login";
include 'partials/header.php';
?>

<div class="auth-container" style="display: flex; justify-content: center; align-items: center; min-height: 80vh;">
    <div class="glass-card auth-card" style="width: 100%; max-width: 450px; padding: 3rem;">
        <div style="text-align: center; margin-bottom: 2rem;">
            <div style="width: 60px; height: 60px; background: var(--gradient-primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem; box-shadow: 0 10px 20px rgba(99, 102, 241, 0.3);">
                <i class="fas fa-user" style="font-size: 1.5rem; color: white;"></i>
            </div>
            <h2 style="font-size: 2rem; margin-bottom: 0.5rem;">Welcome Back!</h2>
            <p class="auth-subtitle" style="color: var(--text-secondary);">Login to manage your events or tickets.</p>
        </div>
        
        <?php if ($error): ?>
            <div class="message error" style="margin-bottom: 1.5rem; text-align: center;">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="post" class="auth-form">
            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label for="username" style="display: block; margin-bottom: 0.5rem; color: var(--text-secondary);">Username</label>
                <div style="position: relative;">
                    <i class="fas fa-user" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-light);"></i>
                    <input type="text" id="username" name="username" placeholder="e.g., john.doe" required style="width: 100%; padding: 0.8rem 1rem 0.8rem 2.5rem; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 8px; color: var(--text-primary);">
                </div>
            </div>
            <div class="form-group" style="margin-bottom: 2rem;">
                <label for="password" style="display: block; margin-bottom: 0.5rem; color: var(--text-secondary);">Password</label>
                <div style="position: relative;">
                    <i class="fas fa-lock" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-light);"></i>
                    <input type="password" id="password" name="password" placeholder="Your secure password" required style="width: 100%; padding: 0.8rem 1rem 0.8rem 2.5rem; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 8px; color: var(--text-primary);">
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-block" style="width: 100%; justify-content: center; padding: 1rem;">
                Login <i class="fas fa-arrow-right" style="margin-left: 0.5rem;"></i>
            </button>
        </form>
        <div class="auth-footer" style="text-align: center; margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid rgba(255, 255, 255, 0.1);">
            <p style="color: var(--text-secondary);">Don't have an account? <a href="register.php" style="color: var(--primary-color); font-weight: 600;">Register here</a></p>
        </div>
    </div>
</div>

<?php include 'partials/footer.php'; ?>

