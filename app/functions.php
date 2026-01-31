<?php
// functions.php
require_once 'db.php';

// --- Function to insert application ---
function insert_application($pdo, $data) {
    $sql = "
        INSERT INTO scholarship_applications (
            first_name, last_name, email, phone, expected_graduation_year,
            gpa, institution_type, intended_school, intended_major,
            extracurricular, leadership, community_service,
            essay, recommender_name, recommender_email, recommender_relationship,
            financial_need, additional_information
        ) VALUES (
            :first_name, :last_name, :email, :phone, :expected_graduation_year,
            :gpa, :institution_type, :intended_school, :intended_major,
            :extracurricular, :leadership, :community_service,
            :essay, :recommender_name, :recommender_email, :recommender_relationship,
            :financial_need, :additional_information
        )
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':first_name' => $data['first_name'],
        ':last_name' => $data['last_name'],
        ':email' => $data['email'],
        ':phone' => $data['phone'],
        ':expected_graduation_year' => $data['expected_graduation_year'],
        ':gpa' => $data['gpa'],
        ':institution_type' => $data['institution_type'],
        ':intended_school' => $data['intended_school'],
        ':intended_major' => $data['intended_major'],
        ':extracurricular' => $data['extracurricular'],
        ':leadership' => $data['leadership'],
        ':community_service' => $data['community_service'],
        ':essay' => $data['essay'],
        ':recommender_name' => $data['recommender_name'],
        ':recommender_email' => $data['recommender_email'],
        ':recommender_relationship' => $data['recommender_relationship'],
        ':financial_need' => $data['financial_need'] ?? '',
        ':additional_information' => $data['additional_information'] ?? ''
    ]);

    return $pdo->lastInsertId();
}

// --- Handle form submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect form data safely
    $data = [
        'first_name' => $_POST['first_name'] ?? '',
        'last_name' => $_POST['last_name'] ?? '',
        'email' => $_POST['email'] ?? '',
        'phone' => $_POST['phone'] ?? '',
        'expected_graduation_year' => $_POST['expected_graduation_year'] ?? '',
        'gpa' => $_POST['gpa'] ?? '',
        'institution_type' => $_POST['institution_type'] ?? '',
        'intended_school' => $_POST['intended_school'] ?? '',
        'intended_major' => $_POST['intended_major'] ?? '',
        'extracurricular' => $_POST['extracurricular'] ?? '',
        'leadership' => $_POST['leadership'] ?? '',
        'community_service' => $_POST['community_service'] ?? '',
        'essay' => $_POST['essay'] ?? '',
        'recommender_name' => $_POST['recommender_name'] ?? '',
        'recommender_email' => $_POST['recommender_email'] ?? '',
        'recommender_relationship' => $_POST['recommender_relationship'] ?? '',
        'financial_need' => $_POST['financial_need'] ?? '',
        'additional_information' => $_POST['additional_information'] ?? '',
    ];

    try {
        // Insert into database
        $application_id = insert_application($pdo, $data);

        // Redirect to thank you page
        header("Location: thank_you.php");
        exit(); // Always exit after redirect
    } catch (PDOException $e) {
        // Handle DB error gracefully
        echo "Error submitting application: " . $e->getMessage();
        exit();
    }
}