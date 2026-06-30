package org.openpanfu.gameserver.handler.p2p;

import org.openpanfu.gameserver.PanfuPacket;
import org.openpanfu.gameserver.User;

public class CMD_UPDATE_AVATAR implements IP2PHandler {
	@Override
	public void handlePacket(PanfuPacket packet, String receiver, User sender) {
		String pokopet = packet.readString();
		packet.readInt();
		String playerString = packet.readString();
		sender.storeAvatarUpdateSnapshot(pokopet, playerString);
		sender.sendForReceiver(sender.createAvatarUpdateSnapshotPacket(), receiver);
	}
}
