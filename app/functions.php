<?php
// functions.php
require_once 'db.php';
require_once __DIR__ . '/recommendation_mailer.php';

/**
 * Sanitize rich-text HTML submitted by recommenders before it is stored or
 * displayed. The recommendation editor exposes TinyMCE's raw HTML source
 * view, so submitted content can't be trusted as safe formatting -- it must
 * be treated the same as any other untrusted user input. Only a small
 * allowlist of plain formatting tags survives, and every attribute is
 * stripped from what remains (killing onclick=, style=, javascript: hrefs,
 * etc.), removing the stored-XSS risk of rendering it unescaped.
 */
function sanitize_recommendation_html(string $html): string {
    $allowedTags = '<p><br><b><i><u><strong><em><ul><ol><li><h1><h2><h3><h4><blockquote>';
    $stripped = strip_tags($html, $allowedTags);
    // Strip all attributes from the tags that remain -- none of the allowed
    // formatting tags need any for a recommendation letter.
    return preg_replace('/<([a-z0-9]+)[^>]*>/i', '<$1>', $stripped);
}

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

        $recommendation_id = $pdo->lastInsertId();

        $pdo->commit();

        return ['application_id' => $application_id, 'recommendation_id' => $recommendation_id];

    } catch (PDOException $e) {
        $pdo->rollBack();
        throw $e;
    }
}
