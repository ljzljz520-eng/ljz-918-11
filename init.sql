CREATE TABLE IF NOT EXISTS services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    description VARCHAR(255),
    status ENUM('running', 'stopped', 'error') DEFAULT 'stopped',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS system_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_id INT,
    action VARCHAR(50),
    message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE SET NULL
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

INSERT INTO services (name, description, status) VALUES 
('Nginx Web Server', 'Main web server', 'running'),
('MySQL Database', 'Primary database service', 'running'),
('Redis Cache', 'Memory cache system', 'stopped'),
('PHP FastCGI', 'PHP FPM Service', 'running'),
('Cron Scheduler', 'Scheduled tasks', 'running');

INSERT INTO system_logs (service_id, action, message) VALUES 
(1, 'START', 'Service Nginx started automatically by system.'),
(2, 'START', 'Service MySQL started connected.'),
(3, 'STOP', 'Redis stopped manually.');
