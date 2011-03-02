create table if not exists decrypted_entry
(
  id INT NOT NULL,
  salutation VARCHAR(63),
  gname VARCHAR(255),
  sname VARCHAR(255),
  email VARCHAR(255),
  addr_street VARCHAR(511),
  addr_city VARCHAR(255),
  addr_postcode VARCHAR(20),
  addr_country VARCHAR(128),
  verify_ok ENUM('Y','N') NOT NULL DEFAULT 'N',
  display SET('salutation','gname', 'sname', 'addr_country', 'addr_city', 'addr_postcode', 'addr_street'),
  primary key (id)
) ENGINE = InnoDB, CHARACTER SET = "utf8";
