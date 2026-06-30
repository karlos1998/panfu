package org.openpanfu.gameserver;

import static org.junit.Assert.assertEquals;
import static org.junit.Assert.assertNull;

import org.junit.Before;
import org.junit.Test;
import org.openpanfu.gameserver.constants.Packets;
import org.openpanfu.gameserver.handler.CMD_FORCE_COORD;

import io.netty.channel.embedded.EmbeddedChannel;

public class UserAvatarStateTest {
	@Before
	public void setUpLogger() {
		GameServer.getProperties().setProperty("gameserver.ansilogging", "0");
	}

	@Test
	public void playerStringIncludesUsernameForRoomAttendees() {
		User user = user(4, "Safari", 409, 370);
		user.setStatus(2);
		user.setRot(5);

		assertEquals("4:409:370:Safari:2:5:0", user.getPlayerString());
	}

	@Test
	public void avatarBootstrapReplaysKnownAvatarStateToJoiningPlayer() {
		User existing = user(4, "Safari", 409, 370);
		existing.storeAvatarSnapshot(409, 370, "", 0, "", "", "1001");
		existing.storeAvatarUpdateSnapshot("", "1001,104244");

		EmbeddedChannel joiningChannel = new EmbeddedChannel();
		User joining = new User(joiningChannel, null);

		existing.sendAvatarBootstrapTo(joining);

		assertEquals("30;4;4;409;370;Safari|", joiningChannel.readOutbound());
		assertEquals("113;4;10;409;370;;0;;;0;1001|", joiningChannel.readOutbound());
		assertEquals("113;4;11;;0;1001,104244|", joiningChannel.readOutbound());
		assertNull(joiningChannel.readOutbound());
	}

	@Test
	public void forceCoordUpdatesServerSidePlayerPosition() {
		User user = user(4, "Karlos", 450, 450);
		PanfuPacket packet = new PanfuPacket(Packets.CMD_FORCE_COORD);
		packet.writeInt(435);
		packet.writeInt(172);

		new CMD_FORCE_COORD().handlePacket(packet, user);

		assertEquals(435, user.getX());
		assertEquals(172, user.getY());
	}

	private User user(int id, String username, int x, int y) {
		User user = new User(new EmbeddedChannel(), null);
		user.setUserId(id);
		user.setUsername(username);
		user.setRoomId(4);
		user.setX(x);
		user.setY(y);
		return user;
	}
}
