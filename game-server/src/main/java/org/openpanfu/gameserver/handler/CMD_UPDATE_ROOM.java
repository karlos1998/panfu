package org.openpanfu.gameserver.handler;

import org.openpanfu.gameserver.PanfuPacket;
import org.openpanfu.gameserver.User;
import org.openpanfu.gameserver.constants.HomeCommands;

public class CMD_UPDATE_ROOM implements IHandler {
	@Override
	public void handlePacket(PanfuPacket packet, User sender) {
		PanfuPacket updateRoom = new PanfuPacket(HomeCommands.ON_UPDATE_ROOM);
		// The editor already has the new furniture state locally. Reloading it here
		// resets a subroom to the main room while the AMF save is completing.
		sender.sendRoomExcludingMe(updateRoom);
	}
}
