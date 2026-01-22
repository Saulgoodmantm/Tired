-- =============================================================================
-- TIREDOFDOINTM - Initial Database Schema
-- =============================================================================
-- Run this migration to set up the database
-- =============================================================================

-- Roles table
CREATE TABLE IF NOT EXISTS roles (
    id SERIAL PRIMARY KEY,
    name VARCHAR(50) UNIQUE NOT NULL,
    display_name VARCHAR(100),
    level INT NOT NULL DEFAULT 0,
    permissions JSONB DEFAULT '{}',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default roles
INSERT INTO roles (name, display_name, level) VALUES
    ('admin', 'Administrator', 100),
    ('manager', 'Manager', 80),
    ('staff', 'Staff', 50),
    ('photographer', 'Photographer', 30),
    ('model', 'Model', 25),
    ('client', 'Client', 20),
    ('registered', 'Registered User', 10)
ON CONFLICT (name) DO NOTHING;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(50) UNIQUE,
    email VARCHAR(255) UNIQUE NOT NULL,
    role_id INT REFERENCES roles(id) DEFAULT 7,
    avatar_url TEXT,
    legal_names JSONB DEFAULT '[]',
    emails JSONB DEFAULT '[]',
    ips JSONB DEFAULT '[]',
    device_fingerprints JSONB DEFAULT '[]',
    google_id VARCHAR(255),
    stripe_customer_id VARCHAR(255),
    has_booked BOOLEAN DEFAULT FALSE,
    first_booking_date TIMESTAMP,
    previous_galleries JSONB DEFAULT '[]',
    email_verified BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE
);

-- Sessions table
CREATE TABLE IF NOT EXISTS sessions (
    id SERIAL PRIMARY KEY,
    user_id INT REFERENCES users(id) ON DELETE CASCADE,
    token_hash TEXT NOT NULL,
    device_fingerprint TEXT,
    ip_address VARCHAR(50),
    user_agent TEXT,
    remember_me BOOLEAN DEFAULT FALSE,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_sessions_token ON sessions(token_hash);
CREATE INDEX idx_sessions_user ON sessions(user_id);

-- OTP codes table
CREATE TABLE IF NOT EXISTS otp_codes (
    id SERIAL PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    code_hash TEXT NOT NULL,
    attempts INT DEFAULT 0,
    ip_address VARCHAR(50),
    user_agent TEXT,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_otp_email ON otp_codes(email);

-- Galleries table
CREATE TABLE IF NOT EXISTS galleries (
    id SERIAL PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    type VARCHAR(20) DEFAULT 'public', -- public, unlisted, private
    category VARCHAR(50), -- personal, product, group, event, misc
    cover_image_id INT,
    created_by INT REFERENCES users(id),
    shoot_date DATE,
    location_id INT,
    is_pinned BOOLEAN DEFAULT FALSE,
    pin_order INT,
    view_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_galleries_category ON galleries(category);
CREATE INDEX idx_galleries_type ON galleries(type);

-- Gallery links (for unlisted access)
CREATE TABLE IF NOT EXISTS gallery_links (
    id SERIAL PRIMARY KEY,
    gallery_id INT REFERENCES galleries(id) ON DELETE CASCADE,
    token VARCHAR(255) UNIQUE NOT NULL,
    permissions JSONB DEFAULT '{}',
    expires_at TIMESTAMP,
    password_hash TEXT,
    access_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_gallery_links_token ON gallery_links(token);

-- Images table
CREATE TABLE IF NOT EXISTS images (
    id SERIAL PRIMARY KEY,
    gallery_id INT REFERENCES galleries(id) ON DELETE CASCADE,
    uploader_id INT REFERENCES users(id),
    filename VARCHAR(255) NOT NULL,
    original_filename VARCHAR(255),
    caption TEXT,
    location JSONB,
    shoot_date DATE,
    metadata JSONB DEFAULT '{}',
    phash TEXT, -- perceptual hash for similarity search
    is_pinned BOOLEAN DEFAULT FALSE,
    pin_order INT,
    is_watermarked BOOLEAN DEFAULT FALSE,
    view_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_images_gallery ON images(gallery_id);
CREATE INDEX idx_images_pinned ON images(is_pinned) WHERE is_pinned = TRUE;

-- Image versions (different sizes stored in R2)
CREATE TABLE IF NOT EXISTS image_versions (
    id SERIAL PRIMARY KEY,
    image_id INT REFERENCES images(id) ON DELETE CASCADE,
    type VARCHAR(20) NOT NULL, -- raw, full, full_watermarked, web, mobile, thumb
    r2_path TEXT NOT NULL,
    r2_url TEXT,
    width INT,
    height INT,
    file_size BIGINT,
    hash TEXT,
    is_encrypted BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_image_versions_image ON image_versions(image_id);

-- Locations table
CREATE TABLE IF NOT EXISTS locations (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    address TEXT,
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    place_id VARCHAR(255), -- Google Place ID
    notes TEXT,
    weather_data JSONB,
    traffic_data JSONB,
    photos JSONB DEFAULT '[]',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Bookings table
CREATE TABLE IF NOT EXISTS bookings (
    id SERIAL PRIMARY KEY,
    unique_link VARCHAR(255) UNIQUE NOT NULL,
    user_id INT REFERENCES users(id),
    service_type VARCHAR(100),
    date_start TIMESTAMP NOT NULL,
    date_end TIMESTAMP NOT NULL,
    duration_hours DECIMAL(4, 2),
    location_id INT REFERENCES locations(id),
    status VARCHAR(20) DEFAULT 'requested', -- requested, confirmed, paid, completed, cancelled
    google_calendar_event_id TEXT,
    contract_id INT,
    payment_id INT,
    questionnaire_answers JSONB DEFAULT '{}',
    add_ons JSONB DEFAULT '{}',
    total_price DECIMAL(10, 2),
    deposit_amount DECIMAL(10, 2),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_bookings_user ON bookings(user_id);
CREATE INDEX idx_bookings_status ON bookings(status);
CREATE INDEX idx_bookings_date ON bookings(date_start);

-- Payments table
CREATE TABLE IF NOT EXISTS payments (
    id SERIAL PRIMARY KEY,
    booking_id INT REFERENCES bookings(id),
    user_id INT REFERENCES users(id),
    method VARCHAR(50),
    provider VARCHAR(20) DEFAULT 'stripe',
    provider_payment_id TEXT,
    provider_customer_id TEXT,
    amount DECIMAL(10, 2) NOT NULL,
    fee_amount DECIMAL(10, 2) DEFAULT 0,
    currency VARCHAR(3) DEFAULT 'USD',
    status VARCHAR(20) DEFAULT 'pending', -- pending, succeeded, failed, refunded
    is_deposit BOOLEAN DEFAULT TRUE,
    refunded_amount DECIMAL(10, 2) DEFAULT 0,
    metadata JSONB DEFAULT '{}',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_payments_booking ON payments(booking_id);
CREATE INDEX idx_payments_user ON payments(user_id);

-- Contract templates
CREATE TABLE IF NOT EXISTS contract_templates (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    type VARCHAR(50), -- model_release, copyright, client, contractor
    content TEXT NOT NULL,
    clauses JSONB DEFAULT '[]',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Contracts table
CREATE TABLE IF NOT EXISTS contracts (
    id SERIAL PRIMARY KEY,
    template_id INT REFERENCES contract_templates(id),
    booking_id INT REFERENCES bookings(id),
    user_id INT REFERENCES users(id),
    title VARCHAR(255),
    content TEXT NOT NULL,
    signature_data JSONB, -- signature image, timestamp, IP, hash
    status VARCHAR(20) DEFAULT 'pending', -- pending, sent, viewed, signed, expired
    sent_at TIMESTAMP,
    viewed_at TIMESTAMP,
    signed_at TIMESTAMP,
    pdf_path TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_contracts_booking ON contracts(booking_id);
CREATE INDEX idx_contracts_user ON contracts(user_id);

-- Message threads
CREATE TABLE IF NOT EXISTS message_threads (
    id SERIAL PRIMARY KEY,
    user_id INT REFERENCES users(id),
    subject VARCHAR(255),
    status VARCHAR(20) DEFAULT 'open', -- open, closed, archived
    last_message_at TIMESTAMP,
    unread_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_message_threads_user ON message_threads(user_id);

-- Messages
CREATE TABLE IF NOT EXISTS messages (
    id SERIAL PRIMARY KEY,
    thread_id INT REFERENCES message_threads(id) ON DELETE CASCADE,
    sender_id INT REFERENCES users(id),
    sender_email VARCHAR(255),
    content TEXT NOT NULL,
    source VARCHAR(20) DEFAULT 'website', -- website, email
    email_message_id TEXT,
    attachments JSONB DEFAULT '[]',
    read_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_messages_thread ON messages(thread_id);

-- Models table
CREATE TABLE IF NOT EXISTS models (
    id SERIAL PRIMARY KEY,
    user_id INT REFERENCES users(id),
    display_name VARCHAR(255),
    portfolio_gallery_id INT REFERENCES galleries(id),
    bio TEXT,
    stats JSONB DEFAULT '{}', -- height, measurements, etc.
    rates JSONB DEFAULT '{}', -- per shoot type
    commission_rate DECIMAL(5, 2) DEFAULT 20.00, -- platform percentage
    availability JSONB DEFAULT '{}',
    location VARCHAR(255),
    travel_willing BOOLEAN DEFAULT FALSE,
    tags JSONB DEFAULT '[]',
    status VARCHAR(20) DEFAULT 'pending', -- active, inactive, pending
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Model jobs
CREATE TABLE IF NOT EXISTS model_jobs (
    id SERIAL PRIMARY KEY,
    model_id INT REFERENCES models(id),
    booking_id INT REFERENCES bookings(id),
    rate DECIMAL(10, 2),
    commission DECIMAL(10, 2),
    status VARCHAR(20) DEFAULT 'pending', -- pending, accepted, declined, completed, paid
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Testimonials
CREATE TABLE IF NOT EXISTS testimonials (
    id SERIAL PRIMARY KEY,
    client_name VARCHAR(255),
    client_email VARCHAR(255),
    content TEXT NOT NULL,
    rating INT CHECK (rating >= 1 AND rating <= 5),
    gallery_id INT REFERENCES galleries(id),
    is_visible BOOLEAN DEFAULT TRUE,
    display_order INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Analytics events
CREATE TABLE IF NOT EXISTS analytics_events (
    id SERIAL PRIMARY KEY,
    event_type VARCHAR(100) NOT NULL,
    user_id INT REFERENCES users(id),
    session_id VARCHAR(255),
    resource_type VARCHAR(50),
    resource_id INT,
    ip_address VARCHAR(50),
    user_agent TEXT,
    device_type VARCHAR(20),
    country VARCHAR(100),
    city VARCHAR(100),
    referrer TEXT,
    metadata JSONB DEFAULT '{}',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_analytics_type ON analytics_events(event_type);
CREATE INDEX idx_analytics_created ON analytics_events(created_at);

-- Notifications
CREATE TABLE IF NOT EXISTS notifications (
    id SERIAL PRIMARY KEY,
    user_id INT REFERENCES users(id) ON DELETE CASCADE,
    type VARCHAR(50),
    title VARCHAR(255),
    message TEXT,
    action_url TEXT,
    is_read BOOLEAN DEFAULT FALSE,
    read_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_notifications_user ON notifications(user_id);

-- Settings (key-value store)
CREATE TABLE IF NOT EXISTS settings (
    id SERIAL PRIMARY KEY,
    key VARCHAR(100) UNIQUE NOT NULL,
    value JSONB NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default settings
INSERT INTO settings (key, value) VALUES
    ('pricing', '{"personal": {"2h": 200, "4h": 375, "6h": 525}, "product": {"2h": 250, "4h": 450, "6h": 625}}'),
    ('deposit_percentage', '50'),
    ('booking_buffer_hours', '4'),
    ('payment_methods', '["card", "apple_pay", "google_pay", "link"]')
ON CONFLICT (key) DO NOTHING;

-- Cron logs
CREATE TABLE IF NOT EXISTS cron_logs (
    id SERIAL PRIMARY KEY,
    job_name VARCHAR(100) NOT NULL,
    status VARCHAR(20),
    started_at TIMESTAMP,
    completed_at TIMESTAMP,
    error_message TEXT,
    metadata JSONB DEFAULT '{}'
);

-- Add foreign keys that reference other tables
ALTER TABLE galleries ADD CONSTRAINT fk_galleries_cover_image
    FOREIGN KEY (cover_image_id) REFERENCES images(id) ON DELETE SET NULL;

ALTER TABLE galleries ADD CONSTRAINT fk_galleries_location
    FOREIGN KEY (location_id) REFERENCES locations(id);

ALTER TABLE bookings ADD CONSTRAINT fk_bookings_contract
    FOREIGN KEY (contract_id) REFERENCES contracts(id);

ALTER TABLE bookings ADD CONSTRAINT fk_bookings_payment
    FOREIGN KEY (payment_id) REFERENCES payments(id);
