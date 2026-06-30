package org.openpanfu.gameserver.handler;

import org.openpanfu.gameserver.PanfuPacket;
import org.openpanfu.gameserver.User;

public class CMD_ROTATE implements IHandler {
	@Override
	public void handlePacket(PanfuPacket packet, User sender) {
		sender.setRot(packet.readInt());
	}
}
