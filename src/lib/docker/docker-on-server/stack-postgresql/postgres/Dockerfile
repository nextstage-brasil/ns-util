ARG PGVERSION=14

FROM postgres:${PGVERSION}

ARG PGVERSION

# Atualizar imagem
RUN apt update && apt upgrade -y

# Instalar Postgis
RUN apt install postgis postgresql-${PGVERSION}-postgis-3 -y
RUN apt install postgresql-${PGVERSION}-postgis-3-scripts


# Configs and tuning
COPY ./conf.d/tuning.conf /etc/postgresql/${PGVERSION}/main/conf.d/00-tuning.conf
COPY ./conf.d/extra.conf /etc/postgresql/${PGVERSION}/main/conf.d/01-extra.conf

RUN chown -R postgres:postgres /etc/postgresql/${PGVERSION}/main

# Full Text Search config
COPY ./extras/my_synonym.syn /usr/share/postgresql/${PGVERSION}/tsearch_data/my_synonym.syn
COPY ./extras/my_thesaurus.ths /usr/share/postgresql/${PGVERSION}/tsearch_data/my_thesaurus.ths


COPY ./extras /tmp/extras