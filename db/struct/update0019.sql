CREATE TABLE structpublish_assignments_patterns (
     pattern TEXT NOT NULL,
     user TEXT NOT NULL,
     status TEXT NOT NULL,
     PRIMARY KEY (pattern, user, status)
);

CREATE TABLE structpublish_assignments (
     pid TEXT NOT NULL,
     user TEXT NOT NULL,
     status TEXT NOT NULL,
     assigned INTEGER,
     PRIMARY KEY(pid, user, status, assigned)
);
