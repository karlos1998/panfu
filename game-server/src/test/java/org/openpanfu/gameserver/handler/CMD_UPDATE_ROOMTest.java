package org.openpanfu.gameserver.handler;

import static org.junit.Assert.assertEquals;
import static org.junit.Assert.assertNull;

import org.junit.Test;
import org.openpanfu.gameserver.PanfuPacket;
import org.openpanfu.gameserver.User;
import org.openpanfu.gameserver.constants.HomeCommands;

import io.netty.channel.embedded.EmbeddedChannel;

public class CMD_UPDATE_ROOMTest {
	@Test
	public void refreshesOtherVisitorsWithoutReloadingTheEditor() {
		EmbeddedChannel channel = new EmbeddedChannel();
		RecordingUser user = new RecordingUser(channel);

		new CMD_UPDATE_ROOM().handlePacket(new PanfuPacket(HomeCommands.CMD_UPDATE_ROOM), user);

		assertEquals("33|", user.roomUpdate.toString());
		assertNull(channel.readOutbound());
	}

	private static class RecordingUser extends User {
		private PanfuPacket roomUpdate;

		private RecordingUser(EmbeddedChannel channel) {
			super(channel, null);
		}

		@Override
		public void sendRoomExcludingMe(PanfuPacket packet) {
			roomUpdate = packet;
		}
	}
}
