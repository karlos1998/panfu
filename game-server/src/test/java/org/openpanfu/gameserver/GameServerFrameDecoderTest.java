package org.openpanfu.gameserver;

import static org.junit.Assert.assertEquals;
import static org.junit.Assert.assertFalse;
import static org.junit.Assert.assertNull;
import static org.junit.Assert.assertThrows;
import static org.junit.Assert.assertTrue;

import java.nio.charset.StandardCharsets;

import org.junit.Test;

import io.netty.buffer.Unpooled;
import io.netty.channel.embedded.EmbeddedChannel;
import io.netty.handler.codec.TooLongFrameException;
import io.netty.handler.codec.string.StringDecoder;

public class GameServerFrameDecoderTest {
	@Test
	public void decodesBothPacketAndPolicyRequestDelimiters() {
		EmbeddedChannel channel = channel(64);

		assertTrue(channel.writeInbound(Unpooled.copiedBuffer("10;one|<policy>", StandardCharsets.UTF_8)));
		assertEquals("10;one|", channel.readInbound());
		assertEquals("<policy>", channel.readInbound());
		assertNull(channel.readInbound());
	}

	@Test
	public void waitsForACompleteFrameAcrossNetworkFragments() {
		EmbeddedChannel channel = channel(64);

		assertFalse(channel.writeInbound(Unpooled.copiedBuffer("10;frag", StandardCharsets.UTF_8)));
		assertTrue(channel.writeInbound(Unpooled.copiedBuffer("mented|", StandardCharsets.UTF_8)));
		assertEquals("10;fragmented|", channel.readInbound());
		assertNull(channel.readInbound());
	}

	@Test
	public void preservesEmptyParametersInsideAFrame() {
		EmbeddedChannel channel = channel(64);

		assertTrue(channel.writeInbound(Unpooled.copiedBuffer("900;;testConnection|", StandardCharsets.UTF_8)));

		assertEquals("900;;testConnection|", channel.readInbound());
	}

	@Test
	public void rejectsFramesOverTheConfiguredTransportLimit() {
		EmbeddedChannel channel = channel(8);

		assertThrows(TooLongFrameException.class,
				() -> channel.writeInbound(Unpooled.copiedBuffer("123456789|", StandardCharsets.UTF_8)));
	}

	private EmbeddedChannel channel(int maxFrameLength) {
		return new EmbeddedChannel(
				new GameServerFrameDecoder(maxFrameLength),
				new StringDecoder(StandardCharsets.UTF_8));
	}
}
