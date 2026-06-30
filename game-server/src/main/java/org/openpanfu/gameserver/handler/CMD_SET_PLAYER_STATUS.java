package org.openpanfu.gameserver.handler;

import org.openpanfu.gameserver.PanfuPacket;
import org.openpanfu.gameserver.User;

public class CMD_SET_PLAYER_STATUS implements IHandler {
	@Override
	public void handlePacket(PanfuPacket packet, User sender) {
		sender.setStatus(packet.readInt());
	}
}
