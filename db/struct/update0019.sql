CREATE TABLE structpublish_revisions (
     id TEXT NOT NULL,
     user TEXT NOT NULL,
     rev INT NOT NULL,
     status TEXT DEFAULT '',
     version INT DEFAULT 0
);
