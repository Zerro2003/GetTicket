# Event Ticket Generator

This project is a PHP-based Event Ticket Generator web application designed to help organizers easily create events, generate secure tickets, and track availability in real time.

## Problem Statement

Event organizers face issues of overselling, duplicate tickets, and poor tracking. A secure and simple system is needed to ensure fair distribution and real-time availability of tickets.

## Objectives

- Allow organizers to post event details with ticket limits.
- Generate unique tickets with QR codes and security watermarks.
- Show ticket status using progress indicators.
- Use countdown locks to release unconfirmed reservations.
- Promote eco-friendly digital ticketing (QR/PDF).

## Methodology

The application is built using PHP for backend logic, MySQL for data storage, and HTML/CSS for the frontend. QR codes are generated using PHP libraries, and styled tickets are exported as PDFs.

## Setup

1.  **Database Setup:**
    Create a MySQL database named `event_ticket_generator`. Then, execute the following SQL script to create the necessary tables for a fresh installation.

    ```sql
    --
    -- Base schema for the Event Ticket Generator
    --

    -- Users table for authentication and roles
    CREATE TABLE `users` (
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

    -- Events table with a link to the organizer
    CREATE TABLE `events` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `organizer_id` int(11) NOT NULL,
      `name` varchar(255) NOT NULL,
      `description` text NOT NULL,
      `date` datetime NOT NULL,
      `location` varchar(255) DEFAULT NULL,
      `total_tickets` int(11) NOT NULL,
      `available_tickets` int(11) NOT NULL,
      `status` varchar(20) NOT NULL DEFAULT 'upcoming',
      PRIMARY KEY (`id`),
      KEY `organizer_id` (`organizer_id`),
      CONSTRAINT `events_ibfk_1` FOREIGN KEY (`organizer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    -- Ticket categories for different tiers (VVIP, VIP, etc.)
    CREATE TABLE `event_ticket_categories` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `event_id` int(11) NOT NULL,
      `name` varchar(50) NOT NULL,
      `total_tickets` int(11) NOT NULL,
      `available_tickets` int(11) NOT NULL,
      PRIMARY KEY (`id`),
      KEY `event_id` (`event_id`),
      CONSTRAINT `event_ticket_categories_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    -- Tickets table for bookings, linked to users and events
    CREATE TABLE `tickets` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `event_id` int(11) NOT NULL,
      `user_id` int(11) DEFAULT NULL,
      `category_id` int(11) DEFAULT NULL,
      `ticket_code` varchar(255) NOT NULL,
      `status` enum('reserved','booked','confirmed','used','cancelled') NOT NULL DEFAULT 'reserved',
      `reservation_time` datetime DEFAULT NULL,
      `user_email` varchar(255) DEFAULT NULL,
      PRIMARY KEY (`id`),
      UNIQUE KEY `ticket_code` (`ticket_code`),
      KEY `event_id` (`event_id`),
      KEY `user_id` (`user_id`),
      KEY `category_id` (`category_id`),
      CONSTRAINT `tickets_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
      CONSTRAINT `tickets_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `event_ticket_categories` (`id`) ON DELETE SET NULL,
      CONSTRAINT `tickets_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ```

2.  **Major Upgrade (from pre-user-accounts version):**
    If you have an existing database from before the user authentication feature was added, **backup your data first**. Then, run the SQL script located in `upgrade.sql`. This will create the `users` table and add the necessary columns and constraints to the `events` and `tickets` tables.

3.  **Configuration:**
    Update `config.php` with your database credentials.

4.  **Dependencies:**

    - PDF generation: `libs/TCPDF`
    - QR generation: `libs/phpqrcode/qrlib.php` (full phpqrcode repo cloned into `libs/phpqrcode/`)

    Both libraries are vendored inside `libs/` so no Composer installation is required—just keep the folders intact when deploying.

5.  **Resetting the Database (optional during development):**

If you want to start from a clean state:

1.  Open phpMyAdmin: go to `http://localhost/phpmyadmin` in your browser.
2.  Drop the existing database:

    - In the left sidebar, click the `event_ticket_generator` database.
    - Click the **Operations** tab.
    - At the bottom, click **Drop the database (DROP)** and confirm.

3.  Recreate the database:

    - Click the **Databases** tab.
    - Under **Create database**, enter `event_ticket_generator` and click **Create** (use `utf8mb4_general_ci` or the default collation).

4.  Recreate the tables:

    - Select the new `event_ticket_generator` database.
    - Open the **SQL** tab.
    - Paste the full SQL block from **Database Setup** above and click **Go**.

You now have a fresh schema with category support (VVIP, VIP, Regular).

## Expected Outcomes & Significance

The project will deliver a functional ticketing web app with secure QR-based tickets, real-time tracking, and eco-friendly paperless options. Its uniqueness lies in combining usability with security features such as progress indicators, one-time links, and countdown-based ticket release.
