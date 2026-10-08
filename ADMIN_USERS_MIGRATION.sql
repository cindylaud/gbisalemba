DROP TABLE IF EXISTS users_new;

CREATE TABLE users_new (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_users_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO users_new (username, password, created_at, updated_at)
SELECT
    username,
    password,
    COALESCE(created_at, CURRENT_TIMESTAMP),
    COALESCE(updated_at, COALESCE(created_at, CURRENT_TIMESTAMP))
FROM users
ORDER BY id ASC;

RENAME TABLE users TO users_old, users_new TO users;
DROP TABLE users_old;