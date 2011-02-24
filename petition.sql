
create table if not exists entry
(
  id INT NOT NULL AUTO_INCREMENT,
  gname VARCHAR(255),
  sname VARCHAR(255),
  addr_street VARCHAR(511),
  addr_city VARCHAR(255),
  addr_postcode VARCHAR(20),
  addr_country VARCHAR(128),
  crypted_data BLOB NOT NULL,
  crypted_envkey BLOB NOT NULL,
  crypted_enviv VARCHAR(128),
  crypted_mode ENUM('OPENSSL_SEAL','OPENSSL_MCRYPT_AES128','OPENSSL_MCRYPT_AES256') NOT NULL,
  verify_code CHAR(64) UNIQUE,
  verify_ok ENUM('Y','N') NOT NULL DEFAULT 'N',
  display SET('gname', 'sname', 'addr_country', 'addr_city', 'addr_postcode', 'addr_street'),
  primary key (id)
) ENGINE = InnoDB, CHARACTER SET = "utf8";
