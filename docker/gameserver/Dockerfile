FROM maven:3.8.8-eclipse-temurin-8 AS build

WORKDIR /src
COPY game-server/pom.xml .
RUN mvn -B -ntp dependency:go-offline
COPY game-server/src ./src
RUN mvn -B -ntp clean package -DskipTests

FROM eclipse-temurin:8-jre

WORKDIR /srv/openpanfu
COPY --from=build /src/target/gameserver-1.0-SNAPSHOT.jar ./gameserver.jar
COPY docker/gameserver/gameserver-entrypoint.sh /usr/local/bin/openpanfu-gameserver-entrypoint

RUN chmod +x /usr/local/bin/openpanfu-gameserver-entrypoint && mkdir -p plugins

EXPOSE 9595
CMD ["openpanfu-gameserver-entrypoint"]
