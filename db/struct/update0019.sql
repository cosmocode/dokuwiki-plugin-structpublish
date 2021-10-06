CREATE TABLE structpublish_assignments_patterns (
     pattern TEXT NOT NULL,
     assignee TEXT NOT NULL,
     PRIMARY KEY (pattern, assignee)
);

CREATE TABLE structpublish_assignments (
     pid TEXT NOT NULL,
     assigned INTEGER,
     PRIMARY KEY(pid, assigned)
)
