drop text search configuration if exists my_portuguese;
drop text search configuration if exists my_simple;
drop text search dictionary if exists my_thesaurus;
drop text search dictionary if exists my_synonym;

create text search configuration my_portuguese(copy = portuguese);

CREATE TEXT SEARCH DICTIONARY my_synonym(
    TEMPLATE = synonym,
    SYNONYMS = my_synonym
);

ALTER TEXT SEARCH CONFIGURATION my_portuguese
    ALTER MAPPING FOR asciiword, asciihword, hword, hword_part, word
    WITH my_synonym, portuguese_stem;

ALTER TEXT SEARCH CONFIGURATION my_portuguese
    ALTER MAPPING FOR hword, hword_part, word
    WITH unaccent, my_synonym, portuguese_stem;

CREATE TEXT SEARCH DICTIONARY my_thesaurus(
    TEMPLATE = thesaurus,
    DictFile = my_thesaurus,
    Dictionary = pg_catalog.portuguese_stem
);

ALTER TEXT SEARCH CONFIGURATION my_portuguese
    ALTER MAPPING FOR asciiword, asciihword, hword, hword_part, word
    WITH my_thesaurus, portuguese_stem;

ALTER TEXT SEARCH CONFIGURATION my_portuguese
    ALTER MAPPING FOR hword, hword_part, word
    WITH unaccent, my_thesaurus, portuguese_stem;

CREATE TEXT SEARCH configuration my_simple (COPY = simple);

ALTER TEXT SEARCH CONFIGURATION my_simple
    ALTER MAPPING FOR hword, hword_part, word
    WITH unaccent, simple;

ALTER TEXT SEARCH dictionary my_thesaurus(DictFile = my_thesaurus);