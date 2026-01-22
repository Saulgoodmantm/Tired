-- =============================================================================
-- TIREDOFDOINTM - Additional Schema Updates
-- =============================================================================
-- Run after 001_initial_schema.sql
-- =============================================================================

-- Contact form submissions (separate from message threads)
CREATE TABLE IF NOT EXISTS contact_submissions (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    subject VARCHAR(255),
    message TEXT NOT NULL,
    status VARCHAR(20) DEFAULT 'unread', -- unread, read, replied, archived
    ip_address VARCHAR(50),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_contact_status ON contact_submissions(status);

-- Add missing columns to bookings if not exists
DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns 
                   WHERE table_name = 'bookings' AND column_name = 'google_event_id') THEN
        ALTER TABLE bookings ADD COLUMN google_event_id TEXT;
    END IF;
    
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns 
                   WHERE table_name = 'bookings' AND column_name = 'reminder_sent') THEN
        ALTER TABLE bookings ADD COLUMN reminder_sent BOOLEAN DEFAULT FALSE;
    END IF;
    
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns 
                   WHERE table_name = 'bookings' AND column_name = 'reminder_sent_at') THEN
        ALTER TABLE bookings ADD COLUMN reminder_sent_at TIMESTAMP;
    END IF;
    
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns 
                   WHERE table_name = 'bookings' AND column_name = 'cancellation_reason') THEN
        ALTER TABLE bookings ADD COLUMN cancellation_reason TEXT;
    END IF;
    
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns 
                   WHERE table_name = 'bookings' AND column_name = 'cancelled_at') THEN
        ALTER TABLE bookings ADD COLUMN cancelled_at TIMESTAMP;
    END IF;
    
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns 
                   WHERE table_name = 'bookings' AND column_name = 'addons') THEN
        ALTER TABLE bookings ADD COLUMN addons JSONB DEFAULT '[]';
    END IF;
END $$;

-- Add visibility to galleries if not exists
DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns 
                   WHERE table_name = 'galleries' AND column_name = 'visibility') THEN
        ALTER TABLE galleries ADD COLUMN visibility VARCHAR(20) DEFAULT 'public';
    END IF;
    
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns 
                   WHERE table_name = 'galleries' AND column_name = 'slug') THEN
        ALTER TABLE galleries ADD COLUMN slug VARCHAR(255) UNIQUE;
    END IF;
    
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns 
                   WHERE table_name = 'galleries' AND column_name = 'is_featured') THEN
        ALTER TABLE galleries ADD COLUMN is_featured BOOLEAN DEFAULT FALSE;
    END IF;
END $$;

-- Add name to models if not exists
DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns 
                   WHERE table_name = 'models' AND column_name = 'name') THEN
        ALTER TABLE models ADD COLUMN name VARCHAR(255);
    END IF;
END $$;

-- Pricing packages table
CREATE TABLE IF NOT EXISTS pricing_packages (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    duration_hours INT NOT NULL,
    base_price DECIMAL(10, 2) NOT NULL,
    includes JSONB DEFAULT '[]',
    is_active BOOLEAN DEFAULT TRUE,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Pricing add-ons
CREATE TABLE IF NOT EXISTS pricing_addons (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    price_type VARCHAR(20) DEFAULT 'fixed', -- fixed, percentage, per_item
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default pricing packages
INSERT INTO pricing_packages (name, description, duration_hours, base_price, includes, sort_order) VALUES
    ('Personal', 'Perfect for headshots, portraits, and lifestyle photos', 2, 200.00, '["30+ edited photos", "Online gallery", "Print release"]', 1),
    ('Standard', 'Great for couples, families, and small events', 4, 375.00, '["75+ edited photos", "Online gallery", "Print release", "1 outfit change"]', 2),
    ('Extended', 'Ideal for events, parties, and full-day coverage', 6, 525.00, '["120+ edited photos", "Online gallery", "Print release", "Multiple locations"]', 3),
    ('Wedding', 'Complete wedding photography coverage', 8, 1200.00, '["300+ edited photos", "Online gallery", "Print release", "Second shooter option", "Engagement session"]', 4)
ON CONFLICT DO NOTHING;

-- Insert default add-ons
INSERT INTO pricing_addons (name, description, price, price_type) VALUES
    ('Rush Editing', 'Get your photos within 48 hours', 75.00, 'fixed'),
    ('Extra Location', 'Additional location for your shoot', 50.00, 'per_item'),
    ('Printed Album', 'Professional photo album', 150.00, 'fixed'),
    ('Makeup Artist', 'Professional makeup for your shoot', 100.00, 'fixed'),
    ('Styling Consultation', 'Pre-shoot wardrobe consultation', 75.00, 'fixed')
ON CONFLICT DO NOTHING;

-- Add created_at to settings if not exists
DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns 
                   WHERE table_name = 'settings' AND column_name = 'created_at') THEN
        ALTER TABLE settings ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;
    END IF;
END $$;

-- Locations table for shoot locations
CREATE TABLE IF NOT EXISTS locations (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    address TEXT,
    city VARCHAR(100),
    state VARCHAR(100),
    country VARCHAR(100) DEFAULT 'USA',
    postal_code VARCHAR(20),
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    description TEXT,
    notes TEXT, -- Internal notes (parking, access codes, etc.)
    hourly_rate DECIMAL(10, 2),
    is_active BOOLEAN DEFAULT TRUE,
    is_indoor BOOLEAN DEFAULT TRUE,
    amenities JSONB DEFAULT '[]',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_locations_active ON locations(is_active);

-- Location images
CREATE TABLE IF NOT EXISTS location_images (
    id SERIAL PRIMARY KEY,
    location_id INT REFERENCES locations(id) ON DELETE CASCADE,
    image_url TEXT NOT NULL,
    r2_key TEXT,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_location_images_location ON location_images(location_id);

-- Add location_id to bookings if not exists
DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns 
                   WHERE table_name = 'bookings' AND column_name = 'location_id') THEN
        ALTER TABLE bookings ADD COLUMN location_id INT REFERENCES locations(id);
    END IF;
END $$;

-- Booking models junction table (for shoots with multiple models)
CREATE TABLE IF NOT EXISTS booking_models (
    id SERIAL PRIMARY KEY,
    booking_id INT REFERENCES bookings(id) ON DELETE CASCADE,
    user_id INT REFERENCES users(id) ON DELETE CASCADE,
    role VARCHAR(50) DEFAULT 'model', -- model, assistant, stylist, etc.
    rate DECIMAL(10, 2),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(booking_id, user_id)
);

CREATE INDEX IF NOT EXISTS idx_booking_models_booking ON booking_models(booking_id);
CREATE INDEX IF NOT EXISTS idx_booking_models_user ON booking_models(user_id);

-- Add price and features columns to pricing_packages if missing
DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns 
                   WHERE table_name = 'pricing_packages' AND column_name = 'price') THEN
        ALTER TABLE pricing_packages ADD COLUMN price DECIMAL(10, 2);
        UPDATE pricing_packages SET price = base_price WHERE price IS NULL;
    END IF;
    
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns 
                   WHERE table_name = 'pricing_packages' AND column_name = 'features') THEN
        ALTER TABLE pricing_packages ADD COLUMN features JSONB DEFAULT '[]';
        UPDATE pricing_packages SET features = includes WHERE features = '[]';
    END IF;
    
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns 
                   WHERE table_name = 'pricing_packages' AND column_name = 'is_popular') THEN
        ALTER TABLE pricing_packages ADD COLUMN is_popular BOOLEAN DEFAULT FALSE;
    END IF;
END $$;

-- Add sort_order to pricing_addons if missing
DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns 
                   WHERE table_name = 'pricing_addons' AND column_name = 'sort_order') THEN
        ALTER TABLE pricing_addons ADD COLUMN sort_order INT DEFAULT 0;
    END IF;
END $$;
