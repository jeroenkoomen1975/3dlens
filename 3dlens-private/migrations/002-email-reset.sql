CREATE TABLE IF NOT EXISTS lens_account_settings (
    user_id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
    recovery_email VARCHAR(254) NOT NULL,
    CONSTRAINT lens_account_user_fk FOREIGN KEY (user_id) REFERENCES lens_users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS lens_password_resets (
    selector CHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL PRIMARY KEY,
    user_id TINYINT UNSIGNED NOT NULL,
    token_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    password_fingerprint CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    expires_at BIGINT UNSIGNED NOT NULL,
    INDEX lens_reset_user (user_id),
    CONSTRAINT lens_reset_user_fk FOREIGN KEY (user_id) REFERENCES lens_users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
