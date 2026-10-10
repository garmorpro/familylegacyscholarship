-- Morgan Legacy Scholarship -- full database schema (PostgreSQL 13+).
-- Idempotent: safe to run more than once. Run via database/setup_database.sh,
-- or by hand: psql -d morgan_legacy_scholarship -f database/schema.sql
--
-- A few things here are load-bearing even though they look incidental:
--   * recommendations.token has a DEFAULT -- the PHP never sets it, it only
--     reads it back when building the recommender's link.
--   * Child tables use ON DELETE CASCADE -- bulk-delete and "remove committee
--     member" delete the parent row directly, with no cleanup of children.
--   * Every settings key is pre-seeded -- save_settings.php only UPDATEs, so a
--     missing row means a field silently never saves.

CREATE TABLE IF NOT EXISTS scholarship_applications (
    id                       SERIAL PRIMARY KEY,
    first_name               TEXT NOT NULL,
    last_name                TEXT NOT NULL,
    email                    TEXT NOT NULL,
    phone                    TEXT,
    expected_graduation_year INTEGER,
    gpa                      NUMERIC(3,2),
    institution_type         TEXT,
    intended_school          TEXT,
    intended_major           TEXT,
    extracurricular          TEXT,
    leadership               TEXT,
    community_service        TEXT,
    essay                    TEXT,
    financial_need           TEXT,
    additional_information   TEXT,
    application_status       TEXT NOT NULL DEFAULT 'submitted',
    submitted_at             TIMESTAMP NOT NULL DEFAULT NOW(),
    archived_at              TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_applications_status   ON scholarship_applications (application_status);
CREATE INDEX IF NOT EXISTS idx_applications_archived ON scholarship_applications (archived_at);

CREATE TABLE IF NOT EXISTS recommendations (
    id                         SERIAL PRIMARY KEY,
    scholarship_application_id INTEGER NOT NULL REFERENCES scholarship_applications(id) ON DELETE CASCADE,
    recommender_name           TEXT,
    recommender_email          TEXT,
    recommender_relationship   TEXT,
    status                     TEXT NOT NULL DEFAULT 'not_sent',
    token                      TEXT NOT NULL UNIQUE
                               DEFAULT replace(gen_random_uuid()::text || gen_random_uuid()::text, '-', ''),
    recommendation             TEXT,
    requested_date             TIMESTAMP,
    completed_date             TIMESTAMP,
    created_at                 TIMESTAMP NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_recommendations_application ON recommendations (scholarship_application_id);

CREATE TABLE IF NOT EXISTS settings (
    id            SERIAL PRIMARY KEY,
    setting_key   TEXT NOT NULL UNIQUE,
    setting_value TEXT,
    updated_at    TIMESTAMP NOT NULL DEFAULT NOW()
);

INSERT INTO settings (setting_key, setting_value) VALUES
    ('award_amount',          ''),
    ('application_open',      ''),
    ('application_closed',    ''),
    ('review_start',          ''),
    ('review_end',            ''),
    ('announcement_date',     ''),
    ('notification_email',    ''),
    ('essay_prompt',          'In 500-750 words, please tell us about yourself, your goals, and what makes you a strong candidate for this scholarship.'),
    ('final_review_limit',    '10'),
    ('cycle_retention_limit', '')
ON CONFLICT (setting_key) DO NOTHING;

CREATE TABLE IF NOT EXISTS admin_users (
    id                    SERIAL PRIMARY KEY,
    name                  TEXT,
    email                 TEXT NOT NULL UNIQUE,
    password_hash         TEXT NOT NULL,
    is_active             BOOLEAN NOT NULL DEFAULT TRUE,
    failed_login_attempts INTEGER NOT NULL DEFAULT 0,
    last_failed_login     TIMESTAMP,
    last_login_at         TIMESTAMP,
    logged_in             BOOLEAN NOT NULL DEFAULT FALSE,
    unlock_token          TEXT,
    unlock_token_expires  TIMESTAMP,
    created_at            TIMESTAMP NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_admin_users_unlock_token ON admin_users (unlock_token);

CREATE TABLE IF NOT EXISTS committee_members (
    id                 SERIAL PRIMARY KEY,
    name               TEXT NOT NULL,
    email              TEXT NOT NULL UNIQUE,
    confirmation_token VARCHAR(64),
    confirmed_at       TIMESTAMP,
    created_at         TIMESTAMP NOT NULL DEFAULT NOW()
);
CREATE UNIQUE INDEX IF NOT EXISTS idx_committee_members_confirmation_token ON committee_members (confirmation_token);

CREATE TABLE IF NOT EXISTS committee_access (
    id                  SERIAL PRIMARY KEY,
    token               TEXT NOT NULL UNIQUE,
    code                TEXT NOT NULL,
    committee_member_id INTEGER NOT NULL REFERENCES committee_members(id) ON DELETE CASCADE,
    created_at          TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS committee_votes (
    id                  SERIAL PRIMARY KEY,
    committee_member_id INTEGER NOT NULL UNIQUE REFERENCES committee_members(id) ON DELETE CASCADE,
    application_id      INTEGER NOT NULL REFERENCES scholarship_applications(id) ON DELETE CASCADE,
    created_at          TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at          TIMESTAMP NOT NULL DEFAULT NOW()
);

-- Standalone copy of a designated recipient (no FK back to the application;
-- cycle cleanup matches it by email + date_submitted).
CREATE TABLE IF NOT EXISTS recipients (
    id                           SERIAL PRIMARY KEY,
    first_name                   TEXT NOT NULL,
    last_name                    TEXT NOT NULL,
    email                        TEXT,
    phone                        TEXT,
    expected_graduation_year     INTEGER,
    intended_school              TEXT,
    intended_major               TEXT,
    additional_information       TEXT,
    date_submitted               TIMESTAMP,
    application_year             INTEGER NOT NULL,
    recipient_picture            TEXT,
    selection_email_scheduled_at TIMESTAMP,
    selection_email_sent_at      TIMESTAMP,
    created_at                   TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at                   TIMESTAMP NOT NULL DEFAULT NOW()
);

-- Every field is TEXT on purpose: a draft is a half-finished form, so any
-- field (including GPA and graduation year) may legitimately be ''.
CREATE TABLE IF NOT EXISTS application_drafts (
    id                       SERIAL PRIMARY KEY,
    token                    TEXT NOT NULL UNIQUE,
    email                    TEXT NOT NULL,
    first_name               TEXT,
    last_name                TEXT,
    phone                    TEXT,
    expected_graduation_year TEXT,
    gpa                      TEXT,
    institution_type         TEXT,
    intended_school          TEXT,
    intended_major           TEXT,
    extracurricular          TEXT,
    leadership               TEXT,
    community_service        TEXT,
    essay                    TEXT,
    recommender_name         TEXT,
    recommender_email        TEXT,
    recommender_relationship TEXT,
    financial_need           TEXT,
    additional_information   TEXT,
    created_at               TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at               TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS submission_attempts (
    id         BIGSERIAL PRIMARY KEY,
    ip_address TEXT NOT NULL,
    form_type  TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_submission_attempts_lookup ON submission_attempts (ip_address, form_type, created_at);
