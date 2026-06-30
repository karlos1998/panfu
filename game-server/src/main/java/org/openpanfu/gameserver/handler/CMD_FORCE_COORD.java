package org.openpanfu.gameserver.handler;

import org.openpanfu.gameserver.PanfuPacket;
import org.openpanfu.gameserver.User;

public class CMD_FORCE_COORD implements IHandler {
	@Override
	public void handlePacket(PanfuPacket packet, User sender) {
		sender.setX(packet.readInt());
		sender.setY(packet.readInt());
	}
}
