# OpenPanfu GameServer

### About
Panfu is made out of two runtime components: the Laravel application and the Java GameServer.

Laravel exposes the AMF gateway used by the Flash client and handles persistent operations such as login, registration and inventory. The GameServer handles live multiplayer interactions such as movement, actions and multiplayer games like 4boom and hotboom.

### Installation
Start the project with Docker Compose. To run the GameServer separately, update `GameServer.properties` so its database settings match the Laravel application's database.

#### Running the GameServer
If you've got a [release](https://github.com/openPanfu/GameServer/releases), you can simply extract it and run with the following command: `java -jar (jar name)`.

#### Compiling the GameServer
To compile the jar, you need [Maven](https://maven.apache.org/).
After you've installed it and confirmed you can run `mvn -v`, run `mvn clean compile package`

A jar file will appear in the target directory, now copy it and GameServer.properties to a directory and run `java -jar (jar name)`.

#### Adding a new GameServer

In your database, there's a table called gameservers. Add new entries for the gameservers you want to host.

All entries in that table will be hosted from this server.
