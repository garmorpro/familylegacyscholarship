<?php
// functions.php
require_once 'db.php';

function insert_application($data) {
    global $pdo;

    $sql = "
        INSERT INTO scholarship_applications (
            first_name, last_name, email, phone, expected_graduation_year,
            gpa, institution_type, intended_school, intended_major,
            extracurricular, leadership, community_service,
            essay, recommender_name, recommender_email, recommender_relationship,
            financial_need, additional_information
        ) VALUES (
            :first_name, :last_name, :email, :phone, :grad_year,
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
        ':grad_year' => $data['expected_graduation_year'],
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
        ':financial_need' => $data['financialNeed'],
        ':additional_information' => $data['additionalInfo']
    ]);

    return $pdo->lastInsertId();
}
?>
