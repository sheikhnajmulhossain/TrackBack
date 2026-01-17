<?php
session_start();

// Include database connection and functions
include '../Model/database.php';
include '../Model/functions.php';

// Initialize error and success messages
$error = '';
$success = '';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']) ? true : false;
    
    // Validate input
    if (empty($email) || empty($password)) {
        $error = 'Email and password are required';
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format';
    } else {
        // Sanitize email
        $email = $conn->real_escape_string($email);
        
        // Query to find user by email
        $query = "SELECT * FROM users WHERE email = '$email' LIMIT 1";
        $result = $conn->query($query);
        
        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Verify password
            if (password_verify($password, $user['password_hash'])) {
                
                // Check account status
                if ($user['account_status'] === 'banned') {
                    $error = 'Your account has been banned. Please contact administrator.';
                } else if ($user['account_status'] === 'pending') {
                    $error = 'Your account is pending approval. Please wait for admin approval.';
                } else if ($user['account_status'] === 'active') {
                    // Login successful - set session variables
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['student_id'] = $user['student_id'];
                    $_SESSION['name'] = $user['name'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['user_type'] = $user['user_type'];
                    $_SESSION['university'] = $user['university'];
                    
                    // Set remember me cookie if checked (7 days)
                    if ($remember) {
                        setcookie('user_email', $user['email'], time() + (7 * 24 * 60 * 60), '/');
                    }
                    
                    // Log login action
                    $log_query = "INSERT INTO audit_logs (user_id, action_type, action_details, ip_address) 
                                  VALUES ('{$user['user_id']}', 'LOGIN', 'User logged in', '{$_SERVER['REMOTE_ADDR']}')";
                    $conn->query($log_query);
                    
                    // Redirect to dashboard based on user type
                    if ($user['user_type'] === 'admin') {
                        header('Location: ../View/admin-dashboard.html');
                    } else if ($user['user_type'] === 'moderator') {
                        header('Location: ../View/moderator-dashboard.html');
                    } else {
                        header('Location: ../View/user-dashboard.html');
                    }
                    exit;
                }
            } else {
                $error = 'Invalid email or password';
            }
        } else {
            $error = 'Invalid email or password';
        }
    }
}

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_type'] === 'admin') {
        header('Location: ../View/admin-dashboard.html');
    } else if ($_SESSION['user_type'] === 'moderator') {
        header('Location: ../View/moderator-dashboard.html');
    } else {
        header('Location: ../View/user-dashboard.html');
    }
    exit;
}

// Close database connection
$conn->close();
?>

<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login - TrackBack</title>

  <link rel="icon" href="../View/img/logo/favicon-circle.png" type="image/png" />

  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />

  <link
    href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;600;700&family=Poppins:wght@400;500;600;700&display=swap"
    rel="stylesheet" />

  <link rel="stylesheet" href="../View/index.css" />
</head>

<body>

  <!-- Header -->
  <div id="header-placeholder"></div>
  <script>
    fetch("../View/header.html")
      .then(res => res.text())
      .then(data => {
        document.getElementById("header-placeholder").innerHTML = data;
      });
  </script>


  <main>
    <section class="auth">
      <div class="container auth__grid">

   
        <div class="auth__info">
          <h1 class="auth__title">
            Welcome back to <span class="accent">TrackBack</span>
          </h1>
          <p class="auth__subtitle">
            Sign in to report lost items and browse found belongings securely.
          </p>

          <ul class="auth__list">
            <li>Post lost or found items</li>
            <li>Filter by university and category</li>
            <li>Private student-to-student chat</li>
          </ul>

          <div class="auth__badge">
            ✨ Trusted by AIUB, NSU, DU, BRAC &amp; more
          </div>
        </div>


        <div class="auth__card">
          <h2 class="auth-card__title">Login to your account</h2>
          <p class="auth-card__subtitle">Enter your credentials to continue</p>

          <!-- Display error message -->
          <?php if (!empty($error)): ?>
            <div class="alert alert-error" style="margin-bottom: 20px; padding: 12px; background-color: #fee; border: 1px solid #fcc; border-radius: 4px; color: #c33;">
              <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
            </div>
          <?php endif; ?>

          <!-- Display success message -->
          <?php if (!empty($success)): ?>
            <div class="alert alert-success" style="margin-bottom: 20px; padding: 12px; background-color: #efe; border: 1px solid #cfc; border-radius: 4px; color: #3c3;">
              <strong>Success:</strong> <?php echo htmlspecialchars($success); ?>
            </div>
          <?php endif; ?>

          <form class="auth-form" action="login.php" method="post">

            <div class="form-field">
              <label>Email</label>
              <input type="email" name="email" placeholder="name@student.aiub.edu" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
            </div>

            <div class="form-field">
              <div class="form-field__row">
                <label>Password</label>
                <a href="#" class="form-link">Forgot password?</a>
              </div>
              <div class="password-input-wrapper">
                <input type="password" id="passwordInput" name="password" placeholder="Enter your password" required>
                <button type="button" class="password-toggle" id="passwordToggle">
                  <i class="fas fa-eye"></i>
                </button>
              </div>
            </div>

            <div class="form-field form-field--inline">
              <label class="checkbox">
                <input type="checkbox" name="remember" <?php echo (isset($_POST['remember']) ? 'checked' : ''); ?>>
                <span>Keep me signed in</span>
              </label>
            </div>

            <button type="submit" class="btn btn--green btn--pill auth-form__btn">
              Login
            </button>

            <p class="auth-form__footer">
              Don't have an account?
              <a href="../View/registration.html" class="form-link">Sign up now</a>
            </p>
          </form>

          <div class="auth-card__meta">
            <small>
              By continuing, you agree to TrackBack's
              <a href="../View/terms.html">Terms</a> and
              <a href="../View/privacy.html">Privacy Policy</a>.
            </small>
          </div>
        </div>

      </div>
    </section>
  </main>

  <!-- Footer -->
  <div id="footer-placeholder"></div>
  <script>
    fetch("../View/footer.html")
      .then(res => res.text())
      .then(data => {
        document.getElementById("footer-placeholder").innerHTML = data;
      });
  </script>

  <script>
    const backToTop = document.getElementById("backToTop");

    function toggleBackToTop() {
      if (backToTop) {
        if (window.scrollY > 300) backToTop.classList.add("show");
        else backToTop.classList.remove("show");
      }
    }

    window.addEventListener("scroll", toggleBackToTop);
    toggleBackToTop();

    if (backToTop) {
      backToTop.addEventListener("click", () => {
        window.scrollTo({ top: 0, behavior: "smooth" });
      });
    }

    // Password visibility toggle
    const passwordInput = document.getElementById("passwordInput");
    const passwordToggle = document.getElementById("passwordToggle");

    if (passwordToggle && passwordInput) {
      passwordToggle.addEventListener("click", function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const icon = this.querySelector("i");
        
        if (passwordInput.type === "password") {
          passwordInput.type = "text";
          icon.classList.remove("fa-eye");
          icon.classList.add("fa-eye-slash");
          this.classList.add("active");
        } else {
          passwordInput.type = "password";
          icon.classList.remove("fa-eye-slash");
          icon.classList.add("fa-eye");
          this.classList.remove("active");
        }
      });
    }
  </script>

</body>
</html>
