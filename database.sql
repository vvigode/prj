-- Создание базы данных и таблиц для системы заказов

-- Таблица категорий
CREATE TABLE categories (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Таблица продуктов
CREATE TABLE products (
    id SERIAL PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    category_id INTEGER REFERENCES categories(id),
    stock_quantity INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Таблица заказов
CREATE TABLE orders (
    id SERIAL PRIMARY KEY,
    product_id INTEGER REFERENCES products(id),
    quantity INTEGER NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    purchase_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    customer_name VARCHAR(100),
    status VARCHAR(50) DEFAULT 'completed'
);

-- Таблица статистики
CREATE TABLE statistics (
    id SERIAL PRIMARY KEY,
    stat_type VARCHAR(50) NOT NULL,
    stat_value TEXT NOT NULL,
    period_start TIMESTAMP,
    period_end TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Заполнение тестовыми данными
INSERT INTO categories (name, description) VALUES 
('Электроника', 'Электронные устройства и аксессуары'),
('Одежда', 'Мужская и женская одежда'),
('Книги', 'Художественная и техническая литература'),
('Спорт', 'Спортивные товары и инвентарь'),
('Дом и сад', 'Товары для дома и садоводства');

INSERT INTO products (name, price, category_id, stock_quantity) VALUES 
-- Электроника (1)
('iPhone 15', 89999.00, 1, 50),
('Samsung Galaxy S24', 79999.00, 1, 30),
('Планшет iPad Air', 59999.00, 1, 35),
('Смарт-часы Apple Watch', 39999.00, 1, 60),
('Электронная книга Kindle', 12999.00, 1, 45),
('Телевизор LG 55"', 55999.00, 1, 15),
('Монитор Dell 27"', 27999.00, 1, 25),
-- Одежда (2)
('Футболка Nike', 2999.00, 2, 100),
('Джинсы Levis', 5999.00, 2, 75),
('Кроссовки Adidas', 7999.00, 2, 80),
('Куртка The North Face', 11999.00, 2, 70),
('Кроссовки Puma', 6999.00, 2, 90),
('Рюкзак Herschel', 4999.00, 2, 110),
('Шорты Adidas', 2499.00, 2, 120),
-- Книги (3)
('Программирование на Python', 1599.00, 3, 200),
('Учебник по физике', 2299.00, 3, 120),
('Роман "1984"', 799.00, 3, 300),
('Учебник по математике', 1899.00, 3, 150),
('Книга "Clean Code"', 2599.00, 3, 180),
-- Спорт (4)
('Мяч футбольный', 1999.00, 4, 150),
('Гантели 10 кг', 3599.00, 4, 55),
('Скакалка', 499.00, 4, 200),
('Фитнес резинка', 299.00, 4, 250),
('Фонарик LedLenser', 1999.00, 4, 95),
-- Дом и сад (5)
('Кофеварка Philips', 12999.00, 5, 25),
('Надувной бассейн', 8999.00, 5, 20),
('Газонокосилка Bosch', 22999.00, 5, 10),
('Пылесос Xiaomi', 18999.00, 5, 30),
('Термокружка Stanley', 3499.00, 5, 80),
('Наушники Sony', 15999.00, 1, 40);


-- Создание индексов для оптимизации
CREATE INDEX idx_orders_purchase_time ON orders(purchase_time);
CREATE INDEX idx_orders_product_id ON orders(product_id);
CREATE INDEX idx_products_category_id ON products(category_id);