package org.openpanfu.gameserver.handler;

import org.openpanfu.gameserver.PanfuPacket;
import org.openpanfu.gameserver.User;
import org.openpanfu.gameserver.constants.Packets;
import org.openpanfu.gameserver.database.dao.MinigameRewardDAO;

public class CMD_QUIT_GAME implements IHandler {
	@Override
	public void handlePacket(PanfuPacket packet, User sender) {
		int gameId = packet.readInt();
		int points = packet.readInt();
		if (sender.getCurrentGame() == gameId) {
			int awardedCoins = MinigameRewardDAO.awardCoinsForScore(sender.getUserId(), gameId, points);
			if (awardedCoins > 0) {
				PanfuPacket coinsPacket = new PanfuPacket(Packets.RES_UPDATE_PLAYERINFO);
				coinsPacket.writeInt(awardedCoins);
				sender.sendPacket(coinsPacket);
			}
		}

		sender.quitGame();
		// Makes the user rejoin the room they initially joined the game in.
		// We don't use sender.joinRoom() because then they'll be unset.
		PanfuPacket joinRoom = new PanfuPacket(Packets.RES_ON_ROOM_JOINED);
		joinRoom.writeInt(sender.getRoomId());
		sender.sendPacket(joinRoom);
	}
}
