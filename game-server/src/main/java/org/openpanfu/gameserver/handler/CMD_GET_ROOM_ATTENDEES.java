/**
 * This file is part of openPanfu, a project that imitates the Flex remoting
 * and gameservers of Panfu.
 *
 * @author Altro50 <altro50@msn.com>
 */

package org.openpanfu.gameserver.handler;

import java.util.List;

import org.openpanfu.gameserver.PanfuPacket;
import org.openpanfu.gameserver.User;
import org.openpanfu.gameserver.constants.Packets;

public class CMD_GET_ROOM_ATTENDEES implements IHandler {
	@Override
	public void handlePacket(PanfuPacket packet, User sender) {
		List<User> users = sender.getGameServer().getSessionManager().getUsersInRoom(sender.getRoomId(),
				sender.isInHome(), sender.getSubRoom());

		PanfuPacket roomAttendees = new PanfuPacket(Packets.RES_GET_ROOM_ATTENDEES);
		roomAttendees.writeString(getRoomString(sender.getRoomId(), sender.isInHome(), users));
		sender.sendPacket(roomAttendees);

		for (User user : users) {
			if (user.getUserId() != sender.getUserId()) {
				user.sendAvatarBootstrapTo(sender);
			}
		}

		sender.sendRoomExcludingMe(sender.createSetAvatarPacket());
		sender.setChatEnabled(sender.getGameServer().isChatEnabled());
	}

	private String getRoomString(int roomId, boolean inHome, List<User> users) {
		String roomString = String.valueOf(roomId);
		for (User user : users) {
			if (user.isInHome() == inHome) {
				roomString += ";" + user.getPlayerString();
			}
		}
		return roomString;
	}
}
