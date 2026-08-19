CREATE TABLE secret_links (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    token_hash CHAR(64) NOT NULL UNIQUE,
    ciphertext TEXT NOT NULL,
    nonce VARBINARY(24) NOT NULL,

    label VARCHAR(255) NULL,
    ticket VARCHAR(100) NULL,

    max_views INT UNSIGNED NOT NULL DEFAULT 1,
    views INT UNSIGNED NOT NULL DEFAULT 0,

    expires_at DATETIME NOT NULL,
    revoked_at DATETIME NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_token_hash (token_hash),
    INDEX idx_expires_at (expires_at),
    INDEX idx_revoked_at (revoked_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE secret_opens (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    link_id BIGINT UNSIGNED NOT NULL,
    opened_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ip_hash CHAR(64) NULL,
    user_agent VARCHAR(500) NULL,

    FOREIGN KEY (link_id) REFERENCES secret_links(id) ON DELETE CASCADE,
    INDEX idx_link_id (link_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;