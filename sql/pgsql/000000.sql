



DROP TABLE IF EXISTS "user";
CREATE TABLE IF NOT EXISTS "user" (
  id SERIAL PRIMARY KEY,
  name VARCHAR(128),
  email VARCHAR(255),
  username VARCHAR(128),
  password VARCHAR(128),
  role VARCHAR(255),
  notes TEXT,
  last_login TIMESTAMP DEFAULT NULL,
  active NUMERIC(1) NOT NULL DEFAULT 1,
  hash VARCHAR(128),
  del NUMERIC(1) NOT NULL DEFAULT 0,
  modified TIMESTAMP DEFAULT NOW(),
  created TIMESTAMP DEFAULT NOW(),
  CONSTRAINT username UNIQUE (username),
  CONSTRAINT email UNIQUE (email)
);

INSERT INTO "user" (id, name, email, username, password, role, active, hash, modified, created)
VALUES
  (1, 'Administrator', 'admin@example.com', 'admin', MD5('password'), 'admin', 1, MD5('1:admin:admin@example.com'), NOW() , NOW()),
  (2, 'User 1', 'user@example.com', 'user1', MD5('password'), 'user', 1, MD5('2:user:user@example.com'), NOW() , NOW())
;
