<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/mailer_config.php';

$pageTitle = "Become an Instructor";
$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $TEMP_UPLOAD_DIR = __DIR__ . '/storage/temp_uploads/';
    $FINAL_UPLOAD_DIR = __DIR__ . '/uploads/instructors/';
    $MAX_IMAGE_BYTES = 2 * 1024 * 1024;
    $MAX_PDF_BYTES = 5 * 1024 * 1024;
    $ALLOWED_IMAGE_MIMES = ['image/jpeg', 'image/png', 'image/webp'];
    $ALLOWED_PDF_MIMES = ['application/pdf'];

    if (!is_dir($TEMP_UPLOAD_DIR))
        mkdir($TEMP_UPLOAD_DIR, 0755, true);

    function saveFileToTemp($fieldName, $tempDir, &$errorOut)
    {
        if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
            $errorOut = "File '$fieldName' is missing or upload error.";
            return false;
        }

        $origName = $_FILES[$fieldName]['name'];
        $ext = pathinfo($origName, PATHINFO_EXTENSION);
        $unique = bin2hex(random_bytes(12)) . '_' . time() . '.' . strtolower($ext);
        $target = $tempDir . $unique;

        if (!move_uploaded_file($_FILES[$fieldName]['tmp_name'], $target)) {
            $errorOut = "Failed to save uploaded file ($fieldName).";
            return false;
        }
        return $unique;
    }

    $data = [
        "first_name" => trim($_POST['first_name']),
        "last_name" => trim($_POST['last_name']),
        "email" => filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL),
        "password" => password_hash($_POST['password'], PASSWORD_DEFAULT),
        "bio" => trim($_POST['bio']),
        "experience" => trim($_POST['experience']),
        "expertise_area" => trim($_POST['expertise_area'])
    ];

    if (!$data["email"]) {
        $error = "Please enter a valid email address.";
    }

    $err = '';
    $tempSaved = [
        'profile_picture' => '',
        'qualification' => '',
        'id_proof' => '',
        'demo_video' => ''
    ];

    $finfo = finfo_open(FILEINFO_MIME_TYPE);

    // ✅ Profile Picture
    if (!$error) {
        if ($_FILES['profile_picture']['error'] !== UPLOAD_ERR_OK) {
            $error = "Profile picture is required.";
        } elseif ($_FILES['profile_picture']['size'] > $MAX_IMAGE_BYTES) {
            $error = "Profile picture too large (max 2 MB).";
        } elseif (!in_array(finfo_file($finfo, $_FILES['profile_picture']['tmp_name']), $ALLOWED_IMAGE_MIMES)) {
            $error = "Invalid profile picture format.";
        } else {
            $tempSaved['profile_picture'] = saveFileToTemp('profile_picture', $TEMP_UPLOAD_DIR, $err);
        }
    }

    // ✅ Qualification PDF
    if (!$error) {
        if ($_FILES['qualification']['error'] !== UPLOAD_ERR_OK) {
            $error = "Qualification PDF is required.";
        } elseif ($_FILES['qualification']['size'] > $MAX_PDF_BYTES) {
            $error = "Qualification PDF too large.";
        } elseif (!in_array(finfo_file($finfo, $_FILES['qualification']['tmp_name']), $ALLOWED_PDF_MIMES)) {
            $error = "Qualification must be a PDF.";
        } else {
            $tempSaved['qualification'] = saveFileToTemp('qualification', $TEMP_UPLOAD_DIR, $err);
        }
    }

    // ✅ ID Proof PDF
    if (!$error) {
        if ($_FILES['id_proof']['error'] !== UPLOAD_ERR_OK) {
            $error = "ID proof PDF is required.";
        } elseif ($_FILES['id_proof']['size'] > $MAX_PDF_BYTES) {
            $error = "ID proof PDF too large.";
        } elseif (!in_array(finfo_file($finfo, $_FILES['id_proof']['tmp_name']), $ALLOWED_PDF_MIMES)) {
            $error = "ID proof must be a PDF.";
        } else {
            $tempSaved['id_proof'] = saveFileToTemp('id_proof', $TEMP_UPLOAD_DIR, $err);
        }
    }

    // ✅ Demo Teaching Video (10 min min, MP4 only)
    if (!$error) {
        if ($_FILES['demo_video']['error'] !== UPLOAD_ERR_OK) {
            $error = "Demo teaching video is required.";
        } else {
            $videoTmp = $_FILES['demo_video']['tmp_name'];
            $videoMime = finfo_file($finfo, $videoTmp);
            $allowedVideoMimes = ['video/mp4'];

            if (!in_array($videoMime, $allowedVideoMimes)) {
                $error = "Demo video must be in MP4 format.";
            } else {
                // Use ffprobe to get duration
                $cmd = "ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 " . escapeshellarg($videoTmp);
                $duration = floatval(shell_exec($cmd));

                if ($duration < 600) { // 600 seconds = 10 minutes
                    $error = "Demo video must be at least 10 minutes long.";
                } else {
                    $tempSaved['demo_video'] = saveFileToTemp('demo_video', $TEMP_UPLOAD_DIR, $err);
                }
            }
        }
    }

    finfo_close($finfo);

    // ✅ If error → delete temp files
    if ($error) {
        foreach ($tempSaved as $f) {
            if ($f && file_exists($TEMP_UPLOAD_DIR . $f))
                @unlink($TEMP_UPLOAD_DIR . $f);
        }

    } else {
        // ✅ All good → save to session and send OTP
        $data['profile_picture'] = $tempSaved['profile_picture'];
        $data['qualification'] = $tempSaved['qualification'];
        $data['id_proof'] = $tempSaved['id_proof'];
        $data['demo_video'] = $tempSaved['demo_video'];

        $_SESSION['temp_instructor'] = $data;

        // Generate OTP
        $_SESSION['instructor_otp'] = rand(100000, 999999);
        sendOTP($data['email'], $_SESSION['instructor_otp']);

        header("Location: verify_otp_instructor.php");
        exit;
    }
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduTech - <?= htmlspecialchars($pageTitle) ?></title>

    <!-- Bootstrap & FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <style>
        .submit-btn {
            background: linear-gradient(135deg, #007bff, #0056b3);
            border: none;
            width: 100%;
            border-radius: 10px;
            color: #ffffff !important;
            font-weight: 600;
            font-size: 16px;
            padding: 12px 0;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 15px rgba(0, 123, 255, 0.4);
            transition: all 0.3s ease-in-out;
            position: relative;
            overflow: hidden;
        }

        .main-card {
            background: linear-gradient(#007bff, #0056b3);
        }

        .submit-btn::before {
            content: "";
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(120deg, rgba(255, 255, 255, 0.4), transparent);
            transition: all 0.6s ease;
        }

        .submit-btn:hover::before {
            left: 100%;
        }

        .submit-btn:hover {
            color: #ffffff !important;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 123, 255, 0.6);
            background: linear-gradient(135deg, #0056b3, #00408a);
        }

        .submit-btn:active {
            transform: translateY(1px);
            box-shadow: 0 2px 10px rgba(0, 86, 179, 0.4);
        }

        /* Hide spinner in Chrome, Safari, Edge */
        input[type="number"]::-webkit-outer-spin-button,
        input[type="number"]::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        /* Hide spinner in Firefox */
        input[type="number"] {
            -moz-appearance: textfield;
        }
    </style>
</head>

<body class="bg-light">
    <div class="container py-5">
        <div class="card shadow-lg border-0 rounded-4">
            <div class="card-header main-card text-white text-center py-3 rounded-top-4">
                <i class="fas fa-chalkboard-teacher fa-3x mb-2"></i>
                <h4 class="mb-0">Become an Instructor</h4>
            </div>
            <div class="card-body p-4">

                <?php if ($success): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                <?php elseif ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <div class="row">

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">First Name</label>
                            <input type="text" name="first_name" class="form-control" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Last Name</label>
                            <input type="text" name="last_name" class="form-control" required>
                        </div>

                        <div class="col-12 mb-3">
                            <label class="form-label fw-semibold">Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>

                        <div class="col-12 mb-3">
                            <div id="passwordAlert"></div>
                            <label class="form-label fw-semibold">Password</label>
                            <input type="password" id="password" name="password" class="form-control" required>
                            <small id="passwordHelp" class="text-danger fw-semibold"></small>
                        </div>

                        <div class="col-12 mb-3">
                            <label class="form-label fw-semibold">Bio</label>
                            <textarea name="bio" class="form-control" rows="3"
                                placeholder="Write something about yourself..." required></textarea>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Experience (in years)</label>
                            <input type="number" min="0" max="50" name="experience" class="form-control" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Expertise Area</label>
                            <input type="text" name="expertise_area" class="form-control"
                                placeholder="e.g., Web Development, Data Science" required>
                        </div>

                        <div class="col-12 mb-3">
                            <label class="form-label fw-semibold">Demo Teaching Video</label>
                            <input type="file" name="demo_video" class="form-control" accept="video/mp4" required>
                            <small class="text-muted">Upload a demo video of minimum 10 minutes</small>
                        </div>


                        <div class="col-12 mb-3">
                            <label class="form-label fw-semibold">Qualification (PDF only)</label>
                            <input type="file" name="qualification" class="form-control" accept="application/pdf"
                                required>
                            <small class="text-muted">Upload your qualification certificate in PDF format.</small>
                        </div>

                        <div class="col-12 mb-3">
                            <label class="form-label fw-semibold">ID Proof (PDF only)</label>
                            <input type="file" name="id_proof" class="form-control" accept="application/pdf" required>
                            <small class="text-muted">Upload Aadhar, PAN, or any valid ID proof in PDF format.</small>
                        </div>

                        <div class="col-12 mb-3">
                            <label class="form-label fw-semibold">Profile Picture</label>
                            <input type="file" name="profile_picture" class="form-control" accept="image/*" required>
                            <small class="text-muted">Upload your profile photo (JPG, PNG, etc.)</small>
                        </div>

                        <div class="col-12">
                            <button type="submit"
                                class="btn btn-primary py-3 fw-semibold d-flex align-items-center justify-content-center gap-2 submit-btn">
                                <i class="fas fa-chalkboard-teacher fs-5"></i>
                                Submit Instructor Request
                            </button>
                        </div>

                    </div>
                </form>

            </div>
        </div>
    </div>

    <script>
        const password = document.getElementById("password");
        const msg = document.getElementById("passwordHelp");
        const form = document.querySelector("form");

        // Strong password regex
        const strongRegex =
            /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*#?&])[A-Za-z\d@$!%*#?&]{8,}$/;

        // Live validation
        password.addEventListener("input", function () {
            if (password.value.length === 0) {
                msg.textContent = "";
            } else if (!strongRegex.test(password.value)) {
                msg.textContent =
                    "Password must be 8+ characters, include uppercase, lowercase, number, and special character.";
                msg.classList.add("text-danger");
                msg.classList.remove("text-success");
            } else {
                msg.textContent = "Strong password ✓";
                msg.classList.remove("text-danger");
                msg.classList.add("text-success");
            }
        });

        form.addEventListener("submit", function (e) {
            if (!strongRegex.test(password.value)) {
                e.preventDefault(); // stop submit

                form.addEventListener("submit", function (e) {
                    if (!strongRegex.test(password.value)) {
                        e.preventDefault(); // stop submit

                        document.getElementById("passwordAlert").innerHTML = `
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>Weak Password!</strong> Please enter a strong password before submitting.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;

                        password.focus();
                    }
                });


                password.focus();
            }
        });
    </script>

</body>

</html>