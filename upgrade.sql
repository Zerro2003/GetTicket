-- This script upgrades the database schema to support multi-user functionality,
-- including roles for Organizers and Regular Users.

-- --------------------------------------------------------

--
-- Table structure for table `users`
--
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('organizer','regular') NOT NULL DEFAULT 'regular',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Create a default organizer user to assign existing events to.
-- The password is 'password'. You can change this later.
--
INSERT IGNORE INTO `users` (`id`, `username`, `email`, `password_hash`, `role`) VALUES
(1, 'default_organizer', 'default@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'organizer');


-- --------------------------------------------------------

--
-- Alter `events` table to link events to an organizer
--
-- We first add the column as nullable, assign the default organizer, then make it not-null.
--
ALTER TABLE `events`
  ADD COLUMN `organizer_id` INT(11) NULL AFTER `id`,
  ADD COLUMN `status` VARCHAR(20) NOT NULL DEFAULT 'upcoming' AFTER `available_tickets`;

-- Assign all existing events to the default organizer (ID = 1)
UPDATE `events` SET `organizer_id` = 1 WHERE `organizer_id` IS NULL;

-- Now, make the column NOT NULL and add the foreign key constraint
ALTER TABLE `events`
  MODIFY `organizer_id` INT(11) NOT NULL,
  ADD CONSTRAINT `fk_organizer` FOREIGN KEY (`organizer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE;

-- --------------------------------------------------------

--
-- Alter `tickets` table to link bookings to a user
--
ALTER TABLE `tickets`
  ADD COLUMN `user_id` INT(11) NULL AFTER `event_id`,
  MODIFY `status` enum('reserved','booked','confirmed','used','cancelled') NOT NULL DEFAULT 'reserved',
  ADD CONSTRAINT `fk_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL;

-- Note: `user_id` is nullable in the `tickets` table. This allows for guest reservations
-- or scenarios where a user account is deleted but the ticket record is kept for archival purposes.
-- The `ON DELETE SET NULL` ensures that if a user is deleted, their tickets are not, but the link is removed.

