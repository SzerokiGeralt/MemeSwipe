-- Users table
CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    profile_photo VARCHAR(255),
    enabled BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Badges table
CREATE TABLE badges (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    icon VARCHAR(255) NOT NULL
);

-- Quest table
CREATE TABLE quests (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    icon VARCHAR(255) NOT NULL,
    count INTEGER NOT NULL,
    reward INTEGER NOT NULL,
    action_type VARCHAR(50) DEFAULT 'generic'
);

CREATE TABLE user_quests (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    quest_id INTEGER NOT NULL REFERENCES quests(id) ON DELETE CASCADE,
    progress INTEGER DEFAULT 0,
    completed BOOLEAN DEFAULT FALSE,
    expiration_date TIMESTAMP,
    UNIQUE(user_id, quest_id)
);

-- Powerup table
CREATE TABLE items (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    icon VARCHAR(255) NOT NULL,
    cost INTEGER NOT NULL,
    max_quantity INTEGER NOT NULL
);

CREATE TABLE user_items (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    item_id INTEGER NOT NULL REFERENCES items(id) ON DELETE CASCADE,
    UNIQUE(user_id, item_id)
);

-- Posts table
CREATE TABLE posts (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    image VARCHAR(255) NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    upvotes INTEGER DEFAULT 0,
    downvotes INTEGER DEFAULT 0
);

-- User stats table
CREATE TABLE user_stats (
    id SERIAL PRIMARY KEY,
    user_id INTEGER UNIQUE NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    level INTEGER DEFAULT 1,
    experience INTEGER DEFAULT 0,
    diamonds INTEGER DEFAULT 0,
    streak INTEGER DEFAULT 0,
    longest_streak INTEGER DEFAULT 0,
    posts_count INTEGER DEFAULT 0,
    last_active_date DATE DEFAULT CURRENT_DATE,
    last_upload_date DATE DEFAULT CURRENT_DATE
);

-- Users badges junction table
CREATE TABLE users_badges (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    badge_id INTEGER NOT NULL REFERENCES badges(id) ON DELETE CASCADE,
    unlocked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(user_id, badge_id)
);



-- User votes table (prevents double voting)
CREATE TABLE user_votes (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    post_id INTEGER NOT NULL REFERENCES posts(id) ON DELETE CASCADE,
    vote_type VARCHAR(10) NOT NULL CHECK (vote_type IN ('upvote', 'downvote')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(user_id, post_id)
);

-- Seed users
INSERT INTO users (username, email, password, profile_photo, enabled) VALUES
('Janeczek2137', 'jan.kowalski@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'https://randomuser.me/api/portraits/men/1.jpg', TRUE),
('MemeLord420', 'meme.lord@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'https://randomuser.me/api/portraits/men/2.jpg', TRUE),
('KarynaPL', 'karyna@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'https://randomuser.me/api/portraits/women/1.jpg', TRUE),
('DankMaster', 'dank@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'https://randomuser.me/api/portraits/men/3.jpg', TRUE),
('ProGamer99', 'gamer@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'https://randomuser.me/api/portraits/women/2.jpg', TRUE);

-- Seed badges
INSERT INTO badges (name, description, icon) VALUES
('First Post', 'Upload your first meme', 'https://api.iconify.design/noto/party-popper.svg'),
('Streak Master', 'Maintain a 7-day streak', 'https://api.iconify.design/noto/fire.svg'),
('Popular', 'Get 100 upvotes on a single post', 'https://api.iconify.design/noto/star.svg'),
('Viral', 'Get 1000 upvotes on a single post', 'https://api.iconify.design/noto/dizzy.svg'),
('Consistent', 'Upload 50 posts', 'https://api.iconify.design/noto/chart-increasing.svg'),
('Diamond Collector', 'Earn 1000 diamonds', 'https://api.iconify.design/noto/gem-stone.svg'),
('Level 10', 'Reach level 10', 'https://api.iconify.design/noto/trophy.svg'),
('VIP', 'Exclusive VIP Member', 'https://api.iconify.design/noto/crown.svg');

-- Seed user_stats
INSERT INTO user_stats (user_id, level, experience, diamonds, streak, longest_streak, posts_count) VALUES
(1, 12, 15400, 1250, 5, 14, 45),
(2, 8, 4900, 800, 3, 8, 28),
(3, 15, 24800, 2100, 10, 15, 67),
(4, 5, 1500, 420, 2, 5, 15),
(5, 20, 57000, 3500, 7, 20, 92);

-- Seed items BEFORE user_items (foreign key constraint)
INSERT INTO items (name, description, icon, cost, max_quantity) VALUES
-- instant levelup (infinite)
('Instant Level Up', 'Instantly gain one level.', 'https://api.iconify.design/noto/up-arrow.svg', 500, 0),
-- instant streakup (infinite)
('Streak Booster', 'Increase your current streak by 1 day.', 'https://api.iconify.design/noto/calendar.svg', 300, 0),
-- mystery box (infinite)
('Mystery Box', 'Contains random rewards like diamonds or experience.', 'https://api.iconify.design/noto/wrapped-gift.svg', 700, 0),
-- special avatar frame (limited - add to inventory)
('Avatar Frame', 'A special frame for your profile picture.', 'https://api.iconify.design/noto/framed-picture.svg', 1000, 1),
-- VIP badge (limited - add to inventory)
('VIP Badge', 'A special badge displayed on your profile.', 'https://api.iconify.design/noto/crown.svg', 1500, 1),
-- golden page (limited - add to inventory)
('Golden Page', 'Gives your profile a golden theme.', 'https://api.iconify.design/noto/page-with-curl.svg', 2000, 1),
-- 8h cooldown (limited - add to inventory)
('Cooldown Reducer', 'Reduces post upload cooldown to 8 hours.', 'https://api.iconify.design/noto/stopwatch.svg', 10000, 1);

-- Seed user_items (VIP items for some users)
INSERT INTO user_items (user_id, item_id) VALUES
(3, 4), (3, 5), (3, 6),  -- KarynaPL has Avatar Frame, VIP Badge, Golden Page
(5, 4), (5, 5), (5, 6);  -- ProGamer99 has Avatar Frame, VIP Badge, Golden Page

-- Seed posts
INSERT INTO posts (user_id, image, upvotes, downvotes) VALUES
(1, 'https://picsum.photos/seed/meme1/400/400', 245, 12),
(1, 'https://picsum.photos/seed/meme2/400/400', 189, 23),
(2, 'https://picsum.photos/seed/meme3/400/400', 567, 45),
(3, 'https://picsum.photos/seed/meme4/400/400', 892, 34),
(3, 'https://picsum.photos/seed/meme5/400/400', 1234, 89),
(4, 'https://picsum.photos/seed/meme6/400/400', 78, 15),
(5, 'https://picsum.photos/seed/meme7/400/400', 2345, 156),
(5, 'https://picsum.photos/seed/meme8/400/400', 445, 67),
(1, 'https://picsum.photos/seed/meme9/400/400', 321, 43),
(2, 'https://picsum.photos/seed/meme10/400/400', 156, 21);

-- Seed users_badges
INSERT INTO users_badges (user_id, badge_id) VALUES
(1, 1), (1, 2), (1, 5),
(2, 1), (2, 5),
(3, 1), (3, 2), (3, 3), (3, 5),
(4, 1),
(5, 1), (5, 2), (5, 3), (5, 4), (5, 5), (5, 6);

-- Seed user_votes (sample votes from users on different posts)
INSERT INTO user_votes (user_id, post_id, vote_type) VALUES
-- User 1 votes
(1, 3, 'upvote'), (1, 4, 'upvote'), (1, 5, 'upvote'), (1, 7, 'upvote'),
-- User 2 votes
(2, 1, 'upvote'), (2, 4, 'upvote'), (2, 5, 'upvote'), (2, 7, 'upvote'), (2, 6, 'downvote'),
-- User 3 votes
(3, 1, 'upvote'), (3, 2, 'upvote'), (3, 7, 'upvote'), (3, 8, 'upvote'),
-- User 4 votes
(4, 1, 'downvote'), (4, 3, 'upvote'), (4, 4, 'upvote'), (4, 5, 'upvote'),
-- User 5 votes
(5, 1, 'upvote'), (5, 2, 'upvote'), (5, 3, 'upvote'), (5, 4, 'upvote');

INSERT INTO quests (name, description, icon, count, reward, action_type) VALUES
('Uploader', 'Upload 3 new memes', 'https://api.iconify.design/noto/party-popper.svg', 3, 100, 'upload'),
('Uploader', 'Upload 5 new memes', 'https://api.iconify.design/noto/camera.svg', 5, 300, 'upload'),
('Upvote Collector', 'Receive a total of 10 upvotes.', 'https://api.iconify.design/noto/star.svg', 10, 100, 'upvote'),
('Upvote Collector', 'Receive a total of 50 upvotes.', 'https://api.iconify.design/noto/star.svg', 50, 500, 'upvote'),
('Upvote Collector', 'Receive a total of 100 upvotes.', 'https://api.iconify.design/noto/star.svg', 100, 1000, 'upvote'),
('Daily Streak', 'Maintain a 5-day activity streak.', 'https://api.iconify.design/noto/fire.svg', 5, 200, 'streak'),
('Daily Streak', 'Maintain a 3-day activity streak.', 'https://api.iconify.design/noto/fire.svg', 3, 400, 'streak'),
('Vote on memes', 'Vote on 10 different memes', 'https://api.iconify.design/noto/rocket.svg', 10, 400, 'vote'),
('Vote on memes', 'Vote on 30 different memes', 'https://api.iconify.design/noto/rocket.svg', 30, 1200, 'vote'),
('Vote on memes', 'Vote on 50 different memes', 'https://api.iconify.design/noto/rocket.svg', 50, 2000, 'vote'),
('Vote on memes', 'Vote on 100 different memes', 'https://api.iconify.design/noto/rocket.svg', 100, 1000, 'vote');