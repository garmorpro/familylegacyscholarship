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

    // The form marks these required client-side, but that alone isn't
    // trustworthy -- a browser quirk, autofill, or a non-browser client can
    // still submit them empty. expected_graduation_year and gpa in
    // particular map to strictly-typed integer/numeric DB columns, and an
    // empty string there fails as a raw, unfriendly PDOException rather
    // than a normal validation message. Catch that here first.
    $requiredFields = [
        'first_name' => 'First name', 'last_name' => 'Last name', 'email' => 'Email address',
        'phone' => 'Phone number', 'expected_graduation_year' => 'Expected graduation year',
        'gpa' => 'Current GPA', 'institution_type' => 'Institution type',
        'intended_school' => 'Intended school', 'intended_major' => 'Intended major',
        'extracurricular' => 'Extracurricular activities', 'leadership' => 'Leadership experience',
        'community_service' => 'Community service', 'essay' => 'Essay',
        'recommender_name' => "Recommender's name", 'recommender_email' => "Recommender's email",
        'recommender_relationship' => "Recommender's relationship",
    ];
    $missing = [];
    foreach ($requiredFields as $key => $label) {
        if (trim((string) $data[$key]) === '') {
            $missing[] = $label;
        }
    }

    if ($missing) {
        echo "Please fill out: " . implode(', ', $missing) . ".";
        exit();
    }
    if (!ctype_digit(trim($data['expected_graduation_year']))) {
        echo "Expected graduation year must be a whole number (e.g. " . (date('Y') + 3) . ").";
        exit();
    }
    if (!is_numeric($data['gpa'])) {
        echo "Current GPA must be a number (e.g. 4.0).";
        exit();
    }
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        echo "Please enter a valid email address.";
        exit();
    }
    if (!filter_var($data['recommender_email'], FILTER_VALIDATE_EMAIL)) {
        echo "Please enter a valid email address for your recommender.";
        exit();
    }

    try {
        $result = insert_application_with_recommendation($pdo, $data);

        // Best-effort: email the recommender immediately. If this fails
        // (bad address, SMTP hiccup), the recommendation stays 'not_sent'
        // and gets picked up later by the send_not_sent_recommendations.php
        // cron job as a fallback — it must never block the applicant's
        // submission, since their data is already safely saved.
        try {
            send_recommendation_request_email($pdo, $config, $result['recommendation_id']);
        } catch (Throwable $e) {
            error_log("Auto-send recommendation email failed: " . $e->getMessage());
        }

        header("Location: thank_you.php");
        exit();
    } catch (PDOException $e) {
        error_log("Error submitting application: " . $e->getMessage());
        echo "Sorry, something went wrong submitting your application. Please try again, and contact us if the problem continues.";
        exit();
    }
}
