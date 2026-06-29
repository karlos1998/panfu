package org.openpanfu.gameserver.handler.p2p;

import org.openpanfu.gameserver.PanfuPacket;
import org.openpanfu.gameserver.User;
import org.openpanfu.gameserver.constants.Packets;
import org.openpanfu.gameserver.constants.PlayerToPlayerCommands;

public class CMD_USE_SHARED_ITEM implements IP2PHandler {
	@Override
	public void handlePacket(PanfuPacket packet, String receiver, User sender) {
		int posX = packet.readInt();
		int posY = packet.readInt();
		String actionToPerform = packet.readString();
		String actionFrameName = packet.readString();
		int sort = packet.readInt();

		PanfuPacket response = new PanfuPacket(Packets.RES_PLAYER_TO_PLAYER);
		response.writeInt(sender.getUserId());
		response.writeInt(PlayerToPlayerCommands.ON_USE_SHARED_ITEM);
		response.writeInt(posX);
		response.writeInt(posY);
		response.writeString(actionToPerform);
		response.writeString(actionFrameName);
		response.writeInt(sort);

		// Shared room items drive the sender's own seat/action animation too.
		if (!receiverIncludesSender(receiver, sender.getUserId())) {
			sender.sendPacket(response);
		}

		sender.sendForReceiver(response, receiver);
	}

	private boolean receiverIncludesSender(String receiver, int senderId) {
		if (receiver.equals(String.valueOf(senderId))) {
			return true;
		}

		if (!receiver.contains(",")) {
			return false;
		}

		String[] split = receiver.split(",", 2);
		if (split.length != 2) {
			return false;
		}

		try {
			return Integer.valueOf(split[1]) == senderId;
		} catch (NumberFormatException exception) {
			return false;
		}
	}
}
