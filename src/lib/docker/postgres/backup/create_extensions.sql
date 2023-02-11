create extension if not exists unaccent;
create extension if not exists citext;
CREATE EXTENSION if not exists pg_trgm;
create extension if not exists btree_gin;
create extension if not exists postgis;
create extension if not exists fuzzystrmatch;
CREATE EXTENSION if not exists pgcrypto;

CREATE operator ~@ (LEFTARG = jsonb, RIGHTARG = text, PROCEDURE = jsonb_exists);    
CREATE operator ~@| (LEFTARG = jsonb, RIGHTARG = text[], PROCEDURE = jsonb_exists_any);
CREATE operator ~@& (LEFTARG = jsonb, RIGHTARG = text[], PROCEDURE = jsonb_exists_all);