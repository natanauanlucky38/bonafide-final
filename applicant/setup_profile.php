<?php
// setup_profile.php
include '../db.php';  // Include database connection

// Check if the user is logged in and is an applicant
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'APPLICANT') {
    header('Location: index.php');  // Redirect to login if not logged in
    exit();
}

// Handle profile form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fname = trim($_POST['fname']);
    $lname = trim($_POST['lname']);
    $age = (int)$_POST['age'];
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $education_level = $_POST['education_level'];
    $school_graduated = trim($_POST['school_graduated']);
    $year_graduated = trim($_POST['year_graduated']);
    $degree = ($education_level == 'TERTIARY' || $education_level == 'POSTGRADUATE') ? trim($_POST['degree']) : NULL;
    $linkedin_link = !empty($_POST['linkedin_link']) ? trim($_POST['linkedin_link']) : NULL;
    $facebook_link = !empty($_POST['facebook_link']) ? trim($_POST['facebook_link']) : NULL;
    $civil_status = $_POST['civil_status'];

    // Validate phone number format
    if (!preg_match('/^\+639\d{9}$/', $phone)) {
        echo "Invalid phone number format. It should start with +639 followed by 9 digits.";
        exit();
    }

    // Generate referral code
    function generateReferralCode($length = 8)
    {
        return strtoupper(substr(md5(time() . rand()), 0, $length));
    }

    // Auto-generate referral code
    $referral_code = generateReferralCode();

    $user_id = $_SESSION['user_id'];

    // Insert profile details into the `profiles` table
    $sql = "INSERT INTO profiles (user_id, fname, lname, age, phone, address, education_level, school_graduated, year_graduated, degree, linkedin_link, facebook_link, referral_code, civil_status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    prepare_and_execute($conn, $sql, 'ississssssssss', $user_id, $fname, $lname, $age, $phone, $address, $education_level, $school_graduated, $year_graduated, $degree, $linkedin_link, $facebook_link, $referral_code, $civil_status);

    // Redirect to dashboard after profile setup
    header('Location: dashboard.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Setup Profile</title>
    <link rel="stylesheet" href="applicant_styles.css"> <!-- Include your CSS styles here -->

    <script>
        // Script to show/hide degree field based on education level
        function toggleDegreeField() {
            var educationLevel = document.getElementById('education_level').value;
            var degreeField = document.getElementById('degree_field');
            if (educationLevel === 'TERTIARY' || educationLevel === 'POSTGRADUATE') {
                degreeField.style.display = 'block'; // Show degree field
            } else {
                degreeField.style.display = 'none'; // Hide degree field
            }
        }

        function validateForm() {
            var phone = document.forms["profileForm"]["phone"].value;
            var phonePattern = /^\+639\d{9}$/;

            if (!phonePattern.test(phone)) {
                alert("Invalid phone number format. It should start with +639 followed by 9 digits.");
                return false; // Prevent form submission
            }
            return true;
        }
    </script>
</head>

<body class="setup_profile-main-content">

    <div class="setup-profile-container">
        <h2>Setup Your Profile</h2>

        <!-- Added form opening tag with method="POST" -->
        <form name="profileForm" method="POST" action="setup_profile.php" onsubmit="return validateForm()">

            <!-- Personal Information Section -->
            <fieldset>
                <legend>Personal Information</legend>
                <input type="text" name="fname" placeholder="First Name" required><br>
                <input type="text" name="lname" placeholder="Last Name" required><br>
                <input type="number" name="age" placeholder="Age" required><br>

                <!-- Civil Status Selection -->
                <label for="civil_status">Civil Status:</label>
                <select name="civil_status" id="civil_status" required>
                    <option value="SINGLE">Single</option>
                    <option value="MARRIED">Married</option>
                    <option value="DIVORCED">Divorced</option>
                    <option value="WIDOWED">Widowed</option>
                </select><br>
            </fieldset>

            <!-- Contact Information Section -->
            <fieldset>
                <legend>Contact Information</legend>
                <input type="text" name="phone" id="phone" value="+639" maxlength="13" placeholder="Phone Number" required oninput="validatePhoneInput()"><br>
                <input type="text" name="address" placeholder="Address" required><br>
                <input type="text" name="linkedin_link" placeholder="LinkedIn Profile (Optional)"><br>
                <input type="text" name="facebook_link" placeholder="Facebook Profile (Optional)"><br>
            </fieldset>

            <!-- Education Information Section -->
            <fieldset>
                <legend>Education Information</legend>
                <label for="education_level">Education Level:</label>
                <select name="education_level" id="education_level" onchange="toggleDegreeField()" required>
                    <option value="PRIMARY">Primary</option>
                    <option value="SECONDARY">Secondary</option>
                    <option value="TERTIARY">Tertiary</option>
                    <option value="POSTGRADUATE">Postgraduate</option>
                </select><br>

                <input type="text" name="school_graduated" placeholder="School Graduated" required><br>
                <input type="text" name="year_graduated" placeholder="Year Graduated" required><br>

                <!-- Degree field, hidden initially, shown only for Tertiary or Postgraduate -->
                <div id="degree_field" style="display:none;">
                    <input type="text" name="degree" placeholder="Degree Obtained"><br>
                </div>
            </fieldset>

            <!-- Submit Button -->
            <button type="submit">Submit Profile</button>
        </form>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                var phoneInput = document.getElementById("phone");

                // Ensure the input starts with +639 and restrict the user from modifying it
                phoneInput.addEventListener("input", validatePhoneInput);

                function validatePhoneInput() {
                    if (!phoneInput.value.startsWith("+639")) {
                        phoneInput.value = "+639"; // Reset to +639 if modified
                    }
                    // Allow only numeric input after the prefix
                    phoneInput.value = phoneInput.value.slice(0, 4) + phoneInput.value.slice(4).replace(/\D/g, '');
                    // Limit to +639 plus 9 digits (13 characters total)
                    if (phoneInput.value.length > 13) {
                        phoneInput.value = phoneInput.value.slice(0, 13);
                    }
                }
            });
        </script>

    </div>
    <div style="height: 500px;"></div>

</body>
<?php include 'footer.php'; ?>

</html>