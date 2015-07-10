#!/usr/local/bin/bash

lib=/Users/kevin/work/hexagone/sheaker-miscs/elasticsearch-jdbc-1.6.0.0/lib
bin=/Users/kevin/work/hexagone/sheaker-miscs/elasticsearch-jdbc-1.6.0.0/bin

printf 'Deleting index...'
curl -silent -XDELETE 'localhost:9200/client_1' > /dev/null
echo -e '\t\t\tOK'

printf 'Creating mapping...'
curl -silent -XPOST 'http://localhost:9200/client_1' -d '{
    "mappings" : {
        "checkin" : {
            "_parent" : {
              "type" : "user"
            }
        },
        "payment" : {
            "_parent" : {
              "type" : "user"
            }
        }
    }
}' > /dev/null
echo -e '\t\t\tOK'

printf 'Importing users table...'
echo '{
    "type" : "jdbc",
    "jdbc" : {
        "url" : "jdbc:mysql://localhost:3306/client_1",
        "user" : "root",
        "password" : "",
        "sql" : "SELECT *, id AS _id FROM users",
        "index" : "client_1",
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
        "sql" : "SELECT *, id AS _id, user_id AS _parent FROM users_checkin",
        "index" : "client_1",
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
       "sql" : "SELECT *, id AS _id, user_id AS _parent FROM users_payments",
       "index" : "client_1",
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
