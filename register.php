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
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'];

    if (empty($username) || empty($email) || empty($password) || empty($role)) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif (!in_array($role, ['organizer', 'regular'])) {
        $error = 'Invalid role selected.';
    } else {
        try {
            // Check if username or email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->fetch()) {
                $error = 'Username or email is already taken.';
            } else {
                // Hash the password
                $password_hash = password_hash($password, PASSWORD_DEFAULT);

                // Insert new user
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)");
                if ($stmt->execute([$username, $email, $password_hash, $role])) {
                    $success = "Registration successful! You can now <a href='login.php'>login</a>.";
                } else {
                    $error = 'Something went wrong. Please try again later.';
                }
            }
        } catch (PDOException $e) {
            $error = "A database error occurred. Please try again later.";
        }
    }
}

$pageTitle = "Register";
include 'partials/header.php';
?>

<div class="auth-container" style="display: flex; justify-content: center; align-items: center; min-height: 80vh;">
    <div class="glass-card auth-card" style="width: 100%; max-width: 500px; padding: 3rem;">
        <div style="text-align: center; margin-bottom: 2rem;">
            <div style="width: 60px; height: 60px; background: var(--gradient-primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem; box-shadow: 0 10px 20px rgba(99, 102, 241, 0.3);">
                <i class="fas fa-user-plus" style="font-size: 1.5rem; color: white;"></i>
            </div>
            <h2 style="font-size: 2rem; margin-bottom: 0.5rem;">Create Account</h2>
            <p class="auth-subtitle" style="color: var(--text-secondary);">Join our community of event-goers and organizers.</p>
        </div>

        <?php if ($error): ?>
            <div class="message error" style="margin-bottom: 1.5rem; text-align: center;">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="message success" style="margin-bottom: 1.5rem; text-align: center;">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            </div>
        <?php else: ?>
            <form action="register.php" method="post" class="auth-form">
                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label for="username" style="display: block; margin-bottom: 0.5rem; color: var(--text-secondary);">Username</label>
                    <div style="position: relative;">
                        <i class="fas fa-user" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-light);"></i>
                        <input type="text" id="username" name="username" placeholder="e.g., john.doe" required style="width: 100%; padding: 0.8rem 1rem 0.8rem 2.5rem; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 8px; color: var(--text-primary);">
                    </div>
                </div>
                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label for="email" style="display: block; margin-bottom: 0.5rem; color: var(--text-secondary);">Email</label>
                    <div style="position: relative;">
                        <i class="fas fa-envelope" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-light);"></i>
                        <input type="email" id="email" name="email" placeholder="you@example.com" required style="width: 100%; padding: 0.8rem 1rem 0.8rem 2.5rem; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 8px; color: var(--text-primary);">
                    </div>
                </div>
                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label for="password" style="display: block; margin-bottom: 0.5rem; color: var(--text-secondary);">Password</label>
                    <div style="position: relative;">
                        <i class="fas fa-lock" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-light);"></i>
                        <input type="password" id="password" name="password" placeholder="Min. 8 characters" required style="width: 100%; padding: 0.8rem 1rem 0.8rem 2.5rem; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 8px; color: var(--text-primary);">
                    </div>
                </div>
                <div class="form-group" style="margin-bottom: 2rem;">
                    <label for="role" style="display: block; margin-bottom: 0.5rem; color: var(--text-secondary);">I want to...</label>
                    <div style="position: relative;">
                        <i class="fas fa-users-cog" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-light);"></i>
                        <select id="role" name="role" class="form-control" style="width: 100%; padding: 0.8rem 1rem 0.8rem 2.5rem; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 8px; color: var(--text-primary);">
                            <option value="regular" selected>Attend Events</option>
                            <option value="organizer">Organize Events</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-block" style="width: 100%; justify-content: center; padding: 1rem;">
                    Create Account <i class="fas fa-arrow-right" style="margin-left: 0.5rem;"></i>
                </button>
            </form>
        <?php endif; ?>
        <div class="auth-footer" style="text-align: center; margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid rgba(255, 255, 255, 0.1);">
            <p style="color: var(--text-secondary);">Already have an account? <a href="login.php" style="color: var(--primary-color); font-weight: 600;">Login here</a></p>
        </div>
    </div>
</div>

<?php include 'partials/footer.php'; ?>

