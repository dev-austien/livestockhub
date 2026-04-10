-- ==========================================
-- FIXED & IMPROVED DATABASE SCHEMA
-- ==========================================

CREATE TABLE `user` (
  `user_id`           INT             NOT NULL AUTO_INCREMENT,
  `username`          VARCHAR(25)     NOT NULL UNIQUE,
  `user_email`        VARCHAR(100)    NOT NULL UNIQUE,       -- increased: emails can be long
  `user_phone_number` VARCHAR(15)     NOT NULL,              -- changed INT → VARCHAR (phone numbers aren't math)
  `password_hash`     VARCHAR(255)    NOT NULL,              -- fixed: 225 → 255 (standard bcrypt/hash length)
  `user_role`         ENUM('admin','farmer','buyer') NOT NULL DEFAULT 'buyer',  -- fixed: ENUM needs values
  `user_last_name`    VARCHAR(50)     NOT NULL,              -- increased: names can be longer
  `user_first_name`   VARCHAR(50)     NOT NULL,
  `user_middle_name`  VARCHAR(50),
  `created_at`        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_status`       ENUM('active','inactive','suspended') NOT NULL DEFAULT 'active',  -- fixed: ENUM needs values
  `user_pfp`          LONGBLOB,                              -- changed BLOB → LONGBLOB (BLOB only holds ~65KB)
  PRIMARY KEY (`user_id`)
);

CREATE TABLE `category` (
  `category_id`   INT           NOT NULL AUTO_INCREMENT,
  `category_name` VARCHAR(50)   NOT NULL UNIQUE,            -- increased slightly
  `description`   TEXT,
  PRIMARY KEY (`category_id`)
);

CREATE TABLE `farmers` (
  `farmer_id`                  INT           NOT NULL AUTO_INCREMENT,
  `user_id`                    INT           NOT NULL UNIQUE,
  `farm_name`                  VARCHAR(100)  NOT NULL,       -- increased
  `farm_location_brgy`         VARCHAR(100)  NOT NULL,
  `farm_location_city_muni`    VARCHAR(100)  NOT NULL,       -- fixed: removed / in column name
  `farm_location_province`     VARCHAR(100)  NOT NULL,       -- fixed: typo "lcation" → "location"
  `farm_location_latitude`     DECIMAL(9,6)  NOT NULL,       -- fixed: DECIMAL(9,6) is the GPS standard
  `farm_location_longitude`    DECIMAL(9,6)  NOT NULL,       -- fixed: same
  PRIMARY KEY (`farmer_id`),
  FOREIGN KEY (`user_id`) REFERENCES `user`(`user_id`)       -- fixed: proper FK to user, not self-referencing
);

CREATE TABLE `location` (
  `location_id`   INT           NOT NULL AUTO_INCREMENT,
  `farmer_id`     INT           NOT NULL,
  `location_name` VARCHAR(100)  NOT NULL,                   -- increased
  `description`   TEXT,
  PRIMARY KEY (`location_id`),
  FOREIGN KEY (`farmer_id`) REFERENCES `farmers`(`farmer_id`)  -- fixed: was referencing before farmers was defined
);

CREATE TABLE `breeds` (
  `breed_id`    INT           NOT NULL AUTO_INCREMENT,
  `category_id` INT           NOT NULL,
  `breed_name`  VARCHAR(50)   NOT NULL,
  PRIMARY KEY (`breed_id`),
  FOREIGN KEY (`category_id`) REFERENCES `category`(`category_id`)
);

CREATE TABLE `livestock` (
  `livestock_id`  INT           NOT NULL AUTO_INCREMENT,
  `farmer_id`     INT           NOT NULL,
  `location_id`   INT,
  `category_id`   INT           NOT NULL,
  `breed_id`      INT,
  `gender`        ENUM('male','female')  NOT NULL,           -- fixed: ENUM needs values
  `health_status` VARCHAR(100),
  `date_of_birth` DATE,                                      -- changed DATETIME → DATE (birthdate has no time)
  `date_created`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sale_status`   ENUM('available','reserved','sold') NOT NULL DEFAULT 'available',  -- fixed: ENUM needs values
  PRIMARY KEY (`livestock_id`),
  FOREIGN KEY (`farmer_id`)   REFERENCES `farmers`(`farmer_id`),
  FOREIGN KEY (`location_id`) REFERENCES `location`(`location_id`),
  FOREIGN KEY (`category_id`) REFERENCES `category`(`category_id`),
  FOREIGN KEY (`breed_id`)    REFERENCES `breeds`(`breed_id`)
  -- removed: wrong FK livestock_id → farmers.farmer_id (totally incorrect)
);

CREATE TABLE `order` (
  `order_id`           INT           NOT NULL AUTO_INCREMENT,
  `buyer_id`           INT           NOT NULL,
  `livestock_id`       INT           NOT NULL,
  `which_farmer`       INT           NOT NULL,
  `order_type`         VARCHAR(20)   NOT NULL,               -- increased slightly
  `status`             ENUM('pending','confirmed','cancelled','completed') NOT NULL DEFAULT 'pending',  -- fixed
  `total_price`        DECIMAL(12,2) NOT NULL,               -- increased: prices can exceed 9,999,999.99
  `reservation_expiry` DATE,
  PRIMARY KEY (`order_id`),
  FOREIGN KEY (`buyer_id`)     REFERENCES `user`(`user_id`),
  FOREIGN KEY (`livestock_id`) REFERENCES `livestock`(`livestock_id`),
  FOREIGN KEY (`which_farmer`) REFERENCES `farmers`(`farmer_id`)  -- fixed: moved here from farmers table
);

CREATE TABLE `farmers_contact` (
  `contact_id`    INT           NOT NULL AUTO_INCREMENT,
  `farmer_id`     INT           NOT NULL,
  `contact_type`  VARCHAR(30)   NOT NULL,
  `contact_value` VARCHAR(100)  NOT NULL,                    -- changed INT → VARCHAR (contact values aren't always numeric)
  PRIMARY KEY (`contact_id`),
  FOREIGN KEY (`farmer_id`) REFERENCES `farmers`(`farmer_id`)  -- added missing FK
);

CREATE TABLE `livestock_weight` (
  `weight_id`     INT            NOT NULL AUTO_INCREMENT,
  `livestock_id`  INT            NOT NULL,
  `weight`        DECIMAL(6,2)   NOT NULL,                   -- fixed: DECIMAL(3,2) max was 9.99 kg — too small
  `date_recorded` DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`weight_id`),
  FOREIGN KEY (`livestock_id`) REFERENCES `livestock`(`livestock_id`)
);

CREATE TABLE `transaction` (
  `transaction_id`   INT           NOT NULL AUTO_INCREMENT,
  `order_id`         INT           NOT NULL UNIQUE,          -- added UNIQUE: 1 transaction per order
  `payment_method`   VARCHAR(50)   NOT NULL,
  `payment_status`   ENUM('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending',  -- fixed
  `transaction_date` DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `total_price`      DECIMAL(12,2) NOT NULL,                 -- increased to match order table
  PRIMARY KEY (`transaction_id`),
  FOREIGN KEY (`order_id`) REFERENCES `order`(`order_id`)    -- added missing FK
);