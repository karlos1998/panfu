package org.openpanfu.gameserver.database.dao;

import static org.junit.Assert.assertEquals;

import java.math.BigDecimal;

import org.junit.Test;
import org.openpanfu.gameserver.database.dao.MinigameRewardDAO.RewardSettings;

public class MinigameRewardDAOTest {
	@Test
	public void calculatesDefaultPointsToCoinsMultiplier() {
		RewardSettings settings = new RewardSettings(true, new BigDecimal("0.0500"), null);

		assertEquals(20, MinigameRewardDAO.calculateCoins(400, settings));
	}

	@Test
	public void roundsCoinRewardsDown() {
		RewardSettings settings = new RewardSettings(true, new BigDecimal("0.0500"), null);

		assertEquals(0, MinigameRewardDAO.calculateCoins(19, settings));
	}

	@Test
	public void disabledRewardsReturnZeroCoins() {
		RewardSettings settings = new RewardSettings(false, new BigDecimal("0.0500"), null);

		assertEquals(0, MinigameRewardDAO.calculateCoins(400, settings));
	}

	@Test
	public void maxCoinsPerRoundCapsRewards() {
		RewardSettings settings = new RewardSettings(true, new BigDecimal("0.0500"), 10);

		assertEquals(10, MinigameRewardDAO.calculateCoins(400, settings));
	}
}
