package org.openpanfu.gameserver.handler;

import org.openpanfu.gameserver.PanfuPacket;
import org.openpanfu.gameserver.User;
import org.openpanfu.gameserver.constants.Packets;

public class CMD_CHANGE_ROOM implements IHandler {
	private static final int SPACE_STATION_UP = 100870;
	private static final int SPACE_STATION_DOWN = 100871;
	private static final int CASTLE_LEFT = 100938;
	private static final int CASTLE_RIGHT = 100939;

	@Override
	public void handlePacket(PanfuPacket packet, User sender) {
		// The user whose treehouse the player is currently visiting
		int roomOwner = packet.readInt();
		// Zero means the main room; any other value identifies a subroom scenery.
		int subRoom = packet.readInt();
		// Spawn coordinates in the destination room
		int x = packet.readInt();
		int y = packet.readInt();

		if (!sender.isInHome() || sender.getRoomId() != roomOwner || subRoom < 0) {
			return;
		}

		// Remove the avatar from clients which remain in the previous subroom before
		// changing the server-side room filter.
		if (sender.getGameServer() != null) {
			PanfuPacket unsetAvatar = new PanfuPacket(Packets.RES_UNSET_AVATAR);
			unsetAvatar.writeInt(sender.getUserId());
			sender.sendRoomExcludingMe(unsetAvatar);
		}

		SpawnPoint spawnPoint = getSpawnPoint(sender.getSubRoom(), subRoom, x, y);
		sender.setSubRoom(subRoom);
		sender.setX(spawnPoint.x);
		sender.setY(spawnPoint.y);

		// The Flash client handles packet 12 as ON_SUBROOM_ENTER and uses its only
		// parameter to load the corresponding scenery SWF.
		PanfuPacket enteredSubRoom = new PanfuPacket(Packets.RES_ON_SUBROOM_ENTER);
		enteredSubRoom.writeInt(subRoom);
		sender.sendPacket(enteredSubRoom);
	}

	private SpawnPoint getSpawnPoint(int previousSubRoom, int destinationSubRoom, int fallbackX, int fallbackY) {
		switch (destinationSubRoom) {
		case SPACE_STATION_UP:
			return new SpawnPoint(135, 331);
		case SPACE_STATION_DOWN:
			return new SpawnPoint(635, 322);
		case CASTLE_LEFT:
			return new SpawnPoint(610, 271);
		case CASTLE_RIGHT:
			return new SpawnPoint(105, 311);
		case 0:
			return getMainRoomSpawnPoint(previousSubRoom, fallbackX, fallbackY);
		default:
			return new SpawnPoint(fallbackX, fallbackY);
		}
	}

	private SpawnPoint getMainRoomSpawnPoint(int previousSubRoom, int fallbackX, int fallbackY) {
		switch (previousSubRoom) {
		case SPACE_STATION_UP:
			return new SpawnPoint(120, 347);
		case SPACE_STATION_DOWN:
			return new SpawnPoint(635, 334);
		case CASTLE_LEFT:
			return new SpawnPoint(120, 290);
		case CASTLE_RIGHT:
			return new SpawnPoint(620, 316);
		default:
			return new SpawnPoint(fallbackX, fallbackY);
		}
	}

	private static class SpawnPoint {
		private final int x;
		private final int y;

		private SpawnPoint(int x, int y) {
			this.x = x;
			this.y = y;
		}
	}
}
