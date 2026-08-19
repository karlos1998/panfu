package org.openpanfu.gameserver.handler;

import org.openpanfu.gameserver.PanfuPacket;
import org.openpanfu.gameserver.User;
import org.openpanfu.gameserver.constants.Packets;

public class CMD_CHANGE_ROOM implements IHandler {
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

		sender.setSubRoom(subRoom);
		sender.setX(x);
		sender.setY(y);

		// The Flash client handles packet 12 as ON_SUBROOM_ENTER and uses its only
		// parameter to load the corresponding scenery SWF.
		PanfuPacket enteredSubRoom = new PanfuPacket(Packets.RES_ON_SUBROOM_ENTER);
		enteredSubRoom.writeInt(subRoom);
		sender.sendPacket(enteredSubRoom);
	}
}
