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
    $fullname = $_POST['fullname'] ?? '';
    $student_id = $_POST['student_id'] ?? '';
    $email = $_POST['email'] ?? '';
    $university = $_POST['university'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($fullname) || empty($student_id) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'All fields are required';
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format';
    } else if (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long';
    } else if ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } else {
        // Sanitize inputs
        $fullname = $conn->real_escape_string($fullname);
        $student_id = $conn->real_escape_string($student_id);
        $email = $conn->real_escape_string($email);
        $university = $conn->real_escape_string($university);
        
        // Check if email already exists
        $check_email = "SELECT user_id FROM users WHERE email = '$email' LIMIT 1";
        $email_result = $conn->query($check_email);
        
        if ($email_result && $email_result->num_rows > 0) {
            $error = 'Email already registered. Please login instead.';
        } else {
            // Check if student_id already exists
            $check_student = "SELECT user_id FROM users WHERE student_id = '$student_id' LIMIT 1";
            $student_result = $conn->query($check_student);
            
            if ($student_result && $student_result->num_rows > 0) {
                $error = 'Student ID already registered. Please login instead.';
            } else {
                // Hash password
                $password_hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                
                // Insert user into database
                $insert_query = "INSERT INTO users (student_id, name, email, password_hash, user_type, university, account_status) 
                                VALUES ('$student_id', '$fullname', '$email', '$password_hash', 'student', '$university', 'pending')";
                
                if ($conn->query($insert_query)) {
                    $success = 'Registration successful! Your account is pending admin approval. You will receive an email once approved.';
                    // Clear form fields after successful registration
                    $fullname = '';
                    $student_id = '';
                    $email = '';
                    $university = '';
                    $password = '';
                    $confirm_password = '';
                } else {
                    $error = 'Registration failed. Please try again. Error: ' . $conn->error;
                }
            }
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

// Close database connection (but keep it open for the HTML display)
// $conn->close();
?>

<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Create Account - TrackBack</title>

  <link rel="icon" href="../View/img/logo/favicon-circle.png" type="image/png" />

  <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />

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
            Create your <span class="accent">TrackBack</span> account
          </h1>

          <p class="auth__subtitle">
            Join students across universities to securely post lost and found items.
          </p>

          <ul class="auth__list">
            <li>Report lost belongings</li>
            <li>Post found items</li>
            <li>Message with other students</li>
            <li>Verified university accounts</li>
          </ul>

          <div class="auth__badge">
            🎓 Trusted by AIUB, NSU, DU, BRAC students
          </div>
        </div>

        <div class="auth__card">
          <h2 class="auth-card__title">Student Registration</h2>
          <p class="auth-card__subtitle">Fill the form to create your account</p>

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

          <form class="auth-form" action="register.php" method="post">

            <div class="form-field">
              <label>Full Name</label>
              <input type="text" name="fullname" placeholder="Your Name" value="<?php echo htmlspecialchars($fullname ?? ''); ?>" required>
            </div>

            <div class="auth-form__row">
              <div class="form-field">
                <label>University</label>
                <select name="university" required>
                  <option value="" selected>Select University</option>
                  <option value="AIUB" <?php echo ($university === 'AIUB' ? 'selected' : ''); ?>>AIUB</option>
                  <option value="NSU" <?php echo ($university === 'NSU' ? 'selected' : ''); ?>>NSU</option>
                  <option value="DU" <?php echo ($university === 'DU' ? 'selected' : ''); ?>>DU</option>
                  <option value="BRAC" <?php echo ($university === 'BRAC' ? 'selected' : ''); ?>>BRAC</option>
                </select>
              </div>

              <div class="form-field">
                <label>Student ID</label>
                <input type="text" name="student_id" placeholder="ID No." autocomplete="off" value="<?php echo htmlspecialchars($student_id ?? ''); ?>" required>
              </div>
            </div>

            <div class="form-field">
              <label>Email</label>
              <input type="email" name="email" placeholder="student@university.edu" value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
            </div>

            <div class="auth-form__row">
              <div class="form-field">
                <label>Password</label>
                <div class="password-input-wrapper">
                  <input type="password" name="password" id="passwordInput" placeholder="Create password" required>
                  <button type="button" class="password-toggle" id="passwordToggle" title="Show/Hide Password">
                    <i class="fas fa-eye"></i>
                  </button>
                </div>
              </div>

              <div class="form-field">
                <label>Confirm Password</label>
                <div class="password-input-wrapper">
                  <input type="password" name="confirm" id="confirmPasswordInput" placeholder="Re-enter password" required>
                  <button type="button" class="password-toggle" id="confirmPasswordToggle" title="Show/Hide Password">
                    <i class="fas fa-eye"></i>
                  </button>
                </div>
              </div>
            </div>

            <div class="form-field form-field--inline">
              <label class="checkbox">
                <input type="checkbox" name="agree" required>
                <span>
                  I agree to the
                  <a href="../View/terms.html" class="form-link">Terms Of Service</a>
                  &
                  <a href="../View/privacy.html" class="form-link">Privacy Policy</a>
                </span>
              </label>
            </div>

            <button type="submit" class="btn btn--green btn--pill auth-form__btn">
              Create Account
            </button>

            <p class="auth-form__footer">
              Already registered?
              <a href="login.php" class="form-link">Login</a>
            </p>

          </form>
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

    // Password visibility toggle for password field
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

    // Password visibility toggle for confirm password field
    const confirmPasswordInput = document.getElementById("confirmPasswordInput");
    const confirmPasswordToggle = document.getElementById("confirmPasswordToggle");

    if (confirmPasswordToggle && confirmPasswordInput) {
      confirmPasswordToggle.addEventListener("click", function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const icon = this.querySelector("i");
        
        if (confirmPasswordInput.type === "password") {
          confirmPasswordInput.type = "text";
          icon.classList.remove("fa-eye");
          icon.classList.add("fa-eye-slash");
          this.classList.add("active");
        } else {
          confirmPasswordInput.type = "password";
          icon.classList.remove("fa-eye-slash");
          icon.classList.add("fa-eye");
          this.classList.remove("active");
        }
      });
    }
  </script>

</body>
</html>
