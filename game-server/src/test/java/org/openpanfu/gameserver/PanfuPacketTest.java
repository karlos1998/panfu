package org.openpanfu.gameserver;

import static org.junit.Assert.assertEquals;
import static org.junit.Assert.assertSame;

import org.junit.Test;

import io.netty.channel.embedded.EmbeddedChannel;

public class PanfuPacketTest {
	@Test
	public void serializesPacketHeaderAndParametersUsingTheFlashDelimiterContract() {
		PanfuPacket packet = new PanfuPacket(900);
		packet.writeString("secret");
		packet.writeString("testConnection");

		assertEquals("900;secret;testConnection|", packet.toString());
		assertEquals(2, packet.getParameterCount());
	}

	@Test
	public void readsIntegersIncludingLegacyDecimalValuesAndAdvancesTheCursor() {
		PanfuPacket packet = new PanfuPacket(1);
		packet.passParameters(new String[] { "12.75", "-4", "invalid" });

		assertEquals(12, packet.readInt());
		assertEquals(-4, packet.readInt());
		assertEquals(-1, packet.readInt());
		assertEquals(3, packet.getPos());
	}

	@Test
	public void missingParametersReturnSafeSentinelsAndStillAdvanceTheCursor() {
		PanfuPacket packet = new PanfuPacket(1);

		assertEquals(-1, packet.readInt());
		assertEquals("", packet.readString());
		assertEquals(2, packet.getPos());
	}

	@Test
	public void cursorCanBeResetForASecondPass() {
		PanfuPacket packet = new PanfuPacket(1);
		packet.passParameters(new String[] { "first", "second" });

		assertEquals("first", packet.readString());
		packet.setPos(0);

		assertEquals("first", packet.readString());
	}

	@Test
	public void storesTheSenderAssociatedWithThePacket() {
		PanfuPacket packet = new PanfuPacket(1);
		User sender = new User(new EmbeddedChannel(), null);

		packet.setSender(sender);

		assertSame(sender, packet.getSender());
	}
}
