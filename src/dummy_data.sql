use property_management;

INSERT INTO roles (role_name) VALUES
('Admin'),
('Super Admin'),
('Customer');

-- Insert an admin first, since users reference created_by
INSERT INTO users (username, email, password_hash, first_name, last_name, phone, role_id)
VALUES
('admin1', 'admin1@example.com', 'hashed_pass_123', 'Alice', 'Admin', '0123456789', 1);

-- Insert more users, referencing admin as creator (user_id = 1)
INSERT INTO users (username, email, password_hash, first_name, last_name, phone, role_id, created_by)
VALUES
('agent1', 'agent1@example.com', 'hashed_pass_abc', 'Bob', 'Builder', '0712345678', 2, 1),
('customer1', 'customer1@example.com', 'hashed_pass_xyz', 'Charlie', 'Client', '0798765432', 3, 1);

INSERT INTO properties (
    property_code, description, address_line1, address_line2, city, state, postal_code,
    price, bedrooms, bathrooms, surface, construction_status, completion_percentage,
    estimated_completion, sale_status, is_active, created_by
) VALUES
('P-1001', 'Luxury villa with sea view', '123 Ocean Drive', NULL, 'Brighton', 'East Sussex', 'BN1 1AA',
 750000.00, 4, 2.5, 210, 'finishing', 90, '2025-10-01', 'available', TRUE, 2),

('P-1002', 'Modern apartment in city center', '88 High Street', 'Flat 4B', 'Birmingham', 'West Midlands', 'B1 1BB',
 320000.00, 2, 1.0, 80, 'roofing', 45, '2025-12-15', 'under_contract', TRUE, 2);

INSERT INTO property_images (property_id, file_name, file_path, alt_text, is_primary, sort_order, uploaded_by)
VALUES
(1, 'villa_front.jpg', '/images/properties/villa_front.jpg', 'Front view of villa', TRUE, 1, 2),
(1, 'villa_pool.jpg', '/images/properties/villa_pool.jpg', 'Swimming pool', FALSE, 2, 2),
(2, 'apt_living.jpg', '/images/properties/apt_living.jpg', 'Living room', TRUE, 1, 2);

INSERT INTO property_videos (property_id, file_name, file_path, title, description, uploaded_by)
VALUES
(1, 'villa_tour.mp4', '/videos/properties/villa_tour.mp4', 'Villa Tour', 'Walkthrough of the villa', 2),
(2, 'apt_tour.mp4', '/videos/properties/apt_tour.mp4', 'Apartment Tour', 'Modern city apartment', 2);

INSERT INTO property_updates (property_id, content, completion_percentage, created_by)
VALUES
(1, 'Interior painting completed', 90, 2),
(2, 'Roof structure in place, tiles next week', 45, 2);

INSERT INTO update_images (update_id, file_name, file_path, alt_text, is_primary, sort_order, uploaded_by)
VALUES
(1, 'painting_progress.jpg', '/images/updates/painting_progress.jpg', 'Painting progress', TRUE, 1, 2),
(2, 'roof_done.jpg', '/images/updates/roof_done.jpg', 'Roof construction', TRUE, 1, 2);

INSERT INTO update_videos (update_id, file_name, file_path, title, description, uploaded_by)
VALUES
(1, 'interior_progress.mp4', '/videos/updates/interior_progress.mp4', 'Interior Progress', 'Paint and flooring updates', 2);

INSERT INTO user_requests (user_id, property_id, request_type, message, status)
VALUES
(3, 1, 'visit', 'I would like to schedule a viewing for next week.', 'pending'),
(3, 2, 'info', 'Is parking available?', 'approved');
