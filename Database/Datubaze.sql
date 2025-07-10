-- Create User Table
CREATE TABLE users (
    ID INT PRIMARY KEY AUTO_INCREMENT,
    USERNAME VARCHAR(30) NOT NULL,
    UZVARDS VARCHAR(50),
    PASSWORD CHAR(200) NOT NULL,
    EMAIL VARCHAR(50),
    REG_DATE DATE,
    NUMBER INT,
    SVARS DOUBLE(5,2),
    AUGUMS INT,
    VECUMS INT,
    KALORIJAS INT,
    OLBALTUMVIELAS INT,
    TAUKI INT,
    TAUKSKABES INT,
    OGLHIDRATI INT,
    SALS INT,
    CUKURS INT,
    DZIMUMS VARCHAR(10),
    PROFILE_PHOTO VARCHAR(255),
    SPORTS VARCHAR(100),
    EDIENKARTES INT
);


-- Create Food Table (for Meals)
CREATE TABLE food (
    ID INT PRIMARY KEY AUTO_INCREMENT,
    USERS_ID INT NOT NULL,
    TOTAL_CALORIES DECIMAL(10, 2),       -- Total calories for the meal
    TOTAL_PROTEIN DECIMAL(10, 2),       -- Total vitamins for the meal
    TOTAL_FAT DECIMAL(10, 2),        -- Total protein for the meal
    TOTAL_FAT_ACIDS DECIMAL(10, 2),
    TOTAL_CARBOHYDRATES DECIMAL(10, 2),
    TOTAL_SALT DECIMAL(10, 2), 
    TOTAL_SUGAR DECIMAL(10, 2),   
    TOTAL_PRICE DECIMAL(10, 2),          -- Total price for the meal
    DATE_CREATED DATE DEFAULT CURRENT_DATE,  -- Date the meal was created
    EDIENKARTE_NR INT DEFAULT 1,                    -- Meal number on the menu
    FOREIGN KEY (USERS_ID) REFERENCES users(ID) ON DELETE CASCADE
);

-- Create Products Table (Individual Products/Ingredients)
CREATE TABLE products (
    ID INT PRIMARY KEY AUTO_INCREMENT,
    NAME VARCHAR(100) NOT NULL UNIQUE,
    CALORIES DECIMAL(10, 2),              -- Calories per unit
    FAT DECIMAL(10, 2),
    ACIDS DECIMAL(10,2),                   -- Fat per unit
    CARBOHYDRATES DECIMAL(10, 2),         -- Carbohydrates per unit
    SUGAR DECIMAL(10, 2),                 -- Sugar per unit
    PROTEIN DECIMAL(10, 2),               -- Protein per unit
    SALT DECIMAL(10, 2),                  -- Salt per unit
    PRICE DECIMAL(10, 2),                 -- Price per unit
    PRICE_FULL DECIMAL(10, 2),            -- Price per full unit
    PICTUREID VARCHAR(500),                -- Link or identifier for an image
    TYPE VARCHAR(100)                     -- Type of product (e.g. fruit, vegetable, meat, etc.)
);

-- Create Food_Products Table (Join Table for Meals and Products)
CREATE TABLE food_products (
    FOOD_ID INT NOT NULL,                 -- References Food table (Meal)
    PRODUCT_ID INT NOT NULL,              -- References Products table
    QUANTITY DECIMAL(10, 2) NOT NULL,     -- Quantity of product in the meal
    MIN_QUANTITY BOOLEAN DEFAULT FALSE,
    MAX_QUANTITY DECIMAL(10,2) DEFAULT NULL,
    PRIMARY KEY (FOOD_ID, PRODUCT_ID),    -- Composite primary key
    FOREIGN KEY (FOOD_ID) REFERENCES food(ID) ON DELETE CASCADE,
    FOREIGN KEY (PRODUCT_ID) REFERENCES products(ID) ON DELETE CASCADE
);
