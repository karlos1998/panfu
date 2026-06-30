package org.openpanfu.gameserver.database.dao;

import java.math.BigDecimal;
import java.math.RoundingMode;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;

import org.openpanfu.gameserver.database.Database;
import org.openpanfu.gameserver.util.Logger;

public class MinigameRewardDAO {
	private static final BigDecimal DEFAULT_COIN_MULTIPLIER = new BigDecimal("0.0500");

	public static int awardCoinsForScore(int userId, int gameId, int points) {
		int coins = calculateCoins(points, getSettings(gameId));
		if (coins <= 0) {
			return 0;
		}

		try (Connection database = Database.getConnection();
				PreparedStatement preparedStatement = database
						.prepareStatement("UPDATE users SET coins = COALESCE(coins, 0) + ? WHERE id = ?")) {
			preparedStatement.setInt(1, coins);
			preparedStatement.setInt(2, userId);
			preparedStatement.executeUpdate();
			return coins;
		} catch (SQLException e) {
			Logger.error("Could not award minigame coins.");
			e.printStackTrace();
			return 0;
		}
	}

	public static int calculateCoins(int points, RewardSettings settings) {
		if (points <= 0 || !settings.enabled || settings.coinMultiplier.compareTo(BigDecimal.ZERO) <= 0) {
			return 0;
		}

		int coins = BigDecimal.valueOf(points)
				.multiply(settings.coinMultiplier)
				.setScale(0, RoundingMode.DOWN)
				.intValue();

		if (settings.maxCoinsPerRound != null) {
			coins = Math.min(coins, settings.maxCoinsPerRound);
		}

		return Math.max(coins, 0);
	}

	private static RewardSettings getSettings(int gameId) {
		try (Connection database = Database.getConnection();
				PreparedStatement preparedStatement = database.prepareStatement(
						"SELECT enabled, coin_multiplier, max_coins_per_round FROM minigame_rewards WHERE game_id = ? LIMIT 1")) {
			preparedStatement.setInt(1, gameId);
			ResultSet resultSet = preparedStatement.executeQuery();
			if (resultSet.next()) {
				int maxCoins = resultSet.getInt("max_coins_per_round");
				Integer maxCoinsPerRound = resultSet.wasNull() ? null : maxCoins;
				BigDecimal coinMultiplier = resultSet.getBigDecimal("coin_multiplier");

				return new RewardSettings(
						resultSet.getBoolean("enabled"),
						coinMultiplier == null ? DEFAULT_COIN_MULTIPLIER : coinMultiplier,
						maxCoinsPerRound);
			}
		} catch (SQLException e) {
			Logger.error("Could not load minigame reward settings, using defaults.");
			e.printStackTrace();
		}

		return new RewardSettings(true, DEFAULT_COIN_MULTIPLIER, null);
	}

	public static class RewardSettings {
		private final boolean enabled;
		private final BigDecimal coinMultiplier;
		private final Integer maxCoinsPerRound;

		public RewardSettings(boolean enabled, BigDecimal coinMultiplier, Integer maxCoinsPerRound) {
			this.enabled = enabled;
			this.coinMultiplier = coinMultiplier == null ? DEFAULT_COIN_MULTIPLIER : coinMultiplier;
			this.maxCoinsPerRound = maxCoinsPerRound;
		}
	}
}
