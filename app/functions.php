<?php
// functions.php
require_once 'db.php';

/**
 * Insert application + recommendation in a single transaction
 */
function insert_application_with_recommendation(PDO $pdo, array $data) {

    try {
        $pdo->beginTransaction();

        // --------------------------------------------------
        // Insert into scholarship_applications
        // --------------------------------------------------
        $sqlApplication = "
            INSERT INTO scholarship_applications (
                first_name, last_name, email, phone, expected_graduation_year,
                gpa, institution_type, intended_school, intended_major,
                extracurricular, leadership, community_service,
                essay, financial_need, additional_information
            ) VALUES (
                :first_name, :last_name, :email, :phone, :expected_graduation_year,
                :gpa, :institution_type, :intended_school, :intended_major,
                :extracurricular, :leadership, :community_service,
                :essay, :financial_need, :additional_information
            )
        ";

        $stmtApp = $pdo->prepare($sqlApplication);
        $stmtApp->execute([
            ':first_name'               => $data['first_name'],
            ':last_name'                => $data['last_name'],
            ':email'                    => $data['email'],
            ':phone'                    => $data['phone'],
            ':expected_graduation_year' => $data['expected_graduation_year'],
            ':gpa'                      => $data['gpa'],
            ':institution_type'         => $data['institution_type'],
            ':intended_school'          => $data['intended_school'],
            ':intended_major'           => $data['intended_major'],
            ':extracurricular'          => $data['extracurricular'],
            ':leadership'               => $data['leadership'],
            ':community_service'        => $data['community_service'],
            ':essay'                    => $data['essay'],
            ':financial_need'           => $data['financial_need'] ?? '',
            ':additional_information'   => $data['additional_information'] ?? ''
        ]);

        // Grab PK from scholarship_applications
        $application_id = $pdo->lastInsertId();

        // --------------------------------------------------
        // Insert into recommendations
        // NOTE: correct FK column name
        // --------------------------------------------------
        $sqlRecommendation = "
            INSERT INTO recommendations (
                scholarship_application_id,
                recommender_name,
                recommender_email,
                recommender_relationship,
                status
            ) VALUES (
                :scholarship_application_id,
                :recommender_name,
                :recommender_email,
                :recommender_relationship,
                :status
            )
        ";

        $stmtRec = $pdo->prepare($sqlRecommendation);
        $stmtRec->execute([
            ':scholarship_application_id' => $application_id,
            ':recommender_name'           => $data['recommender_name'],
            ':recommender_email'          => $data['recommender_email'],
            ':recommender_relationship'   => $data['recommender_relationship'],
            ':status'                     => 'not_sent'
        ]);

        $pdo->commit();

        return $application_id;

    } catch (PDOException $e) {
        $pdo->rollBack();
        throw $e;
    }
}

// --------------------------------------------------
// Handle form submission
// --------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

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
        'additional_information' => $_POST['additional_information'] ?? ''
    ];

    try {
        insert_application_with_recommendation($pdo, $data);
        header("Location: thank_you.php");
        exit();
    } catch (PDOException $e) {
        error_log("Error submitting application: " . $e->getMessage());
        echo "Sorry, something went wrong submitting your application. Please try again, and contact us if the problem continues.";
        exit();
    }
}
