CREATE TABLE users (
user_id INT NOT NULL,
email VARCHAR(100) NOT NULL,
first_name VARCHAR(50) NOT NULL,
surname VARCHAR(75) NOT NULL,
username VARCHAR(20) NOT NULL,
password TEXT NOT NULL,
salt varchar(25) NOT NULL, 
CONSTRAINT usertb_unique UNIQUE (email, username),
PRIMARY KEY (user_id)
)