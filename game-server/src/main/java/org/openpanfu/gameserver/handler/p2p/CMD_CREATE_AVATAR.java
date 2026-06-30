package org.openpanfu.gameserver.handler.p2p;

import org.openpanfu.gameserver.PanfuPacket;
import org.openpanfu.gameserver.User;

public class CMD_CREATE_AVATAR implements IP2PHandler {
	@Override
	public void handlePacket(PanfuPacket packet, String receiver, User sender) {
		int x = packet.readInt();
		int y = packet.readInt();
		String action = packet.readString();
		int rotation = packet.readInt();
		String timeTravel = packet.readString();
		String pokopetType = packet.readString();
		int sheriff = packet.readInt();
		String clothes = packet.readString();
		sender.storeAvatarSnapshot(x, y, action, rotation, timeTravel, pokopetType, clothes);
		sender.sendSetAvatarForReceiver(receiver);
		sender.sendForReceiver(sender.createAvatarSnapshotPacket(), receiver);
	}
}
