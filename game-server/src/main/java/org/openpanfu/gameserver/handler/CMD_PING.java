package org.openpanfu.gameserver.handler;

import org.openpanfu.gameserver.PanfuPacket;
import org.openpanfu.gameserver.User;

public class CMD_PING implements IHandler {
	@Override
	public void handlePacket(PanfuPacket packet, User sender) {
		// The Flash client sends this as a keep-alive. The TCP connection itself is
		// enough for now, so acknowledging it server-side would only add noise.
	}
}
