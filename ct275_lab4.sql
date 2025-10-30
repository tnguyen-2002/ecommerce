CREATE TABLE users (
    id serial PRIMARY KEY,
    name character varying(255) NOT NULL,
    email character varying(255) NOT NULL,
    password character varying(255) NOT NULL,
    created_at timestamp with time zone,
    updated_at timestamp with time zone
);

CREATE TABLE tags (
    id serial PRIMARY KEY,
    name character varying(255) NOT NULL,
    user_id integer NOT NULL REFERENCES users(id),
    created_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(name, user_id)
);

CREATE TABLE shortened_urls (
    id serial PRIMARY KEY,
    short_slug character varying(255) NOT NULL UNIQUE,
    long_url character varying(255) NOT NULL,
    user_id integer NOT NULL REFERENCES users(id),
    tags integer[] DEFAULT '{}',
    created_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP
);
