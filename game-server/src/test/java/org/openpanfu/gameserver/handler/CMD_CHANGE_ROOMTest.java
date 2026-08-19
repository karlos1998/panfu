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
		assertEquals(135, user.getX());
		assertEquals(331, user.getY());
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
		assertEquals(635, user.getX());
		assertEquals(334, user.getY());
		assertEquals("12;0|", channel.readOutbound());
	}

	@Test
	public void usesDoorSpawnPointsForEveryMultiRoomTreehouseTransition() {
		assertTransitionSpawn(0, 100870, 135, 331);
		assertTransitionSpawn(0, 100871, 635, 322);
		assertTransitionSpawn(100870, 0, 120, 347);
		assertTransitionSpawn(100871, 0, 635, 334);
		assertTransitionSpawn(0, 100938, 610, 271);
		assertTransitionSpawn(0, 100939, 105, 311);
		assertTransitionSpawn(100938, 0, 120, 290);
		assertTransitionSpawn(100939, 0, 620, 316);
	}

	@Test
	public void preservesClientSpawnForAnUnknownSubroom() {
		EmbeddedChannel channel = new EmbeddedChannel();
		User user = homeUser(channel, 7, 42);

		new CMD_CHANGE_ROOM().handlePacket(changeRoomPacket(42, 123456, 240, 315), user);

		assertEquals(240, user.getX());
		assertEquals(315, user.getY());
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

	private void assertTransitionSpawn(int previousSubRoom, int destinationSubRoom, int expectedX, int expectedY) {
		EmbeddedChannel channel = new EmbeddedChannel();
		User user = homeUser(channel, 7, 42);
		user.setSubRoom(previousSubRoom);

		new CMD_CHANGE_ROOM().handlePacket(changeRoomPacket(42, destinationSubRoom, 100, 100), user);

		assertEquals(expectedX, user.getX());
		assertEquals(expectedY, user.getY());
	}
}
