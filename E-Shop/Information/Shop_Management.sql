-- Create Database
CREATE DATABASE shop_management;
USE shop_management;

-- Users Table
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'seller', 'customer') NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
);

-- Insert users
INSERT INTO users (username, password, email, full_name, role, phone, address) VALUES
-- Sellers (6)
('rahim', '48291', 'rahim@storebd.com', 'Abdur Rahim', 'seller', '01811223344', 'Mirpur, Dhaka, Bangladesh'),
('salma', '19573', 'salma@fashionbd.com', 'Salma Akter', 'seller', '01733445566', 'Banani, Dhaka, Bangladesh'),
('faruk', '73820', 'faruk@electronicsbd.com', 'Faruk Hossain', 'seller', '01677889900', 'Dhanmondi, Dhaka, Bangladesh'),
('mitu', '56931', 'mitu@homelivingbd.com', 'Mitu Rahman', 'seller', '01899887755', 'Chattogram, Bangladesh'),
('jamil', '62748', 'jamil@toysworldbd.com', 'Jamil Hasan', 'seller', '01922334455', 'Sylhet, Bangladesh'),
('tanvir', '38425', 'tanvir@sportstorebd.com', 'Tanvir Ahmed', 'seller', '01755667788', 'Uttara, Dhaka, Bangladesh'),

-- Customers (24)
('akash', '31472', 'akash@gmail.com', 'Akash Chowdhury', 'customer', '01566778899', 'Bashundhara, Dhaka, Bangladesh'),
('mithila', '50936', 'mithila@gmail.com', 'Mithila Sultana', 'customer', '01988990011', 'Mohakhali, Dhaka, Bangladesh'),
('rakib', '76281', 'rakib@gmail.com', 'Rakibul Hasan', 'customer', '01855667788', 'Gazipur, Bangladesh'),
('sharmin', '89104', 'sharmin@gmail.com', 'Sharmin Akter', 'customer', '01733445577', 'Motijheel, Dhaka, Bangladesh'),
('imran', '24068', 'imran@gmail.com', 'Imran Hossain', 'customer', '01911224433', 'Narayanganj, Bangladesh'),
('nasrin', '67352', 'nasrin@gmail.com', 'Nasrin Jahan', 'customer', '01644556677', 'Rajshahi, Bangladesh'),
('rony', '35689', 'rony@gmail.com', 'Rony Mahmud', 'customer', '01877889900', 'Chattogram, Bangladesh'),
('fahmida', '62419', 'fahmida@gmail.com', 'Fahmida Islam', 'customer', '01755667744', 'Khilgaon, Dhaka, Bangladesh'),
('tareq', '91237', 'tareq@gmail.com', 'Tareq Mahmud', 'customer', '01599887766', 'Jessore, Bangladesh'),
('anjum', '45031', 'anjum@gmail.com', 'Anjum Hossain', 'customer', '01933445566', 'Barishal, Bangladesh'),
('sadia', '78392', 'sadia@gmail.com', 'Sadia Rahman', 'customer', '01666778822', 'Sylhet, Bangladesh'),
('zahid', '52814', 'zahid@gmail.com', 'Zahid Karim', 'customer', '01833445522', 'Kushtia, Bangladesh'),
('nazmul', '34987', 'nazmul@gmail.com', 'Nazmul Hossain', 'customer', '01788990055', 'Bogura, Bangladesh'),
('rumana', '62175', 'rumana@gmail.com', 'Rumana Akter', 'customer', '01866778899', 'Feni, Bangladesh'),
('ashik', '73082', 'ashik@gmail.com', 'Ashik Rahman', 'customer', '01911223399', 'Cumilla, Bangladesh'),
('sheuli', '15962', 'sheuli@gmail.com', 'Sheuli Khatun', 'customer', '01577889944', 'Noakhali, Bangladesh'),
('maruf', '89153', 'maruf@gmail.com', 'Maruf Ahmed', 'customer', '01722334477', 'Mymensingh, Bangladesh'),
('farzana', '46029', 'farzana@gmail.com', 'Farzana Islam', 'customer', '01899887711', 'Tangail, Bangladesh'),
('salim', '30571', 'salim@gmail.com', 'Salim Reza', 'customer', '01977889922', 'Sirajganj, Bangladesh'),
('karim', '67845', 'karim@gmail.com', 'Karim Ullah', 'customer', '01855667799', 'Pabna, Bangladesh'),
('tania', '89710', 'tania@gmail.com', 'Tania Afroz', 'customer', '01633445588', 'Rangpur, Bangladesh'),
('habib', '53280', 'habib@gmail.com', 'Habib Hossain', 'customer', '01788990033', 'Bogra, Bangladesh'),
('mehedi', '21467', 'mehedi@gmail.com', 'Mehedi Hasan', 'customer', '01866778866', 'Patuakhali, Bangladesh'),
('nahar', '69583', 'nahar@gmail.com', 'Nahar Sultana', 'customer', '01999887700', 'Jashore, Bangladesh');


-- Suppliers Table
CREATE TABLE suppliers (
    supplier_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    contact_person VARCHAR(100),
    email VARCHAR(100),
    phone VARCHAR(20) NOT NULL,
    address TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert Suppliers
INSERT INTO suppliers (name, contact_person, email, phone, address, status) VALUES
('Tech Hub BD', 'Rafiq Ahmed', 'rafiq@techhubbd.com', '01812345678', 'Mirpur, Dhaka, Bangladesh', 'active'),
('Fashion Trend Ltd.', 'Nasima Akter', 'nasima@fashiontrend.com', '01798765432', 'Banani, Dhaka, Bangladesh', 'active'),
('Sports World BD', 'Farhan Karim', 'farhan@sportsworldbd.com', '01611223344', 'Chittagong, Bangladesh', 'active'),
('Home Essentials BD', 'Mahbub Rahman', 'mahbub@homeessentials.com', '01899887766', 'Dhanmondi, Dhaka, Bangladesh', 'active'),
('Beauty & Glam BD', 'Sharmeen Jahan', 'sharmeen@beautyglam.com', '01955667788', 'Gulshan, Dhaka, Bangladesh', 'active'),
('Wellness & Health BD', 'Jamil Hasan', 'jamil@wellnessbd.com', '01522334455', 'Sylhet, Bangladesh', 'active'),
('Toys Kingdom BD', 'Mizanur Rahman', 'mizan@toyskingdombd.com', '01344556677', 'Uttara, Dhaka, Bangladesh', 'inactive'),
('Auto Parts BD', 'Kamrul Hossain', 'kamrul@autopartsbd.com', '01833445566', 'Gazipur, Bangladesh', 'active'),
('Kitchen Delights BD', 'Rokeya Sultana', 'rokeya@kitchendelights.com', '01777889900', 'Rajshahi, Bangladesh', 'active'),
('Book House BD', 'Tanvir Hossain', 'tanvir@bookhousebd.com', '01655667788', 'Motijheel, Dhaka, Bangladesh', 'inactive'),
('Baby Care BD', 'Sharmin Akter', 'sharmin@babycarebd.com', '01899887755', 'Narayanganj, Bangladesh', 'active'),
('Furni World BD', 'Shahidul Islam', 'shahidul@furniworld.com', '01933445566', 'Mohakhali, Dhaka, Bangladesh', 'active'),
('Pet Haven BD', 'Tareq Mahmud', 'tareq@pethavenbd.com', '01766778899', 'Barishal, Bangladesh', 'inactive'),
('Gadget Galaxy BD', 'Sadia Rahman', 'sadia@gadgetgalaxybd.com', '01855667788', 'Khilgaon, Dhaka, Bangladesh', 'active'),
('Beverage Masters BD', 'Rakibul Hasan', 'rakibul@beveragemasters.com', '01577889900', 'Comilla, Bangladesh', 'active'),
('Travel Gear BD', 'Fahim Chowdhury', 'fahim@travelgearbd.com', '01722334455', 'Sylhet, Bangladesh', 'active'),
('Electronic Zone BD', 'Rahmat Ullah', 'rahmat@electroniczonebd.com', '01811223344', 'Bashundhara, Dhaka, Bangladesh', 'active'),
('Fashionista BD', 'Moushumi Khatun', 'moushumi@fashionistabd.com', '01688990011', 'Chattogram, Bangladesh', 'inactive'),
('Smart Tech BD', 'Imran Hossain', 'imran@smarttechbd.com', '01911224433', 'Kushtia, Bangladesh', 'active'),
('Luggage Point BD', 'Tania Afroz', 'tania@luggagepointbd.com', '01566778899', 'Jessore, Bangladesh', 'active');


-- Product Categories Table
CREATE TABLE categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Product Categories INSERT 
INSERT INTO categories (name, description) VALUES
('Beverages & Refreshments', 'Refresh and energize with a variety of drinks! From hot beverages like coffee and tea to refreshing juices, smoothies, and soft drinks, find the perfect sip for every mood and occasion.'),
('Electronic Accessories', 'Find the latest gadgets, cables, and accessories for your devices.'),
('Clothing & Fashion', 'Trendy and comfortable outfits for all occasions.'),
('Sports & Outdoors', 'Gear up for your next adventure with top-quality sports and outdoor equipment.'),
('Home & Lifestyle', 'Enhance your living space with stylish and functional home essentials.'),
('Beauty & Personal Care', 'Skincare, makeup, and grooming products for a perfect self-care routine.'),
('Health & Wellness', 'Stay fit and healthy with essential healthcare products and supplements.'),
('Toys & Games', 'Fun and educational toys for kids and adults alike.'),
('Automotive & Accessories', 'Everything you need to maintain and upgrade your vehicle.'),
('Kitchen & Dining', 'Cook and dine in style with premium kitchenware and dining essentials.'),
('Books & Stationery', 'Explore a vast collection of books, notebooks, and office supplies.'),
('Baby & Kids', 'All essentials for newborns, toddlers, and growing kids.'),
('Furniture & Home Decor', 'Stylish and comfortable furniture to transform your home.'),
('Pet Supplies', 'Toys, food, and accessories to keep your furry friends happy.'),
('Gadgets & Smart Devices', 'Stay ahead with the latest smart gadgets and accessories.'),
('Travel & Luggage', 'Find the best luggage, backpacks, and travel essentials for your journeys.');

-- Products Table
CREATE TABLE products (
    product_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    category_id INT,
    supplier_id INT,
    description TEXT,
    cost_price DECIMAL(10, 2) NOT NULL,
    selling_price DECIMAL(10, 2) NOT NULL,
    quantity INT NOT NULL DEFAULT 0,
    min_stock_level INT DEFAULT 10,
    image_url VARCHAR(255),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(category_id),
    FOREIGN KEY (supplier_id) REFERENCES suppliers(supplier_id)
);

-- Orders Table
CREATE TABLE orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT,
    seller_id INT,
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    total_amount DECIMAL(10, 2) NOT NULL,
    discount DECIMAL(10, 2) DEFAULT 0.00,
    tax DECIMAL(10, 2) DEFAULT 0.00,
    payment_method ENUM('cash', 'credit_card', 'debit_card', 'online') NOT NULL,
    status ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',
    notes TEXT,
    FOREIGN KEY (customer_id) REFERENCES users(user_id),
    FOREIGN KEY (seller_id) REFERENCES users(user_id)
);

-- Order Items Table
CREATE TABLE order_items (
    order_item_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT,
    product_id INT,
    quantity INT NOT NULL,
    unit_price DECIMAL(10, 2) NOT NULL,
    subtotal DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(order_id),
    FOREIGN KEY (product_id) REFERENCES products(product_id)
);

-- Purchase Orders Table (for restocking)
CREATE TABLE purchase_orders (
    po_id INT AUTO_INCREMENT PRIMARY KEY,
    supplier_id INT,
    admin_id INT,
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    total_amount DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'received', 'cancelled') DEFAULT 'pending',
    notes TEXT,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(supplier_id),
    FOREIGN KEY (admin_id) REFERENCES users(user_id)
);

-- Purchase Order Items Table
CREATE TABLE purchase_order_items (
    po_item_id INT AUTO_INCREMENT PRIMARY KEY,
    po_id INT,
    product_id INT,
    quantity INT NOT NULL,
    unit_cost DECIMAL(10, 2) NOT NULL,
    subtotal DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (po_id) REFERENCES purchase_orders(po_id),
    FOREIGN KEY (product_id) REFERENCES products(product_id)
);

-- Customer Reviews Table
CREATE TABLE reviews (
    review_id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT,
    customer_id INT,
    rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(product_id),
    FOREIGN KEY (customer_id) REFERENCES users(user_id)
);

-- Cart Table (for customer's shopping cart)
CREATE TABLE cart (
    cart_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT,
    product_id INT,
    quantity INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(user_id),
    FOREIGN KEY (product_id) REFERENCES products(product_id),
    UNIQUE KEY unique_customer_product (customer_id, product_id)
);

-- Loyalty Program Table
CREATE TABLE loyalty_points (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT,
    points INT NOT NULL DEFAULT 0,
    last_updated TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(user_id)
);

-- Promotions Table
CREATE TABLE promotions (
    promotion_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    discount_type ENUM('percentage', 'fixed') NOT NULL,
    discount_value DECIMAL(10, 2) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert sample admin user
INSERT INTO users (username, password, email, full_name, role, phone)
VALUES ('admin', 'CSE311.3E-Shop', 'admin@shop.com', 'System Administrator', 'admin', '1234567890');
-- Default password: CSE311.3E-Shop 
