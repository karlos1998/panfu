package org.openpanfu.gameserver.sessions;

import static org.junit.Assert.assertEquals;
import static org.junit.Assert.assertNull;
import static org.junit.Assert.assertSame;

import org.junit.Test;
import org.openpanfu.gameserver.User;

import io.netty.channel.embedded.EmbeddedChannel;

public class SessionManagerTest {
	@Test
	public void missingUsersReturnNullInsteadOfThrowing() {
		SessionManager sessions = new SessionManager();

		assertNull(sessions.getUserById(404));
		assertNull(sessions.getUserByUsername("missing"));
	}

	@Test
	public void usersCanBeLookedUpAndRemovedByTheirIdentity() {
		SessionManager sessions = new SessionManager();
		User user = user(7, "Panda", 4, false, 0);

		sessions.addUser(user);

		assertSame(user, sessions.getUserById(7));
		assertSame(user, sessions.getUserByUsername("Panda"));
		assertEquals(1, sessions.getUserCount());

		sessions.removeUserById(7);

		assertEquals(0, sessions.getUserCount());
		assertNull(sessions.getUserById(7));
	}

	@Test
	public void roomQueriesSeparatePublicHomeAndSubroomSessions() {
		SessionManager sessions = new SessionManager();
		User publicRoom = user(1, "Public", 4, false, 0);
		User mainHome = user(2, "Home", 4, true, 0);
		User subroom = user(3, "Subroom", 4, true, 100870);
		sessions.addUser(publicRoom);
		sessions.addUser(mainHome);
		sessions.addUser(subroom);

		assertEquals(1, sessions.getUsersInRoom(4, false, 0).size());
		assertSame(publicRoom, sessions.getUsersInRoom(4, false, 0).get(0));
		assertEquals(1, sessions.getUsersInRoom(4, true, 0).size());
		assertSame(mainHome, sessions.getUsersInRoom(4, true, 0).get(0));
		assertEquals(1, sessions.getUsersInRoom(4, true, 100870).size());
		assertSame(subroom, sessions.getUsersInRoom(4, true, 100870).get(0));
	}

	@Test
	public void aDuplicateLoginKeepsTheExistingSessionAndRejectsTheNewChannel() {
		SessionManager sessions = new SessionManager();
		User existing = user(7, "Existing", 4, false, 0);
		EmbeddedChannel duplicateChannel = new EmbeddedChannel();
		User duplicate = new User(duplicateChannel, null);
		duplicate.setUserId(7);
		duplicate.setUsername("Duplicate");
		sessions.addUser(existing);

		sessions.addUser(duplicate);

		assertSame(existing, sessions.getUserById(7));
		assertEquals(1, sessions.getUserCount());
		assertEquals("2;KICK_NEW_LOGIN|", duplicateChannel.readOutbound());
	}

	private User user(int id, String name, int room, boolean inHome, int subroom) {
		User user = new User(new EmbeddedChannel(), null);
		user.setUserId(id);
		user.setUsername(name);
		user.setRoomId(room);
		user.setInHome(inHome);
		user.setSubRoom(subroom);

		return user;
	}
}
