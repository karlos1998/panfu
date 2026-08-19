package org.openpanfu.gameserver.handler;

import static org.junit.Assert.assertEquals;
import static org.junit.Assert.assertNull;

import org.junit.Before;
import org.junit.Test;
import org.openpanfu.gameserver.GameServer;
import org.openpanfu.gameserver.PanfuPacket;
import org.openpanfu.gameserver.User;
import org.openpanfu.gameserver.constants.HomeCommands;

import io.netty.channel.embedded.EmbeddedChannel;

public class CMD_CHANGE_ROOMTest {
	@Before
	public void setUpLogger() {
		GameServer.getProperties().setProperty("gameserver.ansilogging", "0");
	}

	@Test
	public void entersRequestedHomeSubroomAndAcknowledgesIt() {
		EmbeddedChannel channel = new EmbeddedChannel();
		User user = homeUser(channel, 7, 42);
		PanfuPacket packet = changeRoomPacket(42, 100870, 100, 100);

		new CMD_CHANGE_ROOM().handlePacket(packet, user);

		assertEquals(100870, user.getSubRoom());
		assertEquals(100, user.getX());
		assertEquals(100, user.getY());
		assertEquals("12;100870|", channel.readOutbound());
		assertNull(channel.readOutbound());
	}

	@Test
	public void returnsToMainHomeRoom() {
		EmbeddedChannel channel = new EmbeddedChannel();
		User user = homeUser(channel, 7, 42);
		user.setSubRoom(100871);

		new CMD_CHANGE_ROOM().handlePacket(changeRoomPacket(42, 0, 100, 100), user);

		assertEquals(0, user.getSubRoom());
		assertEquals("12;0|", channel.readOutbound());
	}

	@Test
	public void ignoresSubroomChangeForAnotherHome() {
		EmbeddedChannel channel = new EmbeddedChannel();
		User user = homeUser(channel, 7, 42);

		new CMD_CHANGE_ROOM().handlePacket(changeRoomPacket(99, 100870, 100, 100), user);

		assertEquals(0, user.getSubRoom());
		assertNull(channel.readOutbound());
	}

	private User homeUser(EmbeddedChannel channel, int userId, int roomOwner) {
		User user = new User(channel, null);
		user.setUserId(userId);
		user.setRoomId(roomOwner);
		user.setInHome(true);
		return user;
	}

	private PanfuPacket changeRoomPacket(int roomOwner, int subRoom, int x, int y) {
		PanfuPacket packet = new PanfuPacket(HomeCommands.CMD_CHANGE_ROOM);
		packet.writeInt(roomOwner);
		packet.writeInt(subRoom);
		packet.writeInt(x);
		packet.writeInt(y);
		packet.writeInt(0);
		return packet;
	}
}
