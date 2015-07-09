#!/usr/local/bin/bash

lib=/Users/kevin/work/hexagone/sheaker-miscs/elasticsearch-jdbc-1.6.0.0/lib
bin=/Users/kevin/work/hexagone/sheaker-miscs/elasticsearch-jdbc-1.6.0.0/bin

printf 'Importing users table...'
echo '{
    "type" : "jdbc",
    "jdbc" : {
        "url" : "jdbc:mysql://localhost:3306/client_1",
        "user" : "root",
        "password" : "",
        "sql" : "select *, id as _id from users",
        "index" : "users",
        "type" : "user",
        "elasticsearch" : {
           "cluster" : "elasticsearch_brew",
           "host" : "localhost",
           "port" : 9300
       }
   }
}' | java \
      -cp "${lib}/*" \
      -Dlog4j.configurationFile=${bin}/log4j2.xml \
      org.xbib.tools.Runner \
      org.xbib.tools.JDBCImporter
echo -e '\t\tOK'

printf 'Importing users_checkin table...'
echo '{
    "type" : "jdbc",
    "jdbc" : {
        "url" : "jdbc:mysql://localhost:3306/client_1",
        "user" : "root",
        "password" : "",
        "sql" : "select *, id as _id from users_checkin",
        "index" : "users_checkin",
        "type" : "checkin",
        "elasticsearch" : {
           "cluster" : "elasticsearch_brew",
           "host" : "localhost",
           "port" : 9300
       }
    }
}' | java \
      -cp "${lib}/*" \
      -Dlog4j.configurationFile=${bin}/log4j2.xml \
      org.xbib.tools.Runner \
      org.xbib.tools.JDBCImporter
echo -e '\tOK'

printf 'Importing users_payments table...'
echo '{
    "type" : "jdbc",
    "jdbc" : {
       "url" : "jdbc:mysql://localhost:3306/client_1",
       "user" : "root",
       "password" : "",
       "sql" : "select *, id as _id from users_payments",
       "index" : "users_payments",
       "type" : "payment",
       "elasticsearch" : {
          "cluster" : "elasticsearch_brew",
          "host" : "localhost",
          "port" : 9300
      }
    }
}' | java \
       -cp "${lib}/*" \
       -Dlog4j.configurationFile=${bin}/log4j2.xml \
       org.xbib.tools.Runner \
       org.xbib.tools.JDBCImporter
echo -e '\tOK'
