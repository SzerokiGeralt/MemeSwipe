CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    profile_photo VARCHAR(255),
    enabled BOOLEAN DEFAULT TRUE
);

INSERT INTO users (username, email, password, profile_photo, enabled)
VALUES (
    'Janeczek2137',
    'jan.kowalski@example.com',
    '$2b$10$ZbzQrqD1vDhLJpYe/vzSbeDJHTUnVPCpwlXclkiFa8dO5gOAfg8tq',
    'https://randomuser.me/api/portraits/lego/7.jpg',
    TRUE
);