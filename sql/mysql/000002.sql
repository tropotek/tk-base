






ALTER TABLE user ADD phone varchar(32) DEFAULT '' NULL;
ALTER TABLE user
  MODIFY COLUMN phone varchar(32) DEFAULT '' AFTER image,
  MODIFY COLUMN active tinyint(1) NOT NULL DEFAULT 1 AFTER notes,
  MODIFY COLUMN email varchar(168) NOT NULL DEFAULT '' AFTER image;





