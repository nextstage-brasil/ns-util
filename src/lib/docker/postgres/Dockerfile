ARG PGVERSION

FROM postgres:${PGVERSION}

ARG PGVERSION

# Atualizar imagem
RUN apt-get update

# Instalar Postgis
RUN apt install postgis postgresql-${PGVERSION}-postgis-3 -y
RUN apt install postgresql-${PGVERSION}-postgis-3-scripts -y

# Full Text Search config
COPY ./extras/my_synonym.syn /usr/share/postgresql/${PGVERSION}/tsearch_data/my_synonym.syn
COPY ./extras/my_thesaurus.ths /usr/share/postgresql/${PGVERSION}/tsearch_data/my_thesaurus.ths

COPY ./init-scripts/. ./docker-entrypoint-initdb.d/
COPY ./conf.d/. /etc/postgresql/${PGVERSION}/main/conf.d/


RUN mkdir /backup
COPY ./backup/* /backup/